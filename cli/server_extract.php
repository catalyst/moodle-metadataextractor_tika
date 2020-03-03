<?php

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
        'json' => false
    ], [
        'f' => 'fileid',
        'h' => 'help',
        'j' => 'json'
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

Options:
-h, --help               Print out this help
-f --fileid (required)   The file id of the Moodle instance file to extract metadata for
--showdebugging          Print debugging statements
-j --json                Print metadata as a raw json string
--host (optional)        Set the hostname or IP address of the tika server. 
                         WARNING: This will change the plugin configured host
--port (optional)        Set the port number on the host where tika server API is exposed
                         WARNING: This will change the plugin configured port number 

Example:
\$ php admin/tool/metadata/extractor/tika/cli/server_extract.php -f=10999
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

$jsonmetadata = $server->get_file_metadata($file);
$metadata = json_decode($jsonmetadata, true);
if (empty($options['json'])) {
    mtrace(var_dump($metadata));
} else {
    mtrace($jsonmetadata);
}
exit(0);
