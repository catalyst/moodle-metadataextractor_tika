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
 * Tika server raw metadata CLI extractor.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'host' => 'localhost',
        'port' => 0,
        'fileid' => 0,
        'help' => false,
        'showdebugging' => false,
        'json' => false,
        'metadata' => false,
        'text' => false,
        'connection' => false
    ], [
        'f' => 'fileid',
        'h' => 'help',
        'j' => 'json',
        'm' => 'metadata',
        't' => 'text',
        'c' => 'connection'
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    mtrace(
<<<HELP
Extract json metadata from a file using a remote tika server using RESTful API.

Required:
-f --fileid              The file id of the Moodle instance file to extract metadata for OR
-c --connection          Test the server connection - returns success or failure message and HTTP response code

-j --json                Print metadata as a raw json string OR
-m --metadata            Print metadata as a vardump OR
-t --text                Print Tika extracted text content of file

Options:
-h, --help               Print out this help
--showdebugging          Print debugging statements
--host                   Set the hostname or IP address of the tika server. 
                         Note: Required if not set in Moodle instance
--port                   Set the port number on the host where tika server API is exposed
                         Note: Required if not set in Moodle instance

Example:
\$ php admin/tool/metadata/extractor/tika/cli/server_extract.php -f=10999 -m
HELP
    );
    exit(0);
}

if ($options['showdebugging']) {
    set_debugging(DEBUG_DEVELOPER, true);
}

$host = get_config('metadataextractor_tika', 'tikaserverhost');
$port = get_config('metadataextractor_tika', 'tikaserverport');

if (!empty($options['host'])) {
    set_config('tikaserverhost', $options['host'], 'metadataextractor_tika');
    $host = $options['host'];
} elseif (empty($host)) {
    mtrace('No host name set for tika server, pass in host value or set host in plugin settings.');
    exit(1);
}

if (!empty($options['port'])) {
    set_config('tikaserverport', $options['port'], 'metadataextractor_tika');
    $port = $options['port'];
} elseif (empty($port)) {
    mtrace('No port value set for tika server, pass in port number or set port number in plugin settings.');
    exit(1);
}

if (!empty($options['connection'])) {
    $server = new \metadataextractor_tika\server();

    try {
        $response = $server->test_connection();
        $statuscode = $response->getStatusCode();

        if ($statuscode == 200) {
            mtrace('Connection successful - HTTP Status: ' . $statuscode);
            exit(0);
        } else {
            mtrace('Connection failed - HTTP Status: ' . $statuscode);
            exit(1);
        }
    } catch (moodle_exception $exception) {
        mtrace('Connection failed - No HTTP status code returned');
        exit(1);
    }
}

if (empty($options['fileid'])) {
    mtrace('No file id value, you must pass in the id of a file to extract metadata for.');
    exit(1);
} elseif (!is_number($options['fileid'])) {
    mtrace('File id must be a number.');
    exit(1);
} else {
    $fs = get_file_storage();
    $file = $fs->get_file_by_id($options['fileid']);
}

if(empty($file)) {
    mtrace('No file found with id=' . $options['fileid'] . ' in Moodle instance.');
    exit(1);
}

$server = new \metadataextractor_tika\server();

if (!$server->is_ready()) {
    mtrace('Could not connect to server at ' . $host . ':' . $port);
    exit(1);
}

if (!empty($options['metadata']) || !empty($options['json']) ) {

    $jsonmetadata = $server->get_file_metadata($file);
    $metadata = json_decode($jsonmetadata, true);
    if (empty($options['json'])) {
        mtrace(var_dump($metadata));
    } else {
        mtrace($jsonmetadata);
    }
    exit(0);

} elseif (!empty($options['text'])) {

    $content = $server->get_file_content($file);
    mtrace($content);
    exit(0);

} else {
    mtrace('No valid output format selected, much choose either -j, -m or -t option.');
    mtrace('See help for further information: \$ php admin/tool/metadata/extractor/tika/cli/server_extract.php --help');
    exit(1);
}
