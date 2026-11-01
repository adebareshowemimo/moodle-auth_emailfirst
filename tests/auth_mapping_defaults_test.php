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

namespace auth_emailfirst;

/**
 * Tests for location-field authentication mapping defaults.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::auth_emailfirst_seed_location_auth_mapping_defaults
 */
final class auth_mapping_defaults_test extends \advanced_testcase {
    /**
     * Missing dynamic settings receive Moodle's core defaults once.
     */
    public function test_defaults_are_seeded_without_overwriting_existing_values(): void {
        $this->resetAfterTest();

        $this->getDataGenerator()->create_custom_profile_field([
            'name' => 'State',
            'shortname' => 'state',
            'datatype' => 'text',
        ]);

        $this->assertSame(11, auth_emailfirst_seed_location_auth_mapping_defaults());
        $this->assertSame('', get_config('auth_db', 'field_map_profile_field_state'));
        $this->assertSame('oncreate', get_config('auth_db', 'field_updatelocal_profile_field_state'));
        $this->assertSame('0', get_config('auth_db', 'field_updateremote_profile_field_state'));
        $this->assertSame('unlocked', get_config('auth_db', 'field_lock_profile_field_state'));
        $this->assertSame('', get_config('auth_ldap', 'field_map_profile_field_state'));
        $this->assertSame('oncreate', get_config('auth_ldap', 'field_updatelocal_profile_field_state'));
        $this->assertSame('0', get_config('auth_ldap', 'field_updateremote_profile_field_state'));
        $this->assertSame('unlocked', get_config('auth_ldap', 'field_lock_profile_field_state'));
        $this->assertSame('', get_config('auth_shibboleth', 'field_map_profile_field_state'));
        $this->assertSame('oncreate', get_config('auth_shibboleth', 'field_updatelocal_profile_field_state'));
        $this->assertSame('unlocked', get_config('auth_shibboleth', 'field_lock_profile_field_state'));

        set_config('field_map_profile_field_state', 'remote_state', 'auth_db');

        $this->assertSame(0, auth_emailfirst_seed_location_auth_mapping_defaults());
        $this->assertSame('remote_state', get_config('auth_db', 'field_map_profile_field_state'));
    }
}
