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
 * Reset password page for auth_emailfirst.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public password reset endpoint.
require('../../config.php');
require_once($CFG->dirroot . '/auth/emailfirst/auth.php');
require_once($CFG->dirroot . '/user/lib.php');

$token = required_param('token', PARAM_ALPHANUM);
$password = optional_param('password', '', PARAM_RAW);
$password2 = optional_param('password2', '', PARAM_RAW);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url('/auth/emailfirst/reset_password.php', ['token' => $token]);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('resetpassword', 'auth_emailfirst'));
$PAGE->set_heading(get_string('resetpassword', 'auth_emailfirst'));

// Redirect logged-in users.
if (isloggedin() && !isguestuser()) {
    redirect($CFG->wwwroot . '/');
}

$authplugin = new auth_plugin_emailfirst();
$errors = [];
$success = false;

// Verify the token first.
$user = $authplugin->verify_password_reset_token($token);

// Handle password reset submission.
if ($confirm && $user && !empty($password)) {
    require_sesskey();

    if ($password !== $password2) {
        $errors['password2'] = get_string('passwordsnotmatching', 'auth_emailfirst');
    }

    $errmsg = '';
    if (!check_password_policy($password, $errmsg, $user)) {
        $errors['password'] = $errmsg;
    }

    if (empty($errors)) {
        $success = $authplugin->user_update_password($user, $password);

        if ($success) {
            unset_user_preference('auth_emailfirst_reset_token', $user);
            unset_user_preference('auth_emailfirst_reset_token_expiry', $user);
            complete_user_login($user);
        } else {
            $errors['general'] = get_string('errorresettingpassword', 'auth_emailfirst');
        }
    }
}

echo $OUTPUT->header();

// Determine post-reset redirect URL.
$config = get_config('auth_emailfirst');
$postreseturl = $CFG->wwwroot;
if (!empty($config->postresetredirecturl)) {
    $postreseturl = $config->postresetredirecturl;
} else if (!empty($config->autoenrollcourse)) {
    $postreseturl = (new moodle_url('/course/view.php', ['id' => $config->autoenrollcourse]))->out(false);
}

$templatecontext = [
    'wwwroot' => $CFG->wwwroot,
    'postreseturl' => $postreseturl,
    'sitename' => format_string($CFG->fullname),
    'logourl' => $OUTPUT->get_logo_url() ? $OUTPUT->get_logo_url()->out() : '',
    'token' => $token,
    'sesskey' => sesskey(),
    'errors' => $errors,
    'success' => $success,
    'validtoken' => !empty($user),
    'loginurl' => $CFG->wwwroot . '/login/index.php',
    'forgotpasswordurl' => $CFG->wwwroot . '/auth/emailfirst/forgot_password.php',
];

echo $OUTPUT->render_from_template('auth_emailfirst/reset_password', $templatecontext);

echo $OUTPUT->footer();
