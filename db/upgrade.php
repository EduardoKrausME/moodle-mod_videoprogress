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
    if ($oldversion < 2026081200) {
        $table = new xmldb_table("videoprogress_materials");
        $table->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field("videoprogressid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $table->add_field("plugin", XMLDB_TYPE_CHAR, "64", null, XMLDB_NOTNULL, null, '');
        $table->add_field("name", XMLDB_TYPE_CHAR, "255", null, XMLDB_NOTNULL, null, '');
        $table->add_field("configdata", XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field("sortorder", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("enabled", XMLDB_TYPE_INTEGER, "1", null, XMLDB_NOTNULL, null, "1");
        $table->add_field("timecreated", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("timemodified", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_key("primary", XMLDB_KEY_PRIMARY, ["id"]);
        $table->add_key("activity", XMLDB_KEY_FOREIGN, ["videoprogressid"], "videoprogress", ["id"]);
        $table->add_index('activity-order', XMLDB_INDEX_NOTUNIQUE, ["videoprogressid", "sortorder"]);
        $table->add_index('activity-plugin', XMLDB_INDEX_NOTUNIQUE, ["videoprogressid", "plugin"]);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table("videoprogress_points");
        $table->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field("videoprogressid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $table->add_field("timepoint", XMLDB_TYPE_NUMBER, '12, 3', null, XMLDB_NOTNULL, null, "0");
        $table->add_field("title", XMLDB_TYPE_CHAR, "255", null, XMLDB_NOTNULL, null, '');
        $table->add_field("enabled", XMLDB_TYPE_INTEGER, "1", null, XMLDB_NOTNULL, null, "1");
        $table->add_field("timecreated", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("timemodified", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_key("primary", XMLDB_KEY_PRIMARY, ["id"]);
        $table->add_key("activity", XMLDB_KEY_FOREIGN, ["videoprogressid"], "videoprogress", ["id"]);
        $table->add_index('activity-time', XMLDB_INDEX_NOTUNIQUE, ["videoprogressid", "timepoint"]);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table("videoprogress_pointitems");
        $table->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field("pointid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $table->add_field("plugin", XMLDB_TYPE_CHAR, "64", null, XMLDB_NOTNULL, null, '');
        $table->add_field("configdata", XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field("sortorder", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("enabled", XMLDB_TYPE_INTEGER, "1", null, XMLDB_NOTNULL, null, "1");
        $table->add_field("timecreated", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("timemodified", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_key("primary", XMLDB_KEY_PRIMARY, ["id"]);
        $table->add_key("point", XMLDB_KEY_FOREIGN, ["pointid"], "videoprogress_points", ["id"]);
        $table->add_index('point-order', XMLDB_INDEX_NOTUNIQUE, ["pointid", "sortorder"]);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table("videoprogress_interactions");
        $table->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field("itemid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $table->add_field("userid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $table->add_field("completed", XMLDB_TYPE_INTEGER, "1", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("attempts", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("lastresponse", XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field("timecompleted", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("timecreated", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("timemodified", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_key("primary", XMLDB_KEY_PRIMARY, ["id"]);
        $table->add_key("item", XMLDB_KEY_FOREIGN, ["itemid"], "videoprogress_pointitems", ["id"]);
        $table->add_key("user", XMLDB_KEY_FOREIGN, ["userid"], "user", ["id"]);
        $table->add_index('item-user', XMLDB_INDEX_UNIQUE, ["itemid", "userid"]);
        $table->add_index('user-completed', XMLDB_INDEX_NOTUNIQUE, ["userid", "completed"]);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026081200, "videoprogress");
    }
    if ($oldversion < 2026081202) {
        $table = new xmldb_table("videoprogress");
        $sourcefield = new xmldb_field("videosource", XMLDB_TYPE_CHAR, "100", null, XMLDB_NOTNULL, null, "upload",
            "introformat");
        $dbman->change_field_precision($table, $sourcefield);
        $field = new xmldb_field("sourceconfig", XMLDB_TYPE_TEXT, null, null, null, null, null, "videourl");
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $recordset = $DB->get_recordset("videoprogress", null, '', 'id,videosource,videourl');
        foreach ($recordset as $activity) {
            $legacyvalue = (string)($activity->videourl ?? '');
            $config = match ($activity->videosource) {
                "upload" => ["storage" => "moodle"],
                "url" => [
                    "url" => $legacyvalue,
                    "hls" => (bool)preg_match('/\.m3u8(?:$|\?)/i', $legacyvalue),
                ],
                "youtube" => ["id" => $legacyvalue],
                "vimeo" => [
                    "id" => explode(':', $legacyvalue, 2)[0],
                    "hash" => explode(':', $legacyvalue, 2)[1] ?? '',
                ],
                default => ["legacyvalue" => $legacyvalue],
            };
            $DB->set_field("videoprogress", "sourceconfig",
                json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ["id" => $activity->id]);
        }
        $recordset->close();

        upgrade_mod_savepoint(true, 2026081202, "videoprogress");
    }
    if ($oldversion < 2026081204) {
        $table = new xmldb_table("videoprogress_objectives");
        $table->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field("videoprogressid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL);
        $table->add_field("plugin", XMLDB_TYPE_CHAR, "64", null, XMLDB_NOTNULL, null, '');
        $table->add_field("configdata", XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field("sortorder", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("enabled", XMLDB_TYPE_INTEGER, "1", null, XMLDB_NOTNULL, null, "1");
        $table->add_field("timecreated", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("timemodified", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_key("primary", XMLDB_KEY_PRIMARY, ["id"]);
        $table->add_key("activity", XMLDB_KEY_FOREIGN, ["videoprogressid"], "videoprogress", ["id"]);
        $table->add_index('activity-order', XMLDB_INDEX_NOTUNIQUE, ["videoprogressid", "sortorder"]);
        $table->add_index('activity-plugin', XMLDB_INDEX_NOTUNIQUE, ["videoprogressid", "plugin"]);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $activitytable = new xmldb_table("videoprogress");
        foreach ([1, 2, 3] as $index) {
            $legacyfield = "objective" . $index;
            $field = new xmldb_field($legacyfield);
            if (!$dbman->field_exists($activitytable, $field)) {
                continue;
            }
            $recordset = $DB->get_recordset_select(
                "videoprogress",
                $legacyfield . " IS NOT NULL",
                null,
                '',
                "id," . $legacyfield
            );
            foreach ($recordset as $activity) {
                $description = trim((string)$activity->{$legacyfield});
                $conditions = [
                    "videoprogressid" => $activity->id,
                    "plugin" => "text",
                    "sortorder" => $index * 10,
                ];
                if ($description === '' || $DB->record_exists("videoprogress_objectives", $conditions)) {
                    continue;
                }
                $now = time();
                $DB->insert_record("videoprogress_objectives", (object)($conditions + [
                    "configdata" => json_encode(
                        ["description" => $description],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    "enabled" => 1,
                    "timecreated" => $now,
                    "timemodified" => $now,
                ]));
            }
            $recordset->close();
        }

        upgrade_mod_savepoint(true, 2026081204, "videoprogress");
    }
    return true;
}
