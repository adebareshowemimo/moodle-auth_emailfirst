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

use auth_emailfirst\local\nigeria_locations;

/**
 * Tests for the bundled Nigeria location data.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_emailfirst\local\nigeria_locations
 */
final class nigeria_locations_test extends \basic_testcase {
    /**
     * The dataset contains every state, the FCT, and all 774 LGAs.
     */
    public function test_dataset_is_complete(): void {
        $locations = nigeria_locations::all();

        $this->assertCount(37, $locations);
        $this->assertSame(774, array_sum(array_map('count', $locations)));
        $this->assertCount(6, $locations['Federal Capital Territory']);
        $this->assertCount(13, $locations['Nasarawa']);
    }

    /**
     * LGA validation requires a real pairing with the selected state.
     */
    public function test_state_lga_pair_validation(): void {
        $this->assertTrue(nigeria_locations::is_valid('Lagos', 'Ikeja'));
        $this->assertFalse(nigeria_locations::is_valid('Ogun', 'Ikeja'));
        $this->assertFalse(nigeria_locations::is_valid('Not a state', 'Ikeja'));
    }
}
