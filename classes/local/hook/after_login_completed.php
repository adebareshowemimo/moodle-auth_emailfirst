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
 * Hook callback to redirect emailfirst users to their course after login.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailfirst\local\hook;

/**
 * Redirects emailfirst-authenticated users to the configured course after login.
 *
 * Sets $SESSION->wantsurl so core_login_get_return_url() picks it up.
 */
class after_login_completed {
    /**
     * Hook callback.
     *
     * @param \core_user\hook\after_login_completed $hook
     */
    public static function callback(\core_user\hook\after_login_completed $hook): void {
        global $CFG, $SESSION, $USER;

        // Only apply to emailfirst-authenticated users.
        if (empty($USER->auth) || $USER->auth !== 'emailfirst') {
            return;
        }

        // Check that auto-enroll on login is enabled for existing users.
        if (empty(get_config('auth_emailfirst', 'autoenrollexistingusers'))) {
            return;
        }

        // Only override if no meaningful wantsurl is already set
        // (e.g. user was trying to access a specific page before being sent to login).
        if (
            !empty($SESSION->wantsurl)
            && strpos($SESSION->wantsurl, $CFG->wwwroot) === 0
            && $SESSION->wantsurl !== $CFG->wwwroot . '/'
            && $SESSION->wantsurl !== $CFG->wwwroot . '/index.php'
        ) {
            return;
        }

        $autoenrollcourseid = get_config('auth_emailfirst', 'autoenrollcourse');
        if (empty($autoenrollcourseid) || $autoenrollcourseid <= 0) {
            return;
        }

        // Enroll user if not already enrolled, then redirect to the course.
        require_once($CFG->dirroot . '/auth/emailfirst/lib.php');
        $course = auth_emailfirst_enroll_user_in_course($USER->id, $autoenrollcourseid);
        if ($course) {
            $SESSION->wantsurl = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
        }
    }
}
