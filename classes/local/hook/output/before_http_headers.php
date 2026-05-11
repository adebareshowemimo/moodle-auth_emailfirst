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
 * Hook callback to redirect core signup-related pages before headers are sent.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailfirst\local\hook\output;

/**
 * Redirects core signup-related pages to the branded emailfirst versions.
 *
 * This fires before HTTP headers are sent, so redirect() works cleanly.
 */
class before_http_headers {
    /**
     * Hook callback.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function callback(\core\hook\output\before_http_headers $hook): void {
        global $CFG, $PAGE;

        if (empty($CFG->registerauth) || $CFG->registerauth !== 'emailfirst') {
            return;
        }

        $corepage = new \moodle_url('/login/verify_age_location.php');
        if ($PAGE->url->compare($corepage, URL_MATCH_BASE)) {
            redirect(new \moodle_url('/auth/emailfirst/verify_age_location.php'));
        }
    }
}
