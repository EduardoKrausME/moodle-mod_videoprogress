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
 * upgrade.php
 *
 * @package   mod_videoprogress
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Applies incremental database schema upgrades for the Video Progress activity.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool Whether Moodle should accept the result.
 */
function xmldb_videoprogress_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026091401) {
        $table = new xmldb_table("videoprogress_captions");
        $field = new xmldb_field("sourceurl", XMLDB_TYPE_TEXT, null, null, null, null, null, "source");
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026091401, "videoprogress");
    }


    if ($oldversion < 2026100506) {
        $fs = get_file_storage();
        $contextids = $DB->get_fieldset_sql(
            "SELECT DISTINCT contextid
               FROM {files}
              WHERE component = :component
                AND filearea = :filearea",
            [
                "component" => "mod_videoprogress",
                "filearea" => "video",
            ]
        );

        foreach ($contextids as $contextid) {
            $files = $fs->get_area_files(
                (int)$contextid,
                "mod_videoprogress",
                "video",
                0,
                "id",
                false
            );

            foreach ($files as $file) {
                if (!$fs->file_exists(
                    (int)$contextid,
                    "local_video_bridge",
                    "video",
                    0,
                    $file->get_filepath(),
                    $file->get_filename()
                )) {
                    $fs->create_file_from_storedfile([
                        "contextid" => (int)$contextid,
                        "component" => "local_video_bridge",
                        "filearea" => "video",
                        "itemid" => 0,
                        "filepath" => $file->get_filepath(),
                        "filename" => $file->get_filename(),
                        "userid" => $file->get_userid(),
                        "source" => $file->get_source(),
                        "author" => $file->get_author(),
                        "license" => $file->get_license(),
                        "timecreated" => $file->get_timecreated(),
                        "timemodified" => $file->get_timemodified(),
                        "sortorder" => $file->get_sortorder(),
                    ], $file);
                }
                $file->delete();
            }
        }

        upgrade_mod_savepoint(true, 2026100506, "videoprogress");
    }

    return true;
}
