@auth @auth_emailfirst
Feature: Email-first signup with security features
  As a new user
  I want to sign up with email as username
  So that I can access my account

  Background:
    Given the following config values are set as admin:
      | enablerecaptcha       | 1            | auth_emailfirst |
      | recaptchaversion      | v2           | auth_emailfirst |
      | recaptchasitekey      | test_key     | auth_emailfirst |
      | recaptchasecretkey    | test_secret  | auth_emailfirst |
      | signupratelimit       | 50           | auth_emailfirst |
      | enablelogincaptcha    | 0            | auth_emailfirst |

  @javascript
  Scenario: User signs up with email-first form
    Given I am on the emailfirst signup page
    When I fill in the following:
      | Email address       | newuser@example.com  |
      | Password            | SecurePass123!       |
      | Confirm password    | SecurePass123!       |
      | First name          | John                 |
      | Last name           | Doe                  |
    And I wait for reCAPTCHA to load
    And I solve the reCAPTCHA v2 puzzle
    And I click on "Create my account"
    Then I should see "Congratulations! Your account has been created successfully."
    And a new user should exist with email "newuser@example.com"

  Scenario: User cannot sign up without solving reCAPTCHA
    Given I am on the emailfirst signup page
    When I fill in the following:
      | Email address       | newuser2@example.com |
      | Password            | SecurePass123!       |
      | Confirm password    | SecurePass123!       |
      | First name          | Jane                 |
      | Last name           | Smith                |
    And I click on "Create my account" without solving reCAPTCHA
    Then I should see "reCAPTCHA verification failed"
    And no new user should be created with email "newuser2@example.com"

  @javascript
  Scenario: Rate limiting: User blocked after exceeding signup attempts
    Given I have attempted signup 50 times from IP "192.168.1.100"
    When I attempt to signup again from IP "192.168.1.100"
    Then I should see "Too many signup attempts from your IP address"
    And signup should be prevented

  @javascript
  Scenario: Rate limiting can be disabled
    Given the following config values are set as admin:
      | signupratelimit | 0 | auth_emailfirst |
    And I have attempted signup 100 times from IP "192.168.1.101"
    When I attempt to signup again from IP "192.168.1.101"
    Then signup should be allowed
    And I should not see "Too many signup attempts"

  @javascript
  Scenario: Email validation fails with existing email
    Given a user exists with email "existing@example.com"
    And I am on the emailfirst signup page
    When I fill in the following:
      | Email address       | existing@example.com |
      | Password            | SecurePass123!       |
      | Confirm password    | SecurePass123!       |
      | First name          | Test                 |
      | Last name           | User                 |
    And I wait for reCAPTCHA to load
    And I solve the reCAPTCHA v2 puzzle
    And I click on "Create my account"
    Then I should see "That email address is already registered"
    And duplicate user should not be created

  @javascript
  Scenario: Password requirements are enforced
    Given I am on the emailfirst signup page
    When I fill in the following:
      | Email address       | newuser3@example.com |
      | Password            | weak                 |
      | Confirm password    | weak                 |
      | First name          | Test                 |
      | Last name           | User                 |
    And I click on "Create my account"
    Then I should see "Password must contain"
    And signup should be prevented

  @javascript
  Scenario: Email field accepts valid email formats
    Given I am on the emailfirst signup page
    When I fill in "Email address" with "user+tag@subdomain.example.co.uk"
    Then the email field should be valid

  @javascript
  Scenario: User receives confirmation email
    Given I have signed up with email "newuser4@example.com" and solved reCAPTCHA
    And the system is configured to require email confirmation
    Then an email should be sent to "newuser4@example.com"
    And the email should contain "confirm your email address"
    And the user account should be unconfirmed until link clicked
