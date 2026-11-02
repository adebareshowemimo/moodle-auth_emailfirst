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

use auth_emailfirst\local\registration_fields;

/**
 * Keep registration values compatible with the original Starter U fields.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \auth_emailfirst\local\registration_fields
 */
final class registration_fields_test extends \advanced_testcase {
    /**
     * The dropdown must submit the old stable codes, not their display labels.
     */
    public function test_industry_codes_remain_stable(): void {
        $options = registration_fields::industry_options();

        $this->assertCount(27, $options);
        $this->assertSame('Agriculture & Food Production', $options['agriculture_food']);
        $this->assertSame('Retail & E-commerce', $options['retail_ecommerce']);
        $this->assertSame('Other', $options['other']);
        $this->assertTrue(registration_fields::is_other('other'));
        $this->assertFalse(registration_fields::is_other('retail_ecommerce'));
    }

    /**
     * The multiplier field is a text profile field with yes/no stored values.
     */
    public function test_multiplier_values_remain_stable(): void {
        $field = (object)['datatype' => 'text'];

        $this->assertSame(['yes' => 'yes', 'no' => 'no'], registration_fields::yes_no_values($field));
    }
}
