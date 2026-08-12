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
 * caption_form.php
 *
 * @package   mod_videoprogress
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoprogress\form;

use coding_exception;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Defines the Moodle form used to configure caption form data.
 */
class caption_form extends moodleform {
    /**
     * Defines the fields, defaults, dependencies, and actions displayed by the Moodle form.
     *
     * @return void This method does not return a value.
     * @throws coding_exception
     */
    public function definition(): void {
        $mform = $this->_form;
        $languages = $this->_customdata['languages'] ?? [];

        $mform->addElement("select", "language", get_string("captionlanguage", "videoprogress"), $languages);
        $mform->setType("language", PARAM_ALPHANUMEXT);
        $mform->addRule("language", null, "required", null, "client");

        $options = ["accepted_types" => ['.vtt', '.srt'], "maxbytes" => 5 * 1024 * 1024];
        $mform->addElement("filepicker", "captionfile", get_string("captionfile", "videoprogress"), null, $options);
        $mform->addRule("captionfile", null, "required", null, "client");

        $mform->addElement("selectyesno", "isdefault", get_string("captiondefault", "videoprogress"));
        $mform->addElement("select", "status", get_string("captionstatus", "videoprogress"), [
            "draft" => get_string("captionstatusdraft", "videoprogress"),
            "published" => get_string("captionstatuspublished", "videoprogress"),
        ]);
        $mform->setDefault("status", "published");
        $this->add_action_buttons(true, get_string("addcaption", "videoprogress"));
    }
}
