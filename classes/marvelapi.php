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

namespace tool_powerusers;

use stdClass;

/**
 * Class marvelapi
 *
 * @deprecated since 2026-02-27 use \tool_powerusers\superheroapi instead.
 *
 * @package    tool_powerusers
 * @copyright  2022 David Carrillo <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class marvelapi {
    /**
     * Call Marvel API to get the requested user(s)
     *
     * @param string $name
     * @param string $type
     * @return array [status, results]
     */
    public static function get_users(string $name, string $type): array {
        debugging('tool_powerusers\\marvelapi is deprecated, use tool_powerusers\\superheroapi.', DEBUG_DEVELOPER);
        return superheroapi::get_users($name, $type);
    }

    /**
     * Returns user structure with fetched data
     *
     * @param stdClass $data
     * @return array
     */
    public static function get_user_data(stdClass $data): array {
        debugging('tool_powerusers\\marvelapi is deprecated, use tool_powerusers\\superheroapi.', DEBUG_DEVELOPER);
        return superheroapi::get_user_data($data);
    }
}
