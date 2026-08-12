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
 * transcribe_video.php
 *
 * @package   mod_videoprogress
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoprogress\task;

use coding_exception;
use context_module;
use core\task\adhoc_task;
use dml_exception;
use file_exception;
use mod_videoprogress\ai\provider_factory;
use mod_videoprogress\caption_manager;
use mod_videoprogress\event\caption_generated;
use mod_videoprogress\source\manager;
use moodle_exception;
use stored_file_creation_exception;

/**
 * Runs the transcribe video operation as a Moodle task.
 */
class transcribe_video extends adhoc_task {
    /**
     * Returns the localized task name displayed by Moodle administration.
     *
     * @return string The resolved or formatted string value.
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string("tasktranscribevideo", "videoprogress");
    }

    /**
     * Executes the transcribe video task workload.
     *
     * @return void This method does not return a value.
     * @throws dml_exception
     * @throws file_exception
     * @throws moodle_exception
     * @throws stored_file_creation_exception
     * @throws coding_exception
     */
    public function execute(): void {
        global $DB;
        $data = $this->get_custom_data();
        $activity = $DB->get_record("videoprogress", ["id" => $data->activityid], "*", MUST_EXIST);
        $cm = get_coursemodule_from_instance("videoprogress", $activity->id, $activity->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $source = (new manager())->get_plugin($activity->videosource);
        $file = $source->get_transcription_file($context);
        if (!$source->supports_transcription() || !$file) {
            throw new moodle_exception("transcriptionunsupportedsource", "videoprogress");
        }
        $content = provider_factory::get()->transcribe($file, $data->language);
        caption_manager::validate_webvtt($content);
        $now = time();
        $caption = (object)[
            "videoprogressid" => $activity->id, "language" => $data->language,
            "label" => get_string("generatedcaptionlabel", "videoprogress", $data->language),
            "isdefault" => 0, "status" => "draft", "source" => "ai", "createdby" => $data->userid,
            "timecreated" => $now, "timemodified" => $now,
        ];
        $caption->id = $DB->insert_record("videoprogress_captions", $caption);
        get_file_storage()->create_file_from_string([
            "contextid" => $context->id, "component" => "mod_videoprogress", "filearea" => "caption",
            "itemid" => $caption->id, "filepath" => '/', "filename" => 'generated-' . $caption->id . '.vtt',
        ], $content);
        caption_generated::create([
            "objectid" => $caption->id, "context" => $context, "userid" => $data->userid,
        ])->trigger();
    }
}
