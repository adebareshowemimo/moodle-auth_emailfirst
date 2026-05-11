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
 * Security utilities for auth_emailfirst plugin.
 *
 * Handles reCAPTCHA validation, rate limiting, MFA integration, and anomaly detection.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailfirst\local;

/**
 * Security manager class.
 */
class security {
    /**
     * Check if reCAPTCHA is enabled in config.
     *
     * @return bool True if reCAPTCHA is enabled.
     */
    public static function is_recaptcha_enabled(): bool {
        global $CFG;
        $config = get_config('auth_emailfirst');

        return !empty($config->enablerecaptcha)
            && !empty($CFG->recaptchasitekey)
            && !empty($CFG->recaptchasecretkey);
    }

    /**
     * Validate reCAPTCHA response from Google.
     *
     * @param string $response The g-recaptcha-response token from form.
     * @param string $version The reCAPTCHA version ('v2_checkbox', 'v2_invisible', 'v3').
     * @return array ['valid' => bool, 'score' => float|null, 'error' => string|null]
     */
    public static function validate_recaptcha(string $response, string $version = 'v2_checkbox'): array {
        global $CFG;

        if (empty($response)) {
            return [
                'valid' => false,
                'score' => null,
                'error' => get_string('missingrecaptchachallengefield'),
            ];
        }

        // Use Moodle's reCAPTCHA library.
        require_once($CFG->libdir . '/recaptchalib_v2.php');

        // Call Google to verify.
        $result = recaptcha_check_response(
            RECAPTCHA_VERIFY_URL,
            $CFG->recaptchasecretkey,
            getremoteaddr(),
            $response
        );

        if (!$result['isvalid']) {
            return [
                'valid' => false,
                'score' => null,
                'error' => $result['error'] ?? get_string('recaptcha_validation_failed', 'auth_emailfirst'),
            ];
        }

        // For v3, extract and validate score.
        $score = null;
        if ($version === 'v3' && isset($result['score'])) {
            $score = (float) $result['score'];

            // V3 score: 1.0 (human) to 0.0 (bot).
            // Recommended threshold: 0.5 for signup, 0.3 for login.
            $threshold = ($version === 'v3') ? 0.5 : 0.0;

            if ($score < $threshold) {
                return [
                    'valid' => false,
                    'score' => $score,
                    'error' => get_string('recaptcha_validation_failed', 'auth_emailfirst'),
                ];
            }
        }

        return [
            'valid' => true,
            'score' => $score,
            'error' => null,
        ];
    }

    /**
     * Check if signup rate limit is exceeded.
     *
     * @return bool True if under limit, false if exceeded.
     */
    public static function check_signup_rate_limit(): bool {
        global $DB;

        $config = get_config('auth_emailfirst');
        $ratelimit = (int) (!empty($config->signupratelimit) ? $config->signupratelimit : 50);

        // Rate limiting disabled.
        if ($ratelimit <= 0) {
            return true;
        }

        $ip = getremoteaddr();
        $now = time();
        $onehourago = $now - 3600;

        // Count POST attempts to signup from this IP in past hour.
        try {
            $count = $DB->count_records_select(
                'auth_emailfirst_ratelimit',
                'ip = ? AND timeattempt > ?',
                [$ip, $onehourago]
            );

            if ($count >= $ratelimit) {
                return false;  // Rate limit exceeded.
            }

            // Log this attempt.
            $record = new \stdClass();
            $record->ip = $ip;
            $record->timeattempt = $now;
            $DB->insert_record('auth_emailfirst_ratelimit', $record);

            return true;
        } catch (\Throwable $e) {
            // Table doesn't exist yet (first install). Allow signup.
            return true;
        }
    }

