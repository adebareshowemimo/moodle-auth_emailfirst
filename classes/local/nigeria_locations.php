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
 * Nigeria state and local government area lookup.
 *
 * @package    auth_emailfirst
 * @copyright  2026 Course Commerce Pro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class nigeria_locations {
    /** @var array|null Cached location data. */
    private static ?array $locations = null;

    /**
     * Return all states and their LGAs.
     *
     * @return array
     */
    public static function all(): array {
        if (self::$locations !== null) {
            return self::$locations;
        }

        $path = __DIR__ . '/../../data/nigeria_states_lgas.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new \coding_exception('Unable to read the bundled Nigeria location data.');
        }

        $locations = json_decode($json, true);
        if (!is_array($locations) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \coding_exception('The bundled Nigeria location data is invalid.');
        }

        self::$locations = $locations;
        return self::$locations;
    }

    /**
     * Return the state names.
     *
     * @return string[]
     */
    public static function states(): array {
        return array_keys(self::all());
    }

    /**
     * Return LGAs for a state.
     *
     * @param string $state State name.
     * @return string[]
     */
    public static function lgas_for_state(string $state): array {
        return self::all()[$state] ?? [];
    }

    /**
     * Check a state/LGA pair.
     *
     * @param string $state State name.
     * @param string $lga LGA name.
     * @return bool
     */
    public static function is_valid(string $state, string $lga): bool {
        return isset(self::all()[$state]) && in_array($lga, self::all()[$state], true);
    }
}
