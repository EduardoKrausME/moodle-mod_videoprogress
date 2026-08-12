<?php

use mod_videoprogress\objective\manager;

require('../../config.php');

$id = required_param("id", PARAM_INT);
$cm = get_coursemodule_from_id("videoprogress", $id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", ["id" => $cm->course], "*", MUST_EXIST);
$activity = $DB->get_record("videoprogress", ["id" => $cm->instance], "*", MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoprogress:manageobjectives', $context);

$manager = new manager();
$addurls = [];
foreach ($manager->get_plugins() as $name => $plugin) {
    $addurls[] = [
        "name" => $plugin->get_name(),
        "url" => (new moodle_url('/mod/videoprogress/objective/edit.php', [
            "id" => $cm->id,
            "plugin" => $name,
        ]))->out(false),
    ];
}
$items = $manager->get_manage_data($activity->id, $cm->id, $context);
$PAGE->set_url('/mod/videoprogress/objectives.php', ["id" => $cm->id]);
$PAGE->set_title(get_string("manageobjectives", "videoprogress"));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videoprogress/manage_objectives', [
    "activityname" => format_string($activity->name),
    "backurl" => (new moodle_url('/mod/videoprogress/view.php', ["id" => $cm->id]))->out(false),
    "addurls" => $addurls,
    "items" => $items,
    "hasitems" => (bool)$items,
    "hasplugins" => (bool)$addurls,
]);
echo $OUTPUT->footer();
