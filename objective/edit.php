<?php

use mod_videoprogress\form\objective_form;
use mod_videoprogress\objective\manager;

require('../../../config.php');

$id = required_param("id", PARAM_INT);
$objectiveid = optional_param("objectiveid", 0, PARAM_INT);
$pluginname = optional_param("plugin", '', PARAM_PLUGIN);
$cm = get_coursemodule_from_id("videoprogress", $id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", ["id" => $cm->course], "*", MUST_EXIST);
$activity = $DB->get_record("videoprogress", ["id" => $cm->instance], "*", MUST_EXIST);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
require_capability('mod/videoprogress:manageobjectives', $context);

$manager = new manager();
$objective = $objectiveid ? $manager->get_objective($objectiveid, $activity->id) : null;
$pluginname = $objective ? $objective->plugin : $pluginname;
$plugin = $manager->get_plugin($pluginname);
$urlparams = ["id" => $cm->id, "plugin" => $pluginname];
if ($objective) {
    $urlparams["objectiveid"] = $objective->id;
}
$PAGE->set_url('/mod/videoprogress/objective/edit.php', $urlparams);
$PAGE->set_title($objective ? get_string("editobjective", "videoprogress") : get_string("addobjective", "videoprogress"));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mform = new objective_form($PAGE->url, [
    "plugin" => $plugin,
    "objective" => $objective,
    "context" => $context,
]);
$mform->set_data($manager->prepare_form_data($objective, $context, $pluginname));
$returnurl = new moodle_url('/mod/videoprogress/objectives.php', ["id" => $cm->id]);
if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    $manager->save($activity->id, $objective, $data, $context);
    redirect($returnurl, get_string("objectivesaved", "videoprogress"));
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videoprogress/form_page', [
    "backurl" => $returnurl->out(false),
    "eyebrow" => format_string($activity->name),
    "title" => $objective ? get_string("editobjective", "videoprogress") : get_string("addobjective", "videoprogress"),
    "description" => $plugin->get_name(),
    "formhtml" => $mform->render(),
]);
echo $OUTPUT->footer();
