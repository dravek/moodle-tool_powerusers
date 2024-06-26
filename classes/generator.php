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
use moodle_exception;
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
     * @return array [status, count, message]
     */
    public function generate_users(stdClass $data): array {
        $created = 0;

        $password = (!empty($data->randompassword)) ? generate_password() : trim($data->password);

        // Generate users manually entering the name.
        if ((int) $data->type === constants::MANUAL) {
            $apidata = marvelapi::get_users($data->name, $data->searchaccuracy);

            if ($apidata['status'] === constants::ERROR) {
                return [false, 0, $apidata['results']['code'] . ':' . $apidata['results']['message']];
            }

            if ((int) $apidata['results']['data']->count === 0) {
                return [false, 0, get_string('errornousers', 'tool_powerusers')];
            }

            foreach ($apidata['results']['data']->results as $result) {
                $user = marvelapi::get_user_data($result);
                $user['password'] = $password;
                if ($this->create_user($user)) {
                    $created++;
                }
            }
        } else {
            // Generate users randomly from a list.
            $names = array_values(json_decode(file_get_contents(constants::FILENAME), false));
            $total = count($names) - 1;

            while ($created < (int) $data->quantity) {
                $randomnumber = random_int(0, $total);

                $apidata = marvelapi::get_users($names[$randomnumber], constants::SEARCH_EXACT_MATCH);

                if ($apidata['status'] === constants::ERROR) {
                    return [false, 0, $apidata['results']['code'] . ':' . $apidata['results']['message']];
                }

                if ((int) $apidata['results']['data']->count === 0) {
                    continue;
                }

                $result = reset($apidata['results']['data']->results);
                $user = marvelapi::get_user_data($result);
                $user['password'] = $password;
                if ($this->create_user($user)) {
                    $created++;
                }
            }
        }

        return [true, $created, ''];
    }

    /**
     * Create a user with the profile picture
     *
     * @param array $record
     * @return bool
     */
    private function create_user(array $record): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/user/lib.php');
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
           'skipcertverify' => true,
           'connecttimeout' => 5,
        ];

        try {
            $fs->create_file_from_url($filerecord, $record['urlpicture'], $urlparams);
        } catch (file_exception $e) {
            throw new moodle_exception(get_string($e->errorcode, $e->module, $e->a));
        }

        $iconfile = $fs->get_area_files($context->id, 'user', 'newicon', false, 'itemid', false);

        // There should only be one.
        $iconfile = reset($iconfile);

        // Something went wrong while creating temp file - remove the uploaded file.
        if (!$iconfile = $iconfile->copy_content_to_temp()) {
            $fs->delete_area_files($context->id, 'user', 'newicon');
            throw new moodle_exception('There was a problem copying the profile picture to temp.');
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
}
