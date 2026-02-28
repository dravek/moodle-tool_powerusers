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

use context_user;
use file_exception;
use stdClass;

/**
 * Class generator
 *
 * @package    tool_powerusers
 * @copyright  2022 David Carrillo <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator {
    /**
     * Generate users from the API
     *
     * @param stdClass $data
     * @return array [status, count, message, names]
     */
    public function generate_users(stdClass $data): array {
        $created = 0;
        $creatednames = [];
        $maxattempts = 100;

        $password = (!empty($data->randompassword)) ? generate_password() : trim($data->password);

        // Generate users manually entering the name.
        if ((int) $data->type === constants::MANUAL) {
            if (empty($data->name)) {
                return [false, 0, get_string('errornoname', 'tool_powerusers'), []];
            }
            $apidata = $this->get_users_from_api($data->name, $data->searchaccuracy);

            if ($apidata['status'] === constants::ERROR) {
                return [false, 0, (string) ($apidata['results']['message'] ?? get_string('errornousers', 'tool_powerusers')), []];
            }

            if (count($apidata['results']) === 0) {
                return [false, 0, get_string('errornousers', 'tool_powerusers'), []];
            }

            foreach ($apidata['results'] as $result) {
                $user = $this->map_user_data((object) $result);
                $user['password'] = $password;
                if ($this->create_user($user)) {
                    $created++;
                    $creatednames[] = trim($user['firstname'] . ' ' . $user['lastname']);
                }
            }

            if ($created === 0) {
                return [false, 0, get_string('erroruserexists', 'tool_powerusers'), []];
            }
        } else {
            // Generate users randomly from a list.
            $names = $this->load_random_names();
            if ($names === null) {
                return [false, 0, get_string('errormsg', 'tool_powerusers'), []];
            }

            if (count($names) === 0) {
                return [false, 0, get_string('errornousers', 'tool_powerusers'), []];
            }

            $total = count($names) - 1;

            $foundatleastone = false;
            $attempts = 0;
            while ($created < (int) $data->quantity && $attempts < $maxattempts) {
                $attempts++;
                $randomnumber = random_int(0, $total);

                $apidata = $this->get_users_from_api($names[$randomnumber], constants::SEARCH_EXACT_MATCH);

                if ($apidata['status'] === constants::ERROR) {
                    continue;
                }

                if (count($apidata['results']) === 0) {
                    continue;
                }

                $foundatleastone = true;
                $result = reset($apidata['results']);
                $user = $this->map_user_data((object) $result);
                $user['password'] = $password;
                if ($this->create_user($user)) {
                    $created++;
                    $creatednames[] = trim($user['firstname'] . ' ' . $user['lastname']);
                }
            }

            if ($created === 0) {
                $error = ($foundatleastone) ? 'erroruserexists' : 'errornousers';
                return [false, 0, get_string($error, 'tool_powerusers'), []];
            }
        }

        return [true, $created, '', $creatednames];
    }

    /**
     * Create a user with the profile picture
     *
     * @param array $record
     * @return bool
     */
    protected function create_user(array $record): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->libdir . '/gdlib.php');

        // Check if username is taken. For now skip.
        if ($DB->get_record('user', ['username' => $record['username']])) {
            return false;
        }

        $record['auth'] = 'manual';
        $record['firstnamephonetic'] = '';
        $record['lastnamephonetic'] = '';
        $record['middlename'] = '';
        $record['alternatename'] = '';
        $record['idnumber'] = '';
        $record['mnethostid'] = $CFG->mnet_localhost_id;
        $record['password'] = hash_internal_user_password($record['password']);
        $record['email'] = $record['username'] . '@example.com';
        $record['confirmed'] = 1;
        $record['lastip'] = '0.0.0.0';
        $record['picture'] = 0;
        $record['lang'] = '';

        $userid = user_create_user((object) $record, false, false);

        if ($extrafields = array_intersect_key($record, ['password' => 1, 'timecreated' => 1])) {
            $DB->update_record('user', (object) (['id' => $userid] + $extrafields));
        }

        $context = context_user::instance($userid, MUST_EXIST);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'user', 'newicon');

        $filerecord = [
           'contextid' => $context->id,
           'component' => 'user',
           'filearea' => 'newicon',
           'itemid' => 0,
           'filepath' => '/',
        ];

        $urlparams = [
           'calctimeout' => false,
           'timeout' => 5,
           'connecttimeout' => 5,
        ];

        if (empty($record['urlpicture']) || !$this->is_supported_picture_url((string) $record['urlpicture'])) {
            \core\event\user_created::create_from_userid($userid)->trigger();
            return true;
        }

        try {
            $fs->create_file_from_url($filerecord, $record['urlpicture'], $urlparams);
        } catch (file_exception $e) {
            debugging('tool_powerusers: Unable to download user image: ' . $e->getMessage(), DEBUG_DEVELOPER);
            \core\event\user_created::create_from_userid($userid)->trigger();
            return true;
        }

        $iconfile = $fs->get_area_files($context->id, 'user', 'newicon', false, 'itemid', false);

        // There should only be one.
        $iconfile = reset($iconfile);

        // Something went wrong while creating temp file - remove the uploaded file.
        if (!$iconfile = $iconfile->copy_content_to_temp()) {
            $fs->delete_area_files($context->id, 'user', 'newicon');
            \core\event\user_created::create_from_userid($userid)->trigger();
            return true;
        }

        // Copy file to temporary location and the send it for processing icon.
        $newpicture = (int) process_new_icon($context, 'user', 'icon', 0, $iconfile);
        // Delete temporary file.
        @unlink($iconfile);
        // Remove uploaded file.
        $fs->delete_area_files($context->id, 'user', 'newicon');
        // Set the user's picture.
        $DB->set_field('user', 'picture', $newpicture, ['id' => $userid]);

        \core\event\user_created::create_from_userid($userid)->trigger();

        return true;
    }

    /**
     * Wrapper to fetch users from API.
     *
     * @param string $name
     * @param string $type
     * @return array
     */
    protected function get_users_from_api(string $name, string $type): array {
        return superheroapi::get_users($name, $type);
    }

    /**
     * Wrapper to map API user payload.
     *
     * @param stdClass $data
     * @return array
     */
    protected function map_user_data(stdClass $data): array {
        return superheroapi::get_user_data($data);
    }

    /**
     * Load random names list from plugin JSON file.
     *
     * @return array|null Null when list cannot be loaded or parsed.
     */
    protected function load_random_names(): ?array {
        $namesfile = dirname(__DIR__) . '/' . constants::FILENAME;
        if (!is_readable($namesfile)) {
            return null;
        }

        $content = file_get_contents($namesfile);
        if ($content === false) {
            return null;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return null;
        }

        $names = [];
        foreach ($decoded as $name) {
            if (!is_string($name)) {
                continue;
            }
            $name = trim($name);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values($names);
    }

    /**
     * Validate that image URL has a supported remote scheme.
     *
     * @param string $url
     * @return bool
     */
    private function is_supported_picture_url(string $url): bool {
        $parts = parse_url(trim($url));
        if ($parts === false || !is_array($parts)) {
            return false;
        }
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        return in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true);
    }
}
