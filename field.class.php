<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Extension of URL datafield with optional locking.
 *
 * @package    datafield_lockableurl
 * @copyright  2024 onwards Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../url/field.class.php');

/**
 * Extension of URL datafield with optional locking.
 *
 * @package    datafield_lockableurl
 * @copyright  2024 onwards Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_field_lockableurl extends data_field_url {
    /** @var string The internal datafield type */
    public $type = 'lockableurl';

    /**
     * Output control for editing content.
     *
     * @param int $recordid the id of the data record.
     * @param object $formdata the submitted form.
     *
     * @return string
     */
    public function display_add_field($recordid = 0, $formdata = null) {
        global $CFG, $DB, $OUTPUT, $PAGE;

        require_once($CFG->dirroot. '/repository/lib.php'); // Necessary for the constants used in args.

        $context = \context_module::instance($this->cm->id);
        $readonly = '';
        if ($this->field->param4 === 'on' && !has_capability('datafield/lockableurl:manage', $context)) {
            $readonly = ' readonly';
        }

        $args = new stdClass();
        $args->accepted_types = '*';
        $args->return_types = FILE_EXTERNAL;
        $args->context = $this->context;
        $args->env = 'url';
        $fp = new file_picker($args);
        $options = $fp->options;

        $fieldid = 'field_url_'.$options->client_id;

        $straddlink = get_string('choosealink', 'repository');
        $url = '';
        $text = '';
        if ($formdata) {
            $fieldname = 'field_' . $this->field->id . '_0';
            $url = $formdata->$fieldname;
            $fieldname = 'field_' . $this->field->id . '_1';
            if (isset($formdata->$fieldname)) {
                $text = $formdata->$fieldname;
            }
        } else if ($recordid) {
            if ($content = $DB->get_record('data_content', ['fieldid' => $this->field->id, 'recordid' => $recordid])) {
                $url  = $content->content;
                $text = $content->content1;
            }
        }

        $autolinkable = !empty($this->field->param1) && empty($this->field->param2);

        $str = '<div title="' . s($this->field->description) . '" class="d-flex flex-wrap align-items-center">';

        $label = '<label for="' . $fieldid . '"><span class="accesshide">' . $this->field->name . '</span>';
        if ($this->field->required) {
            $image = $OUTPUT->pix_icon('req', get_string('requiredelement', 'form'));
            if ($autolinkable) {
                $label .= html_writer::div(get_string('requiredelement', 'form'), 'accesshide');
            } else {
                $label .= html_writer::div($image, 'inline-req');
            }
        }
        $label .= '</label>';

        if ($autolinkable) {
            $str .= '<table><tr><td align="right">';
            $str .= '<span class="mod-data-input">' . get_string('url', 'data') . ':</span>';
            if (!empty($image)) {
                $str .= $image;
            }
            $str .= '</td><td>';
            $str .= $label;
            $str .= '<input type="text" name="field_' . $this->field->id . '_0" id="' . $fieldid . '" value="' . s($url) . '" ' .
                    'size="40" class="form-control d-inline"' . $readonly . '/>';
            $str .= '<button class="btn btn-secondary ml-1" id="filepicker-button-' . $options->client_id . '" ' .
                    'style="display:none">' . $straddlink . '</button></td></tr>';
            $str .= '<tr><td align="right"><span class="mod-data-input">' . get_string('text', 'data') . ':</span></td><td>';
            $str .= '<input type="text" name="field_' . $this->field->id . '_1" id="field_' . $this->field->id . '_1" ' .
                    'value="' . s($text) . '" size="40" class="form-control d-inline"' . $readonly .'/></td></tr>';
            $str .= '</table>';
        } else {
            // Just the URL field.
            $str .= $label;
            $str .= '<input type="text" name="field_'.$this->field->id.'_0" id="'.$fieldid.'" value="'.s($url).'"';
            $str .= ' size="40" class="mod-data-input form-control d-inline"' . $readonly . '/>';
            if (count($options->repositories) > 0) {
                $str .= '<button id="filepicker-button-' . $options->client_id . '" class="visibleifjs btn btn-secondary ml-1">' .
                        $straddlink . '</button>';
            }
        }

        $module = ['name' => 'data_urlpicker', 'fullpath' => '/mod/data/data.js', 'requires' => ['core_filepicker']];
        $PAGE->requires->js_init_call('M.data_urlpicker.init', [$options], true, $module);
        $str .= '</div>';
        return $str;
    }
}
