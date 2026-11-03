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
 * Custom signup endpoint for auth_emailfirst.
 *
 * Replaces /login/signup.php with a Mustache-based signup page.
 * Handles both GET (render form) and POST (validate + create user).
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public signup endpoint.
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/login/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

// Ensure signup is enabled and our plugin is the active auth.
if (!$authplugin = signup_is_enabled()) {
    throw new \moodle_exception('notlocalisederrormessage', 'error', '', 'Signup is not enabled.');
}

if ($authplugin->authtype !== 'emailfirst') {
    redirect(new moodle_url('/login/signup.php'));
}

$config = get_config('auth_emailfirst');

$PAGE->set_url(new moodle_url('/auth/emailfirst/signup.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');

$SITE = get_site();
$PAGE->set_title(get_string('newaccount'));
$PAGE->set_heading($SITE->fullname);

// If wantsurl is empty or /login/signup.php, override wanted URL.
if (empty($SESSION->wantsurl)) {
    $SESSION->wantsurl = $CFG->wwwroot . '/';
} else {
    $wantsurl = new moodle_url($SESSION->wantsurl);
    if (
        $wantsurl->compare(new moodle_url('/login/signup.php'), URL_MATCH_BASE) ||
        $wantsurl->compare($PAGE->url, URL_MATCH_BASE)
    ) {
        $SESSION->wantsurl = $CFG->wwwroot . '/';
    }
}

// Already logged in.
if (isloggedin() && !isguestuser()) {
    $OUTPUT = $PAGE->get_renderer('core');
    echo $OUTPUT->header();
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url(
        '/login/logout.php',
        ['sesskey' => sesskey(), 'loginpage' => 1]
    ), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url('/'), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('cannotsignup', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

// Digital minor check.
if (\core_auth\digital_consent::is_age_digital_consent_verification_enabled()) {
    $cache = cache::make('core', 'presignup');
    $isminor = $cache->get('isminor');
    if ($isminor === 'yes') {
        redirect(new moodle_url('/login/digital_minor.php'));
    } else if ($isminor === false || empty($SESSION->auth_emailfirst_registration)) {
        redirect(new moodle_url('/auth/emailfirst/verify_age_location.php'));
    }
}

// Pre signup hooks.
core_login_pre_signup_requests();

// Initialise the renderer early.
$OUTPUT = $PAGE->get_renderer('core');

$errors = [];
$formdata = [
    'email' => '',
    'email2' => '',
    'firstname' => '',
    'lastname' => '',
    'city' => !empty($CFG->defaultcity) ? $CFG->defaultcity : '',
    'country' => !empty($SESSION->auth_emailfirst_registration['country'])
        ? $SESSION->auth_emailfirst_registration['country']
        : (!empty($CFG->country) ? $CFG->country : ''),
    'password' => '',
    'policyagreed' => 0,
    'profile_field_state' => '',
    'profile_field_lga' => '',
    'referral_source' => '',
];

// Add defaults for every other custom profile field enabled on signup.
foreach (get_additional_signup_profile_fields() as $profilefield) {
    $field = $profilefield->object->field;
    $inputname = 'profile_field_' . $field->shortname;
    $formdata[$inputname] = get_signup_profile_field_default($field);
}

// Handle POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    // Check rate limiting first (before collecting any data).
    $ratelimiterror = check_signup_rate_limit($config);
    if ($ratelimiterror) {
        $errors['rate_limit'] = $ratelimiterror;
    } else {
        // Collect form data.
        $formdata['email'] = trim(optional_param('email', '', PARAM_RAW_TRIMMED));
        $formdata['email2'] = trim(optional_param('email2', '', PARAM_RAW_TRIMMED));
        $formdata['firstname'] = trim(optional_param('firstname', '', PARAM_TEXT));
        $formdata['lastname'] = trim(optional_param('lastname', '', PARAM_TEXT));
        $formdata['city'] = trim(optional_param('city', '', PARAM_TEXT));
        $formdata['country'] = optional_param('country', '', PARAM_ALPHA);
        $formdata['password'] = optional_param('password', '', PARAM_RAW);
        $formdata['policyagreed'] = optional_param('policyagreed', 0, PARAM_INT);
        $formdata['profile_field_state'] = trim(optional_param('profile_field_state', '', PARAM_TEXT));
        $formdata['profile_field_lga'] = trim(optional_param('profile_field_lga', '', PARAM_TEXT));
        $formdata['referral_source'] = trim(optional_param('referral_source', '', PARAM_ALPHANUMEXT));
        collect_additional_signup_profile_data($formdata);

        // Validate.
        $errors = validate_signup_data($formdata, $config);
    }

    if (empty($errors)) {
        // Build user object.
        $user = new stdClass();
        $user->email = $formdata['email'];
        $user->username = core_text::strtolower($formdata['email']);
        $user->firstname = $formdata['firstname'];
        $user->lastname = $formdata['lastname'];
        $user->city = $formdata['city'];
        $user->country = $formdata['country'];
        $user->password = $formdata['password'];
        $user->policyagreed = $formdata['policyagreed'];
        if (auth_emailfirst_referral_survey_enabled($config)) {
            $user->referral_source = $formdata['referral_source'];
        }

        foreach ($formdata as $fieldname => $fieldvalue) {
            if (strpos($fieldname, 'profile_field_') === 0) {
                $user->{$fieldname} = $fieldvalue;
            }
        }
        if (!empty($SESSION->auth_emailfirst_registration)) {
            foreach (\auth_emailfirst\local\registration_fields::SHORTNAMES as $shortname) {
                $user->{'profile_field_' . $shortname} = $SESSION->auth_emailfirst_registration[$shortname] ?? '';
            }
        }

        // Add required fields (secret, etc.).
        $user = signup_setup_new_user($user);

        // Post signup hooks.
        core_login_post_signup_requests($user);

        // Create the user (this handles all DB operations).
        unset($SESSION->auth_emailfirst_registration);
        $authplugin->user_signup($user, true);
        exit; // Ser_signup renders a notice and exits.
    }
}

// Build template context.
$context = build_signup_context($formdata, $errors, $config, $OUTPUT, $SITE);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('auth_emailfirst/signupform', $context);
echo $OUTPUT->footer();

// Helper functions.

/**
 * Validate signup form data.
 *
 * @param array $data Form data.
 * @param object $config Plugin config.
 * @return array Errors keyed by field name.
 */
function validate_signup_data(array $data, $config): array {
    global $CFG, $DB;

    $errors = [];

    // Mail validation.
    if (empty($data['email'])) {
        $errors['email'] = get_string('missingemail');
    } else if (!validate_email($data['email'])) {
        $errors['email'] = get_string('invalidemail', 'auth_emailfirst');
    } else if ($data['email'] !== $data['email2']) {
        $errors['email2'] = get_string('emailsnotmatch', 'auth_emailfirst');
    } else if ($DB->record_exists('user', ['email' => $data['email'], 'deleted' => 0])) {
        $errors['email'] = get_string('emailexists', 'auth_emailfirst');
    } else if (!empty($config->alloweddomains)) {
        $alloweddomains = array_map('trim', explode(',', $config->alloweddomains));
        $emaildomain = substr(strrchr($data['email'], '@'), 1);
        if (!in_array($emaildomain, $alloweddomains)) {
            $errors['email'] = get_string('emailnotallowed', 'auth_emailfirst');
        }
    }

    // Also check if email is banned.
    if (empty($errors['email']) && !empty($data['email'])) {
        if (!validate_email($data['email']) || email_is_not_allowed($data['email'])) {
            $errors['email'] = get_string('invalidemail', 'auth_emailfirst');
        }
        // Check username (email) uniqueness.
        $username = core_text::strtolower($data['email']);
        if ($DB->record_exists('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id])) {
            $errors['email'] = get_string('emailexists', 'auth_emailfirst');
        }
    }

    // Name validation.
    if (empty($data['firstname'])) {
        $errors['firstname'] = get_string('missingfirstname');
    }
    if (empty($data['lastname'])) {
        $errors['lastname'] = get_string('missinglastname');
    }

    // Password validation.
    if (empty($data['password'])) {
        $errors['password'] = get_string('missingpassword');
    } else {
        $errmsg = '';
        if (!check_password_policy($data['password'], $errmsg)) {
            $errors['password'] = $errmsg;
        }
    }

    // Nigeria State/LGA custom profile fields.
    $locationfields = get_nigeria_profile_fields();
    if ($locationfields) {
        $state = $data['profile_field_state'];
        $lga = $data['profile_field_lga'];

        if (!empty($locationfields['state']->required) && $state === '') {
            $errors['profile_field_state'] = get_string('required');
        } else if ($state !== '' && !in_array($state, \auth_emailfirst\local\nigeria_locations::states(), true)) {
            $errors['profile_field_state'] = get_string('invalidnigeriastate', 'auth_emailfirst');
        }

        if (!empty($locationfields['lga']->required) && $lga === '') {
            $errors['profile_field_lga'] = get_string('required');
        } else if ($lga !== '' && !\auth_emailfirst\local\nigeria_locations::is_valid($state, $lga)) {
            $errors['profile_field_lga'] = get_string('invalidnigerialga', 'auth_emailfirst');
        }
    }

    // Validate all other custom profile fields configured for signup.
    $errors += validate_additional_signup_profile_data($data);

    if (auth_emailfirst_referral_survey_enabled($config)) {
        $choices = \auth_emailfirst\local\referral_options::choices();
        if ($data['referral_source'] === '') {
            $errors['referral_source'] = get_string('referral_source_required', 'auth_emailfirst');
        } else if (!array_key_exists($data['referral_source'], $choices)) {
            $errors['referral_source'] = get_string('referral_source_invalid', 'auth_emailfirst');
        }
    }

    // Site policy agreement.
    $manager = new \core_privacy\local\sitepolicy\manager();
    if ($manager->is_defined()) {
        if (empty($data['policyagreed'])) {
            $errors['policyagreed'] = get_string('policyagree');
        }
    }

    // ReCAPTCHA validation (using new security settings).
    if (!empty($config->enablerecaptcha) && !empty($config->recaptchasitekey) && !empty($config->recaptchasecretkey)) {
        require_once($CFG->libdir . '/recaptchalib_v2.php');
        $response = optional_param('g-recaptcha-response', '', PARAM_RAW);
        if (empty($response)) {
            $errors['recaptcha'] = get_string('missingrecaptchachallengefield');
            log_security_event('recaptcha_missing', getremoteaddr());
        } else {
            $result = recaptcha_check_response(
                RECAPTCHA_VERIFY_URL,
                $config->recaptchasecretkey,
                getremoteaddr(),
                $response
            );
            if (!$result['isvalid']) {
                $errors['recaptcha'] = get_string('recaptcha_validation_failed', 'auth_emailfirst');
                log_security_event('recaptcha_failed', getremoteaddr(), [
                    'error' => isset($result['error']) ? $result['error'] : 'unknown',
                ]);
            } else {
                // Or v3, also check the score (0.0 = bot, 1.0 = human).
                $isv3 = !empty($config->recaptchaversion) && $config->recaptchaversion === 'v3';
                if ($isv3 && isset($result['score'])) {
                    $minscore = 0.5; // Configurable threshold, default 0.5.
                    if ($result['score'] < $minscore) {
                        $errors['recaptcha'] = get_string('recaptcha_score_too_low', 'auth_emailfirst');
                        log_security_event('recaptcha_score_low', getremoteaddr(), [
                            'score' => $result['score'],
                            'threshold' => $minscore,
                        ]);
                    }
                }
            }
        }
    }

    return $errors;
}

/**
 * Build the template context for the signup form.
 *
 * @param array $formdata Current form field values.
 * @param array $errors Validation errors.
 * @param object $config Plugin configuration.
 * @param \renderer_base $output The renderer.
 * @param object $site The site object.
 * @return stdClass Template context.
 */
function build_signup_context(array $formdata, array $errors, $config, $output, $site): stdClass {
    global $CFG, $PAGE;

    $ctx = new stdClass();

    // Logo.
    $authplugin = get_auth_plugin('emailfirst');
    $method = new ReflectionMethod($authplugin, 'resolve_logo_url');
    $method->setAccessible(true);
    $logourl = $method->invoke($authplugin, $output);
    $ctx->logourl = $logourl ? $logourl->out(false) : false;
    $ctx->externalbrandlogo = ($PAGE->theme->name ?? '') === 'boost_union';

    // Logo dimensions.
    $logoheight = !empty($config->logoheight) ? (int) $config->logoheight : 80;
    $logowidth = !empty($config->logowidth) ? (int) $config->logowidth : 0;
    $logostyle = 'max-height:' . $logoheight . 'px;';
    if ($logowidth > 0) {
        $logostyle .= 'max-width:' . $logowidth . 'px;';
    }
    $ctx->logostyle = $logostyle;

    $ctx->sitename = format_string(
        $site->fullname,
        true,
        ['context' => context_course::instance(SITEID), 'escape' => false]
    );

    // Headings.
    $showheading = !isset($config->showsignupheading) || !empty($config->showsignupheading);
    $ctx->signupheading = '';
    if ($showheading) {
        $ctx->signupheading = !empty($config->signupheading)
            ? format_string($config->signupheading)
            : get_string('signupheading_default', 'auth_emailfirst');
    }

    $showsub = !isset($config->showsignupsubheading) || !empty($config->showsignupsubheading);
    $ctx->signupsubheading = '';
    if ($showsub) {
        $ctx->signupsubheading = !empty($config->signupsubheading)
            ? format_string($config->signupsubheading)
            : get_string('signupsubheading_default', 'auth_emailfirst');
    }

    // URLs.
    $ctx->signupurl = (new moodle_url('/auth/emailfirst/signup.php'))->out(false);
    $ctx->loginurl = (new moodle_url('/login/index.php'))->out(false);
    $ctx->sesskey = sesskey();

    // Form values.
    $ctx->email = s($formdata['email']);
    $ctx->email2 = s($formdata['email2']);
    $ctx->firstname = s($formdata['firstname']);
    $ctx->lastname = s($formdata['lastname']);
    $ctx->city = s($formdata['city']);
    // Errors.
    $errorobj = new stdClass();
    foreach ($errors as $field => $msg) {
        $errorobj->$field = $msg;
    }
    $ctx->errors = $errorobj;
    $ctx->haserrors = !empty($errors);

    // City / Country.
    $ctx->showcitycountry = empty($config->hidecitycountry);

    // Password visibility toggle.
    $ctx->showpasswordtoggle = !empty($config->showpasswordtoggle);
    if ($ctx->showcitycountry) {
        $countries = get_string_manager()->get_list_of_countries();
        $countrylist = [];
        $countrylist[] = ['value' => '', 'label' => get_string('selectacountry'), 'selected' => empty($formdata['country'])];
        foreach ($countries as $code => $name) {
            $countrylist[] = [
                'value' => $code,
                'label' => $name,
                'selected' => ($formdata['country'] === $code),
            ];
        }
        $ctx->countries = $countrylist;
    }

    // Password policy.
    $ctx->passwordpolicy = !empty($CFG->passwordpolicy) ? print_password_policy() : '';

    // Dependent Nigeria State/LGA custom profile fields.
    $ctx->shownigerialocations = false;
    $locationfields = get_nigeria_profile_fields();
    if ($locationfields) {
        $ctx->shownigerialocations = true;
        $ctx->statelabel = format_string($locationfields['state']->name);
        $ctx->lgalabel = format_string($locationfields['lga']->name);
        $ctx->staterequired = !empty($locationfields['state']->required);
        $ctx->lgarequired = !empty($locationfields['lga']->required);
        $ctx->stateoptions = [[
            'value' => '',
            'label' => get_string('selectnigeriastate', 'auth_emailfirst'),
            'selected' => $formdata['profile_field_state'] === '',
        ]];
        foreach (\auth_emailfirst\local\nigeria_locations::states() as $state) {
            $ctx->stateoptions[] = [
                'value' => $state,
                'label' => $state,
                'selected' => $formdata['profile_field_state'] === $state,
            ];
        }

        $ctx->lgaoptions = [[
            'value' => '',
            'label' => $formdata['profile_field_state'] === ''
                ? get_string('selectnigeriastatefirst', 'auth_emailfirst')
                : get_string('selectnigerialga', 'auth_emailfirst'),
            'selected' => $formdata['profile_field_lga'] === '',
        ]];
        foreach (\auth_emailfirst\local\nigeria_locations::lgas_for_state($formdata['profile_field_state']) as $lga) {
            $ctx->lgaoptions[] = [
                'value' => $lga,
                'label' => $lga,
                'selected' => $formdata['profile_field_lga'] === $lga,
            ];
        }

        $PAGE->requires->js_call_amd('auth_emailfirst/nigeria_location_fields', 'init', [[
            'locations' => \auth_emailfirst\local\nigeria_locations::all(),
            'selectState' => get_string('selectnigeriastate', 'auth_emailfirst'),
            'selectLga' => get_string('selectnigerialga', 'auth_emailfirst'),
            'selectStateFirst' => get_string('selectnigeriastatefirst', 'auth_emailfirst'),
        ]]);
    }

    // ReCAPTCHA (using new security settings).
    $ctx->showrecaptcha = false;
    $ctx->recaptchav3 = false;
    if (!empty($config->enablerecaptcha) && !empty($config->recaptchasitekey) && !empty($config->recaptchasecretkey)) {
        require_once($CFG->libdir . '/recaptchalib_v2.php');
        $ctx->showrecaptcha = true;

        // Detect reCAPTCHA version (v2 checkbox vs v3 invisible).
        $isv3 = !empty($config->recaptchaversion) && $config->recaptchaversion === 'v3';

        if ($isv3) {
            // 3: Invisible scoring - just load the script and handle token injection via AMD.
            $ctx->recaptchav3 = true;
            // We'll load the AMD module below after PAGE setup.
        } else {
            // 2: Checkbox - use Moodle's built-in challenge HTML.
            $ctx->recaptchahtml = recaptcha_get_challenge_html(RECAPTCHA_API_URL, $config->recaptchasitekey);
        }
    }

    // Load reCAPTCHA v3 AMD module if needed.
    if (!empty($ctx->recaptchav3)) {
        $PAGE->requires->js_call_amd(
            'auth_emailfirst/recaptcha_v3',
            'init',
            [$config->recaptchasitekey, 'signup', 0.5]
        );
    }

    // Site policy.
    $ctx->hassitepolicy = false;
    $manager = new \core_privacy\local\sitepolicy\manager();
    if ($manager->is_defined()) {
        $ctx->hassitepolicy = true;
        $embedurl = $manager->get_embed_url();
        $ctx->sitepolicyurl = $embedurl ? $embedurl->out(false) : '';
        $ctx->sitepolicylinktext = get_string('policyagreementclick');
        $ctx->sitepolicyacceptlabel = get_string('policyaccept');
    }

    // Identity providers.
    $ctx->hasidentityproviders = false;
    $ctx->identityproviders = [];
    if (!empty($config->showidentityproviders)) {
        $authsequence = get_enabled_auth_plugins();
        $idps = \auth_plugin_base::get_identity_providers($authsequence);
        if (!empty($idps)) {
            $ctx->hasidentityproviders = true;
            foreach ($idps as $idp) {
                $iconurl = '';
                if (!empty($idp['iconurl'])) {
                    $iconurl = ($idp['iconurl'] instanceof moodle_url) ? $idp['iconurl']->out(false) : $idp['iconurl'];
                }
                $url = ($idp['url'] instanceof moodle_url) ? $idp['url']->out(false) : $idp['url'];
                $ctx->identityproviders[] = [
                    'url' => $url,
                    'iconurl' => $iconurl,
                    'name' => $idp['name'],
                ];
            }
        }
    }

    // Contact info.
    $showcontact = !isset($config->showsignupcontact) || !empty($config->showsignupcontact);
    $ctx->showcontact = $showcontact;
    if ($showcontact) {
        $ctx->contacttext = !empty($config->signupcontact)
            ? s($config->signupcontact)
            : s(get_string('signupcontact_default', 'auth_emailfirst'));
    }

    // Already have account text.
    $ctx->alreadyhaveaccount = get_string('alreadyhaveaccount', 'auth_emailfirst');

    // Multi-step wizard.
    $ctx->multistep = !empty($config->enablemultistep);
    if ($ctx->multistep) {
        $stepnames = ['step_email', 'step_name'];
        if (empty($config->hidecitycountry)) {
            $stepnames[] = 'step_location';
        }
        $stepnames[] = 'step_password';
        $stepnames[] = 'step_final';

        $ctx->stepsjson = json_encode($stepnames);
        $ctx->stringsjson = json_encode([
            'next' => get_string('next'),
            'back' => get_string('back'),
            'step_x_of_y' => get_string('step_x_of_y', 'auth_emailfirst'),
            'invalid_email' => get_string('invalidemail', 'auth_emailfirst'),
            'emails_not_match' => get_string('emailsnotmatch', 'auth_emailfirst'),
            'field_required' => get_string('required'),
            'email_exists' => get_string('emailexists', 'auth_emailfirst'),
        ]);
    }

    // Render all remaining signup-enabled custom profile fields as structured context.
    $ctx->showreferralsurvey = auth_emailfirst_referral_survey_enabled($config);
    $ctx->referraloptions = [[
        'value' => '',
        'label' => get_string('referral_select', 'auth_emailfirst'),
        'selected' => $formdata['referral_source'] === '',
    ]];
    if ($ctx->showreferralsurvey) {
        foreach (\auth_emailfirst\local\referral_options::choices() as $key => $label) {
            $ctx->referraloptions[] = [
                'value' => $key,
                'label' => $label,
                'selected' => $formdata['referral_source'] === $key,
            ];
        }
    }
    $ctx->profilefieldcategories = build_additional_profile_fields_context($formdata, $errors);
    $ctx->pluginfields = '';

    return $ctx;
}

/**
 * Whether to ask the signup referral question.
 *
 * @param object $config Plugin configuration.
 * @return bool
 */
function auth_emailfirst_referral_survey_enabled($config): bool {
    $configured = !isset($config->showreferralsurvey) || !empty($config->showreferralsurvey);
    return $configured && !empty(\auth_emailfirst\local\referral_options::choices());
}

/**
 * Return the enabled State and LGA signup profile fields.
 *
 * Both fields must exist, use the short names "state" and "lga", and be
 * configured to display on signup before the dependent controls are shown.
 *
 * @return array<string, stdClass>|null
 */
function get_nigeria_profile_fields(): ?array {
    global $DB;

    $records = $DB->get_records_list('user_info_field', 'shortname', ['state', 'lga']);
    $fields = [];
    foreach ($records as $record) {
        $fields[$record->shortname] = $record;
    }

    if (
        empty($fields['state']) || empty($fields['lga']) ||
            empty($fields['state']->signup) || empty($fields['lga']->signup)
    ) {
        return null;
    }

    return $fields;
}

/**
 * Return all signup profile fields except the State/LGA pair rendered separately.
 *
 * @return array
 */
function get_additional_signup_profile_fields(): array {
    global $SESSION;

    $hasregistration = !empty($SESSION->auth_emailfirst_registration);
    return array_values(array_filter(profile_get_signup_fields(), static function ($profilefield) use ($hasregistration): bool {
        $shortname = $profilefield->object->field->shortname;
        if (in_array($shortname, ['state', 'lga'], true)) {
            return false;
        }
        return !$hasregistration
            || !in_array($shortname, \auth_emailfirst\local\registration_fields::SHORTNAMES, true);
    }));
}

/**
 * Return a suitable initial value for a custom profile field.
 *
 * @param stdClass $field Profile field record.
 * @return mixed
 */
function get_signup_profile_field_default(stdClass $field) {
    if ($field->datatype === 'checkbox') {
        return !empty($field->defaultdata) ? 1 : 0;
    }
    if ($field->datatype === 'datetime') {
        return is_numeric($field->defaultdata) && (int)$field->defaultdata > 0
            ? userdate((int)$field->defaultdata, '%Y-%m-%d', 99, false)
            : '';
    }
    return isset($field->defaultdata) ? (string)$field->defaultdata : '';
}

/**
 * Collect custom-profile values submitted to the Mustache signup form.
 *
 * @param array $formdata Form data to update.
 */
function collect_additional_signup_profile_data(array &$formdata): void {
    foreach (get_additional_signup_profile_fields() as $profilefield) {
        $field = $profilefield->object->field;
        $inputname = 'profile_field_' . $field->shortname;
        if ($field->datatype === 'checkbox') {
            $formdata[$inputname] = optional_param($inputname, 0, PARAM_BOOL);
        } else if ($field->datatype === 'textarea') {
            $formdata[$inputname] = optional_param($inputname, '', PARAM_RAW_TRIMMED);
        } else {
            $formdata[$inputname] = optional_param($inputname, '', PARAM_TEXT);
        }
    }
}

/**
 * Validate custom profile fields rendered by the Mustache signup form.
 *
 * @param array $formdata Submitted form data.
 * @return array
 */
function validate_additional_signup_profile_data(array $formdata): array {
    $errors = [];
    $usernew = (object)['id' => 0];

    foreach (get_additional_signup_profile_fields() as $profilefield) {
        $field = $profilefield->object->field;
        $inputname = 'profile_field_' . $field->shortname;
        $value = $formdata[$inputname] ?? '';
        $usernew->{$inputname} = $value;

        $isempty = $field->datatype === 'checkbox' ? empty($value) : trim((string)$value) === '';
        if (!empty($field->required) && $isempty) {
            $errors[$inputname] = get_string('required');
            continue;
        }

        if ($field->datatype === 'menu' && !$isempty) {
            $options = array_map('trim', preg_split('/\R/', (string)$field->param1));
            if (!in_array($value, $options, true)) {
                $errors[$inputname] = get_string('invaliddata', 'error');
                continue;
            }
        }

        if ($field->datatype === 'datetime' && !$isempty) {
            $dateparts = date_parse_from_format('Y-m-d', (string)$value);
            $validdate = $dateparts['error_count'] === 0 && $dateparts['warning_count'] === 0
                && checkdate($dateparts['month'], $dateparts['day'], $dateparts['year']);
            if (!$validdate || $dateparts['year'] < (int)$field->param1 || $dateparts['year'] > (int)$field->param2) {
                $errors[$inputname] = get_string('invaliddata', 'error');
                continue;
            }
        }

        $errors += $profilefield->object->edit_validate_field($usernew);
    }

    return $errors;
}

/**
 * Build Mustache context for all non-location signup profile fields.
 *
 * @param array $formdata Current form values.
 * @param array $errors Current validation errors.
 * @return array
 */
function build_additional_profile_fields_context(array $formdata, array $errors): array {
    $categories = [];

    foreach (get_additional_signup_profile_fields() as $profilefield) {
        $field = $profilefield->object->field;
        $categoryid = (int)$profilefield->categoryid;
        $inputname = 'profile_field_' . $field->shortname;
        $value = $formdata[$inputname] ?? '';

        if (!isset($categories[$categoryid])) {
            $categories[$categoryid] = [
                'name' => format_string($profilefield->object->get_category_name()),
                'fields' => [],
            ];
        }

        $fieldcontext = [
            'name' => $inputname,
            'id' => $inputname,
            'label' => format_string($field->name),
            'required' => !empty($field->required),
            'value' => $value,
            'error' => $errors[$inputname] ?? '',
            'haserror' => !empty($errors[$inputname]),
            'istext' => in_array($field->datatype, ['text', 'social'], true),
            'istextarea' => $field->datatype === 'textarea',
            'ismenu' => $field->datatype === 'menu',
            'ischeckbox' => $field->datatype === 'checkbox',
            'isdatetime' => $field->datatype === 'datetime',
            'checked' => $field->datatype === 'checkbox' && !empty($value),
            'maxlength' => $field->datatype === 'text' ? max(1, (int)$field->param2) : 255,
            'inputtype' => $field->datatype === 'text' && !empty($field->param3) ? 'password' : 'text',
            'minyear' => $field->datatype === 'datetime' ? (int)$field->param1 : 0,
            'maxyear' => $field->datatype === 'datetime' ? (int)$field->param2 : 0,
        ];

        if ($field->datatype === 'menu') {
            $fieldcontext['options'] = [[
                'value' => '',
                'label' => get_string('choose') . '...',
                'selected' => $value === '',
            ]];
            foreach (preg_split('/\R/', (string)$field->param1) as $option) {
                $option = trim($option);
                if ($option === '') {
                    continue;
                }
                $fieldcontext['options'][] = [
                    'value' => $option,
                    'label' => format_string($option),
                    'selected' => $value === $option,
                ];
            }
        }

        // Unknown third-party field types fall back to a safe text control.
        if (
            !$fieldcontext['istext'] && !$fieldcontext['istextarea'] && !$fieldcontext['ismenu']
                && !$fieldcontext['ischeckbox'] && !$fieldcontext['isdatetime']
        ) {
            $fieldcontext['istext'] = true;
        }

        $categories[$categoryid]['fields'][] = $fieldcontext;
    }

    return array_values($categories);
}

/**
 * Check signup rate limit for the current IP.
 *
 * Prevents brute-force signup attacks by limiting signup attempts per IP.
 *
 * @param object $config Plugin configuration.
 * @return string|false Error message if rate limit exceeded, false if allowed.
 */
function check_signup_rate_limit($config) {
    global $DB;

    $limit = (int) (!empty($config->signupratelimit) ? $config->signupratelimit : 50);
    if ($limit <= 0) {
        return false; // Rate limiting disabled.
    }

    $ip = getremoteaddr();
    $onehourago = time() - 3600; // 1 hour window.

    try {
        // Count signup attempts from this IP in the last hour.
        $count = $DB->count_records_select(
            'auth_emailfirst_ratelimit',
            'ip = ? AND timeattempt > ?',
            [$ip, $onehourago]
        );

        if ($count >= $limit) {
            // Log the blocked attempt.
            log_security_event('rate_limit_exceeded', $ip, [
                'limit' => $limit,
                'attempts' => $count,
                'window' => 3600,
            ]);
            return get_string('rate_limit_exceeded', 'auth_emailfirst');
        }

        // Record this signup attempt.
        $DB->insert_record('auth_emailfirst_ratelimit', (object)[
            'ip' => $ip,
            'timeattempt' => time(),
        ]);

        return false; // Rate limit check passed.
    } catch (Throwable $e) {
        // Table doesn't exist yet or other DB error - allow signup to proceed.
        // This can happen during fresh installation before upgrade completes.
        debugging('Rate limit check failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Log a security event for audit and analytics.
 *
 * @param string $event Event type (e.g., 'recaptcha_failed', 'rate_limit_exceeded').
 * @param string $ip IP address of the event source.
 * @param array $metadata Additional event data (optional).
 */
function log_security_event($event, $ip, $metadata = []) {
    global $DB;

    try {
        $DB->insert_record('auth_emailfirst_security_log', (object)[
            'event' => substr($event, 0, 50),
            'ip' => $ip,
            'timeattempt' => time(),
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
        ]);
    } catch (Throwable $e) {
        // Log table may not exist yet - silently fail.
        debugging('Security event logging failed: ' . $e->getMessage());
    }
}
