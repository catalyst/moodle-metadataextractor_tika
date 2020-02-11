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
 * Class for extraction of metadata using Apache Tika.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

use stored_file;
use tool_metadata\extraction_exception;
use tool_metadata\helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/metadata/constants.php');
require_once($CFG->dirroot . '/mod/url/locallib.php');

/**
 * Class for extraction of metadata using Apache Tika.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class extractor extends \tool_metadata\extractor {

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
     * Local Tika service type: Java and a Tika application jar are installed locally, plugin will use direct commands to CLI.
     */
    const SERVICETYPE_LOCAL = 'local';

    /**
     * Server Tika service type: Tika is being run on a server, plugin will communicate via Tika REST API endpoint.
     */
    const SERVICETYPE_SERVER = 'server';

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
     * Attempt to extract file metadata.
     *
     * @param \stored_file $file the file to create metadata for.
     * @throws \tool_metadata\extraction_exception
     *
     * @return \metadataextractor_tika\metadata|null a metadata object instance or null if no metadata.
     */
    public function extract_file_metadata(stored_file $file) {
        global $CFG;

        $result = null;

        if (!empty($CFG->tikaservicetype)) {
            switch ($CFG->tikaservicetype) {
                case self::SERVICETYPE_LOCAL :
                    $rawmetadata = $this->extract_file_metadata_local($file);
                    break;
                case self::SERVICETYPE_SERVER :
                    $rawmetadata = $this->extract_file_metadata_server($file);
                    break;
                default :
                    throw new extraction_exception('error:invalidservicetype');
                    break;
            }
        }
        if (!empty($rawmetadata)) {
            // Use associative array as some key names may not parse well
            // into php stdClass (eg. 'Content-Type').
            $metadataarray = json_decode($rawmetadata, true);
        }

        if (!empty($metadataarray) && is_array($metadataarray)) {
            $result = new metadata(helper::get_resourcehash($file, TOOL_METADATA_RESOURCE_TYPE_FILE), $metadataarray, true);
        }

        return $result;
    }

    /**
     * Attempt to extract url metadata.
     *
     * @param object $url the url to create metadata for.
     * @throws \tool_metadata\extraction_exception
     *
     * @return \metadataextractor_tika\metadata|null a metadata object instance or false if no metadata.
     */
    public function extract_url_metadata($url) {
        global $CFG;

        $result = null;

        if (!empty($CFG->tikaservicetype)) {
            switch ($CFG->tikaservicetype) {
                case self::SERVICETYPE_LOCAL :
                    $rawmetadata = $this->extract_url_metadata_local($url);
                    break;
                case self::SERVICETYPE_SERVER :
                    $rawmetadata = $this->extract_url_metadata_server($url);
                    break;
                default :
                    throw new extraction_exception('error:invalidservicetype');
                    break;
            }
        }
        if (!empty($rawmetadata)) {
            // Use associative array as some key names may not parse well
            // into php stdClass (eg. 'Content-Type').
            $metadataarray = json_decode($rawmetadata, true);
        } else {
            throw new extraction_exception('error:nometadata', 'metadataextractor_tika');
        }

        if (!empty($metadataarray) && is_array($metadataarray)) {
            $result = new metadata(helper::get_resourcehash($url, TOOL_METADATA_RESOURCE_TYPE_URL), $metadataarray, true);
        }

        return $result;
    }

    /**
     * Create file metadata using the locally installed tika-app.
     *
     * @param \stored_file $file
     *
     * @return string|null $result json encoded metadata or null if no metadata.
     * @throws \tool_metadata\extraction_exception
     */
    protected function extract_file_metadata_local(stored_file $file) {
        global $CFG;

        $localpath = $file->get_filepath();

        if (!$this->is_local_tika_ready()) {
            throw new extraction_exception('error:local:config', 'metadataextractor_tika');
        } else {
            $cmd = 'java -jar ' . $CFG->tikalocalpath . ' -j ' . $localpath;
            $result = shell_exec($cmd);
        }

        return $result;
    }


    /**
     * Extract file metadata using a tika server.
     *
     * @param \stored_file $file
     *
     * @return string|null $result json encoded metadata or null if no metadata.
     * @throws \tool_metadata\extraction_exception
     */
    protected function extract_file_metadata_server(stored_file $file) {
        $server = new server();
        if (!$server->is_ready()) {
            throw new extraction_exception('error:server:notready', 'metadataextractor_tika');
        } else {
            $result = $server->get_file_metadata($file);
        }

        return $result;
    }

    /**
     * Create url metadata using the locally installed tika-app.
     *
     * @param object $url mod_url instance.
     *
     * @return string|null $result json encoded metadata or null if no metadata.
     * @throws \tool_metadata\extraction_exception
     */
    protected function extract_url_metadata_local($url) {
        global $CFG;

        $cm = get_coursemodule_from_instance('url', $url->id, $url->course, false, MUST_EXIST);
        $fullurl = url_get_full_url($url, $cm, $url->course);

        // Create a temp file and add url contents to it for tika parsing.
        $file = tmpfile();
        fwrite($file, file_get_contents($fullurl));
        $localpath = stream_get_meta_data($file)['uri'];

        if (!$this->is_local_tika_ready()) {
            throw new extraction_exception('error:local:config', 'metadataextractor_tika');
        } else {
            $cmd = 'java -jar ' . $CFG->tikalocalpath . ' -j ' . $localpath;
            $rawmetadata = shell_exec($cmd);
            fclose($file);
        }

        if (!empty($rawmetadata)) {
            $result = $rawmetadata;
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Extract url metadata using a tika server.
     *
     * @param object $url mod_url instance.
     *
     * @return string|null $result json encoded metadata or null if no metadata.
     * @throws \tool_metadata\extraction_exception
     */
    protected function extract_url_metadata_server($url) {
        $server = new server();
        if (!$server->is_ready()) {
            throw new extraction_exception('error:server:notready', 'metadataextractor_tika');
        } else {
            $result = $server->get_url_metadata($url);
        }

        return $result;
    }

    /**
     * Get the name of missing dependencies for the current configuration
     * required to extract metadata with tika.
     */
    public function get_missing_dependencies() {
        global $CFG;

        $result = '';

        if ($CFG->tikaservicetype == self::SERVICETYPE_LOCAL) {
            $path = exec('which java');
            if (empty($path)) {
                $result = 'java';
            }
        } elseif ($CFG->tikaservicetype == self::SERVICETYPE_SERVER) {
            if (!class_exists('\GuzzleHttp\Client')) {
                $result = 'guzzle';
            }
        } else {
            throw new extraction_exception('error:invalidservicetype', 'metadataextractor_tika');
        }

        return $result;
    }

    /**
     * Validate that metadata can be extracted from a resource.
     *
     * @param object $resource the resource instance to check
     * @param string $type the type of resource.
     *
     * @return bool
     */
    public function validate_resource($resource, string $type) : bool {
        switch($type) {
            // File resource cannot be directories.
            case TOOL_METADATA_RESOURCE_TYPE_FILE :
                if ($resource->is_directory()) {
                    $result = false;
                } else {
                    $result = true;
                }
                break;
            // Only support valid HTTP(S) URLs.
            case TOOL_METADATA_RESOURCE_TYPE_URL :
                if (!preg_match('/^https?:\/\//i', $resource->externalurl) ||
                    !url_appears_valid_url($resource->externalurl)) {
                    $result = false;
                } else {
                    $result = true;
                }
                break;
            default:
                $result = false;
                break;
        }

        return $result;
    }

    /**
     * Test that local tika install is operational.
     *
     * @return bool true if configured and working correctly.
     */
    public function is_local_tika_ready() {
        global $CFG;

        if (empty($CFG->tikaservicetype) || $CFG->tikaservicetype != extractor::SERVICETYPE_LOCAL) {
            $result = false;
        } elseif (empty($CFG->tikalocalpath) || !empty(self::get_missing_dependencies())) {
            $result = false;
        } elseif (!file_exists($CFG->tikalocalpath)) {
            $result = false;
        } else {
            $cmd = 'java -jar ' . $CFG->tikalocalpath . ' --help';
            $returned = shell_exec($cmd);
            if (!empty($returned)) {
                $result = true;
            } else {
                $result = false;
            }
        }

        return $result;
    }
}
