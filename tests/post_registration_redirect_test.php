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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace auth_emailfirst;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/auth/emailfirst/lib.php');

/**
 * Tests for the post-registration destination.
 *
 * @covers \auth_emailfirst_get_post_registration_url
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class post_registration_redirect_test extends \advanced_testcase {
    /**
     * The Modern Commerce learner dashboard is the fallback destination.
     */
    public function test_default_destination(): void {
        $this->resetAfterTest();
        set_config('postregistrationredirect', '', 'auth_emailfirst');

        $url = auth_emailfirst_get_post_registration_url();
        $this->assertSame('/local/moderncommerce/learner/index.php', $url->out_as_local_url(false));
    }

    /**
     * Administrators can choose another local Moodle path.
     */
    public function test_configured_local_destination(): void {
        $this->resetAfterTest();
        set_config('postregistrationredirect', '/course/index.php', 'auth_emailfirst');

        $url = auth_emailfirst_get_post_registration_url();
        $this->assertSame('/course/index.php', $url->out_as_local_url(false));
    }
}
