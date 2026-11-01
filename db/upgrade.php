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
 * Upgrade steps for auth_emailfirst.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade auth_emailfirst.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_auth_emailfirst_upgrade(int $oldversion): bool {
    global $CFG;

    if ($oldversion < 2026081800) {
        require_once($CFG->dirroot . '/auth/emailfirst/lib.php');
        auth_emailfirst_seed_location_auth_mapping_defaults();

        upgrade_plugin_savepoint(true, 2026081800, 'auth', 'emailfirst');
    }

    if ($oldversion < 2026081801) {
        // No schema change. The savepoint refreshes plugin metadata and settings caches.
        upgrade_plugin_savepoint(true, 2026081801, 'auth', 'emailfirst');
    }

    return true;
}
