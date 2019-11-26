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

namespace metadataextractor_tika;

use stored_file;
use tool_metadata\extraction_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Interface describing the strategy for extracting metadata from a Moodle stored_file resource.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class extractor extends \tool_metadata\extractor implements \tool_metadata\extractor_strategy {

    /**
     * Metadata type - An aggregation of resources.
     */
    const DCMI_TYPE_COLLECTION = 'collection';

    /**
     * Metadata type - Data encoded in a defined structure.
     */
    const DCMI_TYPE_DATASET = 'dataset';

    /**
     * Metadata type - A non-persistent, time-based occurrence.
     */
    const DCMI_TYPE_EVENT = 'event';

    /**
     * Metadata type - A resource requiring interaction from the user to be understood, executed, or experienced.
     */
    const DCMI_TYPE_INTERACTIVE = 'interactiveresource';

    /**
     * Metadata type - A visual representation other than text.
     */
    const DCMI_TYPE_IMAGE = 'image';

    /**
     * Metadata type - A series of visual representations imparting an impression of motion when shown in succession.
     */
    const DCMI_TYPE_MOVINGIMAGE = 'movingimage';

    /**
     * Metadata type - An inanimate, three-dimensional object or substance.
     */
    const DCMI_TYPE_PHYSICAL = 'physicalobject';

    /**
     * Metadata type - A system that provides one or more functions.
     */
    const DCMI_TYPE_SERVICE = 'service';

    /**
     * Metadata type - A computer program in source or compiled form.
     */
    const DCMI_TYPE_SOFTWARE = 'software';

    /**
     * Metadata type - A resource primarily intended to be heard.
     */
    const DCMI_TYPE_SOUND = 'sound';

    /**
     * Metadata type - A static visual representation.
     */
    const DCMI_TYPE_STILLIMAGE = 'stillimage';

    /**
     * Metadata type - A resource consisting primarily of words for reading.
     */
    const DCMI_TYPE_TEXT = 'text';

    /**
     * The plugin name.
     */
    const METADATAEXTRACTOR_NAME = 'tika';

    /**
     * Table name for storing extracted metadata for this extractor.
     */
    const METADATA_TABLE = 'metadataextractor_tika';

    /**
     * A map of the Moodle mimetype groups to DCMI types.
     */
    const DCMI_TYPE_MIMETYPE_GROUP_MAP = [
        self::DCMI_TYPE_MOVINGIMAGE => ['video', 'html_video', 'web_video'],
        self::DCMI_TYPE_SOUND => ['audio', 'html_audio', 'web_audio', 'html_track'],
        self::DCMI_TYPE_IMAGE => ['image', 'web_image'],
        self::DCMI_TYPE_COLLECTION => ['archive'],
        self::DCMI_TYPE_TEXT => ['web_file', 'document', 'spreadsheet', 'presentation'],
    ];

    /**
     * @var string path to jar file for executing tika commands.
     */
    private $path;

    public function __construct() {
        global $CFG;

        $this->path = $CFG->pathtotika;
    }

    /**
     * Attempt to create file metadata.
     *
     * @param \stored_file $file the file to create metadata for.
     * @throws \tool_metadata\extraction_exception
     *
     * @return bool true if metadata successfully extracted, false otherwise.
     */
    public function create_file_metadata(stored_file $file){
        global $DB;

        $success = false;

        $fs = get_file_storage();
        $filesystem = $fs->get_file_system();

        if (!$file->is_directory()) {
            $metadata = null;
            $localpath = $filesystem->get_local_path_from_storedfile($file, true);
            $cmd = 'java -jar ' . $this->path . ' -j ' . $localpath;
            $rawmetadata = shell_exec($cmd);

            if ($rawmetadata) {
                // Use associative array as some key names may not parse well
                // into php stdClass (eg. 'Content-Type').
                $metadataarray = json_decode($rawmetadata, true);
            } else {
                throw new extraction_exception('error:extractionfailed', 'tool_metadata');
            }

            if ($metadataarray) {
                $metadataobject = $this->build_metadata($metadataarray, $file);
                if ($this->get_metadata_id($file->get_contenthash())) {
                    $result = $DB->insert_record(self::METADATA_TABLE, $metadataobject);
                } else {
                    $result = $DB->update_record(self::METADATA_TABLE, $metadataobject);
                }
                if ($result) {
                    $success = true;
                } else {
                    throw new extraction_exception('error:extractionfieldparse');
                }
            }
        }
        return $success;
    }

    /**
     * Build a metadata object for a stored file from raw metadata.
     *
     * @param $rawmetadataarray
     * @param \stored_file $file
     *
     * @return \metadataextractor_tika\metadata_model
     */
    public function build_metadata($rawmetadataarray, stored_file $file) {
        $metadata = new metadata();
        $metadata->contenthash = $file->get_contenthash();
        $metadata->format = (isset($rawmetadataarray['Content-Type'])) ? ($rawmetadataarray['Content-Type']) : null;
        // TODO: Fix `type` to determine a \tool_metadata\DCMI_TYPE.
        $metadata->type = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
        $metadata->description = (isset($rawmetadataarray['dc:description'])) ? ($rawmetadataarray['dc:description']) : null;
        $metadata->title = (isset($rawmetadataarray['dc:title'])) ? ($rawmetadataarray['dc:title']) : null;
        $metadata->creator = (isset($rawmetadataarray['meta:author'])) ? ($rawmetadataarray['meta:author']) : null;
        $metadata->date = (isset($rawmetadataarray['meta:creation-date'])) ? strtotime($rawmetadataarray['meta:creation-date']) : null;
        $metadata->contributor = (isset($rawmetadataarray['meta:last-author'])) ? ($rawmetadataarray['meta:last-author"']) : null;
        $metadata->subject = (isset($rawmetadataarray['subject'])) ? ($rawmetadataarray['subject']) : null;
        $metadata->publisher = (isset($rawmetadataarray['publisher'])) ? ($rawmetadataarray['publisher']) : null;
        $metadata->rights = $file->get_license();
        $metadata->language = null;
        $metadata->timecreated = time();
        $metadata->timemodified = time();

        return $metadata;
    }

    /**
     * Get content from a stored file.
     *
     * @param \stored_file $file
     *
     * @return bool|string
     */
    public function get_content(stored_file $file) {
        $fs = get_file_storage();
        $filesystem = $fs->get_file_system();

        if (!$file->is_directory()) {
            $metadata = null;
            $localpath = $filesystem->get_local_path_from_storedfile($file, true);
            $cmd = 'java -jar ' . $this->path . ' -t ' . $localpath;
            $rawcontent = shell_exec($cmd);

            if ($rawcontent) {
                return trim($rawcontent);
            } else {
                return false;
            }
        }
    }

}