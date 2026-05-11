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
 * Forgot password page for auth_emailfirst.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public password reset request endpoint.
require('../../config.php');
require_once($CFG->dirroot . '/auth/emailfirst/auth.php');

$email = optional_param('email', '', PARAM_EMAIL);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url('/auth/emailfirst/forgot_password.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('forgotpassword', 'auth_emailfirst'));
$PAGE->set_heading(get_string('forgotpassword', 'auth_emailfirst'));

// Redirect logged-in users.
if (isloggedin() && !isguestuser()) {
    redirect($CFG->wwwroot . '/');
}

if (!is_enabled_auth('emailfirst')) {
    throw new moodle_exception('pluginnotenabled', 'auth_emailfirst');
}

$authplugin = new auth_plugin_emailfirst();
$config = get_config('auth_emailfirst');
$error = '';
$emailsent = false;

// Handle form submission.
if ($confirm && !empty($email)) {
    require_sesskey();

    $user = $DB->get_record('user', [
        'email' => $email,
        'deleted' => 0,
        'suspended' => 0,
        'mnethostid' => $CFG->mnet_localhost_id,
    ]);

    // Always show success to prevent email enumeration.
    if ($user) {
        $issiteadmin = is_siteadmin($user);
        $userauth = get_auth_plugin($user->auth);
        $canchangepassword = $userauth && method_exists($userauth, 'can_change_password')
            ? $userauth->can_change_password()
            : false;

        if ($issiteadmin || empty($user->confirmed) || !$canchangepassword) {
            // Don't send email but show success anyway for security.
            $emailsent = true;
        } else {
            $result = $authplugin->send_password_reset_email($user);
            if ($result) {
                $emailsent = true;
            } else {
                $error = get_string('errorsendingemail', 'auth_emailfirst');
            }
        }
    } else {
        $emailsent = true;
    }
}

echo $OUTPUT->header();

// Resolve logo.
$logourl = $OUTPUT->get_logo_url();
$logosource = !empty($config->signuplogo) ? $config->signuplogo : 'core_logo';
switch ($logosource) {
    case 'core_logocompact':
        $logourl = $OUTPUT->get_compact_logo_url();
        break;
    case 'boostunion_logo':
        $raw = get_config('theme_boost_union', 'logo');
        if (!empty($raw)) {
            $logourl = moodle_url::make_pluginfile_url(
                context_system::instance()->id,
                'theme_boost_union',
                'logo',
                theme_get_revision(),
                '/',
                $raw
            );
        }
        break;
    case 'boostunion_logocompact':
        $raw = get_config('theme_boost_union', 'logocompact');
        if (!empty($raw)) {
            $logourl = moodle_url::make_pluginfile_url(
                context_system::instance()->id,
                'theme_boost_union',
                'logocompact',
                theme_get_revision(),
                '/',
                $raw
            );
        }
        break;
    case 'none':
        $logourl = false;
        break;
}

// Logo dimensions.
$logoheight = !empty($config->logoheight) ? (int) $config->logoheight : 80;
$logowidth = !empty($config->logowidth) ? (int) $config->logowidth : 0;
$logostyle = 'max-height:' . $logoheight . 'px;';
if ($logowidth > 0) {
    $logostyle .= 'max-width:' . $logowidth . 'px;';
}

// Contact info.
$showcontact = !isset($config->showsignupcontact) || !empty($config->showsignupcontact);
$contacttext = '';
if ($showcontact) {
    $contacttext = !empty($config->signupcontact)
        ? s($config->signupcontact)
        : s(get_string('signupcontact_default', 'auth_emailfirst'));
}

$templatecontext = [
    'wwwroot' => $CFG->wwwroot,
    'sitename' => format_string(
        $SITE->fullname,
        true,
        ['context' => context_course::instance(SITEID), 'escape' => false]
    ),
    'logourl' => $logourl ? ($logourl instanceof moodle_url ? $logourl->out(false) : $logourl) : false,
    'logostyle' => $logostyle,
    'heading' => get_string('forgotpassword', 'auth_emailfirst'),
    'subheading' => get_string('forgotpassword_desc', 'auth_emailfirst'),
    'email' => s($email),
    'sesskey' => sesskey(),
    'error' => $error,
    'emailsent' => $emailsent,
    'loginurl' => $CFG->wwwroot . '/login/index.php',
    'signupurl' => $CFG->wwwroot . '/auth/emailfirst/signup.php',
    'showcontact' => $showcontact,
    'contacttext' => $contacttext,
];

echo $OUTPUT->render_from_template('auth_emailfirst/forgot_password', $templatecontext);

echo $OUTPUT->footer();
