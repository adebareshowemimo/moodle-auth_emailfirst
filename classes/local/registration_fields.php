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

namespace auth_emailfirst\local;

/**
 * Profile fields collected before the account signup form.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class registration_fields {
    /** @var string[] Profile field shortnames collected during age verification. */
    public const SHORTNAMES = ['agesignup', 'industry', 'industryother', 'entrepreneurmultiplier'];

    /** @var string[] Stable industry codes used by the original Starter U registration form. */
    private const INDUSTRIES = [
        'agriculture_food',
        'automotive',
        'beauty_personal_care',
        'construction',
        'consulting_professional',
        'education_training',
        'energy_utilities',
        'fashion_apparel',
        'financial_insurance',
        'fitness_health_wellness',
        'food_beverage',
        'government_public',
        'healthcare_medical',
        'hospitality_travel',
        'legal',
        'manufacturing_industrial',
        'marketing_advertising_pr',
        'media_arts_entertainment',
        'nonprofit_social_impact',
        'personal_home_services',
        'real_estate',
        'retail_ecommerce',
        'software_technology',
        'telecommunications',
        'transportation_logistics',
        'wholesale_distribution',
        'other',
    ];

    /**
     * Load the existing Moodle profile field definitions.
     *
     * @return array<string, \stdClass>
     */
    public static function definitions(): array {
        global $DB;

        $records = $DB->get_records_list('user_info_field', 'shortname', self::SHORTNAMES);
        $definitions = [];
        foreach ($records as $record) {
            $definitions[$record->shortname] = $record;
        }
        return $definitions;
    }

    /**
     * Get the industry codes and labels from the original registration flow.
     *
     * The Industry profile field is plain text. These codes are what existing
     * users have in user_info_data, so their values must remain stable.
     *
     * @return array<string, string>
     */
    public static function industry_options(): array {
        $options = [];
        foreach (self::INDUSTRIES as $key) {
            $options[$key] = get_string('industry_' . $key, 'auth_emailfirst');
        }
        return $options;
    }

    /**
     * Whether the industry option means that a free-text answer is needed.
     *
     * @param string $industry Selected menu value.
     * @return bool
     */
    public static function is_other(string $industry): bool {
        return \core_text::strtolower(trim($industry)) === 'other';
    }

    /**
     * Find the configured Yes and No values for a profile field.
     *
     * @param \stdClass|null $field Profile field definition.
     * @return array<string, string>
     */
    public static function yes_no_values(?\stdClass $field): array {
        if ($field && $field->datatype === 'checkbox') {
            return ['yes' => '1', 'no' => '0'];
        }
        if (!$field || $field->datatype === 'text') {
            return ['yes' => 'yes', 'no' => 'no'];
        }
        $values = [];
        foreach (preg_split('/\R/', (string)$field->param1) as $option) {
            $option = trim($option);
            $normalised = \core_text::strtolower($option);
            if ($normalised === 'yes' || $normalised === 'no') {
                $values[$normalised] = $option;
            }
        }
        return $values;
    }
}
