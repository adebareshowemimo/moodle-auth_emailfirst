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
 * MFA enrollment gateway for auth_emailfirst.
 *
 * After successful login/signup, users with MFA requirement are redirected here.
 * Guides users through setting up a multi-factor authentication method.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_login();

$PAGE->set_url(new moodle_url('/auth/emailfirst/enroll_mfa.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');

$config = get_config('auth_emailfirst');
$userid = $USER->id;

// Check if MFA is enabled.
if (empty($config->requiremfafor)) {
    redirect(new moodle_url('/my'));
}

// Get the factor manager for Moodle 4.1+ native MFA.
$factormanager = \core_mfa\manager::get_factor_manager();
$factors = $factormanager->get_available_factors();

if (empty($factors)) {
    // No MFA factors available on this system.
    redirect(new moodle_url('/my'));
}

// Check if user already has an enrolled MFA factor.
$userfactors = $factormanager->get_user_factors($userid);
if (!empty($userfactors)) {
    // User already enrolled.
    redirect(new moodle_url('/my'));
}

// Check if user is in grace period (can skip for now).
$graceperiod = (int) (!empty($config->mfagraceperiod) ? $config->mfagraceperiod : 7);
$usergraceend = $USER->timecreated + ($graceperiod * 86400);
$ingrace = time() < $usergraceend;

// Build template context.
$ctx = new stdClass();
$ctx->sitename = get_site()->fullname;
$ctx->heading = get_string('mfa_enrollment_heading', 'auth_emailfirst');
$ctx->content = get_string('mfa_enrollment_content', 'auth_emailfirst');
$ctx->in_grace_period = $ingrace;
$ctx->grace_until = userdate($usergraceend, get_string('strftimedatetime'));
$ctx->factors = [];
$ctx->skip_url = new moodle_url('/my');

// List available factors.
foreach ($factors as $factor) {
    $ctx->factors[] = [
        'id' => $factor->get_id(),
        'name' => $factor->get_display_name(),
        'description' => $factor->get_info(),
        'setup_url' => new moodle_url('/auth/mfa/setup.php', ['factor' => $factor->get_id()]),
    ];
}

$OUTPUT = $PAGE->get_renderer('core');

// Handle form submission (skip grace period).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = optional_param('action', '', PARAM_ALPHA);
    if ($action === 'skip' && $ingrace) {
        // Mark grace period as understood, don't require MFA enrollment yet.
        redirect(new moodle_url('/my'));
    }
}

// Render template.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('auth_emailfirst/enroll_mfa', $ctx);
echo $OUTPUT->footer();
