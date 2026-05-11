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
 * Branded verify age and location page for auth_emailfirst.
 *
 * Replaces /login/verify_age_location.php with the emailfirst branded design.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public age verification endpoint.
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/authlib.php');

$authplugin = signup_is_enabled();

if (!$authplugin || !\core_auth\digital_consent::is_age_digital_consent_verification_enabled()) {
    redirect(new moodle_url('/'), get_string('verifyagedigitalconsentnotpossible', 'error'));
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/auth/emailfirst/verify_age_location.php'));
$PAGE->set_pagelayout('login');

$SITE = get_site();
$PAGE->set_title(get_string('agelocationverification'));
$PAGE->set_heading($SITE->fullname);

if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/'), get_string('cannotsignup', 'error', fullname($USER)));
}

$cache = cache::make('core', 'presignup');
$isminor = $cache->get('isminor');
if ($isminor === 'yes') {
    redirect(new moodle_url('/login/digital_minor.php'));
} else if ($isminor === 'no') {
    redirect(new moodle_url('/auth/emailfirst/signup.php'));
}

$config = get_config('auth_emailfirst');
$OUTPUT = $PAGE->get_renderer('core');

$errors = [];
$age = '';
$country = 'US';
$errormessage = '';

// Handle POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $age = optional_param('age', '', PARAM_INT);
    $country = optional_param('country', '', PARAM_ALPHA);

    // Validate.
    if (empty($age) || !is_numeric($age) || $age < 1) {
        $errors['age'] = get_string('required');
    } else if ($age > 120) {
        $errors['age'] = get_string('agecorrect', 'auth_emailfirst');
    }
    if (empty($country)) {
        $errors['country'] = get_string('required');
    }

    if (empty($errors)) {
        try {
            $minor = \core_auth\digital_consent::is_minor($age, $country);
            $cache->set('isminor', $minor ? 'yes' : 'no');
            if ($minor) {
                redirect(new moodle_url('/login/digital_minor.php'));
            } else {
                redirect(new moodle_url('/auth/emailfirst/signup.php'));
            }
        } catch (moodle_exception $e) {
            $errormessage = get_string('couldnotverifyagedigitalconsent', 'error');
        }
    }
}

// Build template context.
$ctx = new stdClass();

// Logo.
$authinstance = get_auth_plugin('emailfirst');
$method = new ReflectionMethod($authinstance, 'resolve_logo_url');
$method->setAccessible(true);
$logourl = $method->invoke($authinstance, $OUTPUT);
$ctx->logourl = $logourl ? $logourl->out(false) : false;

// Logo dimensions.
$logoheight = !empty($config->logoheight) ? (int) $config->logoheight : 80;
$logowidth = !empty($config->logowidth) ? (int) $config->logowidth : 0;
$logostyle = 'max-height:' . $logoheight . 'px;';
if ($logowidth > 0) {
    $logostyle .= 'max-width:' . $logowidth . 'px;';
}
$ctx->logostyle = $logostyle;

$ctx->sitename = format_string(
    $SITE->fullname,
    true,
    ['context' => context_course::instance(SITEID), 'escape' => false]
);

$ctx->heading = get_string('agelocationverification');
$ctx->subheading = get_string('explanationdigitalminor');

$ctx->actionurl = (new moodle_url('/auth/emailfirst/verify_age_location.php'))->out(false);
$ctx->loginurl = (new moodle_url('/login/index.php'))->out(false);
$ctx->sesskey = sesskey();

$ctx->age = s($age);

// Countries.
$countries = get_string_manager()->get_list_of_countries();
$countrylist = [];
$countrylist[] = ['value' => '', 'label' => get_string('selectacountry'), 'selected' => empty($country)];
foreach ($countries as $code => $name) {
    $countrylist[] = [
        'value' => $code,
        'label' => $name,
        'selected' => ($country === $code),
    ];
}
$ctx->countries = $countrylist;

// Errors.
$errorobj = new stdClass();
foreach ($errors as $field => $msg) {
    $errorobj->$field = $msg;
}
$ctx->errors = $errorobj;
$ctx->error = !empty($errormessage) ? $errormessage : false;

// Contact info.
$showcontact = !isset($config->showsignupcontact) || !empty($config->showsignupcontact);
$ctx->showcontact = $showcontact;
if ($showcontact) {
    $ctx->contacttext = !empty($config->signupcontact)
        ? s($config->signupcontact)
        : s(get_string('signupcontact_default', 'auth_emailfirst'));
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('auth_emailfirst/verify_age_location', $ctx);
echo $OUTPUT->footer();
