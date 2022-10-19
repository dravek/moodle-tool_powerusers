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

        // Name to search.
        $mform->addElement('text', 'name', get_string('charactername', 'tool_powerusers'));
        $mform->setType('name', PARAM_TEXT);
        $missingnamestr = get_string('errornoname', 'tool_powerusers');
        $mform->addRule('name', $missingnamestr, 'required', null, 'client');

        // Type of search.
        $options = [
            'exactmatch' => get_string('searchexactmatch', 'tool_powerusers'),
            'startswith' => get_string('searchstartswith', 'tool_powerusers'),
        ];
        $mform->addElement('select', 'searchaccuracy', get_string('searchaccuracy', 'tool_powerusers'), $options);

        $this->add_action_buttons(false, get_string('generateprogram', 'tool_powerusers'));
    }
}