    /**
     * Clean up old rate limit entries (older than 24 hours).
     *
     * @return int Number of records deleted.
     */
    public static function cleanup_rate_limit_entries(): int {
        global $DB;

        try {
            $onedayago = time() - 86400;
            return $DB->delete_records_select(
                'auth_emailfirst_ratelimit',
                'timeattempt < ?',
                [$onedayago]
            );
        } catch (\Throwable $e) {
            // Table doesn't exist or query failed. Silently fail.
            return 0;
        }
    }

    /**
     * Log security event (reCAPTCHA failures, suspicious patterns, etc.).
     *
     * @param string $event Event type ('recaptcha_failure', 'rate_limit_exceeded', etc.).
     * @param array $data Additional event data (email, ip, score, etc.).
     */
    public static function log_security_event(string $event, array $data = []): void {
        global $DB;

        try {
            $record = new \stdClass();
            $record->event = $event;
            $record->ip = getremoteaddr();
            $record->timeattempt = time();

            // Store optional fields as JSON.
            $record->metadata = json_encode($data);

            $DB->insert_record('auth_emailfirst_security_log', $record);
        } catch (\Throwable $e) {
            // Table doesn't exist or query failed. Don't crash.
            debugging("auth_emailfirst: Could not log security event '$event': " . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Check if user must set up MFA based on role.
     *
     * @param \stdClass $user User object.
     * @return bool True if user must set up MFA.
     */
    public static function is_mfa_required_for_user(\stdClass $user): bool {
        $config = get_config('auth_emailfirst');

        if (empty($config->requiremfafor)) {
            return false;  // No roles require MFA.
        }

        $requiremfafor = (array) $config->requiremfafor;
        if (empty($requiremfafor)) {
            return false;
        }

        // Check if user has any of the required roles.
        foreach ($requiremfafor as $rolename) {
            $role = get_role_by_shortname($rolename);
            if (!$role) {
                continue;
            }

            if (user_has_role_assignment($user->id, $role->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is within MFA grace period.
     *
     * @param \stdClass $user User object.
     * @return bool True if still in grace period, false if MFA must be enforced now.
     */
    public static function is_in_mfa_grace_period(\stdClass $user): bool {
        $config = get_config('auth_emailfirst');

        $graceperiod = (int) (!empty($config->mfagraceperiod) ? $config->mfagraceperiod : 7);
        if ($graceperiod <= 0) {
            return false;  // No grace period; enforce immediately.
        }

        $gracenildate = $user->timecreated + ($graceperiod * 86400);
        return time() < $gracenildate;
    }

    /**
     * Check if user has any MFA factor enrolled.
     *
     * @param \stdClass $user User object.
     * @return bool True if user has at least one factor.
     */
    public static function user_has_mfa_factor(\stdClass $user): bool {
        try {
            if (!class_exists('\\core_auth\\factor_manager')) {
                return false;  // MFA not available in this Moodle version.
            }

            $factormanager = \core_auth\factor_manager::instance();
            return $factormanager->has_any_factor($user);
        } catch (\Throwable $e) {
            debugging("auth_emailfirst: Error checking MFA factors: " . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Trigger admin notification for suspicious activity.
     *
     * @param string $subject Notification subject.
     * @param string $message Notification body.
     */
    public static function notify_admin(string $subject, string $message): void {
        global $DB, $CFG;

        $admins = $DB->get_records_select(
            'user',
            "id IN (SELECT userid FROM {role_assignments} WHERE roleid = ?)",
            [get_role_by_shortname('admin')->id]
        );

        foreach ($admins as $admin) {
            $messageobject = new \stdClass();
            $messageobject->component = 'auth_emailfirst';
            $messageobject->name = 'security_alert';
            $messageobject->userfrom = get_admin();
            $messageobject->userto = $admin;
            $messageobject->subject = $subject;
            $messageobject->fullmessage = $message;
            $messageobject->fullmessageformat = FORMAT_PLAIN;
            $messageobject->fullmessagehtml = $message;
            $messageobject->smallmessage = $subject;
            $messageobject->notification = 1;

            message_send($messageobject);
        }
    }
}
