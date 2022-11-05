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

use tool_powerusers\constants;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * @package    tool_powerusers
 * @copyright  2022 David Matamoros <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_powerusers_form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $html = html_writer::tag('div', get_string('formmsg', 'tool_powerusers'));
        $mform->addElement('static', 'formmsg', '', $html);

        $mform->addElement('static', '', '', html_writer::empty_tag('hr'));

        // Type of search (manual or random).
        $mform->addElement('radio', 'type', null, get_string('searchmanual', 'tool_powerusers'), constants::MANUAL);
        $mform->addElement('radio', 'type', null, get_string('searchrandom', 'tool_powerusers'), constants::RANDOM);
        $mform->setType('type', PARAM_INT);
        $mform->setDefault('type', constants::MANUAL);

        // Name to search.
        $mform->addElement('text', 'name', get_string('charactername', 'tool_powerusers'));
        $mform->setType('name', PARAM_TEXT);
        $mform->hideIf('name', 'type', 'noteq', constants::MANUAL);

        // Search accuracy.
        $options = [
            constants::SEARCH_EXACT_MATCH => get_string('searchexactmatch', 'tool_powerusers'),
            constants::SEARCH_STARTS_WITH => get_string('searchstartswith', 'tool_powerusers'),
        ];
        $mform->addElement('select', 'searchaccuracy', get_string('searchaccuracy', 'tool_powerusers'), $options);
        $mform->hideIf('searchaccuracy', 'type', 'noteq', constants::MANUAL);

        // Random quantity search.
        $options = [
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5',
            10 => '10',
        ];
        $mform->addElement('select', 'quantity', get_string('quantity', 'tool_powerusers'), $options);
        $mform->setType('quantity', PARAM_INT);
        $mform->setDefault('quantity', 3);
        $mform->hideIf('quantity', 'type', 'noteq', constants::RANDOM);

        // Password.
        $group = [];
        $group[] =& $mform->createElement('password', 'password');
        $group[] =& $mform->createElement('checkbox', 'randompassword', get_string('randompassword', 'tool_powerusers'), null);
        $mform->setType('password', PARAM_TEXT);
        $mform->addGroup($group, 'passwordgroup', get_string('password', 'moodle'), ' ', false);
        $mform->disabledIf('password', 'randompassword', 'eq', 1);
        $mform->setDefault('randompassword', 1);

        $this->add_action_buttons(false, get_string('generateprogram', 'tool_powerusers'));
    }

    /**
     * Perform some extra moodle validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = [];

        if ((int) $data['type'] === constants::MANUAL && empty($data['name'])) {
            $errors['name'] = get_string('errornoname', 'tool_powerusers');
        }

        return $errors;
    }
}
