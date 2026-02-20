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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'aiplacement_textprocessor',
        get_string('pluginname', 'aiplacement_textprocessor'),
        'moodle/site:config'
    );

    // Information only - all configuration is in AI Manager.
    $settings->add(new admin_setting_heading('info_heading',
        get_string('info_heading', 'aiplacement_textprocessor'),
        get_string('info_heading_desc', 'aiplacement_textprocessor')
    ));

    $ADMIN->add('ai', $settings);
}