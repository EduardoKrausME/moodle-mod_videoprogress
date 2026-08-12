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
 * plugin.php
 *
 * @package   videoprogressobjective_text
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $mform->addElement(
            "editor",
            "description",
            get_string("description", "videoprogressobjective_text"),
            ["rows" => 8],
            ["maxfiles" => 0]
        );
        $mform->setType("description", PARAM_RAW);
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
        $data->description = [
            "text" => $config["description"] ?? '',
            "format" => FORMAT_HTML,
        ];
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
        $description = $data["description"]["text"] ?? '';
        if (trim(strip_tags((string)$description)) === '') {
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
        $description = is_array($data->description)
            ? ($data->description["text"] ?? '')
            : (string)$data->description;

        return [
            "description" => clean_text((string)$description, FORMAT_HTML),
        ];
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
        return format_string(strip_tags($config["description"] ?? ''));
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

        $config = $this->decode_config($objective);
        return $OUTPUT->render_from_template("videoprogressobjective_text/objective", [
            "description" => format_text(
                $config["description"] ?? '',
                FORMAT_HTML,
                ["context" => $context]
            ),
        ]);
    }
}
