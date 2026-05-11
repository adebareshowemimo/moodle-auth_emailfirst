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
 * Admin settings for auth_emailfirst.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Signup form logo source.
    $logochoices = [
        'core_logo' => get_string('signuplogo_core_logo', 'auth_emailfirst'),
        'core_logocompact' => get_string('signuplogo_core_logocompact', 'auth_emailfirst'),
    ];
    // Add Boost Union options if that theme is installed.
    if (file_exists($CFG->dirroot . '/theme/boost_union/version.php')) {
        $logochoices['boostunion_logo'] = get_string('signuplogo_boostunion_logo', 'auth_emailfirst');
        $logochoices['boostunion_logocompact'] = get_string('signuplogo_boostunion_logocompact', 'auth_emailfirst');
    }
    $logochoices['none'] = get_string('signuplogo_none', 'auth_emailfirst');
    $settings->add(new admin_setting_configselect(
        'auth_emailfirst/signuplogo',
        get_string('signuplogo', 'auth_emailfirst'),
        get_string('signuplogo_desc', 'auth_emailfirst'),
        'core_logo',
        $logochoices
    ));

    // Logo width.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/logowidth',
        get_string('logowidth', 'auth_emailfirst'),
        get_string('logowidth_desc', 'auth_emailfirst'),
        '',
        PARAM_INT
    ));

    // Logo height.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/logoheight',
        get_string('logoheight', 'auth_emailfirst'),
        get_string('logoheight_desc', 'auth_emailfirst'),
        '80',
        PARAM_INT
    ));

    // Enable custom login page.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/enablecustomlogin',
        get_string('enablecustomlogin', 'auth_emailfirst'),
        get_string('enablecustomlogin_desc', 'auth_emailfirst'),
        1
    ));

    // Login page text.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/logintext_header',
        get_string('logintext_settings', 'auth_emailfirst'),
        ''
    ));

    // Show login heading.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/showloginheading',
        get_string('showloginheading', 'auth_emailfirst'),
        get_string('showloginheading_desc', 'auth_emailfirst'),
        1
    ));

    // Login heading text.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/loginheading',
        get_string('loginheading', 'auth_emailfirst'),
        get_string('loginheading_desc', 'auth_emailfirst'),
        get_string('loginheading_default', 'auth_emailfirst'),
        PARAM_TEXT
    ));

    // Show login subheading.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/showloginsubheading',
        get_string('showloginsubheading', 'auth_emailfirst'),
        get_string('showloginsubheading_desc', 'auth_emailfirst'),
        1
    ));

    // Login subheading text.
    $settings->add(new admin_setting_configtextarea(
        'auth_emailfirst/loginsubheading',
        get_string('loginsubheading', 'auth_emailfirst'),
        get_string('loginsubheading_desc', 'auth_emailfirst'),
        get_string('loginsubheading_default', 'auth_emailfirst'),
        PARAM_TEXT
    ));

    // Show signup button in navbar.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/shownavsignup',
        get_string('shownavsignup', 'auth_emailfirst'),
        get_string('shownavsignup_desc', 'auth_emailfirst'),
        1
    ));

    // Navbar login button.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/navloginbtn_header',
        get_string('navloginbtn_settings', 'auth_emailfirst'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/navlogin_text',
        get_string('navlogin_text', 'auth_emailfirst'),
        get_string('navlogin_text_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/navlogin_textcolor',
        get_string('navlogin_textcolor', 'auth_emailfirst'),
        get_string('navlogin_textcolor_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/navlogin_bgcolor',
        get_string('navlogin_bgcolor', 'auth_emailfirst'),
        get_string('navlogin_bgcolor_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/navlogin_padding',
        get_string('navlogin_padding', 'auth_emailfirst'),
        get_string('navlogin_padding_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/navlogin_borderradius',
        get_string('navlogin_borderradius', 'auth_emailfirst'),
        get_string('navlogin_borderradius_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    // Navbar sign up button.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/navsignupbtn_header',
        get_string('navsignupbtn_settings', 'auth_emailfirst'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/navsignup_text',
        get_string('navsignup_text', 'auth_emailfirst'),
        get_string('navsignup_text_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/navsignup_textcolor',
        get_string('navsignup_textcolor', 'auth_emailfirst'),
        get_string('navsignup_textcolor_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/navsignup_bgcolor',
        get_string('navsignup_bgcolor', 'auth_emailfirst'),
        get_string('navsignup_bgcolor_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/navsignup_padding',
        get_string('navsignup_padding', 'auth_emailfirst'),
        get_string('navsignup_padding_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/navsignup_borderradius',
        get_string('navsignup_borderradius', 'auth_emailfirst'),
        get_string('navsignup_borderradius_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    // Signup page text.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/signuptext_header',
        get_string('signuptext_settings', 'auth_emailfirst'),
        ''
    ));

    // Show signup heading.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/showsignupheading',
        get_string('showsignupheading', 'auth_emailfirst'),
        get_string('showsignupheading_desc', 'auth_emailfirst'),
        1
    ));

    // Signup heading text.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/signupheading',
        get_string('signupheading', 'auth_emailfirst'),
        get_string('signupheading_desc', 'auth_emailfirst'),
        get_string('signupheading_default', 'auth_emailfirst'),
        PARAM_TEXT
    ));

    // Show signup subheading.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/showsignupsubheading',
        get_string('showsignupsubheading', 'auth_emailfirst'),
        get_string('showsignupsubheading_desc', 'auth_emailfirst'),
        1
    ));

    // Signup subheading text.
    $settings->add(new admin_setting_configtextarea(
        'auth_emailfirst/signupsubheading',
        get_string('signupsubheading', 'auth_emailfirst'),
        get_string('signupsubheading_desc', 'auth_emailfirst'),
        get_string('signupsubheading_default', 'auth_emailfirst'),
        PARAM_TEXT
    ));

    // Show signup contact info.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/showsignupcontact',
        get_string('showsignupcontact', 'auth_emailfirst'),
        get_string('showsignupcontact_desc', 'auth_emailfirst'),
        1
    ));

    // Signup contact info text.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/signupcontact',
        get_string('signupcontact', 'auth_emailfirst'),
        get_string('signupcontact_desc', 'auth_emailfirst'),
        get_string('signupcontact_default', 'auth_emailfirst'),
        PARAM_TEXT
    ));

    // Enable multi-step signup wizard.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/enablemultistep',
        get_string('enablemultistep', 'auth_emailfirst'),
        get_string('enablemultistep_desc', 'auth_emailfirst'),
        1
    ));

    // Enable reCAPTCHA.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/recaptcha',
        get_string('auth_emailfirstrecaptcha', 'auth_emailfirst'),
        get_string('auth_emailfirstrecaptcha_desc', 'auth_emailfirst'),
        1
    ));

    // Require email verification.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/requireemailverification',
        get_string('requireemailverification', 'auth_emailfirst'),
        get_string('requireemailverification_desc', 'auth_emailfirst'),
        1
    ));

    // Verification expiry time.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/verificationexpiry',
        get_string('verificationexpiry', 'auth_emailfirst'),
        get_string('verificationexpiry_desc', 'auth_emailfirst'),
        86400,
        PARAM_INT
    ));

    // Send welcome email.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/sendwelcomeemail',
        get_string('sendwelcomeemail', 'auth_emailfirst'),
        get_string('sendwelcomeemail_desc', 'auth_emailfirst'),
        1
    ));

    // Auto-enroll in course after email verification.
    // Build list of available courses.
    $courseoptions = [0 => get_string('none')];
    if ($ADMIN->fulltree) {
        global $DB;
        $courses = $DB->get_records('course', ['visible' => 1], 'fullname ASC', 'id, shortname, fullname');
        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue; // Skip site course.
            }
            $courseoptions[$course->id] = format_string($course->fullname) . ' (' . $course->shortname . ')';
        }
    }
    $settings->add(new admin_setting_configselect_autocomplete(
        'auth_emailfirst/autoenrollcourse',
        get_string('autoenrollcourse', 'auth_emailfirst'),
        get_string('autoenrollcourse_desc', 'auth_emailfirst'),
        0,
        $courseoptions
    ));

    // Redirect to course after verification (bypasses site policy).
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/redirecttocourse',
        get_string('redirecttocourse', 'auth_emailfirst'),
        get_string('redirecttocourse_desc', 'auth_emailfirst'),
        1
    ));

    // Auto-enroll existing users on login.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/autoenrollexistingusers',
        get_string('autoenrollexistingusers', 'auth_emailfirst'),
        get_string('autoenrollexistingusers_desc', 'auth_emailfirst'),
        0
    ));

    // Show referral survey on signup.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/showreferralsurvey',
        get_string('showreferralsurvey', 'auth_emailfirst'),
        get_string('showreferralsurvey_desc', 'auth_emailfirst'),
        1
    ));

    // Hide city and country on signup form.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/hidecitycountry',
        get_string('hidecitycountry', 'auth_emailfirst'),
        get_string('hidecitycountry_desc', 'auth_emailfirst'),
        1
    ));

    // Password visibility toggle on signup form.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/showpasswordtoggle',
        get_string('showpasswordtoggle', 'auth_emailfirst'),
        get_string('showpasswordtoggle_desc', 'auth_emailfirst'),
        1
    ));

    // Show identity providers on signup page.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/showidentityproviders',
        get_string('showidentityproviders', 'auth_emailfirst'),
        get_string('showidentityproviders_desc', 'auth_emailfirst'),
        1
    ));

    // Allowed domains.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/alloweddomains',
        get_string('alloweddomains', 'auth_emailfirst'),
        get_string('alloweddomains_desc', 'auth_emailfirst'),
        '',
        PARAM_TEXT
    ));

    // Verification email settings.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/verification_email_header',
        get_string('verification_email_settings', 'auth_emailfirst'),
        ''
    ));

    // Verification email subject.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/verificationemailsubject',
        get_string('verificationemailsubject', 'auth_emailfirst'),
        get_string('verificationemailsubject_desc', 'auth_emailfirst'),
        get_string('verificationemailsubject', 'auth_emailfirst'),
        PARAM_TEXT
    ));

    // Verification email body (HTML editor).
    $settings->add(new admin_setting_confightmleditor(
        'auth_emailfirst/verificationemailbody',
        get_string('verificationemailbody', 'auth_emailfirst'),
        get_string('verificationemailbody_desc', 'auth_emailfirst'),
        get_string('defaultverificationbody', 'auth_emailfirst')
    ));

    // Available placeholders for verification email.
    $verificationhtml = '<div class="alert alert-info">';
    $verificationhtml .= '<strong>' . get_string('available_placeholders', 'auth_emailfirst') . '</strong><br>';
    $verificationhtml .= '<div class="mt-2"><strong>';
    $verificationhtml .= get_string('user_placeholders', 'auth_emailfirst') . ':</strong><br>';
    $verificationhtml .= '<code>{firstname}</code>, <code>{lastname}</code>, <code>{email}</code></div>';
    $verificationhtml .= '<div class="mt-2"><strong>';
    $verificationhtml .= get_string('verification_placeholders', 'auth_emailfirst') . ':</strong><br>';
    $verificationhtml .= '<code>{verifyurl}</code> - ' . get_string('verifyurl_desc', 'auth_emailfirst') . '</div>';
    $verificationhtml .= '<div class="mt-2"><strong>';
    $verificationhtml .= get_string('global_placeholders', 'auth_emailfirst') . ':</strong><br>';
    $verificationhtml .= '<code>{sitename}</code>, <code>{siteurl}</code>, <code>{supportemail}</code></div>';
    $verificationhtml .= '</div>';
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/verification_placeholders',
        '',
        $verificationhtml
    ));

    // Welcome email settings.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/welcome_email_header',
        get_string('welcome_email_settings', 'auth_emailfirst'),
        ''
    ));

    // Welcome email subject.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/welcomeemailsubject',
        get_string('welcomeemailsubject', 'auth_emailfirst'),
        get_string('welcomeemailsubject_desc', 'auth_emailfirst'),
        get_string('welcomeemailsubject', 'auth_emailfirst'),
        PARAM_TEXT
    ));

    // Welcome email body (HTML editor).
    $settings->add(new admin_setting_confightmleditor(
        'auth_emailfirst/welcomeemailbody',
        get_string('welcomeemailbody', 'auth_emailfirst'),
        get_string('welcomeemailbody_desc', 'auth_emailfirst'),
        get_string('defaultwelcomebody', 'auth_emailfirst')
    ));

    // Available placeholders for welcome email.
    $welcomehtml = '<div class="alert alert-info">';
    $welcomehtml .= '<strong>' . get_string('available_placeholders', 'auth_emailfirst') . '</strong><br>';
    $welcomehtml .= '<div class="mt-2"><strong>';
    $welcomehtml .= get_string('user_placeholders', 'auth_emailfirst') . ':</strong><br>';
    $welcomehtml .= '<code>{firstname}</code>, <code>{lastname}</code>, <code>{email}</code></div>';
    $welcomehtml .= '<div class="mt-2"><strong>';
    $welcomehtml .= get_string('login_placeholders', 'auth_emailfirst') . ':</strong><br>';
    $welcomehtml .= '<code>{loginurl}</code> - ' . get_string('loginurl_desc', 'auth_emailfirst') . '</div>';
    $welcomehtml .= '<div class="mt-2"><strong>';
    $welcomehtml .= get_string('global_placeholders', 'auth_emailfirst') . ':</strong><br>';
    $welcomehtml .= '<code>{sitename}</code>, <code>{siteurl}</code>, <code>{supportemail}</code></div>';
    $welcomehtml .= '</div>';
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/welcome_placeholders',
        '',
        $welcomehtml
    ));

    // Password reset email settings.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/passwordreset_email_header',
        get_string('passwordreset_email_settings', 'auth_emailfirst'),
        ''
    ));

    // Password reset email subject.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/passwordresetemailsubject',
        get_string('passwordresetemailsubject', 'auth_emailfirst'),
        get_string('passwordresetemailsubject_desc', 'auth_emailfirst'),
        get_string('passwordresetemailsubject', 'auth_emailfirst'),
        PARAM_TEXT
    ));

    // Password reset email body (HTML editor).
    $settings->add(new admin_setting_confightmleditor(
        'auth_emailfirst/passwordresetemailbody',
        get_string('passwordresetemailbody', 'auth_emailfirst'),
        get_string('passwordresetemailbody_desc', 'auth_emailfirst'),
        get_string('defaultpasswordresetbody', 'auth_emailfirst')
    ));

    // Available placeholders for password reset email.
    $passwordresethtml = '<div class="alert alert-info">';
    $passwordresethtml .= '<strong>' . get_string('available_placeholders', 'auth_emailfirst') . '</strong><br>';
    $passwordresethtml .= '<div class="mt-2"><strong>';
    $passwordresethtml .= get_string('user_placeholders', 'auth_emailfirst') . ':</strong><br>';
    $passwordresethtml .= '<code>{firstname}</code>, <code>{lastname}</code>, <code>{email}</code></div>';
    $passwordresethtml .= '<div class="mt-2"><strong>';
    $passwordresethtml .= get_string('reset_placeholders', 'auth_emailfirst') . ':</strong><br>';
    $passwordresethtml .= '<code>{reseturl}</code> - ' . get_string('reseturl_desc', 'auth_emailfirst') . '</div>';
    $passwordresethtml .= '<div class="mt-2"><strong>';
    $passwordresethtml .= get_string('global_placeholders', 'auth_emailfirst') . ':</strong><br>';
    $passwordresethtml .= '<code>{sitename}</code>, <code>{siteurl}</code>, <code>{supportemail}</code></div>';
    $passwordresethtml .= '</div>';
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/passwordreset_placeholders',
        '',
        $passwordresethtml
    ));

    // Post-password-reset redirect.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/postresetredirect_header',
        get_string('postresetredirect_settings', 'auth_emailfirst'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/postresetredirecturl',
        get_string('postresetredirecturl', 'auth_emailfirst'),
        get_string('postresetredirecturl_desc', 'auth_emailfirst'),
        '',
        PARAM_URL
    ));

    // Referral report link.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/report_header',
        get_string('referral_report', 'auth_emailfirst'),
        html_writer::link(
            new moodle_url('/auth/emailfirst/report.php'),
            get_string('viewreport', 'auth_emailfirst'),
            ['class' => 'btn btn-secondary']
        )
    ));

    // Security and bot protection.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/security_header',
        get_string('security_settings', 'auth_emailfirst'),
        get_string('security_settings_desc', 'auth_emailfirst')
    ));

    // Enable reCAPTCHA on signup.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/enablerecaptcha',
        get_string('enablerecaptcha', 'auth_emailfirst'),
        get_string('enablerecaptcha_desc', 'auth_emailfirst'),
        0
    ));

    // ReCAPTCHA version.
    $recaptchachoices = [
        'v2_checkbox' => get_string('recaptcha_v2_checkbox', 'auth_emailfirst'),
        'v3' => get_string('recaptcha_v3', 'auth_emailfirst'),
    ];
    $settings->add(new admin_setting_configselect(
        'auth_emailfirst/recaptchaversion',
        get_string('recaptchaversion', 'auth_emailfirst'),
        get_string('recaptchaversion_desc', 'auth_emailfirst'),
        'v2_checkbox',
        $recaptchachoices
    ));

    // ReCAPTCHA site key.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/recaptchasitekey',
        get_string('recaptchasitekey', 'auth_emailfirst'),
        get_string('recaptchasitekey_desc', 'auth_emailfirst'),
        '',
        PARAM_RAW_TRIMMED
    ));

    // ReCAPTCHA secret key.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/recaptchasecretkey',
        get_string('recaptchasecretkey', 'auth_emailfirst'),
        get_string('recaptchasecretkey_desc', 'auth_emailfirst'),
        '',
        PARAM_RAW_TRIMMED
    ));

    // Enable reCAPTCHA on login.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/enablelogincaptcha',
        get_string('enablelogincaptcha', 'auth_emailfirst'),
        get_string('enablelogincaptcha_desc', 'auth_emailfirst'),
        0
    ));

    // Signup rate limit.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/signupratelimit',
        get_string('signupratelimit', 'auth_emailfirst'),
        get_string('signupratelimit_desc', 'auth_emailfirst'),
        '50',
        PARAM_INT
    ));

    // Multi-factor authentication.
    $settings->add(new admin_setting_heading(
        'auth_emailfirst/mfa_header',
        get_string('mfa_settings', 'auth_emailfirst'),
        get_string('mfa_settings_desc', 'auth_emailfirst')
    ));

    // Require MFA for roles.
    $rolechoices = [
        'admin' => get_string('mfa_role_admin', 'auth_emailfirst'),
        'manager' => get_string('mfa_role_manager', 'auth_emailfirst'),
        'teacher' => get_string('mfa_role_teacher', 'auth_emailfirst'),
    ];
    $settings->add(new admin_setting_configmulticheckbox(
        'auth_emailfirst/requiremfafor',
        get_string('requiremfafor', 'auth_emailfirst'),
        get_string('requiremfafor_desc', 'auth_emailfirst'),
        ['admin' => 1],
        $rolechoices
    ));

    // Prompt MFA at signup.
    $settings->add(new admin_setting_configcheckbox(
        'auth_emailfirst/promptmfaatnext',
        get_string('promptmfaatnext', 'auth_emailfirst'),
        get_string('promptmfaatnext_desc', 'auth_emailfirst'),
        0
    ));

    // Grace period for MFA.
    $settings->add(new admin_setting_configtext(
        'auth_emailfirst/mfagraceperiod',
        get_string('mfagraceperiod', 'auth_emailfirst'),
        get_string('mfagraceperiod_desc', 'auth_emailfirst'),
        '7',
        PARAM_INT
    ));
}
