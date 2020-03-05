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
     * The plugin name.
     */
    const METADATAEXTRACTOR_NAME = 'tika';

    /**
     * Default table name for storing extracted metadata for this extractor.
     */
    const METADATA_BASE_TABLE = 'metadataextractor_tika';

    /**
     * Local Tika service type: Java and a Tika application jar are installed locally, plugin will use direct commands to CLI.
     */
    const SERVICETYPE_LOCAL = 'local';

    /**
     * Server Tika service type: Tika is being run on a server, plugin will communicate via Tika REST API endpoint.
     */
    const SERVICETYPE_SERVER = 'server';

    /**
     * The string used my Apache Tika in raw metadata to identify mimetype of resource parsed.
     */
    const TIKA_MIMETYPE_KEY = 'Content-Type';

    /**
     * Get the configured servicetype from plugin config.
     *
     * @return string|false the set value or false if not set.
     */
    public function get_servicetype_set() {
        return get_config('metadataextractor_tika', 'tikaservicetype');
    }

    /**
     * Attempt to extract file metadata.
     *
     * @param \stored_file $file the file to create metadata for.
     * @throws \tool_metadata\extraction_exception
     *
     * @return \metadataextractor_tika\metadata|null a metadata object instance or null if no metadata.
     */
    public function extract_file_metadata(stored_file $file) {

        $result = null;

        if (!empty($servicetype = $this->get_servicetype_set())) {
            switch ($servicetype) {
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
            $mimetype = $metadataarray[self::TIKA_MIMETYPE_KEY];
            $class = tika_helper::get_metadata_class($mimetype);
            $result = new $class(0, helper::get_resourcehash($file, TOOL_METADATA_RESOURCE_TYPE_FILE), $metadataarray);
        }

        return $result;
    }

    /**
     * Attempt to extract url metadata.
     *
     * @param object $url the url to create metadata for.
     *
     * @return \metadataextractor_tika\metadata|null a metadata object instance or false if no metadata.
     * @throws \tool_metadata\extraction_exception
     */
    public function extract_url_metadata($url) {

        $result = null;

        if (!empty($servicetype = $this->get_servicetype_set())) {
            switch ($servicetype) {
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
            $result = new metadata(0, helper::get_resourcehash($url, TOOL_METADATA_RESOURCE_TYPE_URL), $metadataarray);
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

        // This is polyfill for Moodle 3.7 backport, in later revisions, the filesystem method
        // `get_local_path_from_storedfile($file, true)` is public and can be utilised to obtain local
        // path.
        $resource = $file->get_content_file_handle();
        $localpath = stream_get_meta_data($resource)['uri'];
        $tikapath = get_config('metadataextractor_tika', 'tikalocalpath');

        if (!$this->is_local_tika_ready()) {
            throw new extraction_exception('error:local:config', 'metadataextractor_tika');
        } else {
            $cmd = 'java -jar ' . $tikapath . ' -j ' . $localpath;
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
        $cm = get_coursemodule_from_instance('url', $url->id, $url->course, false, MUST_EXIST);
        $fullurl = url_get_full_url($url, $cm, $url->course);

        // Create a temp file and add url contents to it for tika parsing.
        $file = tmpfile();
        fwrite($file, file_get_contents($fullurl));
        $filepath = stream_get_meta_data($file)['uri'];

        if (!$this->is_local_tika_ready()) {
            throw new extraction_exception('error:local:config', 'metadataextractor_tika');
        } else {
            $tikapath = get_config('metadataextractor_tika', 'tikalocalpath');
            $cmd = 'java -jar ' . $tikapath . ' -j ' . $filepath;
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

    public function get_metadata(string $resourcehash) {
        global $DB;

        // Get the standard metadata record so we can determine the type.
        $record = $DB->get_record(static::METADATA_TABLE,
            ['resourcehash' => $resourcehash]);

        if (!empty($record)) {
            if ($record->type == TOOL_METADATA_RESOURCE_TYPE_FILE) {
                $filetype = tika_helper::get_filetype($record->format);


            } else {

            }
        } else {
            $metadata = null;
        }

        return $metadata;
    }

    public function create_metadata(metadata $metadata) {
        global $DB;

        $existingmetadata = $this->get_metadata($metadata->get_resourcehash());

        if ($existingmetadata) {
            $this->update_metadata($existingmetadata->id, $metadata, $extractor);
        } else {
            $id = $DB->insert_record(self::get_table(), $metadata);
            $metadata->id = $id;
        }

        return $metadata;
    }

    public function update_metadata(int $id, metadata $metadata) {

    }

    /**
     * Get the name of missing dependencies for a servicetype.
     *
     * @param string $servicetype the servicetype to check for missing dependencies for.
     *
     * @return array string[] or missing dependency names.
     * @throws \tool_metadata\extraction_exception
     */
    public function get_missing_dependencies(string $servicetype) : array {
        $result = [];

        if ($servicetype == self::SERVICETYPE_LOCAL) {
            $path = exec('which java');
            if (empty($path)) {
                $result[] = 'java';
            }
        } elseif ($servicetype == self::SERVICETYPE_SERVER) {
            if (!class_exists('\GuzzleHttp\Client')) {
                $result[] = 'guzzle';
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
    public function is_local_tika_ready() : bool {
        $tikapath = get_config('metadataextractor_tika', 'tikalocalpath');

        if (empty($tikapath) || !empty($this->get_missing_dependencies(self::SERVICETYPE_LOCAL))) {
            $result = false;
        } elseif (!file_exists($tikapath)) {
            $result = false;
        } else {
            $cmd = 'java -jar ' . $tikapath . ' --help';
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
