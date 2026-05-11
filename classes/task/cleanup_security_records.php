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
 * Scheduled cleanup task for auth_emailfirst security records.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailfirst\task;

/**
 * Remove expired rate-limit and old security-log records.
 */
class cleanup_security_records extends \core\task\scheduled_task {
    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanup_security_records', 'auth_emailfirst');
    }

    /**
     * Execute cleanup.
     */
    public function execute(): void {
        global $DB;

        $DB->delete_records_select(
            'auth_emailfirst_ratelimit',
            'timeattempt < ?',
            [time() - DAYSECS]
        );
        $DB->delete_records_select(
            'auth_emailfirst_security_log',
            'timeattempt < ?',
            [time() - (90 * DAYSECS)]
        );
    }
}
