<?php

namespace mod_videoprogress\form;

use coding_exception;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Defines the Moodle form used to create and edit learning objectives.
 */
class objective_form extends moodleform {
    /**
     * Defines shared and type-specific objective fields.
     *
     * @return void
     * @throws coding_exception
     */
    public function definition(): void {
        $mform = $this->_form;
        $plugin = $this->_customdata["plugin"];
        $mform->addElement("hidden", "plugin");
        $mform->setType("plugin", PARAM_PLUGIN);
        $mform->addElement("advcheckbox", "enabled", get_string("enabled", "videoprogress"));
        $mform->setDefault("enabled", 1);
        $mform->addElement("header", "pluginsettings", $plugin->get_name());
        $plugin->add_form_elements($mform);
        $this->add_action_buttons(true, get_string("savechanges"));
    }

    /**
     * Delegates server-side validation to the selected objective type.
     *
     * @param mixed $data Submitted form data.
     * @param mixed $files Submitted files.
     * @return array Validation errors indexed by field name.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        return $errors + $this->_customdata["plugin"]->validation(
            $data,
            $files,
            $this->_customdata["objective"],
            $this->_customdata["context"]
        );
    }
}
