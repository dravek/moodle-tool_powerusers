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

/**
 * User end point for interacting with the tool
 *
 * @package    tool_powerusers
 * @copyright  2022 David Carrillo <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

admin_externalpage_setup('tool_powerusers');

$url = new moodle_url('/admin/tool/powerusers/index.php');

$pluginname = get_string('pluginname', 'tool_powerusers');
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title($pluginname);
$PAGE->set_heading($pluginname);

$mform = new \tool_powerusers\form(null, []);

// Process Form data.
if ($mform->is_cancelled()) {
    redirect($url);
} else if ($data = $mform->get_data()) {

    $generator = new \tool_powerusers\generator();
    [$status, $count, $message] = $generator->generate_users($data);

    if (!$status) {
        $string = get_string('error', 'tool_powerusers', $message);
        redirect($url, $string, null, \core\output\notification::NOTIFY_ERROR);
    }

    $string = get_string('userscreated', 'tool_powerusers', $count);
    redirect($url, $string, null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display form.
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
