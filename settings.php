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
 * Settings for tool_powerusers.
 *
 * @var admin_category $ADMIN
 * @package   tool_powerusers
 * @copyright 2022 David Carrillo <davidmc@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('development', new admin_externalpage(
    'tool_powerusers',
    get_string('pluginname', 'tool_powerusers'),
    new moodle_url('/admin/tool/powerusers/index.php')
));

if ($hassiteconfig) {
    $ADMIN->add('root', new admin_category('tool_powerusers_settings_root',
        new lang_string('pluginname', 'tool_powerusers')),
        'location');

    $temp = new admin_settingpage('tool_powerusers_settings',
        new lang_string('tool_poweruserssettings', 'tool_powerusers'));

    $temp->add(new admin_setting_heading('tool_powerusers/comment', '',
        new lang_string('settingsmsg', 'tool_powerusers')));

    $temp->add(new admin_setting_configtext('tool_powerusers/marvelprivatekey',
        new lang_string('privatekey', 'tool_powerusers'),
        new lang_string('privatekey_desc', 'tool_powerusers'), '', PARAM_TEXT));

    $temp->add(new admin_setting_configtext('tool_powerusers/marvelpublickey',
        new lang_string('publickey', 'tool_powerusers'),
        new lang_string('publickey_desc', 'tool_powerusers'), '', PARAM_TEXT));

    $ADMIN->add('tool_powerusers_settings_root', $temp);
}
