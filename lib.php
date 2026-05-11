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
 * Lib functions for auth_emailfirst.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Redirect core signup-related pages before headers are sent.
 *
 * Legacy callback for Moodle < 4.4. On 4.4+ the hook in
 * db/hooks.php takes over.
 */
function auth_emailfirst_before_http_headers() {
    global $CFG, $PAGE;

    if (!empty($CFG->registerauth) && $CFG->registerauth === 'emailfirst') {
        $corepage = new moodle_url('/login/verify_age_location.php');
        if ($PAGE->url->compare($corepage, URL_MATCH_BASE)) {
            redirect(new moodle_url('/auth/emailfirst/verify_age_location.php'));
        }
    }
}

/**
 * Inject a "Sign Up" button next to the login link in the navbar.
 * Also redirects core verify_age_location.php to the branded version.
 *
 * Legacy callback for Moodle < 4.4. On 4.4+ the hook in
 * db/hooks.php takes over and this function is not called.
 *
 * @return string HTML/JS to inject into the page body.
 */
function auth_emailfirst_before_standard_top_of_body_html() {
    global $CFG, $PAGE;

    // Redirect core verify_age_location.php to our branded version.
    if (!empty($CFG->registerauth) && $CFG->registerauth === 'emailfirst') {
        $corepage = new moodle_url('/login/verify_age_location.php');
        $custompage = new moodle_url('/auth/emailfirst/verify_age_location.php');
        if ($PAGE->url->compare($corepage, URL_MATCH_BASE)) {
            redirect($custompage);
        }
    }

    // Delegate to the shared helper used by the hook callback.
    return \auth_emailfirst\local\hook\output\before_standard_top_of_body_html_generation::get_signup_button_html();
}

/**
 * Pre-signup hook: redirect from core /login/signup.php to our custom endpoint.
 *
 * Called by core_login_pre_signup_requests() before the signup form is displayed.
 */
function auth_emailfirst_pre_signup_requests() {
    global $CFG, $PAGE;

    // Only redirect if emailfirst is the active registration plugin.
    if (!empty($CFG->registerauth) && $CFG->registerauth === 'emailfirst') {
        // Avoid redirect loop when already on the custom signup page.
        $target = new moodle_url('/auth/emailfirst/signup.php');
        if (!$PAGE->url->compare($target, URL_MATCH_BASE)) {
            redirect($target);
        }
    }
}

/**
 * Enroll a user in a course using manual enrollment.
 *
 * Shared helper used by both user_signup() (auth.php) and confirm.php.
 *
 * @param int $userid The user ID
 * @param int $courseid The course ID
 * @return object|null The course object if enrolled successfully, null otherwise
 */
function auth_emailfirst_enroll_user_in_course($userid, $courseid) {
    global $DB;

    // Get the course.
    $course = $DB->get_record('course', ['id' => $courseid, 'visible' => 1]);
    if (!$course) {
        return null;
    }

    // Check if already enrolled.
    $context = context_course::instance($courseid);
    if (is_enrolled($context, $userid)) {
        return $course; // Already enrolled, return course anyway.
    }

    // Find the manual enrollment instance for this course.
    $enrolplugin = enrol_get_plugin('manual');
    if (!$enrolplugin) {
        return null;
    }

    $instances = enrol_get_instances($courseid, true);
    $manualinstance = null;
    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            $manualinstance = $instance;
            break;
        }
    }

    if (!$manualinstance) {
        // Try to add a manual enrollment instance.
        $enrolplugin->add_instance($course);
        $instances = enrol_get_instances($courseid, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                $manualinstance = $instance;
                break;
            }
        }
    }

    if (!$manualinstance) {
        return null;
    }

    // Get the student role.
    $studentrole = $DB->get_record('role', ['shortname' => 'student']);
    if (!$studentrole) {
        return null;
    }

    // Enroll the user.
    $enrolplugin->enrol_user($manualinstance, $userid, $studentrole->id);

    return $course;
}
