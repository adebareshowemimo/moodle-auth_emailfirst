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
 * Referral choices used on the Starter U signup form.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class referral_options {
    /** @var string[] Original choice keys, including the catch-all last. */
    private const DEFAULT_KEYS = [
        'search_engine', 'social_media', 'friend_family', 'colleague', 'online_ad',
        'blog_article', 'email_newsletter', 'event_conference', 'youtube', 'podcast',
        'billboard', 'ai_chat', 'black_chamber_raleigh', 'carolinas_aacc',
        'flywheel_foundation', 'livingstone_college', 'mitchell_cc_sbc',
        'rocky_mount_chamber', 'southeastern_cc_sbc', 'other',
    ];

    /**
     * Return active choices, preserving existing site-specific wording and order.
     *
     * The former plugin stored editable options in auth_emailfirst_referral. Older
     * installations without that table still get the original Starter U choices.
     *
     * @param bool $enabledonly Include only choices currently available at signup.
     * @return array<string, string> Choice key to display label.
     */
    public static function choices(bool $enabledonly = true): array {
        global $DB;

        if ($DB->get_manager()->table_exists('auth_emailfirst_referral')) {
            $records = $DB->get_records(
                'auth_emailfirst_referral',
                $enabledonly ? ['enabled' => 1] : null,
                'sortorder ASC, id ASC'
            );
            $choices = [];
            foreach ($records as $record) {
                $choices[$record->optionkey] = format_string(
                    $record->label,
                    true,
                    ['context' => \context_system::instance()]
                );
            }
            return $choices;
        }

        $choices = [];
        foreach (self::DEFAULT_KEYS as $key) {
            $choices[$key] = get_string('referral_' . $key, 'auth_emailfirst');
        }
        return $choices;
    }
}
