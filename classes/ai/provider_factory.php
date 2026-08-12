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
 * provider_factory.php
 *
 * @package   mod_videoprogress
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoprogress\ai;

use coding_exception;
use dml_exception;
use moodle_exception;

/**
 * Resolves the configured server-side AI provider without exposing credentials to the browser.
 */
class provider_factory {
    /**
     * Returns the configured server-side AI provider implementation.
     *
     * @return provider_interface The resolved identifier or numeric value.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get(): provider_interface {
        $classname = trim((string)get_config("mod_videoprogress", "aiproviderclass"));
        if ($classname === '' || !class_exists($classname)) {
            throw new moodle_exception("aiprovidernotconfigured", "videoprogress");
        }
        $provider = new $classname();
        if (!$provider instanceof provider_interface) {
            throw new coding_exception(get_string("invalidproviderinterface", "videoprogress"));
        }
        return $provider;
    }
}
