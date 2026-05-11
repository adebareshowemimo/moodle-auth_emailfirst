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
 * External function to check whether an email is already registered.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailfirst\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Check if the given email address is already associated with an existing user account.
 */
class check_email extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'email' => new external_value(PARAM_EMAIL, 'The email address to check'),
        ]);
    }

    /**
     * Check if the email exists.
     *
     * @param string $email Email address.
     * @return array {exists: bool, message: string}
     */
    public static function execute(string $email): array {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), ['email' => $email]);
        $email = \core_text::strtolower($params['email']);

        // Check whether a non-deleted user has this email.
        $exists = $DB->record_exists('user', [
            'email' => $email,
            'deleted' => 0,
            'mnethostid' => $CFG->mnet_localhost_id,
        ]);

        $message = '';
        if ($exists) {
            $message = get_string('emailexists', 'auth_emailfirst');
        }

        return [
            'exists' => $exists,
            'message' => $message,
        ];
    }

    /**
     * Describes the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'exists' => new external_value(PARAM_BOOL, 'Whether the email is already registered'),
            'message' => new external_value(PARAM_TEXT, 'User-facing message when email exists'),
        ]);
    }
}
