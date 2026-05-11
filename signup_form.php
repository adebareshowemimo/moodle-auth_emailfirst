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
 * Email-first signup form. Hides the username field and uses email as username.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/login/signup_form.php');

/**
 * Signup form that uses email as username, supports multi-step wizard mode,
 * and adds a referral source survey question.
 */
class auth_emailfirst_signup_form extends login_signup_form {
    /**
     * Define the form elements.
     *
     * We build the form from scratch (not calling parent::definition()) to have
     * full control over field order and step grouping.
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;
        $config = get_config('auth_emailfirst');
        $multistep = !empty($config->enablemultistep);

        // Site logo at the top of the form, followed by heading.
        global $OUTPUT;
        $logosource = $config->signuplogo ?? 'core_logo';
        $logourl = false;
        switch ($logosource) {
            case 'core_logo':
                $logourl = $OUTPUT->get_logo_url();
                break;
            case 'core_logocompact':
                $logourl = $OUTPUT->get_compact_logo_url();
                break;
            case 'boostunion_logo':
                $raw = get_config('theme_boost_union', 'logo');
                if (!empty($raw)) {
                    $logourl = \moodle_url::make_pluginfile_url(
                        \context_system::instance()->id,
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
                    $logourl = \moodle_url::make_pluginfile_url(
                        \context_system::instance()->id,
                        'theme_boost_union',
                        'logocompact',
                        theme_get_revision(),
                        '/',
                        $raw
                    );
                }
                break;
            case 'none':
            default:
                $logourl = false;
                break;
        }
        // Configurable heading (falls back to core 'newaccount' if not set or hidden).
        $showheading = !isset($config->showsignupheading) || !empty($config->showsignupheading);
        $heading = '';
        if ($showheading) {
            $heading = !empty($config->signupheading)
                ? $config->signupheading
                : get_string('signupheading_default', 'auth_emailfirst');
        }

        // Configurable subheading.
        $showsubheading = !isset($config->showsignupsubheading) || !empty($config->showsignupsubheading);
        $subheading = '';
        if ($showsubheading) {
            $subheading = !empty($config->signupsubheading)
                ? $config->signupsubheading
                : get_string('signupsubheading_default', 'auth_emailfirst');
        }

        // Build the top banner: logo + heading + subheading.
        $bannerhtml = '<div class="text-center mb-4">';
        if ($logourl) {
            $SITE = get_site();
            $sitename = format_string($SITE->fullname);
            $logoheight = !empty($config->logoheight) ? (int) $config->logoheight : 80;
            $logowidth = !empty($config->logowidth) ? (int) $config->logowidth : 0;
            $logostyle = 'max-height:' . $logoheight . 'px;';
            if ($logowidth > 0) {
                $logostyle .= 'max-width:' . $logowidth . 'px;';
            }
            $bannerhtml .= '<img src="' . s($logourl->out(false)) . '" alt="' . s($sitename) . '" ' .
                'class="img-fluid" style="' . s($logostyle) . '">';
        }
        if ($heading !== '') {
            $bannerhtml .= '<h2 class="mt-3">' . s($heading) . '</h2>';
        }
        if ($subheading !== '') {
            $bannerhtml .= '<p class="text-muted mt-2">' . s($subheading) . '</p>';
        }
        $bannerhtml .= '</div>';
        $mform->addElement('html', $bannerhtml);

        // Hidden username, always present and populated from email.
        $mform->addElement('hidden', 'username', '');
        $mform->setType('username', PARAM_RAW);

        // Step 1: Email.
        if ($multistep) {
            $mform->addElement('header', 'step_email', get_string('step_email', 'auth_emailfirst'));
            $mform->setExpanded('step_email', true);
        }

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="25"');
        $mform->setType('email', core_user::get_property_type('email'));
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'client');
        $mform->setForceLtr('email');

        $mform->addElement('text', 'email2', get_string('emailagain'), 'maxlength="100" size="25"');
        $mform->setType('email2', core_user::get_property_type('email'));
        $mform->addRule('email2', get_string('missingemail'), 'required', null, 'client');
        $mform->setForceLtr('email2');

        // Step 2: Name.
        if ($multistep) {
            $mform->addElement('header', 'step_name', get_string('step_name', 'auth_emailfirst'));
            $mform->setExpanded('step_name', true);
        }

        $namefields = useredit_get_required_name_fields();
        foreach ($namefields as $field) {
            $mform->addElement('text', $field, get_string($field), 'maxlength="100" size="30"');
            $mform->setType($field, core_user::get_property_type('firstname'));
            $stringid = 'missing' . $field;
            if (!get_string_manager()->string_exists($stringid, 'moodle')) {
                $stringid = 'required';
            }
            $mform->addRule($field, get_string($stringid), 'required', null, 'client');
        }

        // Step 3: Location (optional).
        if (empty($config->hidecitycountry)) {
            if ($multistep) {
                $mform->addElement('header', 'step_location', get_string('step_location', 'auth_emailfirst'));
                $mform->setExpanded('step_location', true);
            }

            $mform->addElement('text', 'city', get_string('city'), 'maxlength="120" size="20"');
            $mform->setType('city', core_user::get_property_type('city'));
            if (!empty($CFG->defaultcity)) {
                $mform->setDefault('city', $CFG->defaultcity);
            }

            $country = get_string_manager()->get_list_of_countries();
            $defaultcountry[''] = get_string('selectacountry');
            $country = array_merge($defaultcountry, $country);
            $mform->addElement('select', 'country', get_string('country'), $country);
            if (!empty($CFG->country)) {
                $mform->setDefault('country', $CFG->country);
            } else {
                $mform->setDefault('country', '');
            }
        }

        // Step 4: Password.
        if ($multistep) {
            $mform->addElement('header', 'step_password', get_string('step_password', 'auth_emailfirst'));
            $mform->setExpanded('step_password', true);
        }

        if (!empty($CFG->passwordpolicy)) {
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        $mform->addElement('password', 'password', get_string('password'), [
            'maxlength' => MAX_PASSWORD_CHARACTERS,
            'size' => 12,
            'autocomplete' => 'new-password',
        ]);
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');
        $mform->addRule(
            'password',
            get_string('maximumchars', '', MAX_PASSWORD_CHARACTERS),
            'maxlength',
            MAX_PASSWORD_CHARACTERS,
            'client'
        );

        // Step 5: Final (survey, profile, captcha, policy, submit).
        if ($multistep) {
            $mform->addElement('header', 'step_final', get_string('step_final', 'auth_emailfirst'));
            $mform->setExpanded('step_final', true);
        }

        // Referral survey (if enabled).
        if (!empty($config->showreferralsurvey)) {
            $referraloptions = [
                '' => get_string('referral_select', 'auth_emailfirst'),
                'search_engine' => get_string('referral_search_engine', 'auth_emailfirst'),
                'social_media' => get_string('referral_social_media', 'auth_emailfirst'),
                'friend_family' => get_string('referral_friend_family', 'auth_emailfirst'),
                'colleague' => get_string('referral_colleague', 'auth_emailfirst'),
                'online_ad' => get_string('referral_online_ad', 'auth_emailfirst'),
                'blog_article' => get_string('referral_blog_article', 'auth_emailfirst'),
                'email_newsletter' => get_string('referral_email_newsletter', 'auth_emailfirst'),
                'event_conference' => get_string('referral_event_conference', 'auth_emailfirst'),
                'youtube' => get_string('referral_youtube', 'auth_emailfirst'),
                'podcast' => get_string('referral_podcast', 'auth_emailfirst'),
                'other' => get_string('referral_other', 'auth_emailfirst'),
            ];
            $mform->addElement(
                'select',
                'referral_source',
                get_string('referral_source', 'auth_emailfirst'),
                $referraloptions
            );
            $mform->setType('referral_source', PARAM_TEXT);
            $mform->addRule(
                'referral_source',
                get_string('referral_source_required', 'auth_emailfirst'),
                'required',
                null,
                'client'
            );
        }

        // Custom profile fields.
        profile_signup_fields($mform);

        // ReCAPTCHA (using new security settings).
        if (!empty($config->enablerecaptcha) && !empty($config->recaptchasitekey) && !empty($config->recaptchasecretkey)) {
            $isv3 = !empty($config->recaptchaversion) && $config->recaptchaversion === 'v3';

            if (!$isv3) {
                // 2: Checkbox element (standard Moodle).
                $mform->addElement('recaptcha', 'recaptcha_element', get_string('security_question', 'auth'));
                $mform->addHelpButton('recaptcha_element', 'recaptcha', 'auth');
                $mform->closeHeaderBefore('recaptcha_element');
            } else {
                // 3: Hidden field for token injection via AMD module.
                $mform->addElement('hidden', 'g-recaptcha-response', '');
                $mform->setType('g-recaptcha-response', PARAM_RAW);
                // Token will be populated by recaptcha_v3.js before form submission.
            }
        }

        // Plugin hooks.
        core_login_extend_signup_form($mform);

        // Site policy.
        $manager = new \core_privacy\local\sitepolicy\manager();
        $manager->signup_form($mform);

        // Action buttons.
        $this->set_display_vertical();
        $this->add_action_buttons(true, get_string('createaccount'));

        // Identity providers (OAuth2, OIDC, etc.).
        if (!empty($config->showidentityproviders)) {
            $authsequence = get_enabled_auth_plugins();
            $identityproviders = \auth_plugin_base::get_identity_providers($authsequence);
            if (!empty($identityproviders)) {
                $mform->addElement('html', '<div class="login-divider my-3"><hr></div>');
                $mform->addElement('html', '<div class="login-identityproviders text-center">');
                $mform->addElement('html', '<h6 class="mb-3">' .
                    get_string('aboraliasloginwith', 'auth_emailfirst') . '</h6>');
                foreach ($identityproviders as $idp) {
                    $iconhtml = '';
                    if (!empty($idp['iconurl'])) {
                        $iconurl = ($idp['iconurl'] instanceof moodle_url) ? $idp['iconurl']->out(false) : $idp['iconurl'];
                        $iconhtml = '<img src="' . s($iconurl) . '" alt="" width="24" height="24" class="mr-2"> ';
                    }
                    $url = ($idp['url'] instanceof moodle_url) ? $idp['url']->out(false) : $idp['url'];
                    $mform->addElement(
                        'html',
                        '<a class="btn btn-outline-secondary btn-lg w-100 mb-2" href="' . s($url) . '">' .
                        $iconhtml . s($idp['name']) . '</a>'
                    );
                }
                $mform->addElement('html', '</div>');
            }
        }

        // Login link for existing accounts.
        $loginurl = new moodle_url('/login/index.php');
        $mform->addElement(
            'html',
            '<div class="auth-emailfirst-login-link text-center">' .
            '<span class="text-muted">' . get_string('alreadyhaveaccount', 'auth_emailfirst') . ' </span>' .
            '<a href="' . s($loginurl->out(false)) . '">' . get_string('login') . '</a>' .
            '</div>'
        );

        // Contact info at the bottom of the form.
        $showcontact = !isset($config->showsignupcontact) || !empty($config->showsignupcontact);
        if ($showcontact) {
            $contacttext = !empty($config->signupcontact)
                ? $config->signupcontact
                : get_string('signupcontact_default', 'auth_emailfirst');
            $mform->addElement(
                'html',
                '<div class="auth-emailfirst-contact text-center text-muted mt-4 mb-2">' .
                '<small>' . s($contacttext) . '</small>' .
                '</div>'
            );
        }

        // Initialize multi-step JS.
        if ($multistep) {
            $stepnames = ['step_email', 'step_name'];
            if (empty($config->hidecitycountry)) {
                $stepnames[] = 'step_location';
            }
            $stepnames[] = 'step_password';
            $stepnames[] = 'step_final';

            $PAGE->requires->js_call_amd('auth_emailfirst/signup_steps', 'init', [[
                'steps' => $stepnames,
                'strings' => [
                    'next' => get_string('next'),
                    'back' => get_string('back'),
                    'step_x_of_y' => get_string('step_x_of_y', 'auth_emailfirst'),
                    'invalid_email' => get_string('invalidemail', 'auth_emailfirst'),
                    'emails_not_match' => get_string('emailsnotmatch', 'auth_emailfirst'),
                    'field_required' => get_string('required'),
                    'email_exists' => get_string('emailexists', 'auth_emailfirst'),
                ],
            ]]);
        }
    }

    /**
     * After data is set, populate username from email.
     */
    public function definition_after_data() {
        $mform = $this->_form;

        // Set username to lowercase email.
        if ($mform->elementExists('email')) {
            $email = $mform->getElementValue('email');
            if (is_array($email)) {
                $email = reset($email);
            }
            if (!empty($email)) {
                $mform->getElement('username')->setValue(core_text::strtolower($email));
            }
        }

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }
    }

    /**
     * Validate user supplied data on the signup form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files
     * @return array of "element_name"=>"error_description" if there are errors
     */
    public function validation($data, $files) {
        // Inject username from email before validation so signup_validate_data() works.
        if (!empty($data['email'])) {
            $data['username'] = core_text::strtolower($data['email']);
        }

        // Check allowed domains.
        $config = get_config('auth_emailfirst');
        if (!empty($config->alloweddomains) && !empty($data['email'])) {
            $alloweddomains = array_map('trim', explode(',', $config->alloweddomains));
            $emaildomain = substr(strrchr($data['email'], '@'), 1);
            if (!in_array($emaildomain, $alloweddomains)) {
                $errors = parent::validation($data, $files);
                $errors['email'] = get_string('emailnotallowed', 'auth_emailfirst');
                return $errors;
            }
        }

        return parent::validation($data, $files);
    }
}
