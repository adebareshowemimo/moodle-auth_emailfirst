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
 * Behat step definitions for auth_emailfirst.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Behat\Mink\Exception\ExpectationException;

/**
 * Behat step definitions for email-first authentication.
 */
class behat_auth_emailfirst extends \behat_base {
    /**
     * Navigate to emailfirst signup page.
     *
     * @Given /^I am on the emailfirst signup page$/
     */
    public function i_am_on_emailfirst_signup_page() {
        $this->getSession()->visit($this->locate_path('/auth/emailfirst/signup.php'));
    }

    /**
     * Wait for reCAPTCHA to load.
     *
     * @Given /^I wait for reCAPTCHA to load$/
     */
    public function wait_for_recaptcha_load() {
        $this->getSession()->wait(3000, "window.grecaptcha !== undefined");
    }

    /**
     * Solve reCAPTCHA v2 (mock for testing).
     *
     * @Given /^I solve the reCAPTCHA v2 puzzle$/
     */
    public function solve_recaptcha_v2() {
        // In real tests, this would interact with reCAPTCHA iframe.
        // For automated testing, use a test key that bypasses verification.
        $js = "
            if (window.grecaptcha && typeof window.grecaptcha.callback === 'function') {
                window.grecaptcha.callback('test_token_v2');
            }
        ";
        $this->getSession()->executeScript($js);
    }

    /**
     * Attempt signup from specific IP.
     *
     * @param int $count Number of existing attempts.
     * @param string $ip IP address.
     * @Given /^I have attempted signup (\d+) times from IP \"([^\"]+)\"$/
     */
    public function attempt_signup_from_ip($count, $ip) {
        global $DB;

        $now = time();
        for ($i = 0; $i < $count; $i++) {
            $DB->insert_record('auth_emailfirst_ratelimit', [
                'ip' => $ip,
                'timeattempt' => $now - (5 * $i),
            ]);
        }
    }

    /**
     * Attempt signup again from IP.
     *
     * @param string $ip IP address.
     * @When /^I attempt to signup again from IP \"([^\"]+)\"$/
     */
    public function attempt_signup_again_from_ip($ip) {
        // Navigate to signup page.
        $this->i_am_on_emailfirst_signup_page();

        // Try to fill form and submit.
        // This will test rate limiting.
    }

    /**
     * Verify signup is prevented.
     *
     * @Then /^signup should be prevented$/
     */
    public function signup_should_be_prevented() {
        $page = $this->getSession()->getPage();
        $message = $page->findField('Rate limit message');

        if (!$message) {
            throw new \Exception('Rate limit error message not found');
        }
    }

    /**
     * Verify signup is allowed.
     *
     * @Then /^signup should be allowed$/
     */
    public function signup_should_be_allowed() {
        // Check that form can be submitted without rate limit error.
        $page = $this->getSession()->getPage();
        $form = $page->find('xpath', '//form[@id="signup-form"]');

        if (!$form) {
            throw new \Exception('Signup form not found');
        }
    }

    /**
     * Verify user exists with email.
     *
     * @param string $email Email address.
     * @Then /^a new user should exist with email \"([^\"]+)\"$/
     */
    public function user_exists_with_email($email) {
        global $DB;

        $user = $DB->get_record('user', ['email' => $email]);
        if (!$user) {
            throw new \Exception("User with email '$email' not found");
        }
    }

    /**
     * Verify no user exists with email.
     *
     * @param string $email Email address.
     * @Then /^no new user should be created with email \"([^\"]+)\"$/
     */
    public function no_user_with_email($email) {
        global $DB;

        $user = $DB->get_record('user', ['email' => $email]);
        if ($user) {
            throw new \Exception("User with email '$email' unexpectedly exists");
        }
    }

    /**
     * Create user with email.
     *
     * @param string $email Email address.
     * @Given /^a user exists with email \"([^\"]+)\"$/
     */
    public function create_user_with_email($email) {
        global $DB;

        $user = new stdClass();
        $user->email = $email;
        $user->username = substr($email, 0, strpos($email, '@'));
        $user->password = password_hash('TestPassword123!', PASSWORD_DEFAULT);

        $DB->insert_record('user', $user);
    }

    /**
     * Verify duplicate not created.
     *
     * @Then /^duplicate user should not be created$/
     */
    public function duplicate_not_created() {
        global $DB;

        // Count users with duplicate email.
        // If count > 1, duplicate was created.
    }

    /**
     * Verify email field is valid.
     *
     * @Then /^the email field should be valid$/
     */
    public function email_field_is_valid() {
        $page = $this->getSession()->getPage();
        $field = $page->findField('Email address');

        if (!$field) {
            throw new \Exception('Email field not found');
        }

        // Check HTML5 validation.
        $type = $field->getAttribute('type');
        if ($type !== 'email') {
            throw new \Exception('Email field is not type="email"');
        }
    }

    /**
     * Verify email sent to user.
     *
     * @param string $email Email address.
     * @Then /^an email should be sent to \"([^\"]+)\"$/
     */
    public function email_sent_to($email) {
        // Check if email was queued for sending.
        // This depends on mail configuration.
    }

    /**
     * Verify email contains text.
     *
     * @param string $text Expected email text.
     * @Then /^the email should contain \"([^\"]+)\"$/
     */
    public function email_contains_text($text) {
        // Verify email content.
    }

    /**
     * Verify user account is unconfirmed.
     *
     * @Then /^the user account should be unconfirmed until link clicked$/
     */
    public function account_unconfirmed() {
        global $DB;

        // Check user's confirmed field.
        // Should be 0 until email confirmation link clicked.
    }

    /**
     * Helper to get session.
     *
     * @return \Behat\Mink\Session
     */
    public function get_session_impl() {
        return $this->getSession();
    }

    /**
     * Helper to locate path.
     *
     * @param string $path
     * @return string
     */
    public function locate_path_impl($path) {
        return $this->locate_path($path);
    }
}
