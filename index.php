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
 * Tika metadata extractor test page.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

defined('MOODLE_INTERNAL') || die();

$fileid  = required_param('fileid', PARAM_INT);
$getcontent = optional_param('getcontent', false, PARAM_RAW);

$fs = get_file_storage();
$file = $fs->get_file_by_id($fileid);

if ($file = $fs->get_file_by_id($fileid)) {
    $extractor = new \metadataextractor_tika\extractor();
    $metadata = $extractor->read_metadata($file);
    if ($getcontent) {
        $content = $extractor->get_content($file);
    }
} else {
    print_error('No such file.');
}
// Build the page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('metadata', 'metadataextractor_tika') . ': ' . $file->get_filename());
var_dump($metadata);
if ($getcontent) {
    var_dump($content);
}
echo $OUTPUT->footer();