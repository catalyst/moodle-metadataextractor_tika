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
 * The main api for handling file metadata.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings->add(new admin_setting_heading('tikasettings',
        get_string('settings:heading', 'metadataextractor_tika'), ''));

    $tikaservicetypes = [
        \metadataextractor_tika\extractor::SERVICETYPE_LOCAL => get_string('setting:tikaservicetype:local', 'metadataextractor_tika'),
        \metadataextractor_tika\extractor::SERVICETYPE_SERVER => get_string('setting:tikaservicetype:server', 'metadataextractor_tika')
    ];
    
    $settings->add(new admin_setting_configselect('tikaservicetype',
        get_string('settings:tikaservicetype', 'metadataextractor_tika'),
        get_string('settings:tikaservicetype_desc', 'metadataextractor_tika'),
        \metadataextractor_tika\extractor::SERVICETYPE_LOCAL, $tikaservicetypes));

    if (!empty($CFG->tikaservicetype) && $CFG->tikaservicetype == \metadataextractor_tika\extractor::SERVICETYPE_LOCAL) {
        $settings->add(new admin_setting_heading('tikalocalsettings',
            get_string('settings:local:heading', 'metadataextractor_tika'), ''));

        $settings->add(new admin_setting_configtext('tikalocalpath',
            get_string('settings:local:pathtotika', 'metadataextractor_tika'),
            get_string('settings:local:pathtotika_help', 'metadataextractor_tika'),
            '/usr/bin/tika-app-1.22.jar'));

    } elseif (!empty($CFG->tikaservicetype) && $CFG->tikaservicetype == \metadataextractor_tika\extractor::SERVICETYPE_SERVER) {
        $settings->add(new admin_setting_heading('tikaserversettings',
            get_string('settings:server:heading', 'metadataextractor_tika'), ''));

        $settings->add(new admin_setting_configtext('tikaserverhost',
            get_string('settings:server:host', 'metadataextractor_tika'),
            get_string('settings:server:host_help', 'metadataextractor_tika'),
            $CFG->wwwroot, PARAM_URL));

        $settings->add(new admin_setting_configtext('tikaserverport',
            get_string('settings:server:port', 'metadataextractor_tika'),
            get_string('settings:server:port_help', 'metadataextractor_tika'),
            9998, PARAM_INT));

    }
}
