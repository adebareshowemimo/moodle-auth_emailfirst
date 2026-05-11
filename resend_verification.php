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
 * Resend verification email page for auth_emailfirst.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public email verification resend endpoint.
require('../../config.php');
require_once($CFG->dirroot . '/auth/emailfirst/auth.php');

$email = optional_param('email', '', PARAM_EMAIL);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url('/auth/emailfirst/resend_verification.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('resend_verification', 'auth_emailfirst'));
$PAGE->set_heading(get_string('resend_verification', 'auth_emailfirst'));

// Redirect logged-in users.
if (isloggedin() && !isguestuser()) {
    redirect($CFG->wwwroot . '/');
}

if (!is_enabled_auth('emailfirst')) {
    throw new moodle_exception('pluginnotenabled', 'auth_emailfirst');
}

$authplugin = new auth_plugin_emailfirst();
$error = '';
$emailsent = false;

// Handle form submission.
if ($confirm && !empty($email)) {
    require_sesskey();

    $user = $DB->get_record('user', [
        'email' => $email,
        'auth' => 'emailfirst',
        'deleted' => 0,
        'mnethostid' => $CFG->mnet_localhost_id,
    ]);

    if ($user && empty($user->confirmed)) {
        $authplugin->send_verification_email($user);
    }
    $emailsent = true;
}

echo $OUTPUT->header();

$templatecontext = [
    'wwwroot' => $CFG->wwwroot,
    'sitename' => format_string($CFG->fullname),
    'logourl' => $OUTPUT->get_logo_url() ? $OUTPUT->get_logo_url()->out() : '',
    'email' => s($email),
    'sesskey' => sesskey(),
    'error' => $error,
    'emailsent' => $emailsent,
    'loginurl' => $CFG->wwwroot . '/login/index.php',
    'signupurl' => $CFG->wwwroot . '/login/signup.php',
];

echo $OUTPUT->render_from_template('auth_emailfirst/resend_verification', $templatecontext);

echo $OUTPUT->footer();
