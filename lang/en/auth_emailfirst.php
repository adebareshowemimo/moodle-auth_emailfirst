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
 * Strings for component 'auth_emailfirst'
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['aboraliasloginwith'] = 'Or sign up with';
$string['accountcreated'] = 'Account created successfully!';
$string['accountcreated_desc'] = 'Your account has been created and is ready to use.';
$string['agecorrect'] = 'Please confirm that your age and location are correct.';
$string['alloweddomains'] = 'Allowed email domains';
$string['alloweddomains_desc'] = 'Comma-separated list of allowed email domains (e.g., company.com,example.org). Leave empty to allow all domains.';
$string['alreadyhaveaccount'] = 'Already have an account?';
$string['alreadyverified'] = 'Your email has already been verified.';
$string['auth_emailfirstdescription'] = 'Email-first authentication where the email address is used as the username. Users sign up via the standard Moodle signup form with the username field hidden.';
$string['auth_emailfirstrecaptcha'] = 'Enable reCAPTCHA on signup';
$string['auth_emailfirstrecaptcha_desc'] = 'Adds a visual/audio confirmation form element to the signup page. You must also configure reCAPTCHA keys in Site administration > Plugins > Authentication > Manage authentication.';
$string['autoenrollcourse'] = 'Auto-enrol course';
$string['autoenrollcourse_desc'] = 'Select a course to automatically enrol users in after they verify their email address. Select none to disable automatic course enrolment.';
$string['autoenrollexistingusers'] = 'Auto-enrol existing users on login';
$string['autoenrollexistingusers_desc'] = 'If enabled, existing email-first users are enrolled in the selected course the next time they log in.';
$string['available_placeholders'] = 'Available Placeholders';
$string['backtologin'] = 'Back to Login';
$string['confirmpassword'] = 'Confirm Password';
$string['continuetodashboard'] = 'Continue to Dashboard';
$string['continuewith'] = 'Continue with {$a}';
$string['defaultpasswordresetbody'] = 'Hi {firstname},

We received a request to reset your password for {sitename}.

Click the link below to reset your password:

{reseturl}

This link will expire in 1 hour.

If you did not request this password reset, please ignore this email and your password will remain unchanged.

Best regards,
The {sitename} Team';
$string['defaultverificationbody'] = 'Hi {firstname},

Thank you for signing up for {sitename}!

Please verify your email address by clicking the link below:

{verifyurl}

This link will expire in 24 hours.

If you did not create this account, please ignore this email.

Best regards,
The {sitename} Team';
$string['defaultwelcomebody'] = 'Hi {firstname},

Welcome to {sitename}!

Your account has been successfully created and verified. You can now log in and start exploring our courses.

Login here: {loginurl}

