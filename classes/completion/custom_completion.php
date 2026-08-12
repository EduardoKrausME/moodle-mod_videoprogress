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
 * custom_completion.php
 *
 * @package   mod_videoprogress
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoprogress\completion;

use coding_exception;
use core_completion\activity_custom_completion;
use dml_exception;

/**
 * Evaluates the percentage and confirmation rules through Moodle Custom Completion API.
 */
class custom_completion extends activity_custom_completion {
    /**
     * Evaluates whether the user satisfies the configured custom completion condition.
     *
     * @param string $rule Custom completion rule identifier.
     * @return int The resolved identifier or numeric value.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_state(string $rule): int {
        global $DB;

        if (!in_array($rule, ["completionpercent", "requireconfirmation"], true)) {
            throw new coding_exception(get_string("invalidcompletionrule", "videoprogress", $rule));
        }
        $activity = $DB->get_record("videoprogress", ["id" => $this->cm->instance], "*", MUST_EXIST);
        $progress = $DB->get_record("videoprogress_progress", [
            "videoprogressid" => $activity->id,
            "userid" => $this->userid,
        ]);
        if (!$progress) {
            return COMPLETION_INCOMPLETE;
        }
        if ($rule === "completionpercent") {
            return (float)$progress->percent >= (float)$activity->completionpercent ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        return !$activity->requireconfirmation || !empty($progress->confirmation) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Returns the custom completion rule identifiers supported by the activity.
     *
     * @return array Structured data produced by the operation.
     */
    public static function get_defined_custom_rules(): array {
        return ["completionpercent", "requireconfirmation"];
    }

    /**
     * Returns localized descriptions for the configured custom completion rules.
     *
     * @return array Structured data produced by the operation.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;
        $activity = $DB->get_record("videoprogress", ["id" => $this->cm->instance], "*", MUST_EXIST);
        $descriptions = [
            "completionpercent" => get_string('completiondetail:percent', "videoprogress", $activity->completionpercent),
        ];
        if ($activity->requireconfirmation) {
            $descriptions["requireconfirmation"] = get_string('completiondetail:confirmation', "videoprogress");
        }
        return $descriptions;
    }

    /**
     * Checks whether the supplied custom completion rule is configured for the activity.
     *
     * @param string $rule Custom completion rule identifier.
     * @return bool Whether the evaluated condition or operation succeeded.
     * @throws dml_exception
     */
    public function is_defined(string $rule): bool {
        global $DB;
        if ($rule === "completionpercent") {
            return true;
        }
        if ($rule === "requireconfirmation") {
            return (bool)$DB->get_field("videoprogress", "requireconfirmation", ["id" => $this->cm->instance]);
        }
        return false;
    }

    /**
     * Function get_sort_order
     * @return string[]
     */
    public function get_sort_order(): array {
        return [
            "completionpercent",
            "requireconfirmation",
        ];
    }
}
