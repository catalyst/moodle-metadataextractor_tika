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
 * Strings for component 'metadataextractor_tika', language 'en'
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Tika';
$string['metadata'] = 'Metadata';

// Settings strings.
$string['settings:heading'] = 'Tika settings';
$string['settings:local:heading'] = 'Tika local application settings';
$string['settings:local:pathtotika'] = 'Tika';
$string['settings:local:pathtotika_help'] = 'The path to installed tika application java archive (*.jar file).';
$string['settings:server:heading'] = 'Tika server endpoint settings';
$string['settings:server:host'] = 'Tika host';
$string['settings:server:host_help'] = 'The hostname of the Apache Tika server endpoint';
$string['settings:server:port'] = 'Tika port';
$string['settings:server:port_help'] = 'The port of the Apache Tika server endpoint';
$string['settings:tikaservicetype'] = 'Service type';
$string['settings:tikaservicetype_desc'] = "The type of Tika service implementation:\n
Local Tika application - Tika app installed on Moodle server (requires Java install)\n
Tika server - Use REST API calls to a Tika server";
$string['setting:tikaservicetype:server'] = 'Tika server';
$string['setting:tikaservicetype:local'] = 'Local Tika application';

// Error strings.
$string['error:connectionerror'] = 'Error connecting to Tika server, please check server settings.';
$string['error:invalidservicetype'] = 'Invalid Tika service type set.';
$string['error:local:config'] = 'Local Tika application not installed or incorrectly configured.';
$string['error:missingdependency'] = 'Missing Tika metadataextractor dependency: {$a}.';
$string['error:nometadata'] = 'Tika processing returned no metadata for resource.';
$string['error:resource:contentdoesnotexist'] = 'Content could not be found for {$a->type} ID: {$a->id}.';
$string['error:server'] = 'Tika server responded with an error.';
$string['error:server:ftpnotsupported'] = 'Metadata extraction from FTP urls is not supported.';
$string['error:server:nohostset'] = 'No Tika server hostname or IP address set.';
$string['error:server:httprequest'] = 'Error attempting to make HTTP request.';
$string['error:server:notready'] = 'Tika server is not ready to process HTTP requests.';
$string['error:tikapathnotset'] = 'Path to Tika application jar not set.';
