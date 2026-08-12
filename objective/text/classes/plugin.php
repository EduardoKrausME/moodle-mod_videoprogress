<?php

namespace videoprogressobjective_text;

use coding_exception;
use context_module;
use mod_videoprogress\objective\plugin_base;
use MoodleQuickForm;
use stdClass;

/**
 * Implements a plain-text learning objective type.
 */
class plugin extends plugin_base {
    /**
     * Returns the localized objective type name.
     *
     * @return string Localized type name.
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string("pluginname", "videoprogressobjective_text");
    }

    /**
     * Adds the objective description field to the Moodle form.
     *
     * @param MoodleQuickForm $mform Moodle form receiving the field.
     * @return void
     * @throws coding_exception
     */
    public function add_form_elements(MoodleQuickForm $mform): void {
        $mform->addElement("textarea", "description", get_string("description", "videoprogressobjective_text"), [
            "rows" => 4,
        ]);
        $mform->setType("description", PARAM_TEXT);
        $mform->addRule("description", null, "required", null, "client");
    }

    /**
     * Copies the stored description into the edit form.
     *
     * @param stdClass $data Base form data.
     * @param stdClass|null $objective Existing objective record.
     * @param context_module $context Activity context.
     * @return stdClass Prepared form data.
     */
    public function prepare_form_data(stdClass $data, stdClass|null $objective, context_module $context): stdClass {
        $config = $this->decode_config($objective);
        $data->description = $config["description"] ?? '';
        return $data;
    }

    /**
     * Validates that the objective description is not empty.
     *
     * @param array $data Submitted form data.
     * @param array $files Submitted files.
     * @param stdClass|null $objective Existing objective record.
     * @param context_module $context Activity context.
     * @return array Validation errors indexed by field name.
     * @throws coding_exception
     */
    public function validation(array $data, array $files, stdClass|null $objective, context_module $context): array {
        if (trim((string)($data["description"] ?? '')) === '') {
            return ["description" => get_string("required")];
        }
        return [];
    }

    /**
     * Normalizes the submitted description for storage.
     *
     * @param stdClass $objective Objective database record.
     * @param stdClass $data Submitted form data.
     * @param context_module $context Activity context.
     * @return array Objective type configuration.
     * @throws coding_exception
     */
    public function save(stdClass $objective, stdClass $data, context_module $context): array {
        return ["description" => clean_param($data->description, PARAM_TEXT)];
    }

    /**
     * Returns the objective text used on management screens.
     *
     * @param stdClass $objective Objective database record.
     * @param context_module $context Activity context.
     * @return string Formatted objective summary.
     */
    public function get_summary(stdClass $objective, context_module $context): string {
        $config = $this->decode_config($objective);
        return format_string($config["description"] ?? '');
    }

    /**
     * Renders the objective through the type Mustache template.
     *
     * @param stdClass $objective Objective database record.
     * @param context_module $context Activity context.
     * @param int $cmid Course module identifier.
     * @return string Rendered objective HTML.
     */
    public function render(stdClass $objective, context_module $context, int $cmid): string {
        global $OUTPUT;
        return $OUTPUT->render_from_template("videoprogressobjective_text/objective", [
            "description" => $this->get_summary($objective, $context),
        ]);
    }
}
