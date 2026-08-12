<?php

namespace videoprogressobjective_bloom;

use coding_exception;
use context_module;
use mod_videoprogress\objective\plugin_base;
use MoodleQuickForm;
use stdClass;

/**
 * Implements a learning objective classified by Bloom's taxonomy.
 */
class plugin extends plugin_base {
    /** @var array Allowed Bloom taxonomy levels. */
    private const LEVELS = ["remember", "understand", "apply", "analyze", "evaluate", "create"];

    /**
     * Returns the localized objective type name.
     *
     * @return string Localized type name.
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string("pluginname", "videoprogressobjective_bloom");
    }

    /**
     * Adds Bloom level and objective description fields to the Moodle form.
     *
     * @param MoodleQuickForm $mform Moodle form receiving the fields.
     * @return void
     * @throws coding_exception
     */
    public function add_form_elements(MoodleQuickForm $mform): void {
        $options = [];
        foreach (self::LEVELS as $level) {
            $options[$level] = get_string("level:{$level}", "videoprogressobjective_bloom");
        }
        $mform->addElement("select", "level", get_string("level", "videoprogressobjective_bloom"), $options);
        $mform->setType("level", PARAM_ALPHA);
        $mform->addElement("textarea", "description", get_string("description", "videoprogressobjective_bloom"), [
            "rows" => 4,
        ]);
        $mform->setType("description", PARAM_TEXT);
        $mform->addRule("description", null, "required", null, "client");
    }

    /**
     * Copies stored Bloom values into the edit form.
     *
     * @param stdClass $data Base form data.
     * @param stdClass|null $objective Existing objective record.
     * @param context_module $context Activity context.
     * @return stdClass Prepared form data.
     */
    public function prepare_form_data(stdClass $data, stdClass|null $objective, context_module $context): stdClass {
        $config = $this->decode_config($objective);
        $data->level = $config["level"] ?? self::LEVELS[0];
        $data->description = $config["description"] ?? '';
        return $data;
    }

    /**
     * Validates the Bloom level and objective description.
     *
     * @param array $data Submitted form data.
     * @param array $files Submitted files.
     * @param stdClass|null $objective Existing objective record.
     * @param context_module $context Activity context.
     * @return array Validation errors indexed by field name.
     * @throws coding_exception
     */
    public function validation(array $data, array $files, stdClass|null $objective, context_module $context): array {
        $errors = [];
        if (!in_array($data["level"] ?? '', self::LEVELS, true)) {
            $errors["level"] = get_string("invalidlevel", "videoprogressobjective_bloom");
        }
        if (trim((string)($data["description"] ?? '')) === '') {
            $errors["description"] = get_string("required");
        }
        return $errors;
    }

    /**
     * Normalizes the submitted Bloom configuration for storage.
     *
     * @param stdClass $objective Objective database record.
     * @param stdClass $data Submitted form data.
     * @param context_module $context Activity context.
     * @return array Objective type configuration.
     * @throws coding_exception
     */
    public function save(stdClass $objective, stdClass $data, context_module $context): array {
        $level = in_array($data->level, self::LEVELS, true) ? $data->level : self::LEVELS[0];
        return [
            "level" => $level,
            "description" => clean_param($data->description, PARAM_TEXT),
        ];
    }

    /**
     * Returns a readable Bloom level and objective summary.
     *
     * @param stdClass $objective Objective database record.
     * @param context_module $context Activity context.
     * @return string Formatted objective summary.
     * @throws coding_exception
     */
    public function get_summary(stdClass $objective, context_module $context): string {
        $config = $this->decode_config($objective);
        $level = in_array($config["level"] ?? '', self::LEVELS, true) ? $config["level"] : self::LEVELS[0];
        return get_string("summary", "videoprogressobjective_bloom", (object)[
            "level" => get_string("level:{$level}", "videoprogressobjective_bloom"),
            "description" => format_string($config["description"] ?? ''),
        ]);
    }

    /**
     * Renders the Bloom objective through the type Mustache template.
     *
     * @param stdClass $objective Objective database record.
     * @param context_module $context Activity context.
     * @param int $cmid Course module identifier.
     * @return string Rendered objective HTML.
     * @throws coding_exception
     */
    public function render(stdClass $objective, context_module $context, int $cmid): string {
        global $OUTPUT;
        $config = $this->decode_config($objective);
        $level = in_array($config["level"] ?? '', self::LEVELS, true) ? $config["level"] : self::LEVELS[0];
        return $OUTPUT->render_from_template("videoprogressobjective_bloom/objective", [
            "level" => get_string("level:{$level}", "videoprogressobjective_bloom"),
            "description" => format_string($config["description"] ?? ''),
        ]);
    }
}
