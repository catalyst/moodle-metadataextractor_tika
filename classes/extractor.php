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

defined('MOODLE_INTERNAL') || die();

/**
 * Interface describing the strategy for extracting metadata from a Moodle stored_file resource.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class extractor implements \tool_metadata\extractor_strategy {

    /**
     * @var string path to jar file for executing tika commands.
     */
    private $path;

    public function __construct() {
        global $CFG;

        $this->path = $CFG->pathtotika;
    }

    /**
     * Create metadata in the {metadata} table and return a metadata object or false if metadata could not be created.
     *
     * @param \stored_file $file the file to create metadata for.
     *
     * @return \tool_metadata\metadata_model|false an instance of the metadata model or one of its' children.
     */
    public function create_metadata(stored_file $file){
        global $DB;

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
                return $metadata;
            }

            if ($metadataarray) {
                $metadataobject = $this->build_metadata($metadataarray, $file);
                $result = $DB->insert_record('tool_metadata', $metadataobject);
                if ($result) {
                    $metadata = $metadataobject;
                }
            }

        } else {
            print_error('Can not create metadata for a directory');
        }

        return $metadata;
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
        $metadata->filecontenthash = $file->get_contenthash();
        $metadata->format = (isset($rawmetadataarray['Content-Type'])) ? ($rawmetadataarray['Content-Type']) : null;
        // TODO: Fix `type` to determine a \tool_metadata\metadata_model::DCMI_TYPE.
        $metadata->type = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
        $metadata->description = (isset($rawmetadataarray['dc:description'])) ? ($rawmetadataarray['dc:description']) : null;
        $metadata->title = (isset($rawmetadataarray['dc:title'])) ? ($rawmetadataarray['dc:title']) : null;
        $metadata->creator = (isset($rawmetadataarray['meta:author'])) ? ($rawmetadataarray['meta:author']) : null;
        $metadata->creationdate = (isset($rawmetadataarray['meta:creation-date'])) ? strtotime($rawmetadataarray['meta:creation-date']) : null;
        $metadata->contributor = (isset($rawmetadataarray['meta:last-author'])) ? ($rawmetadataarray['meta:last-author"']) : null;
        $metadata->subject = (isset($rawmetadataarray['subject'])) ? ($rawmetadataarray['subject']) : null;
        $metadata->publisher = (isset($rawmetadataarray['publisher'])) ? ($rawmetadataarray['publisher']) : null;
        $metadata->rights = $file->get_license();
        $metadata->extractor = self::class;
        $metadata->timecreated = time();
        $metadata->timemodified = time();

        return $metadata;
    }

    /**
     * Return a metadata model for a stored_file resource.
     *
     * @param \stored_file $file
     *
     * @return false|\metadataextractor_tika\metadata_model
     */
    public function read_metadata(stored_file $file) {
        global $DB;

        $record = $DB->get_record('tool_metadata', [
            'filecontenthash' => $file->get_contenthash(),
            'extractor' => self::class
        ]);

        if ($record) {
            $metadata = metadata::create_instance($record);
        } else {
            $metadata = $this->create_metadata($file);
        }

        return $metadata;

    }

    /**
     * Update the metadata for a stored file.
     *
     * @param \stored_file $file
     */
    public function update_metadata(stored_file $file) {
        // TODO: Implement an update method for when a stored file is modified.
    }

    /**
     * Delete metadata records from the database for a stored_file.
     *
     * @param \stored_file $file
     */
    public function delete_metadata(stored_file $file){
        // TODO: Implement this method to allow removal of records when files are deleted.
    }

    /**
     * Get all file extensions supported by the implementing class.
     *
     * @return array listing all supported file extensions.
     */
    public function get_supported_file_extensions() {
        // TODO: Add a check for supported file extensions to $this->create_metadata.
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