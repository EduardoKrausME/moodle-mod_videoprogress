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
 * caption_manager.php
 *
 * @package   mod_videoprogress
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoprogress;

use coding_exception;
use context_module;
use context_user;
use dml_exception;
use dml_transaction_exception;
use file_exception;
use moodle_exception;
use moodle_url;
use stdClass;
use stored_file_creation_exception;

/**
 * Coordinates caption upload, validation, review, publication, transcription, and translation.
 */
class caption_manager {
    /**
     * Imports uploaded caption files into draft caption records for review.
     *
     * @param int $draftitemid draftitemid value used by the operation.
     * @param int $activityid Video Progress activity identifier.
     * @param context_module $context Module context used for permissions and File API access.
     * @param int $userid Target user identifier.
     * @return int The resolved identifier or numeric value.
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function import_draft_files(int $draftitemid, int $activityid, context_module $context, int $userid): int {
        global $DB;
        $fs = get_file_storage();
        $files = $fs->get_area_files(context_user::instance($userid)->id, "user", "draft", $draftitemid, "filename", false);
        $imported = 0;
        $transaction = $DB->start_delegated_transaction();
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            if (!in_array($extension, ["vtt", "srt"], true)) {
                continue;
            }
            $content = $extension === "srt" ? self::convert_srt_to_vtt($file->get_content()) : $file->get_content();
            self::validate_webvtt($content);
            $basename = pathinfo($file->get_filename(), PATHINFO_FILENAME);
            $parts = explode('.', $basename);
            $language = clean_param(end($parts) ?: current_language(), PARAM_ALPHANUMEXT);
            if ($language === '') {
                $language = clean_param(current_language(), PARAM_ALPHANUMEXT);
            }
            $now = time();
            $record = (object)[
                "videoprogressid" => $activityid,
                "language" => strtolower($language),
                "label" => $language,
                "isdefault" =>
                    $imported === 0 && !$DB->record_exists("videoprogress_captions", ["videoprogressid" => $activityid]) ? 1 : 0,
                "status" => "published",
                "source" => "upload",
                "createdby" => $userid,
                "timecreated" => $now,
                "timemodified" => $now,
            ];
            $record->id = $DB->insert_record("videoprogress_captions", $record);
            $fs->create_file_from_string([
                "contextid" => $context->id, "component" => "mod_videoprogress", "filearea" => "caption",
                "itemid" => $record->id, "filepath" => '/', "filename" => clean_filename($basename . '.vtt'),
            ], $content);
            $imported++;
        }
        $transaction->allow_commit();
        return $imported;
    }

    /**
     * Validates and saves an uploaded caption as a reviewable track.
     *
     * @param int $activityid Video Progress activity identifier.
     * @param context_module $context Module context used for permissions and File API access.
     * @param stdClass $data Validated input or tracking data.
     * @param int $userid Target user identifier.
     * @return int The resolved identifier or numeric value.
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function save_upload(int $activityid, context_module $context, stdClass $data, int $userid): int {
        global $DB;

        $fs = get_file_storage();
        $draftfiles = $fs->get_area_files(context_user::instance($userid)->id, "user", "draft", $data->captionfile, "id", false);
        if (!$draftfiles) {
            throw new moodle_exception("captionfilemissing", "videoprogress");
        }
        $draft = reset($draftfiles);
        $content = $draft->get_content();
        if (strtolower(pathinfo($draft->get_filename(), PATHINFO_EXTENSION)) === "srt") {
            $content = self::convert_srt_to_vtt($content);
        }
        self::validate_webvtt($content);
        $transaction = $DB->start_delegated_transaction();
        if (!empty($data->isdefault)) {
            $DB->set_field("videoprogress_captions", "isdefault", 0, ["videoprogressid" => $activityid]);
        }
        $now = time();
        $record = (object)[
            "videoprogressid" => $activityid,
            "language" => strtolower($data->language),
            "label" => $data->label,
            "isdefault" => !empty($data->isdefault) ? 1 : 0,
            "status" => $data->status,
            "source" => "upload",
            "createdby" => $userid,
            "timecreated" => $now,
            "timemodified" => $now,
        ];
        $record->id = $DB->insert_record("videoprogress_captions", $record);
        $filename = clean_filename(pathinfo($draft->get_filename(), PATHINFO_FILENAME) . '.vtt');
        $fs->create_file_from_string([
            "contextid" => $context->id,
            "component" => "mod_videoprogress",
            "filearea" => "caption",
            "itemid" => $record->id,
            "filepath" => '/',
            "filename" => $filename,
        ], $content);
        $transaction->allow_commit();
        return $record->id;
    }

    /**
     * Validates and saves edited WebVTT content for a caption track.
     *
     * @param stdClass $caption caption value used by the operation.
     * @param context_module $context Module context used for permissions and File API access.
     * @param stdClass $data Validated input or tracking data.
     * @return void This method does not return a value.
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     */
    public function update_content(stdClass $caption, context_module $context, stdClass $data): void {
        global $DB;
        self::validate_webvtt($data->content);
        $transaction = $DB->start_delegated_transaction();
        if (!empty($data->isdefault)) {
            $DB->set_field("videoprogress_captions", "isdefault", 0, ["videoprogressid" => $caption->videoprogressid]);
        }
        $caption->language = strtolower($data->language);
        $caption->label = $data->label;
        $caption->isdefault = !empty($data->isdefault) ? 1 : 0;
        $caption->status = $data->status;
        $caption->timemodified = time();
        $DB->update_record("videoprogress_captions", $caption);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, "mod_videoprogress", "caption", $caption->id, "id", false);
        $filename = $files ? reset($files)->get_filename() : 'caption-' . $caption->id . '.vtt';
        $fs->delete_area_files($context->id, "mod_videoprogress", "caption", $caption->id);
        $fs->create_file_from_string([
            "contextid" => $context->id, "component" => "mod_videoprogress", "filearea" => "caption",
            "itemid" => $caption->id, "filepath" => '/', "filename" => $filename,
        ], $data->content);
        $transaction->allow_commit();
    }

    /**
     * Deletes a caption record and its associated protected WebVTT file.
     *
     * @param int $captionid Caption record identifier.
     * @param int $activityid Video Progress activity identifier.
     * @param context_module $context Module context used for permissions and File API access.
     * @return void This method does not return a value.
     * @throws dml_exception
     * @throws dml_transaction_exception
     */
    public function delete(int $captionid, int $activityid, context_module $context): void {
        global $DB;
        $conditions = ["id" => $captionid, "videoprogressid" => $activityid];
        $caption = $DB->get_record("videoprogress_captions", $conditions, "*", MUST_EXIST);
        $transaction = $DB->start_delegated_transaction();
        get_file_storage()->delete_area_files($context->id, "mod_videoprogress", "caption", $caption->id);
        $DB->delete_records("videoprogress_captions", ["id" => $caption->id]);
        $transaction->allow_commit();
    }

    /**
     * Returns published caption tracks with protected File API URLs.
     *
     * @param int $activityid Video Progress activity identifier.
     * @param context_module $context Module context used for permissions and File API access.
     * @return array Structured data produced by the operation.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_published_tracks(int $activityid, context_module $context): array {
        global $DB;
        $tracks = [];
        $conditions = ["videoprogressid" => $activityid, "status" => "published"];
        $captions = $DB->get_records("videoprogress_captions", $conditions, 'isdefault DESC,language');
        $fs = get_file_storage();
        foreach ($captions as $caption) {
            $files = $fs->get_area_files($context->id, "mod_videoprogress", "caption", $caption->id, "filename", false);
            if (!$files) {
                continue;
            }
            $file = reset($files);
            $tracks[] = [
                "id" => $caption->id,
                "language" => s($caption->language),
                "label" => format_string($caption->label),
                "isdefault" => (bool)$caption->isdefault,
                "url" => moodle_url::make_pluginfile_url($context->id, "mod_videoprogress", "caption", $caption->id,
                    $file->get_filepath(), $file->get_filename())->out(false),
            ];
        }
        return $tracks;
    }

    /**
     * Checks whether caption content has a valid WebVTT header and cue timing structure.
     *
     * @param string $content Caption or editor content.
     * @return void This method does not return a value.
     * @throws moodle_exception
     */
    public static function validate_webvtt(string $content): void {
        $normalised = preg_replace('/^\xEF\xBB\xBF/', '', trim($content));
        if (!str_starts_with($normalised, "WEBVTT") ||
            !preg_match('/\d{2}:\d{2}(?::\d{2})?\.\d{3}\s+-->\s+\d{2}:\d{2}(?::\d{2})?\.\d{3}/', $normalised)) {
            throw new moodle_exception("invalidwebvtt", "videoprogress");
        }
    }

    /**
     * Converts valid SRT caption content into WebVTT format while preserving timestamps.
     *
     * @param string $content Caption or editor content.
     * @return string The resolved or formatted string value.
     */
    public static function convert_srt_to_vtt(string $content): string {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $content = preg_replace_callback(
            '/(\d{2}:\d{2}:\d{2}),(\d{3})\s+-->\s+(\d{2}:\d{2}:\d{2}),(\d{3})/',
            static fn(array $matches): string => $matches[1] . '.' . $matches[2] . ' --> ' . $matches[3] . '.' . $matches[4],
            $content
        );
        return "WEBVTT\n\n" . trim($content) . "\n";
    }
}
