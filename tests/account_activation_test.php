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

/**
 * Tests for registration account activation controls.
 *
 * @covers \auth_plugin_emailfirst
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class account_activation_test extends \advanced_testcase {
    /**
     * Admin approval suspends an account without making it email-unconfirmed.
     */
    public function test_admin_approval_without_email_verification(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        set_config('registerauth', 'emailfirst');
        set_config('requireemailverification', 0, 'auth_emailfirst');
        set_config('requireadminapproval', 1, 'auth_emailfirst');
        set_config('sendwelcomeemail', 0, 'auth_emailfirst');

        require_once($CFG->dirroot . '/login/lib.php');
        require_once($CFG->dirroot . '/user/editlib.php');
        require_once($CFG->dirroot . '/auth/emailfirst/auth.php');

        $plugin = new \auth_plugin_emailfirst();
        $result = $plugin->user_signup($this->make_signup_user(), false);
        $created = $DB->get_record('user', ['username' => 'pending@example.com'], '*', MUST_EXIST);

        $this->assertFalse($result);
        $this->assertEquals(1, $created->confirmed);
        $this->assertEquals(1, $created->suspended);
    }

    /**
     * Email verification and admin approval remain independent states.
     */
    public function test_admin_approval_with_email_verification(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        set_config('registerauth', 'emailfirst');
        set_config('requireemailverification', 1, 'auth_emailfirst');
        set_config('requireadminapproval', 1, 'auth_emailfirst');
        set_config('sendwelcomeemail', 0, 'auth_emailfirst');

        require_once($CFG->dirroot . '/login/lib.php');
        require_once($CFG->dirroot . '/user/editlib.php');
        require_once($CFG->dirroot . '/auth/emailfirst/auth.php');

        $sink = $this->redirectEmails();
        $plugin = new \auth_plugin_emailfirst();
        $result = $plugin->user_signup($this->make_signup_user(), false);
        $created = $DB->get_record('user', ['username' => 'pending@example.com'], '*', MUST_EXIST);

        $this->assertFalse($result);
        $this->assertEquals(0, $created->confirmed);
        $this->assertEquals(1, $created->suspended);
        $this->assertCount(1, $sink->get_messages());

        $this->assertEquals(AUTH_CONFIRM_OK, $plugin->user_confirm($created->username, $created->secret));
        $created = $DB->get_record('user', ['id' => $created->id], '*', MUST_EXIST);
        $this->assertEquals(1, $created->confirmed);
        $this->assertEquals(1, $created->suspended);
    }

    /**
     * Build a user in the same shape as the public signup endpoint.
     *
     * @return \stdClass
     */
    private function make_signup_user(): \stdClass {
        $user = (object) [
            'email' => 'pending@example.com',
            'username' => 'pending@example.com',
            'firstname' => 'Pending',
            'lastname' => 'User',
            'password' => 'Valid_password1!',
            'city' => 'Test city',
            'country' => 'US',
        ];

        return signup_setup_new_user($user);
    }
}
