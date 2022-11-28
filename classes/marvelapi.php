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

use moodle_exception;
use stdClass;

/**
 * Class marvelapi
 *
 * @package    tool_powerusers
 * @copyright  2022 David Matamoros <davidmc@moodle.com>
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
        $ts = time();
        $privatekey = get_config('tool_powerusers', 'marvelprivatekey');
        $publickey = get_config('tool_powerusers', 'marvelpublickey');

        if (empty($privatekey) || empty($publickey)) {
            throw new moodle_exception(get_string('errorkeys', 'tool_powerusers'));
        }

        $name = str_replace( ' ', '%20', trim($name));
        $hash = md5($ts.$privatekey.$publickey);
        $type = ($type === constants::SEARCH_EXACT_MATCH) ? 'name' : 'nameStartsWith';

        $url = "https://gateway.marvel.com/v1/public/characters?hash=$hash&apikey=$publickey&ts=$ts&$type=$name";
        $content = download_file_content($url, null, null, true);

        return [
            'status' => ((int) $content->status !== 200) ? constants::ERROR : constants::OK,
            'results' => (array) json_decode($content->results),
        ];
    }

    /**
     * Returns user structure with fetched data
     *
     * @param stdClass $data
     * @return array
     */
    public static function get_user_data(stdClass $data): array {
        $name = format_string($data->name);
        [$firstname, $lastname] = explode(' ', "$name ", 2);

        // Some characters don't have last names.
        if ($lastname === null || trim($lastname) === '') {
            $lastname = ' ';
        }

        return [
            'username' => clean_param(strtolower(str_replace(' ', '', $data->name)), PARAM_ALPHANUM),
            'firstname' => $firstname,
            'lastname' => $lastname,
            'urlpicture' => $data->thumbnail->path . '.' . $data->thumbnail->extension,
            'description' => $data->description ?? '',
        ];
    }
}
