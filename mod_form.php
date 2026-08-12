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
 * mod_form.php
 *
 * @package   mod_videoprogress
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_videoprogress\source\manager;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Defines the main Moodle activity configuration and custom completion form.
 */
class mod_videoprogress_mod_form extends moodleform_mod {
    /**
     * Defines the fields, defaults, dependencies, and actions displayed by the Moodle form.
     *
     * @return void This method does not return a value.
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function definition(): void {
        $mform = $this->_form;
        $sourcemanager = new manager();
        $sourceoptions = $sourcemanager->get_options();
        if (!$sourceoptions) {
            throw new moodle_exception("nosourceplugins", "videoprogress");
        }
        $mform->addElement("header", "general", get_string("general", "form"));
        $mform->addElement("text", "name", get_string("videoprogressname", "videoprogress"), ["size" => 64]);
        $mform->setType("name", PARAM_TEXT);
        $mform->addRule("name", null, "required", null, "client");
        $this->standard_intro_elements();

        $mform->addElement("html", html_writer::tag("h3", get_string("sourceheader", "videoprogress")));

        $mform->addElement("select", "videosource", get_string("videosource", "videoprogress"), $sourceoptions);
        $mform->setType("videosource", PARAM_PLUGIN);
        $mform->setDefault("videosource", $sourcemanager->get_default_source());
        $sourcemanager->add_form_elements($mform, "videosource");

        $filemanageroptions = [
            "subdirs" => 0,
            // "maxfiles" => 1,
            "accepted_types" => ["image"],
        ];
        $mform->addElement("filemanager", "poster", get_string("poster", "videoprogress"), null, $filemanageroptions);
        $nopostersources = $sourcemanager->get_sources_without_poster();
        if ($nopostersources) {
            $mform->hideIf("poster", "videosource", "in", $nopostersources);
        }

        $filemanageroptions=[
            "subdirs" => 0,
            // "maxfiles" => 10,
            "accepted_types" => ['.vtt', '.srt'],
        ];
        $mform->addElement("filemanager", "subtitles", get_string("subtitles", "videoprogress"), null, $filemanageroptions);
        $mform->addHelpButton("subtitles", "subtitles", "videoprogress");
        $nocaptionsources = $sourcemanager->get_sources_without_uploaded_captions();
        if ($nocaptionsources) {
            $mform->hideIf("subtitles", "videosource", "in", $nocaptionsources);
        }

        $mform->addElement("html", html_writer::tag("h3", get_string("playbackheader", "videoprogress")));
        $mform->addElement("select", "resumeplayback", get_string("resumeplayback", "videoprogress"), [
            1 => get_string("resumeautomatic", "videoprogress"),
            2 => get_string("resumeask", "videoprogress"),
            0 => get_string("resumefromstart", "videoprogress"),
        ]);
        $mform->setDefault("resumeplayback", 1);
        $mform->addElement("selectyesno", "allowseek", get_string("allowseek", "videoprogress"));
        $mform->setDefault("allowseek", 1);
        $mform->addElement("select", "maxplaybackrate", get_string("maxplaybackrate", "videoprogress"), [
            "0" => get_string("nolimit", "videoprogress"), "1" => "1x", '1.25' => '1.25x',
            '1.5' => '1.5x', '1.75' => '1.75x', "2" => "2x",
        ]);
        foreach (["disabledownload", "disablepip", "disablecontextmenu"] as $field) {
            $mform->addElement("selectyesno", $field, get_string($field, "videoprogress"));
            $mform->setDefault($field, 0);
        }

        $mform->addElement("html", html_writer::div(get_string("protectionnotice", "videoprogress"), "alert alert-info"));

        $this->standard_grading_coursemodule_elements();
        $mform->setDefault("grade", 100);
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Performs server-side validation for submitted Moodle form values.
     *
     * @param mixed $data Validated input or tracking data.
     * @param mixed $files Files submitted with the Moodle form.
     * @return array Structured data produced by the operation.
     * @throws coding_exception
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $percentfield = $this->get_suffixed_name("completionpercent");
        if (isset($data[$percentfield]) && ((int)$data[$percentfield] < 1 || (int)$data[$percentfield] > 100)) {
            $errors[$percentfield] = get_string("errorpercent", "videoprogress");
        }
        $errors += (new manager())->validation((array)$data, (array)$files);
        return $errors;
    }

    /**
     * Prepares stored activity files and values before the edit form is displayed.
     *
     * @param mixed $defaultvalues defaultvalues value used by the operation.
     * @return void This method does not return a value.
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function data_preprocessing(&$defaultvalues): void {
        foreach (["completionpercent", "requireconfirmation"] as $field) {
            if (array_key_exists($field, $defaultvalues)) {
                $defaultvalues[$this->get_suffixed_name($field)] = $defaultvalues[$field];
            }
        }
        if (empty($this->current->instance)) {
            return;
        }
        $context = $this->context;
        (new manager())->prepare_form_data($defaultvalues, $context);
        $posterdraftid = file_get_submitted_draft_itemid("poster");
        file_prepare_draft_area($posterdraftid, $context->id, "mod_videoprogress", "poster", 0, ["subdirs" => 0]);
        $defaultvalues["poster"] = $posterdraftid;
    }

    /**
     * Adds the custom completion percentage controls to the activity form.
     *
     * @return array Structured data produced by the operation.
     * @throws coding_exception
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $percentfield = $this->get_suffixed_name("completionpercent");
        $confirmationfield = $this->get_suffixed_name("requireconfirmation");
        $mform->addElement("text", $percentfield, get_string("completionpercent", "videoprogress"), ["size" => 5]);
        $mform->setType($percentfield, PARAM_INT);
        $mform->setDefault($percentfield, 80);
        $mform->addRule($percentfield, null, "numeric", null, "client");
        $mform->addElement("advcheckbox", $confirmationfield, get_string("requireconfirmation", "videoprogress"));
        $mform->setDefault($confirmationfield, 0);
        return [$percentfield, $confirmationfield];
    }

    /**
     * Checks whether the custom percentage completion rule is enabled.
     *
     * @param mixed $data Validated input or tracking data.
     * @return bool Whether the evaluated condition or operation succeeded.
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data[$this->get_suffixed_name("completionpercent")]);
    }

    /**
     * Returns submitted activity data after applying completion and grade defaults.
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }
        foreach (["completionpercent", "requireconfirmation"] as $field) {
            $suffixed = $this->get_suffixed_name($field);
            if (property_exists($data, $suffixed)) {
                $data->{$field} = $data->{$suffixed};
                unset($data->{$suffixed});
            }
        }
        return $data;
    }

    /**
     * Returns a unique form field name for a Video Progress completion rule.
     *
     * @param string $field Base field name.
     * @return string Suffixed form field name.
     */
    private function get_suffixed_name(string $field): string {
        return "{$field}_videoprogress";
    }
}