Best regards,
The {sitename} Team';
$string['didnt_receive_email'] = 'Didn\'t receive the email?';
$string['emailexists'] = 'An account with this email already exists';
$string['emailexists_desc'] = 'This email is already registered. Please sign in using:';
$string['emailnotallowed'] = 'Email domain not allowed. Please use an email from an approved domain.';
$string['emailsnotmatch'] = 'Email addresses do not match';
$string['enablecustomlogin'] = 'Enable custom login page';
$string['enablecustomlogin_desc'] = 'If enabled, the login page is replaced with a branded version that uses the same logo configured above and shows identity provider buttons prominently.';
$string['enablelogincaptcha'] = 'Enable reCAPTCHA on login';
$string['enablelogincaptcha_desc'] = 'If enabled, the custom login form will require reCAPTCHA validation.';
$string['enablemultistep'] = 'Enable multi-step signup wizard';
$string['enablemultistep_desc'] = 'If enabled, the signup form is presented as a guided multi-step wizard with progress bar and client-side AJAX email check.';
$string['enablerecaptcha'] = 'Enable reCAPTCHA';
$string['enablerecaptcha_desc'] = 'If enabled, signup and configured login forms use reCAPTCHA bot protection.';
$string['errorresettingpassword'] = 'There was an error resetting your password. Please try again.';
$string['errorsendingemail'] = 'There was an error sending the email. Please try again later.';
$string['forgotpassword'] = 'Forgot Password';
$string['forgotpassword_desc'] = 'Enter your email address and we\'ll send you a link to reset your password.';
$string['forgotpassword_email_help'] = 'Enter the email address associated with your account.';
$string['global_placeholders'] = 'Global Placeholders';
$string['gotologin'] = 'Go to Login';
$string['hidecitycountry'] = 'Hide city and country on signup';
$string['hidecitycountry_desc'] = 'If enabled, the city and country fields will be hidden from the signup form.';
$string['invalidemail'] = 'Invalid email address format';
$string['invalidresettoken'] = 'Invalid or Expired Reset Link';
$string['invalidresettoken_desc'] = 'The password reset link has expired or is invalid. Reset links are valid for 1 hour.';
$string['login_placeholders'] = 'Login Placeholders';
$string['loginheading'] = 'Login heading';
$string['loginheading_default'] = 'Starter U Login';
$string['loginheading_desc'] = 'The main heading shown on the login page.';
$string['loginlocal'] = 'Or sign in with your email and password';
$string['loginsubheading'] = 'Login subheading';
$string['loginsubheading_default'] = 'Welcome back! Sign in to continue your learning journey.';
$string['loginsubheading_desc'] = 'A welcome or introductory message shown below the login heading.';
$string['logintext_settings'] = 'Login Page Text';
$string['loginurl_desc'] = 'Site login URL';
$string['logoheight'] = 'Logo height (px)';
$string['logoheight_desc'] = 'Maximum height of the logo in pixels on both login and signup pages. Default is 80px.';
$string['logowidth'] = 'Logo width (px)';
$string['logowidth_desc'] = 'Maximum width of the logo in pixels on both login and signup pages. Leave empty for auto width.';
$string['mfa_enrollment_content'] = 'Your account requires multi-factor authentication. Set up an available factor now to continue.';
$string['mfa_enrollment_heading'] = 'Set up multi-factor authentication';
$string['mfa_role_admin'] = 'Site administrators';
$string['mfa_role_manager'] = 'Managers';
$string['mfa_role_teacher'] = 'Teachers';
$string['mfa_settings'] = 'Multi-factor authentication';
$string['mfa_settings_desc'] = 'Configure which users are prompted to set up Moodle multi-factor authentication after login.';
$string['mfagraceperiod'] = 'MFA grace period';
$string['mfagraceperiod_desc'] = 'Number of days users may continue without completing MFA setup after first being prompted. Use 0 to require setup immediately.';
$string['navlogin_bgcolor'] = 'Background colour';
$string['navlogin_bgcolor_desc'] = 'CSS colour value for the button background (e.g. #0d6efd). Leave empty for default.';
$string['navlogin_borderradius'] = 'Border radius';
$string['navlogin_borderradius_desc'] = 'CSS border-radius value (e.g. 4px, 20px). Leave empty for default.';
$string['navlogin_padding'] = 'Padding';
$string['navlogin_padding_desc'] = 'CSS padding value (e.g. 6px 16px). Leave empty for default.';
$string['navlogin_text'] = 'Button text';
$string['navlogin_text_desc'] = 'Custom text for the login button. Leave empty to use the default.';
$string['navlogin_textcolor'] = 'Text colour';
$string['navlogin_textcolor_desc'] = 'CSS colour value for the button text (e.g. #ffffff, white). Leave empty for default.';
$string['navloginbtn_settings'] = 'Navbar Login Button';
$string['navsignup'] = 'Sign Up';
$string['navsignup_bgcolor'] = 'Background colour';
$string['navsignup_bgcolor_desc'] = 'CSS colour value for the button background (e.g. #0d6efd). Leave empty for default.';
$string['navsignup_borderradius'] = 'Border radius';
$string['navsignup_borderradius_desc'] = 'CSS border-radius value (e.g. 4px, 20px). Leave empty for default.';
$string['navsignup_padding'] = 'Padding';
$string['navsignup_padding_desc'] = 'CSS padding value (e.g. 6px 16px). Leave empty for default.';
$string['navsignup_text'] = 'Button text';
$string['navsignup_text_desc'] = 'Custom text for the sign-up button. Leave empty to use the default.';
$string['navsignup_textcolor'] = 'Text colour';
$string['navsignup_textcolor_desc'] = 'CSS colour value for the button text (e.g. #ffffff, white). Leave empty for default.';
$string['navsignupbtn_settings'] = 'Navbar Sign Up Button';
$string['newpassword'] = 'New Password';
$string['passwordreset_email_settings'] = 'Password Reset Email Settings';
$string['passwordresetemailbody'] = 'Password reset email body';
$string['passwordresetemailbody_desc'] = 'Body of password reset email. Placeholders: {firstname}, {lastname}, {reseturl}, {sitename}, {siteurl}, {supportemail}';
$string['passwordresetemailsent'] = 'Password Reset Email Sent!';
$string['passwordresetemailsent_check'] = 'Please check your inbox and follow the instructions to reset your password.';
$string['passwordresetemailsent_desc'] = 'If an account exists with this email, you will receive password reset instructions.';
$string['passwordresetemailsubject'] = 'Reset your password for {sitename}';
$string['passwordresetemailsubject_desc'] = 'Subject line for password reset emails. Placeholders: {sitename}, {firstname}';
$string['passwordresetsuccess'] = 'Password Reset Successful!';
$string['passwordresetsuccess_desc'] = 'Your password has been reset successfully. You are now logged in.';
$string['passwordsnotmatching'] = 'Passwords do not match.';
$string['pluginname'] = 'Email-First Authentication';
$string['pluginnotenabled'] = 'The Email-First authentication plugin is not enabled.';
$string['postresetredirect_settings'] = 'Post-password-reset redirect';
$string['postresetredirecturl'] = 'Redirect URL after password reset';
$string['postresetredirecturl_desc'] = 'Optional URL to send users to after a successful password reset. Leave empty to use the Moodle default.';
$string['privacy:metadata:auth_emailfirst_survey'] = 'Stores the referral source survey response from user registration.';
$string['privacy:metadata:auth_emailfirst_survey:referral_source'] = 'How the user heard about the site.';
$string['privacy:metadata:auth_emailfirst_survey:timecreated'] = 'The time the referral source was recorded.';
$string['privacy:metadata:auth_emailfirst_survey:userid'] = 'The ID of the user who provided the referral source.';
$string['privacy:metadata:preference:auth_emailfirst_reset_token'] = 'Temporary password reset token.';
$string['privacy:metadata:preference:auth_emailfirst_reset_token_expiry'] = 'Expiry time for password reset token.';
$string['privacy:metadata:preference:auth_emailfirst_token'] = 'Temporary email verification token.';
$string['privacy:metadata:preference:auth_emailfirst_token_expiry'] = 'Expiry time for verification token.';
$string['promptmfaatnext'] = 'Prompt MFA at next login';
$string['promptmfaatnext_desc'] = 'If enabled, eligible users are prompted to enrol in MFA after their next successful login.';
$string['rate_limit_exceeded'] = 'Too many signup attempts have been made from this location. Please wait before trying again.';
$string['recaptcha_score_too_low'] = 'Bot verification score was too low. Please try again.';
$string['recaptcha_v2_checkbox'] = 'reCAPTCHA v2 checkbox';
$string['recaptcha_v3'] = 'reCAPTCHA v3 invisible';
$string['recaptcha_validation_failed'] = 'Bot verification failed. Please try again.';
$string['recaptchasecretkey'] = 'reCAPTCHA secret key';
$string['recaptchasecretkey_desc'] = 'The secret key from your Google reCAPTCHA configuration.';
$string['recaptchasitekey'] = 'reCAPTCHA site key';
$string['recaptchasitekey_desc'] = 'The site key from your Google reCAPTCHA configuration.';
$string['recaptchaversion'] = 'reCAPTCHA version';
$string['recaptchaversion_desc'] = 'Choose whether to use the visible v2 checkbox or invisible v3 scoring.';
$string['redirecttocourse'] = 'Redirect to auto-enrol course';
$string['redirecttocourse_desc'] = 'If enabled, users are sent to the selected auto-enrol course after email verification.';
$string['referral_blog_article'] = 'Blog article';
$string['referral_colleague'] = 'Colleague';
$string['referral_email_newsletter'] = 'Email newsletter';
$string['referral_event_conference'] = 'Event or conference';
$string['referral_friend_family'] = 'Friend or family';
$string['referral_online_ad'] = 'Online advertisement';
$string['referral_other'] = 'Other';
$string['referral_podcast'] = 'Podcast';
$string['referral_report'] = 'Referral Source Report';
$string['referral_report_desc'] = 'View how users heard about this site.';
$string['referral_search_engine'] = 'Search engine';
$string['referral_select'] = 'Select an option';
$string['referral_social_media'] = 'Social media';
$string['referral_source'] = 'How did you hear about us?';
$string['referral_source_desc'] = 'Please be specific (e.g., word of mouth, recommendation from friend).';
$string['referral_source_required'] = 'Please tell us how you heard about us.';
$string['referral_youtube'] = 'YouTube';
$string['report_apply'] = 'Apply filters';
$string['report_date'] = 'Registration Date';
$string['report_datefrom'] = 'Date from';
$string['report_dateto'] = 'Date to';
$string['report_email'] = 'Email';
$string['report_email_placeholder'] = 'Search by email';
$string['report_filtered'] = 'Filtered records';
$string['report_fullname'] = 'Full Name';
$string['report_nodata'] = 'No referral data found.';
$string['report_referral'] = 'Referral Source';
$string['report_reset'] = 'Reset filters';
$string['report_top_source'] = 'Top source';
$string['report_total'] = 'Total referrals';
$string['report_username'] = 'Username';
$string['requestnewlink'] = 'Request New Reset Link';
$string['requireemailverification'] = 'Require email verification';
$string['requireemailverification_desc'] = 'If enabled, users must verify their email address before they can log in.';
$string['requiremfafor'] = 'Require MFA for roles';
$string['requiremfafor_desc'] = 'Select the role groups that should be required to enrol in multi-factor authentication.';
$string['resend_email_button'] = 'Send Verification Email';
$string['resend_verification'] = 'Resend Verification Email';
$string['resend_verification_button'] = 'Resend Verification Email';
$string['resend_verification_desc'] = 'Enter your email address to receive a new verification link.';
$string['resend_verification_email_help'] = 'Enter the email address you used to sign up.';
$string['reset_placeholders'] = 'Password Reset Placeholders';
$string['resetpassword'] = 'Reset Password';
$string['resetpassword_desc'] = 'Enter your new password below.';
$string['resetpasswordbutton'] = 'Reset Password';
$string['reseturl_desc'] = 'Password reset link';
$string['security_settings'] = 'Security and bot protection';
$string['security_settings_desc'] = 'Configure reCAPTCHA, signup rate limiting, and related security controls.';
$string['sendresetlink'] = 'Send Reset Link';
$string['sendwelcomeemail'] = 'Send welcome email';
$string['sendwelcomeemail_desc'] = 'Send a welcome email after successful registration or verification.';
$string['showidentityproviders'] = 'Show identity providers on signup page';
$string['showidentityproviders_desc'] = 'If enabled, buttons for other enabled authentication methods (e.g., Google, Microsoft via OAuth2/OIDC) will be displayed on the signup page.';
$string['showloginheading'] = 'Show login heading';
$string['showloginheading_desc'] = 'Display a custom heading on the login page below the logo.';
$string['showloginsubheading'] = 'Show login subheading';
$string['showloginsubheading_desc'] = 'Display a message below the heading on the login page.';
$string['shownavsignup'] = 'Show signup button in navbar';
$string['shownavsignup_desc'] = 'Display a "Sign Up" button next to the login link in the top navigation bar for guests.';
$string['showpasswordtoggle'] = 'Show password visibility toggle';
$string['showpasswordtoggle_desc'] = 'If enabled, users can reveal or hide the password field while signing up.';
$string['showreferralsurvey'] = 'Show referral survey on signup';
$string['showreferralsurvey_desc'] = 'If enabled, users will be asked "How did you hear about us?" during signup. Responses are stored and can be viewed in the referral report.';
$string['showsignupcontact'] = 'Show signup contact info';
$string['showsignupcontact_desc'] = 'Display contact information below the registration form.';
$string['showsignupheading'] = 'Show signup heading';
$string['showsignupheading_desc'] = 'Display a custom heading at the top of the registration form.';
$string['showsignupsubheading'] = 'Show signup subheading';
$string['showsignupsubheading_desc'] = 'Display a welcome message below the heading on the registration form.';
$string['signinnow'] = 'Sign In Now';
$string['signinwithpassword'] = 'Sign in with username and password';
$string['signup_email'] = 'Email address';
$string['signup_email_desc'] = 'Enter your email address to get started';
$string['signup_heading'] = 'Create your account';
$string['signupcontact'] = 'Signup contact info';
$string['signupcontact_default'] = 'Questions? We are happy to help. Contact us at: info@brianhamilton.org';
$string['signupcontact_desc'] = 'Contact information shown at the bottom of the registration form.';
$string['signupheading'] = 'Signup heading';
$string['signupheading_default'] = 'Starter U Registration';
$string['signupheading_desc'] = 'The main heading shown on the registration page.';
$string['signuplogo'] = 'Signup form logo';
$string['signuplogo_boostunion_logo'] = 'Boost Union logo';
$string['signuplogo_boostunion_logocompact'] = 'Boost Union compact logo';
$string['signuplogo_core_logo'] = 'Site logo (core)';
$string['signuplogo_core_logocompact'] = 'Site logo compact (core)';
$string['signuplogo_desc'] = 'Choose which logo to display on the signup form. To change the logo image itself, go to:<br><strong>Site logo:</strong> Site administration &gt; Appearance &gt; Logos<br><strong>Boost Union logo:</strong> Site administration &gt; Appearance &gt; Themes &gt; Boost Union &gt; Look &gt; Branding';
$string['signuplogo_none'] = 'No logo';
$string['signupratelimit'] = 'Signup rate limit';
$string['signupratelimit_desc'] = 'Maximum signup attempts allowed from one IP address per hour. Use 0 to disable rate limiting.';
$string['signupsubheading'] = 'Signup subheading';
$string['signupsubheading_default'] = 'Welcome to Starter U, a free online course for aspiring entrepreneurs! Fill out the form below to create an account.';
$string['signupsubheading_desc'] = 'A welcome or introductory message shown below the heading.';
$string['signuptext_settings'] = 'Signup Page Text';
$string['step_email'] = 'Your Email';
$string['step_final'] = 'Almost Done';
$string['step_location'] = 'Your Location';
$string['step_name'] = 'Your Name';
$string['step_password'] = 'Create Password';
$string['step_x_of_y'] = 'Step {current} of {total}';
$string['task_cleanup_security_records'] = 'Clean up email-first security records';
$string['user_placeholders'] = 'User Placeholders';
$string['usernotconfirmed'] = 'Your account has not been verified yet. Please check your email for the verification link.';
$string['usernotfound'] = 'No unverified account found with this email address.';
$string['verification_email_settings'] = 'Verification Email Settings';
$string['verification_placeholders'] = 'Verification Placeholders';
$string['verificationemailbody'] = 'Verification email body';
$string['verificationemailbody_desc'] = 'Body of verification email. Placeholders: {firstname}, {lastname}, {verifyurl}, {sitename}, {siteurl}, {supportemail}';
$string['verificationemailresent_desc'] = 'A new verification email has been sent to:';
$string['verificationemailsent'] = 'Verification email sent!';
$string['verificationemailsent_check'] = 'Please check your inbox and click the link to activate your account.';
$string['verificationemailsent_desc'] = 'We\'ve sent a verification link to';
$string['verificationemailsubject'] = 'Verify your email address';
$string['verificationemailsubject_desc'] = 'Subject line for verification emails. Placeholders: {sitename}';
$string['verificationexpired'] = 'Verification link has expired. Please sign up again.';
$string['verificationexpiry'] = 'Verification link expiry (seconds)';
$string['verificationexpiry_desc'] = 'How long verification links remain valid. Default is 86400 (24 hours).';
$string['verificationinvalid'] = 'Invalid or Expired Verification Link';
$string['verificationinvalid_desc'] = 'The verification link may have expired or is invalid. Verification links are valid for 24 hours.';
$string['verificationsuccess'] = 'Email Verified Successfully!';
$string['verificationsuccess_desc'] = 'Your email has been verified and your account is now active.';
$string['verifyurl_desc'] = 'Email verification link';
$string['viewreport'] = 'View referral source report';
$string['welcome_email_settings'] = 'Welcome Email Settings';
$string['welcome_user'] = 'Welcome, {$a}!';
$string['welcomeemailbody'] = 'Welcome email body';
$string['welcomeemailbody_desc'] = 'Body of welcome email. Placeholders: {firstname}, {lastname}, {sitename}, {siteurl}, {loginurl}';
$string['welcomeemailsubject'] = 'Welcome to {sitename}!';
$string['welcomeemailsubject_desc'] = 'Subject line for welcome emails. Placeholders: {sitename}';
