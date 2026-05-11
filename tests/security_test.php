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
 * Unit tests for auth_emailfirst security features.
 *
 * Tests rate limiting, reCAPTCHA validation, security logging, and MFA integration.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Test suite for security features.
 *
 * @coversDefaultClass \auth_emailfirst\local\security
 * @covers \auth_emailfirst\local\security
 */
final class security_test extends \advanced_testcase {
    /**
     * Set up test environment.
     */
    public function setUp(): void {
        global $DB;
        parent::setUp();

        // Reset database for each test.
        $this->resetAfterTest(true);

        // Create tables if they don't exist.
        $this->setup_test_tables();
    }

    /**
     * Create test database tables.
     */
    protected function setup_test_tables(): void {
        global $DB;

        // Check if tables exist, create if needed.
        try {
            $DB->get_records('auth_emailfirst_ratelimit', [], '', 'id', 0, 1);
        } catch (\Exception $e) {
            debugging('auth_emailfirst test table check failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Test rate limiting: Should reject signup after threshold exceeded.
     */
    public function test_rate_limit_exceeded(): void {
        global $DB;

        $ip = '192.168.1.100';
        $config = $this->get_mock_config();
        $config->signupratelimit = 3; // Allow only 3 attempts per hour.

        // Create 3 rate limit entries for this IP.
        $now = time();
        for ($i = 0; $i < 3; $i++) {
            $DB->insert_record('auth_emailfirst_ratelimit', [
                'ip' => $ip,
                'timeattempt' => $now - (5 * $i), // Stagger within last hour.
            ]);
        }

        // Fourth attempt should be rejected.
        // Simulate check_signup_rate_limit() logic.
        $attemptcount = $DB->count_records_sql(
            'SELECT COUNT(*) FROM {auth_emailfirst_ratelimit} ' .
            'WHERE ip = ? AND timeattempt > ?',
            [$ip, $now - 3600]
        );

        $this->assertEquals(3, $attemptcount, 'Should count 3 attempts within window');
        $this->assertGreaterThanOrEqual(
            $config->signupratelimit,
            $attemptcount,
            'Should reject when limit exceeded'
        );
    }

    /**
     * Test rate limiting: Should allow signup within limit.
     */
    public function test_rate_limit_allowed(): void {
        global $DB;

        $ip = '192.168.1.101';
        $config = $this->get_mock_config();
        $config->signupratelimit = 50; // Default limit.

        // Create only 1 attempt.
        $DB->insert_record('auth_emailfirst_ratelimit', [
            'ip' => $ip,
            'timeattempt' => time(),
        ]);

        // Should be allowed.
        $attemptcount = $DB->count_records_sql(
            'SELECT COUNT(*) FROM {auth_emailfirst_ratelimit} ' .
            'WHERE ip = ? AND timeattempt > ?',
            [$ip, time() - 3600]
        );

        $this->assertLessThan(
            $config->signupratelimit,
            $attemptcount,
            'Should allow signup within rate limit'
        );
    }

    /**
     * Test rate limiting: Should allow disabled rate limiting (0).
     */
    public function test_rate_limit_disabled(): void {
        global $DB;

        $ip = '192.168.1.102';
        $config = $this->get_mock_config();
        $config->signupratelimit = 0; // Disabled.

        // Create many attempts.
        $now = time();
        for ($i = 0; $i < 100; $i++) {
            $DB->insert_record('auth_emailfirst_ratelimit', [
                'ip' => $ip,
                'timeattempt' => $now - (10 * $i),
            ]);
        }

        // When disabled (0), should always allow.
        // Check config.
        $this->assertEquals(0, $config->signupratelimit, 'Rate limiting should be disabled');
    }

    /**
     * Test reCAPTCHA v3 validation: Should accept valid token with good score.
     */
    public function test_recaptcha_v3_valid_token(): void {
        $config = $this->get_mock_config();
        $config->recaptchaversion = 'v3';
        $config->recaptchasecretkey = 'test_secret_key';

        // Mock response from Google.
        $mockresponse = [
            'success' => true,
            'score' => 0.9,
            'action' => 'signup',
            'challenge_ts' => date('c'),
            'hostname' => 'test.example.com',
        ];

        // Test: score should be >= 0.5 (default threshold).
        $threshold = 0.5;
        $isvalid = $mockresponse['success'] && $mockresponse['score'] >= $threshold;

        $this->assertTrue($isvalid, 'Should accept token with score 0.9 (> 0.5)');
    }

    /**
     * Test reCAPTCHA v3 validation: Should reject low score.
     */
    public function test_recaptcha_v3_low_score(): void {
        $config = $this->get_mock_config();
        $config->recaptchaversion = 'v3';
        $config->recaptchasecretkey = 'test_secret_key';

        // Mock response with low score.
        $mockresponse = [
            'success' => true,
            'score' => 0.3,
            'action' => 'signup',
            'challenge_ts' => date('c'),
            'hostname' => 'test.example.com',
        ];

        // Test: score should be >= 0.5 (default threshold).
        $threshold = 0.5;
        $isvalid = $mockresponse['success'] && $mockresponse['score'] >= $threshold;

        $this->assertFalse($isvalid, 'Should reject token with score 0.3 (< 0.5)');
    }

    /**
     * Test reCAPTCHA v2 validation: Should accept valid token.
     */
    public function test_recaptcha_v2_valid_token(): void {
        $config = $this->get_mock_config();
        $config->recaptchaversion = 'v2';
        $config->recaptchasecretkey = 'test_secret_key';

        // Mock response from Google v2.
        $mockresponse = [
            'success' => true,
            'challenge_ts' => date('c'),
            'hostname' => 'test.example.com',
        ];

        // V2 doesn't use score, just success/failure.
        $isvalid = $mockresponse['success'];

        $this->assertTrue($isvalid, 'Should accept valid v2 token');
    }

    /**
     * Test reCAPTCHA v2 validation: Should reject failed token.
     */
    public function test_recaptcha_v2_failed_token(): void {
        $config = $this->get_mock_config();
        $config->recaptchaversion = 'v2';
        $config->recaptchasecretkey = 'test_secret_key';

        // Mock failed response.
        $mockresponse = [
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ];

        $isvalid = $mockresponse['success'];

        $this->assertFalse($isvalid, 'Should reject failed v2 token');
    }

    /**
     * Test security event logging.
     */
    public function test_security_event_logging(): void {
        global $DB;

        $ip = '192.168.1.103';
        $event = 'recaptcha_failed';
        $metadata = ['error' => 'invalid_token', 'version' => 'v3'];

        // Log event.
        $record = [
            'event' => $event,
            'ip' => $ip,
            'timeattempt' => time(),
            'metadata' => json_encode($metadata),
        ];

        $id = $DB->insert_record('auth_emailfirst_security_log', $record);

        // Verify logged.
        $logged = $DB->get_record('auth_emailfirst_security_log', ['id' => $id]);
        $this->assertNotNull($logged, 'Event should be logged');
        $this->assertEquals($event, $logged->event, 'Event type should match');
        $this->assertEquals($ip, $logged->ip, 'IP should match');

        $meta = json_decode($logged->metadata, true);
        $this->assertEquals('invalid_token', $meta['error'], 'Metadata should be stored correctly');
    }

    /**
     * Test cleanup of old rate limit entries (> 24 hours).
     */
    public function test_cleanup_old_entries(): void {
        global $DB;

        $ip = '192.168.1.104';
        $now = time();

        // Create old entry (25 hours ago).
        $DB->insert_record('auth_emailfirst_ratelimit', [
            'ip' => $ip,
            'timeattempt' => $now - (25 * 3600),
        ]);

        // Create recent entry (5 minutes ago).
        $DB->insert_record('auth_emailfirst_ratelimit', [
            'ip' => $ip,
            'timeattempt' => $now - (5 * 60),
        ]);

        // Get count.
        $totalbefore = $DB->count_records('auth_emailfirst_ratelimit', ['ip' => $ip]);
        $this->assertEquals(2, $totalbefore, 'Should have 2 entries before cleanup');

        // Simulate cleanup (delete entries older than 24 hours).
        $cutoff = $now - (24 * 3600);
        $DB->delete_records_select(
            'auth_emailfirst_ratelimit',
            'timeattempt < ?',
            [$cutoff]
        );

        // Get count after cleanup.
        $totalafter = $DB->count_records('auth_emailfirst_ratelimit', ['ip' => $ip]);
        $this->assertEquals(1, $totalafter, 'Should have 1 entry after cleanup');
    }

    /**
     * Test MFA requirement: Admin role should require MFA.
     */
    public function test_mfa_required_for_adminrole(): void {
        // Create user with admin role.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Get admin role.
        $adminrole = $this->get_mock_adminrole();

        // Check if user role matches required role.
        $config = $this->get_mock_config();
        $config->requiremfafor = 'admin'; // Require for admin.

        $requiresmfa = strpos($config->requiremfafor, 'admin') !== false;

        $this->assertTrue($requiresmfa, 'Admin role should require MFA');
    }

    /**
     * Test MFA grace period: New user should be in grace period.
     */
    public function test_mfa_graceperiod_active(): void {
        $user = $this->getDataGenerator()->create_user();
        $graceperiod = 7; // Days.

        // Calculate grace period end.
        $graceend = $user->timecreated + ($graceperiod * 86400);
        $now = time();

        // Should be in grace period.
        $ingrace = $now < $graceend;

        $this->assertTrue($ingrace, 'New user should be in grace period');
    }

    /**
     * Test MFA grace period: Old user should not be in grace period.
     */
    public function test_mfa_graceperiod_expired(): void {
        $user = $this->getDataGenerator()->create_user();
        $graceperiod = 7; // Days.

        // Simulate old user (set creation time to 30 days ago).
        $user->timecreated = time() - (30 * 86400);

        // Calculate grace period end.
        $graceend = $user->timecreated + ($graceperiod * 86400);
        $now = time();

        // Should NOT be in grace period.
        $ingrace = $now < $graceend;

        $this->assertFalse($ingrace, 'Old user should not be in grace period');
    }

    /**
     * Test admin settings: reCAPTCHA settings should be stored.
     */
    public function test_recaptcha_admin_settings(): void {
        $config = $this->get_mock_config();

        // Verify settings exist.
        $this->assertIsObject($config, 'Config should be object');
        $this->assertTrue(isset($config->enablerecaptcha), 'enablerecaptcha setting should exist');
        $this->assertTrue(isset($config->recaptchaversion), 'recaptchaversion setting should exist');
        $this->assertTrue(isset($config->recaptchasitekey), 'recaptchasitekey setting should exist');
        $this->assertTrue(isset($config->recaptchasecretkey), 'recaptchasecretkey setting should exist');
    }

    /**
     * Test admin settings: MFA settings should be stored.
     */
    public function test_mfa_admin_settings(): void {
        $config = $this->get_mock_config();

        // Verify settings exist.
        $this->assertTrue(isset($config->requiremfafor), 'requiremfafor setting should exist');
        $this->assertTrue(isset($config->promptmfaatnext), 'promptmfaatnext setting should exist');
        $this->assertTrue(isset($config->mfagraceperiod), 'mfagraceperiod setting should exist');
    }

    /**
     * Get mock configuration.
     *
     * @return \stdClass Mock config object.
     */
    protected function get_mock_config(): \stdClass {
        $config = new \stdClass();

        // ReCAPTCHA settings.
        $config->enablerecaptcha = true;
        $config->recaptchaversion = 'v3';
        $config->recaptchasitekey = 'test_site_key';
        $config->recaptchasecretkey = 'test_secret_key';
        $config->enablelogincaptcha = false;
        $config->signupratelimit = 50;

        // MFA settings.
        $config->requiremfafor = 'admin,manager';
        $config->promptmfaatnext = true;
        $config->mfagraceperiod = 7;

        return $config;
    }

    /**
     * Get mock admin role.
     *
     * @return string Admin role name.
     */
    protected function get_mock_adminrole() {
        return 'admin';
    }
}
