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

use Psr\Http\Message\StreamInterface;
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
     * Tika extraction option for extracting text content.
     */
    const EXTRACTION_OPTION_TEXT_CONTENT = '--text';

    /**
     * Tika extraction option for extracting metadata in JSON format.
     */
    const EXTRACTION_OPTION_JSON_METADATA = '--json';

    /**
     * Tika extraction option for detecting mimetype.
     */
    const EXTRACTION_OPTION_DETECT_TYPE = '--detect';

    /**
     * Supported extraction options for Tika parsing.
     */
    const SUPPORTED_EXTRACTION_OPTIONS = [
        self::EXTRACTION_OPTION_JSON_METADATA,
        self::EXTRACTION_OPTION_TEXT_CONTENT,
        self::EXTRACTION_OPTION_DETECT_TYPE,
    ];

    /**
     * Get the configured servicetype from plugin config.
     *
     * @return string|false the set value or false if not set.
     */
    public function get_servicetype_set() {
        return get_config('metadataextractor_tika', 'tikaservicetype');
    }

    /**
     * Extract metadata from resource using Tika.
     *
     * @param object $resource the resource instance to parse.
     * @param string $type the type of the resource to parse.
     * @param array|string $options the option/options to apply to Tika extraction, taken from
     * SUPPORTED_EXTRACTION_OPTIONS, determines what types of metadata will be parsed from resource.
     *
     * @throws \tool_metadata\extraction_exception if invalid service type, invalid options, or unsupported resource.
     */
    protected function extract($resource, string $type, $options = []) {
        // Convert single option to array of options.
        if (!is_array($options)) {
            $options = [$options];
        }

        $servicetype = $this->get_servicetype_set();
        $stream = helper::get_resource_stream($resource, $type);

        if (empty($stream)) {
            throw new extraction_exception('status:extractionnotsupported', 'tool_metadata', '',
                [
                    'resourceid' => helper::get_resource_id($resource, $type),
                    'type' => $type,
                    'plugin' => self::METADATAEXTRACTOR_NAME
                ]);
        }

        switch($servicetype) {
            case self::SERVICETYPE_LOCAL :
                $result = $this->extract_local($stream, $options);
                break;
            case self::SERVICETYPE_SERVER :
                if (count($options) != 1) {
                    throw new extraction_exception('error:server:invalidoptions', 'metadataextractor_tika');
                }
                $result = $this->extract_server($stream, reset($options));
                break;
            default :
                throw new extraction_exception('error:invalidservicetype', 'metadataextractor_tika');
                break;
        }

        return $result;
    }

    /**
     * Parse metadata from a resource using locally installed tika-app.
     *
     * @param \Psr\Http\Message\StreamInterface $stream the stream to extract metadata for.
     * @param array|string $options the option/options to apply to local extraction, taken from
     * SUPPORTED_EXTRACTION_OPTIONS, determines what types of data will be parsed from resource.
     *
     * @return string|null data results of Tika parsing resource.
     * @throws \tool_metadata\extraction_exception
     */
    protected function extract_local(StreamInterface $stream, $options = []) {
        return $this->execute_tika_command($options, $stream);
    }

    /**
     * Use Tika Server to parse metadata from a resource.
     *
     * @param \Psr\Http\Message\StreamInterface $stream the stream to extract metadata for.
     * @param string $option the option to apply to Tika server extraction, taken from SUPPORTED_EXTRACTION_OPTIONS,
     * determines what data will be parsed from resource.
     *
     * @return string|null data results of Tika parsing resource.
     * @throws \tool_metadata\extraction_exception
     */
    protected function extract_server(StreamInterface $stream, $option) {
        $result = null;
        $server = new server();

        if (!$server->is_ready()) {
            throw new extraction_exception('error:server:notready', 'metadataextractor_tika');
        }

        switch ($option) {
            case (self::EXTRACTION_OPTION_TEXT_CONTENT) :
                $result = $server->get_content($stream);
                break;
            case (self::EXTRACTION_OPTION_JSON_METADATA) :
                $result = $server->get_metadata($stream);
                break;
            case (self::EXTRACTION_OPTION_DETECT_TYPE) :
                $result = $server->get_mimetype($stream);
                break;
            default :
                throw new extraction_exception('error:server:optionnotsupported', 'metadataextractor_tika');
                break;
        }

        return $result;
    }

    /**
     * Get Tika extracted content for a resource.
     *
     * @param object $resource the resource instance to get mimetype of content for.
     * @param string $type the type of the resource to extract mimetype for.
     *
     * @return string|null $content string content of resource, null if resource could not be parsed.
     * @throws \tool_metadata\extraction_exception if service type not configured correctly.
     */
    protected function extract_content($resource, string $type) {
        return $this->extract($resource, $type, self::EXTRACTION_OPTION_TEXT_CONTENT);
    }

    /**
     * Extract content from a file resource.
     *
     * @param \stored_file $file the file to extract content for.
     *
     * @return string|null $content text content or null if no content to extract.
     */
    public function extract_file_content(stored_file $file) {
        return $this->extract_content($file, TOOL_METADATA_RESOURCE_TYPE_FILE);
    }

    /**
     * Extract content for a URL resource.
     *
     * @param object $url the URL resource to extract content for.
     *
     * @return string|null $content text content or null if no content to extract.
     */
    public function extract_url_content($url) {
        return $this->extract_content($url, TOOL_METADATA_RESOURCE_TYPE_URL);
    }

    /**
     * Extract the mimetype of a resource's content.
     *
     * @param object $resource the resource instance to get mimetype of content for.
     * @param string $type the type of the resource to extract mimetype for.
     *
     * @return string|null IANA mimetype, possibly with optional parameter, 'type/subtype;parameter=value', null if could not be
     * determined.
     * @throws \tool_metadata\extraction_exception if service type not configured correctly.
     */
    protected function extract_mimetype($resource, string $type) {
        return $this->extract($resource, $type, self::EXTRACTION_OPTION_DETECT_TYPE);
    }

    /**
     * Extract the mimetype of a file's content.
     *
     * @param \stored_file $file the file instance to extract mimetype for.
     *
     * @return string|null IANA mimetype, possibly with optional parameter, 'type/subtype;parameter=value', null if could not be
     * determined.
     */
    public function extract_file_mimetype(stored_file $file) {
        return $this->extract_mimetype($file, TOOL_METADATA_RESOURCE_TYPE_FILE);
    }

    /**
     * Extract the mimetype of a url's content.
     *
     * @param object $url the url resource to extract mimetype for.
     *
     * @return string|null IANA mimetype, possibly with optional parameter, 'type/subtype;parameter=value', null if could not be
     * determined.
     */
    public function extract_url_mimetype($url) {
        return $this->extract_mimetype($url, TOOL_METADATA_RESOURCE_TYPE_URL);
    }

    /**
     * Attempt to extract resource metadata.
     *
     * @param object $resource the resource to extract metadata for.
     * @param string $type the type of the resource to extract mimetype for.
     *
     * @return \metadataextractor_tika\metadata|null a metadata object instance or null if no metadata.
     */
    public function extract_metadata($resource, string $type) {
        $result = null;

        $jsonmetadata = $this->extract($resource, $type, self::EXTRACTION_OPTION_JSON_METADATA);

        $metadataarray = $this->clean_metadata($jsonmetadata);

        if (!empty($metadataarray) && is_array($metadataarray)) {
            if (array_key_exists('Content-Type', $metadataarray)) {
                $class = tika_helper::get_metadata_class($metadataarray['Content-Type']);
                $result = new $class(0, helper::get_resourcehash($resource, $type), $metadataarray);
            } else {
                $result = new metadata(0, helper::get_resourcehash($resource, $type), $metadataarray);
            }
        }

        return $result;
    }

    /**
     * Attempt to extract file metadata.
     *
     * @param \stored_file $file the file to create metadata for.
     *
     * @return \metadataextractor_tika\metadata|null a metadata object instance or null if no metadata.
     */
    public function extract_file_metadata(stored_file $file) {
        return $this->extract_metadata($file, TOOL_METADATA_RESOURCE_TYPE_FILE);
    }

    /**
     * Attempt to extract url metadata.
     *
     * @param object $url the url to create metadata for.
     *
     * @return \metadataextractor_tika\metadata|null a metadata object instance or false if no metadata.
     */
    public function extract_url_metadata($url) {
        return $this->extract_metadata($url, TOOL_METADATA_RESOURCE_TYPE_URL);
    }

    /**
     * Read metadata for a resource.
     *
     * Override parent class method to ensure correct metadata class is returned.
     *
     * @param string $resourcehash the unique hash of resource content or resource content id.
     *
     * @return \tool_metadata\metadata|null metadata instance or null if no metadata found.
     * @throws \tool_metadata\extraction_exception
     */
    public function get_metadata(string $resourcehash) {
        global $DB;

        $mimetype = $DB->get_field($this->get_base_table(), 'format', ['resourcehash' => $resourcehash]);

        $metadataclass = tika_helper::get_metadata_class((string) $mimetype);

        return new $metadataclass(0, $resourcehash);
    }

    /**
     * Clean a JSON string of metadata and return an array of cleaned data.
     *
     * @param string $jsonmetadata JSON string containing metadata.
     *
     * @return array $metadataarray an array of metadata.
     */
    public function clean_metadata(string $jsonmetadata) {
        if (!empty($jsonmetadata)) {
            // Use associative array as some key names may not parse well
            // into php stdClass (eg. 'Content-Type').
            $metadataarray = json_decode($jsonmetadata, true);
        }

        foreach ($metadataarray as $key => $value) {
            if (is_array($value)) {
                $metadataarray[$key] = implode(', ', $value);
            }
        }

        return $metadataarray;
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
        } else if ($servicetype == self::SERVICETYPE_SERVER) {
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
        } else if (!file_exists($tikapath)) {
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

    /**
     * Is this extractor ready to extract tika metadata?
     *
     * @return bool true if configured and ready, false otherwise.
     */
    public function is_ready() : bool {
        $servicetype = $this->get_servicetype_set();

        if (!empty($servicetype)) {
            switch($servicetype) {
                case self::SERVICETYPE_LOCAL :
                    $result = $this->is_local_tika_ready();
                    break;
                case self::SERVICETYPE_SERVER :
                    try {
                        $server = new server();
                        $result = $server->is_ready();
                    } catch (\Exception $e) {
                        $result = false;
                    }
                    break;
                default :
                    $result = false;
                    break;
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Check if a Tika CLI option is supported by this extractor.
     *
     * @param string $option the option to check.
     *
     * @return bool true if supported, false otherwise.
     */
    public function is_cli_option_supported(string $option) : bool {
        $result = false;

        if (in_array($option, self::SUPPORTED_EXTRACTION_OPTIONS)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Execute a Tika extraction command using local Tika install.
     *
     * @param array|string $options string[] of tika options to apply to CLI command, or a single string option,
     * must be contained in SUPPORTED_EXTRACTION_OPTIONS.
     * @param \GuzzleHttp\Psr7\Stream $stream the resource stream to run tika command on.
     * @param bool $close true if stream resource to be closed following command execution.
     *
     * @return string|null $result the Tika parsed content.
     * @throws \tool_metadata\extraction_exception if local Tika install is not ready, missing dependencies, or invalid option.
     */
    protected function execute_tika_command($options = [], $stream = null, $close = false) {
        if (!$this->is_local_tika_ready()) {
            throw new extraction_exception('error:local:config', 'metadataextractor_tika');
        } else {
            $tikapath = get_config('metadataextractor_tika', 'tikalocalpath');
            $filepath = $stream->getMetadata('uri');

            $optionstring = '';
            foreach ($options as $option) {
                if (!$this->is_cli_option_supported($option)) {
                    throw new extraction_exception('error:local:clioptionnotsupported', 'metadataextractor_tika');
                }
                $optionstring .= $option . ' ';
            }

            if (empty($optionstring)) {
                throw new extraction_exception('error:local:nooptionset', 'metadataextractor_tika');
            }

            $cmd = "java -jar $tikapath $optionstring $filepath";

            $result = shell_exec($cmd);

            if ($close) {
                $stream->close();
            }
        }

        return $result;
    }
}
