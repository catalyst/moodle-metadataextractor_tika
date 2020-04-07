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
 * Admin settings for metadataextractor_tika.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings->add(new admin_setting_heading('metadataextractor_tika/tikasettings',
        get_string('settings:heading', 'metadataextractor_tika'), ''));

    $tikaservicetypes = [
        \metadataextractor_tika\extractor::SERVICETYPE_LOCAL => get_string('setting:tikaservicetype:local',
            'metadataextractor_tika'),
        \metadataextractor_tika\extractor::SERVICETYPE_SERVER => get_string('setting:tikaservicetype:server',
            'metadataextractor_tika')
    ];

    $settings->add(new admin_setting_configselect('metadataextractor_tika/tikaservicetype',
        get_string('settings:tikaservicetype', 'metadataextractor_tika'),
        get_string('settings:tikaservicetype_desc', 'metadataextractor_tika'),
        \metadataextractor_tika\extractor::SERVICETYPE_LOCAL, $tikaservicetypes));

    $servicetype = get_config('metadataextractor_tika', 'tikaservicetype');

    // Conditionally display configurable settings based on tika service type.
    if (!empty($servicetype) && $servicetype == \metadataextractor_tika\extractor::SERVICETYPE_LOCAL) {
        $settings->add(new admin_setting_heading('metadataextractor_tika/tikalocalsettings',
            get_string('settings:local:heading', 'metadataextractor_tika'), ''));

        $settings->add(new admin_setting_configfile('metadataextractor_tika/tikalocalpath',
            get_string('settings:local:pathtotika', 'metadataextractor_tika'),
            get_string('settings:local:pathtotika_help', 'metadataextractor_tika'),
            '/usr/bin/tika-app-1.23.jar'));

    } elseif (!empty($servicetype) && $servicetype == \metadataextractor_tika\extractor::SERVICETYPE_SERVER) {
        $settings->add(new admin_setting_heading('metadataextractor_tika/tikaserversettings',
            get_string('settings:server:heading', 'metadataextractor_tika'), ''));

        $settings->add(new admin_setting_configtext('metadataextractor_tika/tikaserverhost',
            get_string('settings:server:host', 'metadataextractor_tika'),
            get_string('settings:server:host_help', 'metadataextractor_tika'),
            $CFG->wwwroot, PARAM_URL));

        $settings->add(new admin_setting_configtext('metadataextractor_tika/tikaserverport',
            get_string('settings:server:port', 'metadataextractor_tika'),
            get_string('settings:server:port_help', 'metadataextractor_tika'),
            9998, PARAM_INT));

    }
}
