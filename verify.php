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
 * Email verification handler for auth_emailfirst.
 *
 * This page handles the custom verify flow. The standard flow uses /login/confirm.php.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public email verification endpoint.
require('../../config.php');
require_once($CFG->dirroot . '/auth/emailfirst/auth.php');

$token = required_param('token', PARAM_ALPHANUM);

$PAGE->set_url('/auth/emailfirst/verify.php', ['token' => $token]);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('pluginname', 'auth_emailfirst'));

// Check if user is already logged in.
if (isloggedin() && !isguestuser()) {
    redirect($CFG->wwwroot . '/');
}

if (!is_enabled_auth('emailfirst')) {
    throw new moodle_exception('pluginnotenabled', 'auth_emailfirst');
}

$authplugin = new auth_plugin_emailfirst();

// Verify the token.
$user = $authplugin->verify_email_token($token);

echo $OUTPUT->header();

$content = '';
if ($user) {
    $content .= html_writer::tag('i', '', [
        'class' => 'fa fa-check-circle fa-4x text-success mb-3',
        'aria-hidden' => 'true',
    ]);
    $content .= html_writer::tag('h3', get_string('verificationsuccess', 'auth_emailfirst'));
    $content .= html_writer::tag('p', get_string('welcome_user', 'auth_emailfirst', fullname($user)));
    $content .= html_writer::tag('p', get_string('verificationsuccess_desc', 'auth_emailfirst'), ['class' => 'text-muted']);
    $content .= html_writer::tag('div', html_writer::link(
        new moodle_url('/login/index.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-sign-in', 'aria-hidden' => 'true']) . ' ' .
            get_string('gotologin', 'auth_emailfirst'),
        ['class' => 'btn btn-primary btn-lg']
    ), ['class' => 'mt-4']);
} else {
    $content .= html_writer::tag('i', '', [
        'class' => 'fa fa-times-circle fa-4x text-danger mb-3',
        'aria-hidden' => 'true',
    ]);
    $content .= html_writer::tag('h3', get_string('verificationinvalid', 'auth_emailfirst'));
    $content .= html_writer::tag('p', get_string('verificationinvalid_desc', 'auth_emailfirst'), ['class' => 'text-muted']);
    $content .= html_writer::tag('div', html_writer::link(
        new moodle_url('/auth/emailfirst/resend_verification.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-redo', 'aria-hidden' => 'true']) . ' ' .
            get_string('resend_verification_button', 'auth_emailfirst'),
        ['class' => 'btn btn-primary btn-lg']
    ), ['class' => 'mt-4']);
    $content .= html_writer::tag(
        'div',
        html_writer::link(
            new moodle_url('/login/signup.php'),
            get_string('signup_heading', 'auth_emailfirst'),
            ['class' => 'btn btn-outline-secondary']
        ) . ' ' . html_writer::link(
            new moodle_url('/login/index.php'),
            get_string('gotologin', 'auth_emailfirst'),
            ['class' => 'btn btn-outline-secondary']
        ),
        ['class' => 'mt-3']
    );
}

echo html_writer::tag(
    'div',
    html_writer::tag(
        'div',
        html_writer::tag('div', $content, ['class' => 'card-body text-center']),
        ['class' => 'card border-0']
    ),
    ['class' => 'emailfirst-verify-container', 'style' => 'max-width: 500px; margin: 2rem auto;']
);

echo $OUTPUT->footer();
