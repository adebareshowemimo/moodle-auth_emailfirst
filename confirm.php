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
 * Custom email confirmation page for auth_emailfirst.
 *
 * Handles user confirmation, auto-enrollment, and redirect to course.
 *
 * @package    auth_emailfirst
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public email confirmation endpoint.
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../login/lib.php');
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->libdir . '/enrollib.php');

$data = optional_param('data', '', PARAM_RAW);  // Formatted as: secret/username.

$PAGE->set_url('/auth/emailfirst/confirm.php');
$PAGE->set_context(context_system::instance());

// Get the emailfirst auth plugin.
$authplugin = get_auth_plugin('emailfirst');

if (!$authplugin) {
    throw new moodle_exception('confirmationnotenabled');
}

if (empty($data)) {
    throw new moodle_exception('errorwhenconfirming');
}

$dataelements = explode('/', $data, 2); // Stop after 1st slash. Rest is username.
$usersecret = $dataelements[0];
$username = $dataelements[1] ?? '';

if (empty($username)) {
    throw new moodle_exception('errorwhenconfirming');
}

$confirmed = $authplugin->user_confirm($username, $usersecret);

if ($confirmed == AUTH_CONFIRM_ALREADY) {
    $user = get_complete_user_data('username', $username);
    $PAGE->navbar->add(get_string('alreadyconfirmed'));
    $PAGE->set_title(get_string('alreadyconfirmed'));
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->box_start('generalbox centerpara boxwidthnormal boxaligncenter');
    echo "<p>" . get_string('alreadyconfirmed') . "</p>\n";
    echo $OUTPUT->single_button(new moodle_url('/my/'), get_string('continue'));
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
} else if ($confirmed == AUTH_CONFIRM_OK) {
    // Get the confirmed user.
    $user = get_complete_user_data('username', $username);
    if (!$user) {
        throw new moodle_exception('cannotfinduser', '', '', s($username));
    }

    // Auto-enroll in course if configured.
    $autoenrollcourseid = get_config('auth_emailfirst', 'autoenrollcourse');
    $redirecttocourse = get_config('auth_emailfirst', 'redirecttocourse');
    $enrolledcourse = null;

    if (!empty($autoenrollcourseid) && $autoenrollcourseid > 0) {
        $enrolledcourse = auth_emailfirst_enroll_user_in_course($user->id, $autoenrollcourseid);
    }

    // Log the user in if not suspended.
    if (!$user->suspended) {
        complete_user_login($user);
        \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

        // Record site policy acceptance (user agreed during signup).
        // This prevents the site policy page from showing again.
        $policymanager = new \core_privacy\local\sitepolicy\manager();
        if ($policymanager->is_defined() && empty($USER->policyagreed)) {
            $policymanager->accept();
        }

        // Clear any wantsurl that might redirect to site policy.
        if (!empty($SESSION->wantsurl)) {
            unset($SESSION->wantsurl);
        }

        // If redirect to course is enabled and we enrolled successfully, go there.
        if (!empty($redirecttocourse) && $enrolledcourse) {
            redirect(new moodle_url('/course/view.php', ['id' => $enrolledcourse->id]));
        }
    }

    // Show confirmation page.
    $PAGE->navbar->add(get_string('confirmed'));
    $PAGE->set_title(get_string('confirmed'));
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->box_start('generalbox centerpara boxwidthnormal boxaligncenter');
    echo "<h3>" . get_string('thanks') . ", " . fullname($USER) . "</h3>\n";
    echo "<p>" . get_string('confirmed') . "</p>\n";

    // Button to continue to course or dashboard.
    if ($enrolledcourse) {
        echo $OUTPUT->single_button(
            new moodle_url('/course/view.php', ['id' => $enrolledcourse->id]),
            get_string('continue')
        );
    } else {
        echo $OUTPUT->single_button(new moodle_url('/my/'), get_string('continue'));
    }

    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
} else {
    throw new moodle_exception('invalidconfirmdata');
}
