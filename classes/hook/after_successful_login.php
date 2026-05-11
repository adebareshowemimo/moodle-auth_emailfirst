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
 * Hook for auth_emailfirst to enforce MFA after successful login.
 *
 * Checks if a user meets MFA requirements and redirects to enrollment
 * if they haven't set up a factor yet.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailfirst\hook;

/**
 * Hook callback for after_login_completed event.
 *
 * Triggers MFA enrollment check if required for user's role.
 */
class after_successful_login {
    /**
     * Handles the after_login_completed hook to enforce MFA enrollment if needed.
     */
    public static function callback(): void {
        global $USER, $CFG;

        // Only run for emailfirst auth.
        if (empty($USER->auth) || $USER->auth !== 'emailfirst') {
            return;
        }

        // Check if MFA is enabled.
        $config = get_config('auth_emailfirst');
        if (empty($config->requiremfafor)) {
            return;
        }

        // Get user's roles.
        $usercontext = \context_user::instance($USER->id);
        $roles = get_user_roles($usercontext);

        if (empty($roles)) {
            return;
        }

        // Parse required roles from config (comma-separated role IDs or names).
        $requiredroles = explode(',', $config->requiremfafor ?? '');
        $requiredroles = array_map('trim', $requiredroles);

        // Check if user has a required role.
        $userroleids = array_keys($roles);
        $userrolenames = array_map(function ($role) {
            return $role->shortname;
        }, array_values($roles));

        $hasrequiredrole = false;
        foreach ($requiredroles as $requiredrole) {
            if (in_array($requiredrole, $userroleids) || in_array($requiredrole, $userrolenames)) {
                $hasrequiredrole = true;
                break;
            }
        }

        if (!$hasrequiredrole) {
            return;
        }

        // Check if user is in grace period.
        $graceperiod = (int) (!empty($config->mfagraceperiod) ? $config->mfagraceperiod : 7);
        $graceends = $USER->timecreated + ($graceperiod * 86400);

        if (time() < $graceends) {
            // Still in grace period, don't enforce yet.
            return;
        }

        // Check if user already has an enrolled MFA factor (Moodle 4.1+ native MFA).
        if (class_exists('\core_mfa\manager')) {
            try {
                $factormanager = \core_mfa\manager::get_factor_manager();
                $userfactors = $factormanager->get_user_factors($USER->id);

                if (!empty($userfactors)) {
                    // User has MFA enrolled, all good.
                    return;
                }
            } catch (\Throwable $e) {
                // MFA system issue, log and continue.
                debugging('MFA check failed: ' . $e->getMessage());
                return;
            }
        } else {
            // Moodle version doesn't support native MFA.
            return;
        }

        // Redirect to MFA enrollment page.
        redirect(new \moodle_url('/auth/emailfirst/enroll_mfa.php'));
    }
}
