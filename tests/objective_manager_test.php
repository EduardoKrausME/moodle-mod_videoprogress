<?php

namespace mod_videoprogress;

use context_module;
use mod_videoprogress\objective\manager;

/**
 * Verifies unlimited learning objective persistence and rendering behavior.
 */
class objective_manager_test extends \advanced_testcase {
    /**
     * Confirms that more than three objectives can be stored and displayed in order.
     *
     * @return void
     */
    public function test_unlimited_objectives_are_stored_and_rendered(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module("videoprogress", [
            "course" => $course->id,
        ]);
        $cm = get_coursemodule_from_instance("videoprogress", $activity->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $manager = new manager();

        foreach (range(1, 5) as $index) {
            $manager->save($activity->id, null, (object)[
                "plugin" => "text",
                "description" => "Objective " . $index,
                "enabled" => $index !== 5,
            ], $context);
        }

        self::assertSame(5, $DB->count_records("videoprogress_objectives", [
            "videoprogressid" => $activity->id,
        ]));
        $items = $manager->get_student_items($activity->id, $context, $cm->id);
        self::assertCount(4, $items);
        self::assertStringContainsString("Objective 1", $items[0]["html"]);
        self::assertStringContainsString("Objective 4", $items[3]["html"]);
    }

    /**
     * Confirms that objective type configuration is normalized by its subplugin.
     *
     * @return void
     */
    public function test_bloom_objective_configuration_is_normalized(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module("videoprogress", [
            "course" => $course->id,
        ]);
        $cm = get_coursemodule_from_instance("videoprogress", $activity->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $manager = new manager();

        $objective = $manager->save($activity->id, null, (object)[
            "plugin" => "bloom",
            "level" => "apply",
            "description" => "Apply the composition rule",
            "enabled" => 1,
        ], $context);
        $config = json_decode($objective->configdata, true);

        self::assertSame("apply", $config["level"]);
        self::assertSame("Apply the composition rule", $config["description"]);
        self::assertStringContainsString(
            "Aplicar",
            $manager->get_plugin("bloom")->get_summary($objective, $context)
        );
    }
}
