<?php

namespace mod_videoprogress\plugininfo;

use core\exception\moodle_exception;
use core\plugininfo\base;
use dml_exception;
use moodle_url;
use part_of_admin_tree;

/**
 * Describes Video Progress objective type subplugins to Moodle's plugin manager.
 */
class videoprogressobjective extends base {
    /**
     * Allows removal only when no learning objective still uses the subplugin.
     *
     * @return bool Whether Moodle may offer the uninstall action.
     * @throws dml_exception
     */
    public function is_uninstall_allowed(): bool {
        global $DB;
        return !$DB->record_exists("videoprogress_objectives", ["plugin" => $this->name]);
    }

    /**
     * Returns the administration page that lists installed objective types.
     *
     * @return moodle_url Objective subplugin management URL.
     * @throws moodle_exception
     */
    public static function get_manage_url(): moodle_url {
        return new moodle_url('/mod/videoprogress/admin_plugins.php', [
            "type" => "videoprogressobjective",
        ]);
    }

    /**
     * Returns the unique administration section for this subplugin's settings.
     *
     * @return string Administration section identifier.
     */
    public function get_settings_section_name(): string {
        return $this->type . "_" . $this->name;
    }

    /**
     * Loads an optional settings file supplied by the objective type subplugin.
     *
     * @param part_of_admin_tree $adminroot Moodle administration tree.
     * @param string $parentnodename Parent node receiving the settings page.
     * @param bool $hassiteconfig Whether the current user can configure the site.
     * @return void
     */
    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig): void {
        $plugininfo = $this;
        if (!$this->is_installed_and_upgraded() || !$hassiteconfig ||
            !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $settings = new \admin_settingpage(
            $this->get_settings_section_name(),
            $this->displayname,
            'moodle/site:config'
        );
        if ($adminroot->fulltree) {
            include($this->full_path('settings.php'));
        }
        if ($settings) {
            $adminroot->add($parentnodename, $settings);
        }
    }
}
