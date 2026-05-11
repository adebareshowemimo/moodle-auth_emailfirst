# Auth EmailFirst — Google Captcha & MFA Implementation Checklist

**Document Date**: 2026-05-10  
**Status**: READY FOR IMPLEMENTATION  
**Estimated Timeline**: 2-3 weeks (Phases 1-5)

---

## Phase 1: reCAPTCHA v2 Setup (Week 1, Days 1-3)

### 1.1 Google reCAPTCHA Admin Console Setup

- [ ] Log in to Google Cloud Console (https://cloud.google.com)
- [ ] Create new project: `Moodle auth_emailfirst` or reuse existing
- [ ] Enable reCAPTCHA v2 API
- [ ] Go to https://www.google.com/recaptcha/admin/create
- [ ] Create new site:
  - [ ] Label: `Emailfirst Signup`
  - [ ] reCAPTCHA type: `reCAPTCHA v2`
  - [ ] Subtype: `I'm not a robot` Checkbox
  - [ ] Domains: `yourdomain.com` + `www.yourdomain.com` + `admin.yourdomain.com`
  - [ ] Accept terms
  - [ ] Create
- [ ] Copy **Site Key** (public, embed in page)
- [ ] Copy **Secret Key** (private, server-side only)
- [ ] Store keys securely (add to next step)

### 1.2 Moodle Admin Configuration

- [ ] Log in as Site Admin
- [ ] Navigate to **Site Administration > Plugins > Authentication > Manage Authentication**
- [ ] Find `emailfirst` in list
- [ ] Click **Settings** button
- [ ] Scroll to **Security & Bot Protection** section
- [ ] Enable: `Enable reCAPTCHA on signup` → **YES**
- [ ] Version: **reCAPTCHA v2 (Checkbox + Fallback)**
- [ ] Site Key: Paste from Google Console
- [ ] Secret Key: Paste from Google Console
- [ ] Rate Limit: **50** (signups per IP per hour)
- [ ] Save changes

### 1.3 Test reCAPTCHA Integration

- [ ] Clear Moodle theme cache (Admin > Appearance > Themes > Clear theme cache)
- [ ] Clear all caches (Admin > Development > Purge Caches)
- [ ] Open signup page: `/auth/emailfirst/signup.php`
- [ ] Verify reCAPTCHA checkbox appears below form
- [ ] Attempt signup **without** completing checkbox:
  - [ ] Error message appears: "Please verify you are human"
- [ ] Check the reCAPTCHA checkbox
- [ ] Complete signup form with valid data
- [ ] Form submits successfully
- [ ] New user created in database
- [ ] Log in as new user with email + password
- [ ] Success ✓

### 1.4 Database Table Creation

After confirming v2 works, add rate limiting table:

- [ ] Edit `db/install.xml` (see [Implementation Guide](#implementation-roadmap))
- [ ] Add `auth_emailfirst_ratelimit` table
- [ ] Run upgrade: `php admin/cli/upgrade.php`
- [ ] Verify table created: Check database via phpMyAdmin

### 1.5 Rate Limiting Test

- [ ] Edit `settings.php`, set rate limit to **3** (temporarily for testing)
- [ ] Attempt signup 3 times from same IP within 1 minute
  - [ ] Attempt 1: Success
  - [ ] Attempt 2: Success
  - [ ] Attempt 3: Success
- [ ] Attempt signup 4th time from same IP
  - [ ] Error message: "Too many signup attempts from your IP"
- [ ] Wait 10 minutes, try again
  - [ ] Should succeed (1-hour window expired for old attempts)
- [ ] Reset to production value (e.g., 50)

**Deliverables after Phase 1**:
- ✅ reCAPTCHA v2 active on signup form
- ✅ Rate limiting prevents brute-force signup attacks
- ✅ Admin can configure keys + limits

---

## Phase 2: Language Strings & Admin UI (Week 1, Days 3-4)

### 2.1 Add Language Strings

- [ ] Edit `lang/en/auth_emailfirst.php`
- [ ] Add all strings from [Security Integration Guide](AUTH_EMAILFIRST_SECURITY_INTEGRATION.md#step-2-language-strings)
- [ ] Categories to add:
  - [ ] `security_settings*` (3 strings)
  - [ ] `enablerecaptcha*` (2 strings)
  - [ ] `recaptchaversion*` (4 strings)
  - [ ] `recaptchasitekey*` (2 strings)
  - [ ] `recaptchasecretkey*` (2 strings)
  - [ ] `enablelogincaptcha*` (2 strings)
  - [ ] `signupratelimit*` (2 strings)
  - [ ] Error messages: `recaptcha_validation_failed`, `recaptcha_error_timeout`, `rate_limit_exceeded`

### 2.2 Enhance Admin Settings

- [ ] Edit `settings.php`
- [ ] Add **Security & Bot Protection** section with all settings from [Implementation Guide](AUTH_EMAILFIRST_SECURITY_INTEGRATION.md#step-1-admin-configuration)
- [ ] Settings to add:
  - [ ] `enablerecaptcha` (checkbox) — Default OFF
  - [ ] `recaptchaversion` (dropdown) — v2_checkbox / v2_invisible / v3
  - [ ] `recaptchasitekey` (text) — Public key
  - [ ] `recaptchasecretkey` (password) — Secret key
  - [ ] `enablelogincaptcha` (checkbox) — Optional login captcha
  - [ ] `signupratelimit` (text) — Per-IP limit

### 2.3 Verify in Admin UI

- [ ] Log in as admin
- [ ] Navigate to emailfirst settings
- [ ] Verify **Security & Bot Protection** heading visible
- [ ] Verify all 6 settings render correctly
- [ ] Verify help text (descriptions) visible for each setting
- [ ] Test saving each setting
- [ ] Verify values persist after save & reload page

**Deliverables after Phase 2**:
- ✅ Admin UI complete for reCAPTCHA config
- ✅ All language strings in place
- ✅ Settings persist correctly

---

## Phase 3: reCAPTCHA v3 (Optional, Week 2, Days 1-3)

### 3.1 Create AMD Module

- [ ] Verify `amd/src/recaptcha_v3.js` exists and is correct
- [ ] Build AMD module: `npm run build` (from theme/boost or root)
- [ ] Verify output in `amd/build/recaptcha_v3.min.js`

### 3.2 Enhance Signup Form

- [ ] Edit `signup_form.php`
- [ ] Modify reCAPTCHA section to detect version:
  - [ ] If `enablerecaptcha` = OFF: Don't render anything
  - [ ] If version = `v2_checkbox`: Use `$mform->addElement('recaptcha', ...)`
  - [ ] If version = `v3`: Add hidden field + call AMD module
- [ ] Test v3 token injection via JavaScript

### 3.3 Add v3 Score Validation

- [ ] Edit `signup.php` validation function
- [ ] Add score check: if score < 0.5, block signup with error
- [ ] Log v3 scores to `auth_emailfirst_security_log` table for analysis
- [ ] Test:
  - [ ] Valid human signup (should succeed)
  - [ ] Simulate bot with low score (should fail)

### 3.4 Login Page Captcha (Optional)

- [ ] Edit `auth.php`, modify `loginpage_hook()`
- [ ] Add v3 captcha to login context if enabled
- [ ] Create `amd/src/recaptcha_v3_login.js` (similar to signup)
- [ ] Test v3 on custom login page

**Deliverables after Phase 3**:
- ✅ reCAPTCHA v3 seamless signup
- ✅ Score-based bot detection active
- ✅ v3 analytics logging in database

---

## Phase 4: MFA Integration (Week 2-3)

### 4.1 Check Moodle MFA Availability

- [ ] Log in as admin
- [ ] Navigate to **Site Administration > Security > Multi-Factor Authentication**
- [ ] Verify page exists and shows:
  - [ ] Available factors (TOTP, Email, WebAuthn, etc.)
  - [ ] User MFA management link
- [ ] If page missing: MFA not enabled in your Moodle version

### 4.2 Add MFA Admin Settings

- [ ] Edit `settings.php`
- [ ] Add **Multi-Factor Authentication** section with:
  - [ ] `requiremfafor` (multi-checkbox) — Select roles (Admin, Teacher, Manager)
  - [ ] `promptmfaatnext` (checkbox) — Prompt enrollment at signup
  - [ ] `mfagraceperiod` (text) — Days before enforcement (default 7)

### 4.3 Add MFA Language Strings

- [ ] Edit `lang/en/auth_emailfirst.php`
- [ ] Add MFA-related strings:
  - [ ] `mfa_settings*`
  - [ ] `requiremfafor*`
  - [ ] `promptmfaatnext*`
  - [ ] `mfagraceperiod*`
  - [ ] `enrollmfa`, `mfaenrollmentcomplete`

### 4.4 Create MFA Enrollment Page

- [ ] Create `enroll_mfa.php` (see [Implementation Guide](AUTH_EMAILFIRST_SECURITY_INTEGRATION.md#step-4-create-mfa-enrollment-page))
- [ ] Page should:
  - [ ] Show available MFA factors
  - [ ] Display factor icons + descriptions
  - [ ] Link to factor setup pages
  - [ ] Show "setup complete" message if user has factor

### 4.5 Integrate with Signup

- [ ] Edit `signup.php`
- [ ] After successful user creation, check if MFA enrollment should be prompted:
  ```php
  if (!empty($config->promptmfaatnext)) {
      redirect(new moodle_url('/auth/emailfirst/enroll_mfa.php'));
  }
  ```

### 4.6 Create Hook for MFA Enforcement at Login

- [ ] Edit `db/hooks.php`
- [ ] Add hook for `after_successful_login` (if available in Moodle version)
- [ ] Create hook class: `classes/local/hook/after_successful_login.php`
- [ ] Logic:
  - [ ] Check if user has required role (from config)
  - [ ] Check if within grace period
  - [ ] If not in grace period AND no MFA: Redirect to enrollment

### 4.7 Test MFA Integration

- [ ] Create test admin user via signup
- [ ] Verify `promptmfaatnext` redirects to enrollment page
- [ ] Try to set up TOTP factor
- [ ] Log out and log in as admin
- [ ] Verify MFA challenge prompt appears
- [ ] Complete MFA challenge with TOTP code
- [ ] Verify login succeeds

**Deliverables after Phase 4**:
- ✅ MFA integration with Moodle core
- ✅ Role-based MFA enforcement
- ✅ Grace period + enrollment prompts
- ✅ Admin-configurable MFA policies

---

## Phase 5: Testing & Documentation (Week 3)

### 5.1 Unit Testing (PHPUnit)

- [ ] Create `tests/auth_emailfirst_test.php` with test cases for:
  - [ ] `security::is_recaptcha_enabled()` 
  - [ ] `security::validate_recaptcha()` (mock Google response)
  - [ ] `security::check_signup_rate_limit()` (verify counting logic)
  - [ ] `security::is_mfa_required_for_user()` (role checking)
  - [ ] `security::is_in_mfa_grace_period()` (time comparison)

- [ ] Run tests: `php vendor/bin/phpunit auth_emailfirst_test`
- [ ] Target: **80%+ code coverage** for security module

### 5.2 Behat Acceptance Tests

- [ ] Create `tests/behat/recaptcha.feature`:
  ```gherkin
  Scenario: User cannot sign up without completing reCAPTCHA
    Given reCAPTCHA is enabled with v2 checkbox
    When I visit the signup page
    And I fill the form with valid email and password
    And I click "Sign up" without completing reCAPTCHA
    Then I should see error "Please verify you are human"
  
  Scenario: User can sign up after completing reCAPTCHA
    Given reCAPTCHA is enabled
    And I have completed reCAPTCHA validation
    When I fill the signup form
    And I click "Sign up"
    Then my account is created successfully
  ```

- [ ] Create `tests/behat/mfa.feature`:
  ```gherkin
  Scenario: Admin is prompted to set up MFA
    Given MFA is required for admin role
    And I am a new admin user
    When I log in for the first time
    Then I should see MFA enrollment page
    And I can set up TOTP factor
  ```

- [ ] Run Behat tests: `php vendor/bin/behat --dry-run features/` (initial check)

### 5.3 Security Audit

- [ ] Review code against OWASP Top 10:
  - [ ] ✅ A01: Broken Access Control — Check capability requirements
  - [ ] ✅ A02: Cryptographic Failures — Secret keys not logged, HTTPS enforced
  - [ ] ✅ A03: Injection — Use prepared statements (DB) + validation (forms)
  - [ ] ✅ A04: Insecure Design — Rate limiting + reCAPTCHA implemented
  - [ ] ✅ A05: Security Misconfiguration — No hardcoded keys
  - [ ] ✅ A06: Vulnerable Components — Use Moodle's blessed reCAPTCHA lib
  - [ ] ✅ A07: Authentication Failures — MFA + strong password checks
  - [ ] ✅ A08: Data Integrity — CSRF tokens via forms
  - [ ] ✅ A09: Logging & Monitoring — Security events logged
  - [ ] ✅ A10: SSRF — No external calls except to Google

- [ ] Run Moodle's code checker: `moodle-plugin-ci phplint`

### 5.4 Documentation

#### Admin Documentation

- [ ] Create `ADMIN_SETUP.md`:
  - [ ] How to get reCAPTCHA keys from Google
  - [ ] How to configure in Moodle
  - [ ] How to set rate limits
  - [ ] How to require MFA for roles
  - [ ] Troubleshooting guide (key validation, network issues, etc.)

#### User Documentation

- [ ] Create `USER_GUIDE.md`:
  - [ ] What is reCAPTCHA and why we use it
  - [ ] How to sign up (step-by-step with screenshots)
  - [ ] What is MFA and why recommended
  - [ ] How to set up TOTP in Authenticator app
  - [ ] What to do if you lose your phone/codes
  - [ ] FAQ section

#### Developer Documentation

- [ ] Update `README.md` with:
  - [ ] New database tables schema
  - [ ] New hooks used (if any)
  - [ ] New AMD modules
  - [ ] Configuration reference
  - [ ] Testing instructions

### 5.5 Load & Performance Testing

- [ ] Simulate signup load:
  - [ ] 100 concurrent users signing up
  - [ ] Monitor reCAPTCHA API latency
  - [ ] Monitor rate limit query performance
  - [ ] Monitor Moodle response time

- [ ] Check database indexes:
  - [ ] `auth_emailfirst_ratelimit` — index on (ip, timeattempt)
  - [ ] `auth_emailfirst_security_log` — index on (event, timeattempt)

### 5.6 Browser Compatibility Testing

- [ ] Test signup/login on:
  - [ ] Chrome (latest)
  - [ ] Firefox (latest)
  - [ ] Safari (latest)
  - [ ] Edge (latest)
  - [ ] Mobile (iOS Safari, Chrome Android)

- [ ] Verify:
  - [ ] reCAPTCHA renders correctly
  - [ ] JavaScript doesn't error
  - [ ] Form validation works
  - [ ] Responsive design OK

**Deliverables after Phase 5**:
- ✅ 80%+ unit test coverage
- ✅ Behat acceptance tests passing
- ✅ Security audit completed & signed off
- ✅ Admin + User documentation written
- ✅ Performance tested & optimized

---

## Production Deployment Checklist

### Pre-Deployment (1 week before go-live)

- [ ] Staging deployment completed & tested
- [ ] All phase tasks checked off
- [ ] Admin documentation reviewed by IT team
- [ ] Security audit approved by InfoSec
- [ ] Load testing passed (concurrent users, response time)
- [ ] Browser compatibility tested
- [ ] Backup strategy defined (DB restore plan)
- [ ] Rollback plan documented (in case of issues)

### Deployment Day

- [ ] Maintenance window announced to users (1 hour)
- [ ] Database backup taken
- [ ] Code deployed to production
- [ ] Database upgrade run: `php admin/cli/upgrade.php`
- [ ] Caches purged: `php admin/cli/purge_caches.php`
- [ ] Theme cache cleared
- [ ] Admin settings configured with production reCAPTCHA keys
- [ ] Test signup flow end-to-end
- [ ] Test login with MFA (if enabled)
- [ ] Monitor error logs for first 30 minutes
- [ ] Notify stakeholders of completion

### Post-Deployment (Week 1)

- [ ] Daily log review for errors
- [ ] Monitor reCAPTCHA success rate (target: 95%+)
- [ ] Monitor signup completion rate (should not drop >10%)
- [ ] Gather user feedback on UX
- [ ] Track MFA enrollment rate (if prompted)
- [ ] Weekly security event review

---

## Rollback Plan (If Issues)

| Issue | Rollback Step |
|-------|---------------|
| **reCAPTCHA not working** | Disable in admin settings, redeploy without captcha |
| **Rate limiting too strict** | Increase `signupratelimit` to 100+ |
| **MFA breaking logins** | Disable `requiremfafor` until fixed |
| **Database corruption** | Restore from pre-deployment backup |
| **Code bug** | Revert code commit, re-run upgrade.php |

---

## Sign-Off

**Implementer**: ___________________  
**Date**: ___________________  

**Reviewer**: ___________________  
**Date**: ___________________  

**Approver**: ___________________  
**Date**: ___________________  

---

**Document Version**: 1.0  
**Last Updated**: 2026-05-10  
**Status**: READY FOR IMPLEMENTATION
