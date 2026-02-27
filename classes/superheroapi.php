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
use moodle_url;
use stdClass;

/**
 * Class superheroapi
 *
 * @package    tool_powerusers
 * @copyright  2026 David Carrillo <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class superheroapi {
    /** @var string API base URL. */
    private const BASE_URL = 'https://superheroapi.com/api';

    /**
     * Call SuperHero API to get requested user(s).
     *
     * @param string $name
     * @param string $type
     * @return array [status, results]
     */
    public static function get_users(string $name, string $type): array {
        $token = trim((string) get_config('tool_powerusers', 'superheroapitoken'));
        if ($token === '') {
            throw new moodle_exception(
                'errorkeys',
                'tool_powerusers',
                new moodle_url('/admin/settings.php', ['section' => 'tool_powerusers_settings'])
            );
        }

        $name = trim($name);
        if ($name === '') {
            return ['status' => constants::ERROR, 'results' => ['message' => get_string('errornoname', 'tool_powerusers')]];
        }

        $url = self::BASE_URL . "/{$token}/search/" . rawurlencode($name);
        $content = download_file_content($url, null, null, true);
        $json = json_decode((string) ($content->results ?? ''), true);

        if (empty($json) || ($json['response'] ?? 'error') !== 'success') {
            $message = $json['error'] ?? get_string('errornousers', 'tool_powerusers');
            return ['status' => constants::ERROR, 'results' => ['message' => $message]];
        }

        $results = (array) ($json['results'] ?? []);
        if ($type === constants::SEARCH_EXACT_MATCH) {
            $targetname = \core_text::strtolower($name);
            $results = array_values(array_filter($results, function (array $result) use ($targetname): bool {
                return \core_text::strtolower(trim((string) ($result['name'] ?? ''))) === $targetname;
            }));
        } else {
            $targetname = \core_text::strtolower($name);
            $results = array_values(array_filter($results, function (array $result) use ($targetname): bool {
                $resultname = \core_text::strtolower(trim((string) ($result['name'] ?? '')));
                return strpos($resultname, $targetname) === 0;
            }));
        }

        return ['status' => constants::OK, 'results' => $results];
    }

    /**
     * Returns user structure with fetched data.
     *
     * @param stdClass $data
     * @return array
     */
    public static function get_user_data(stdClass $data): array {
        $name = format_string((string) ($data->name ?? ''));
        [$firstname, $lastname] = explode(' ', "$name ", 2);

        if ($lastname === null || trim($lastname) === '') {
            $lastname = ' ';
        }

        $description = '';
        $biography = $data->biography ?? null;
        if (is_array($biography)) {
            $biography = (object) $biography;
        }
        if ($biography instanceof stdClass) {
            $full = trim((string) ($biography->{'full-name'} ?? ''));
            $publisher = trim((string) ($biography->publisher ?? ''));
            $firstappearance = trim((string) ($biography->{'first-appearance'} ?? ''));
            $parts = array_filter([$full ? "Full name: {$full}" : '', $publisher ? "Publisher: {$publisher}" : '',
                $firstappearance ? "First appearance: {$firstappearance}" : '']);
            $description = implode(' | ', $parts);
        }

        $urlpicture = '';
        $image = $data->image ?? null;
        if (is_array($image)) {
            $image = (object) $image;
        }
        if ($image instanceof stdClass) {
            $urlpicture = (string) ($image->url ?? '');
        }

        return [
            'username' => clean_param(strtolower(str_replace(' ', '', (string) ($data->name ?? ''))), PARAM_ALPHANUM),
            'firstname' => $firstname,
            'lastname' => $lastname,
            'urlpicture' => $urlpicture,
            'description' => $description,
        ];
    }
}
