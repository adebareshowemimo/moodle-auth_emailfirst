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
 * Authentication Plugin: Email-First Authentication
 *
 * Uses the user's email address as their username. Integrates with
 * Moodle's standard /login/signup.php via a custom signup form.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Email-first authentication plugin.
 */
class auth_plugin_emailfirst extends auth_plugin_base {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'emailfirst';
        $this->config = get_config('auth_emailfirst');
    }

    /**
     * Returns true if the username and password work.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        global $DB, $CFG;

        $user = $DB->get_record('user', [
            'username' => $username,
            'mnethostid' => $CFG->mnet_localhost_id,
        ]);

        if (!$user) {
            return false;
        }

        return validate_internal_user_password($user, $password);
    }

    /**
     * Updates the user's password.
     *
     * @param object $user User table object
     * @param string $newpassword Plaintext password
     * @return bool True on success
     */
    public function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);
        return update_internal_user_password($user, $newpassword);
    }

    /**
     * Returns true if this authentication plugin can change the user's password.
     *
     * @return bool
     */
    public function can_change_password() {
        return true;
    }

    /**
     * Returns the URL for changing the user's password, or empty if the default can be used.
     *
     * @return moodle_url|null
     */
    public function change_password_url() {
        return null;
    }

    /**
     * Returns true if plugin allows signups.
     *
     * @return bool
     */
    public function can_signup() {
        return true;
    }

    /**
     * Returns true if plugin allows confirming of new users.
     *
     * @return bool
     */
    public function can_confirm() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can edit the users' profile.
     *
     * @return bool
     */
    public function can_edit_profile() {
        return true;
    }

    /**
     * Returns whether or not the captcha element is enabled.
     *
     * @return bool
     */
    public function is_captcha_enabled() {
        return get_config('auth_emailfirst', 'recaptcha');
    }

    /**
     * Hook for overriding the login page with a custom branded template.
     *
     * Called by /login/index.php before any form processing or rendering.
     * On GET requests (when enablecustomlogin is on), renders our custom
     * login template and exits. POST requests pass through so Moodle's
     * standard authentication flow handles form submission.
     */
    public function loginpage_hook() {
        global $CFG, $PAGE, $OUTPUT, $SESSION;

        if (empty($this->config->enablecustomlogin)) {
            return;
        }

        // Let Moodle handle POST requests (form submission / authentication).
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }

        // Set up the page.
        $SITE = get_site();
        $PAGE->set_url(new moodle_url('/login/index.php'));
        $PAGE->set_context(context_system::instance());
        $PAGE->set_pagelayout('login');
        $PAGE->set_title(get_string('loginto', '', format_string($SITE->fullname)));

        // Ensure $OUTPUT is a full renderer (not bootstrap_renderer) before
        // constructing the login renderable, which calls export_for_action_menu().
        $OUTPUT = $PAGE->get_renderer('core');

        // Build the login renderable — this gathers IDPs, instructions,
        // guest login, session errors, etc.
        $authsequence = get_enabled_auth_plugins();
        $username = optional_param('username', '', PARAM_RAW);
        $loginform = new \core_auth\output\login($authsequence, $username);
        $context = $loginform->export_for_template($OUTPUT);

        // Format error text.
        if (!empty($context->error)) {
            $context->errorformatted = $OUTPUT->error_text($context->error);
        }

        // Resolve logo from plugin settings (same source as signup form).
        $logourl = $this->resolve_logo_url($OUTPUT);
        $context->logourl = $logourl ? $logourl->out(false) : false;

        // Logo dimensions.
        $logoheight = !empty($this->config->logoheight) ? (int) $this->config->logoheight : 80;
        $logowidth = !empty($this->config->logowidth) ? (int) $this->config->logowidth : 0;
        $logostyle = 'max-height:' . $logoheight . 'px;';
        if ($logowidth > 0) {
            $logostyle .= 'max-width:' . $logowidth . 'px;';
        }
        $context->logostyle = $logostyle;

        $context->sitename = format_string(
            $SITE->fullname,
            true,
            ['context' => context_course::instance(SITEID), 'escape' => false]
        );

        // Configurable login heading.
        $showheading = !isset($this->config->showloginheading) || !empty($this->config->showloginheading);
        $context->loginheading = '';
        if ($showheading) {
            $context->loginheading = !empty($this->config->loginheading)
                ? format_string($this->config->loginheading)
                : get_string('loginheading_default', 'auth_emailfirst');
        }

        // Configurable login subheading.
        $showsub = !isset($this->config->showloginsubheading) || !empty($this->config->showloginsubheading);
        $context->loginsubheading = '';
        if ($showsub) {
            $context->loginsubheading = !empty($this->config->loginsubheading)
                ? format_string($this->config->loginsubheading)
                : get_string('loginsubheading_default', 'auth_emailfirst');
        }

        // Point "Forgot password" link to our branded page.
        $context->forgotpasswordurl = (new moodle_url('/auth/emailfirst/forgot_password.php'))->out(false);

        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('auth_emailfirst/loginform', $context);
        echo $OUTPUT->footer();
        // Footer calls session_write_close() internally; die is safe here.
        die;
    }

    /**
     * Resolve the logo URL based on the plugin's signuplogo setting.
     *
     * Shared helper used by loginpage_hook(). The signup form uses the
     * same switch logic inline in its definition() method.
     *
     * @param \renderer_base $output The renderer (for core logo methods).
     * @return \moodle_url|false Logo URL or false if none configured.
     */
    protected function resolve_logo_url($output) {
        $logosource = $this->config->signuplogo ?? 'core_logo';

        switch ($logosource) {
            case 'core_logo':
                return $output->get_logo_url();
            case 'core_logocompact':
                return $output->get_compact_logo_url();
            case 'boostunion_logo':
                $raw = get_config('theme_boost_union', 'logo');
                if (!empty($raw)) {
                    return \moodle_url::make_pluginfile_url(
                        \context_system::instance()->id,
                        'theme_boost_union',
                        'logo',
                        theme_get_revision(),
                        '/',
                        $raw
                    );
                }
                return false;
            case 'boostunion_logocompact':
                $raw = get_config('theme_boost_union', 'logocompact');
                if (!empty($raw)) {
                    return \moodle_url::make_pluginfile_url(
                        \context_system::instance()->id,
                        'theme_boost_union',
                        'logocompact',
                        theme_get_revision(),
                        '/',
                        $raw
                    );
                }
                return false;
            case 'none':
            default:
                return false;
        }
    }

    /**
     * Return a form to capture user details for account creation.
     *
     * @return moodleform
     */
    public function signup_form() {
        global $CFG;
        require_once($CFG->dirroot . '/auth/emailfirst/signup_form.php');
        return new auth_emailfirst_signup_form(null, null, 'post', '', ['autocomplete' => 'on']);
    }

    /**
     * Sign up a new user ready for confirmation.
     *
     * @param object $user new user object
     * @param bool $notify print notice with link and terminate
     * @return bool
     */
    public function user_signup($user, $notify = true) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        // Enforce email as username.
        $user->username = core_text::strtolower($user->email);

        $plainpassword = $user->password;
        $user->password = hash_internal_user_password($user->password);

        if (empty($user->calendartype)) {
            $user->calendartype = $CFG->calendartype;
        }

        $user->id = user_create_user($user, false, false);

        // Accept site policy at signup time so users are not redirected to the
        // policy page after registration — regardless of email-verification setting.
        $policymanager = new \core_privacy\local\sitepolicy\manager();
        if ($policymanager->is_defined()) {
            $DB->set_field('user', 'policyagreed', 1, ['id' => $user->id]);
        }

        user_add_password_history($user->id, $plainpassword);

        // Save any custom profile field information.
        profile_save_data($user);

        // Save referral source survey response.
        if (!empty($user->referral_source)) {
            $record = new stdClass();
            $record->userid = $user->id;
            $record->referral_source = $user->referral_source;
            $record->timecreated = time();
            $DB->insert_record('auth_emailfirst_survey', $record);
        }

        // Trigger event.
        \core\event\user_created::create_from_userid($user->id)->trigger();

        // Check if email verification is enabled.
        if (!empty($this->config->requireemailverification)) {
            $user->confirmed = 0;
            $DB->set_field('user', 'confirmed', 0, ['id' => $user->id]);

            // Send verification email using $user->secret (set by signup_setup_new_user).
            $this->send_verification_email($user);

            if ($notify) {
                global $PAGE, $OUTPUT;
                $emailconfirm = get_string('verificationemailsent', 'auth_emailfirst');
                $PAGE->navbar->add($emailconfirm);
                $PAGE->set_title($emailconfirm);
                $PAGE->set_heading($PAGE->course->fullname);
                $OUTPUT = $PAGE->get_renderer('core');
                echo $OUTPUT->header();
                echo $OUTPUT->render_from_template(
                    'auth_emailfirst/signup_confirmation',
                    $this->build_confirmation_context($OUTPUT, [
                        'heading' => get_string('verificationemailsent', 'auth_emailfirst'),
                        'message' => get_string('verificationemailsent_desc', 'auth_emailfirst'),
                        'email' => s($user->email),
                        'submessage' => get_string('verificationemailsent_check', 'auth_emailfirst'),
                        'actionurl' => "$CFG->wwwroot/index.php",
                        'actionlabel' => get_string('continue'),
                        'icon' => 'fa-envelope',
                        'variant' => 'info',
                    ])
                );
                echo $OUTPUT->footer();
            }
            return false;
        }

        // No verification required — confirm immediately.
        $DB->set_field('user', 'confirmed', 1, ['id' => $user->id]);

        if (!empty($this->config->sendwelcomeemail)) {
            $this->send_welcome_email($user);
        }

        // Auto-login the new user so they land inside the site immediately.
        $fulluser = get_complete_user_data('username', $user->username);
        if ($fulluser && !$fulluser->suspended) {
            global $SESSION;
            complete_user_login($fulluser);
            \core\session\manager::apply_concurrent_login_limit($fulluser->id, session_id());

            // Accept site policy (already set policyagreed=1 in DB above).
            $policymanager = new \core_privacy\local\sitepolicy\manager();
            if ($policymanager->is_defined() && empty($fulluser->policyagreed)) {
                $policymanager->accept();
            }

            // Clear any stale wantsurl to prevent unwanted redirects.
            if (!empty($SESSION->wantsurl)) {
                unset($SESSION->wantsurl);
            }

            // Auto-enroll in course if configured.
            require_once($CFG->dirroot . '/auth/emailfirst/lib.php');
            $autoenrollcourseid = get_config('auth_emailfirst', 'autoenrollcourse');
            $enrolledcourse = null;
            if (!empty($autoenrollcourseid) && $autoenrollcourseid > 0) {
                $enrolledcourse = auth_emailfirst_enroll_user_in_course($fulluser->id, $autoenrollcourseid);
            }

            // Redirect to course if enrolled, otherwise dashboard.
            if ($enrolledcourse) {
                redirect(new moodle_url('/course/view.php', ['id' => $enrolledcourse->id]));
            }
            redirect(new moodle_url('/my/'));
        }
        return true;
    }

    /**
     * Build the template context for the signup confirmation page.
     *
     * @param \renderer_base $output The renderer (for logo resolution).
     * @param array $overrides Values to merge (heading, message, email, actionurl, etc.).
     * @return stdClass Template context.
     */
    protected function build_confirmation_context($output, array $overrides): stdClass {
        $SITE = get_site();
        $ctx = new stdClass();

        // Logo.
        $logourl = $this->resolve_logo_url($output);
        $ctx->logourl = $logourl ? $logourl->out(false) : false;

        $logoheight = !empty($this->config->logoheight) ? (int) $this->config->logoheight : 80;
        $logowidth = !empty($this->config->logowidth) ? (int) $this->config->logowidth : 0;
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

        // Contact info.
        $showcontact = !isset($this->config->showsignupcontact) || !empty($this->config->showsignupcontact);
        $ctx->showcontact = $showcontact;
        if ($showcontact) {
            $ctx->contacttext = !empty($this->config->signupcontact)
                ? s($this->config->signupcontact)
                : s(get_string('signupcontact_default', 'auth_emailfirst'));
        }

        // Merge overrides.
        foreach ($overrides as $key => $value) {
            $ctx->$key = $value;
        }

        return $ctx;
    }

    /**
     * Confirm the new user as registered.
     *
     * @param string $username
     * @param string $confirmsecret
     * @return int AUTH_CONFIRM_OK, AUTH_CONFIRM_ALREADY, AUTH_CONFIRM_ERROR, or AUTH_CONFIRM_FAIL
     */
    public function user_confirm($username, $confirmsecret) {
        global $DB;

        $user = get_complete_user_data('username', $username);

        if (!$user) {
            return AUTH_CONFIRM_ERROR;
        }

        if ($user->auth != 'emailfirst') {
            return AUTH_CONFIRM_ERROR;
        }

        if ($user->confirmed) {
            return AUTH_CONFIRM_ALREADY;
        }

        if ($user->secret === $confirmsecret) {
            $DB->set_field('user', 'confirmed', 1, ['id' => $user->id]);

            if (!empty($this->config->sendwelcomeemail)) {
                $this->send_welcome_email($user);
            }

            return AUTH_CONFIRM_OK;
        }

        return AUTH_CONFIRM_ERROR;
    }

    /**
     * Send verification email to user.
     *
     * Uses $user->secret (set by signup_setup_new_user) to generate the
     * custom /auth/emailfirst/confirm.php URL (for auto-enrollment).
     *
     * @param object $user User object (must have ->secret set)
     * @return bool Success status
     */
    public function send_verification_email($user) {
        global $CFG;
        $SITE = get_site();

        // Build custom confirmation URL for auth_emailfirst (supports auto-enrollment).
        // Do not urlencode() the username here — moodle_url already encodes parameter values.
        $data = $user->secret . '/' . $user->username;
        $verifyurl = new moodle_url('/auth/emailfirst/confirm.php', ['data' => $data]);

        $subject = $this->config->verificationemailsubject ?? get_string('verificationemailsubject', 'auth_emailfirst');
        $messagebody = $this->config->verificationemailbody ?? get_string('defaultverificationbody', 'auth_emailfirst');

        // Replace placeholders.
        $replacements = [
            '{firstname}' => $user->firstname,
            '{lastname}' => $user->lastname,
            '{email}' => $user->email,
            '{verifyurl}' => $verifyurl->out(false),
            '{sitename}' => format_string($SITE->fullname),
            '{siteurl}' => $CFG->wwwroot,
            '{supportemail}' => $CFG->supportemail ?? '',
        ];
        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $messagebody = str_replace(array_keys($replacements), array_values($replacements), $messagebody);

        $user->email = clean_param($user->email, PARAM_EMAIL);
        return email_to_user(
            $user,
            core_user::get_support_user(),
            $subject,
            html_to_text($messagebody),
            $messagebody
        );
    }

    /**
     * Send welcome email to user.
     *
     * @param object $user User object
     * @return bool Success status
     */
    public function send_welcome_email($user) {
        global $CFG;
        $SITE = get_site();

        $subject = $this->config->welcomeemailsubject ?? get_string('welcomeemailsubject', 'auth_emailfirst');
        $messagebody = $this->config->welcomeemailbody ?? get_string('defaultwelcomebody', 'auth_emailfirst');

        $replacements = [
            '{firstname}' => $user->firstname,
            '{lastname}' => $user->lastname,
            '{email}' => $user->email,
            '{loginurl}' => $CFG->wwwroot . '/login/index.php',
            '{sitename}' => format_string($SITE->fullname),
            '{siteurl}' => $CFG->wwwroot,
            '{supportemail}' => $CFG->supportemail ?? '',
        ];
        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $messagebody = str_replace(array_keys($replacements), array_values($replacements), $messagebody);

        return email_to_user(
            $user,
            core_user::get_support_user(),
            $subject,
            html_to_text($messagebody),
            $messagebody
        );
    }

    /**
     * Check if email already exists and return authentication methods.
     *
     * @param string $email Email address
     * @return array|false Array with user info and auth methods, or false
     */
    public function check_email_exists($email) {
        global $DB, $CFG;

        $email = trim(core_text::strtolower($email));

        $user = $DB->get_record('user', [
            'email' => $email,
            'deleted' => 0,
            'mnethostid' => $CFG->mnet_localhost_id,
        ]);

        if (!$user) {
            return false;
        }

        $authmethods = [$user->auth];

        // Check for OAuth2 linked accounts.
        if ($DB->record_exists('auth_oauth2_linked_login', ['userid' => $user->id])) {
            $linkedaccounts = $DB->get_records('auth_oauth2_linked_login', ['userid' => $user->id]);
            foreach ($linkedaccounts as $account) {
                $issuer = $DB->get_record('oauth2_issuer', ['id' => $account->issuerid]);
                if ($issuer) {
                    $authmethods[] = 'oauth2:' . $issuer->name;
                }
            }
        }

        return [
            'exists' => true,
            'auth' => $user->auth,
            'methods' => $authmethods,
            'username' => $user->username,
        ];
    }

    /**
     * Send password reset email to user.
     *
     * @param object $user User object
     * @return bool Success status
     */
    public function send_password_reset_email($user) {
        global $CFG;
        $SITE = get_site();

        // Generate reset token.
        $token = bin2hex(random_bytes(32));
        $expiry = time() + 3600; // 1 hour expiry.

        set_user_preference('auth_emailfirst_reset_token', $token, $user);
        set_user_preference('auth_emailfirst_reset_token_expiry', $expiry, $user);

        $reseturl = new moodle_url('/auth/emailfirst/reset_password.php', ['token' => $token]);

        $subject = $this->config->passwordresetemailsubject ?? get_string('passwordresetemailsubject', 'auth_emailfirst');
        $messagebody = $this->config->passwordresetemailbody ?? get_string('defaultpasswordresetbody', 'auth_emailfirst');

        $replacements = [
            '{firstname}' => $user->firstname,
            '{lastname}' => $user->lastname,
            '{email}' => $user->email,
            '{reseturl}' => $reseturl->out(false),
            '{sitename}' => format_string($SITE->fullname),
            '{siteurl}' => $CFG->wwwroot,
            '{supportemail}' => $CFG->supportemail ?? '',
        ];
        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $messagebody = str_replace(array_keys($replacements), array_values($replacements), $messagebody);

        $user->email = clean_param($user->email, PARAM_EMAIL);
        return email_to_user(
            $user,
            core_user::get_support_user(),
            $subject,
            html_to_text($messagebody),
            $messagebody
        );
    }

    /**
     * Verify password reset token.
     *
     * @param string $token Reset token
     * @return object|false User object on success, false on failure
     */
    public function verify_password_reset_token($token) {
        global $DB;

        if (empty($token)) {
            return false;
        }

        $sql = "SELECT u.*
                  FROM {user} u
                  JOIN {user_preferences} up ON up.userid = u.id
                 WHERE up.name = :prefname
                   AND up.value = :token
                   AND u.deleted = 0";

        $user = $DB->get_record_sql($sql, [
            'prefname' => 'auth_emailfirst_reset_token',
            'token' => $token,
        ]);

        if (!$user) {
            return false;
        }

        $expiry = get_user_preferences('auth_emailfirst_reset_token_expiry', 0, $user);
        if ($expiry < time()) {
            unset_user_preference('auth_emailfirst_reset_token', $user);
            unset_user_preference('auth_emailfirst_reset_token_expiry', $user);
            return false;
        }

        return $user;
    }

    /**
     * Verify email token (for custom verify.php page).
     *
     * @param string $token Verification token
     * @return object|false User object on success, false on failure
     */
    public function verify_email_token($token) {
        global $DB;

        $sql = "SELECT u.*
                  FROM {user} u
                  JOIN {user_preferences} up ON u.id = up.userid
                 WHERE up.name = 'auth_emailfirst_token'
                   AND up.value = :token
                   AND u.deleted = 0";

        $user = $DB->get_record_sql($sql, ['token' => $token]);

        if (!$user) {
            return false;
        }

        $expiry = get_user_preferences('auth_emailfirst_token_expiry', 0, $user);
        if ($expiry < time()) {
            unset_user_preference('auth_emailfirst_token', $user);
            unset_user_preference('auth_emailfirst_token_expiry', $user);
            return false;
        }

        // Mark user as confirmed.
        $DB->set_field('user', 'confirmed', 1, ['id' => $user->id]);

        // Delete token preferences.
        unset_user_preference('auth_emailfirst_token', $user);
        unset_user_preference('auth_emailfirst_token_expiry', $user);

        // Send welcome email if enabled.
        if (!empty($this->config->sendwelcomeemail)) {
            $this->send_welcome_email($user);
        }

        return $user;
    }
}
