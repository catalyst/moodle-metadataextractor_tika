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
 * Class for making API requests to a Tika server.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

use stored_file;
use tool_metadata\extraction_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');

/**
 * Class for making API requests to a Tika server.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class server {

    /**
     * @var \GuzzleHttp\Client the client used to make API requests.
     */
    private $client;

    /**
     * @var string the base URI of the tika server to make HTTP requests to.
     */
    private $baseuri;

    /**
     * Server constructor.
     *
     * @param null|\GuzzleHttp\HandlerStack $handlerstack optional stack of handlers for middleware or testing mocks.
     *
     * @throws \tool_metadata\extraction_exception
     */
    public function __construct($handlerstack = null) {
        global $CFG;

        if (!empty($CFG->tikaserverhost)) {
            $baseuri = $CFG->tikaserverhost;
            if (!empty($CFG->tikaserverport)) {
                $baseuri .= ':' . $CFG->tikaserverport;
            }
        } else {
            throw new extraction_exception('error:server:nohostset', 'metadataextractor_tika');
        }

        // Check that local_aws plugin is installed as this is a dependency for tika server configuration.
        $dependencyinfo = \core_plugin_manager::instance()->get_plugin_info('local_aws');
        if (empty($dependencyinfo)) {
            throw new extraction_exception('error:server:missingdependency', 'metadataextractor_tika', '',
                'local_aws');
        }

        $this->baseuri = $baseuri;
        $params = [];

        // Add handlerstack for testing and any middleware.
        if (!empty($handlerstack)) {
            $params['handler'] = $handlerstack;
        }

        $this->client = new \GuzzleHttp\Client($params);
    }

    /**
     * Get json encoded file metadata from Tika server.
     *
     * @param \stored_file $file the file to extract metadata for.
     *
     * @return string|false $result json encoded metadata or false if metadata could not be extracted.
     * @throws \tool_metadata\extraction_exception
     */
    public function get_file_metadata(stored_file $file) {

        try {
            $response = $this->client->request('POST', "$this->baseuri/meta/form", [
                'headers' => ['Accept' => 'application/json'],
                'multipart' => [
                    [
                        'name' => $file->get_filename(),
                        'contents' => $file->get_content_file_handle(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            if (method_exists($e, 'getReasonPhrase')) {
                $debuginfo = $e->getReasonPhrase();
            } else {
                $debuginfo = $e->getMessage();
            }
            throw new extraction_exception('error:server:httprequest', 'metadataextractor_tika', '', null, $debuginfo);
        }

        $result = $this->extract_response_metadata($response);

        return $result;
    }

    /**
     * Get json encoded metadata for a mod_url instance's external url.
     *
     * @param $url object mod_url instance.
     *
     * @return string|false $result json encoded metadata or false if metadata could not be extracted.
     * @throws \tool_metadata\extraction_exception
     */
    public function get_url_metadata($url) {
        $result = false;

        // Only support valid HTTP(S) URLs, exit early if url is invalid.
        if (!preg_match('/^https?:\/\//i', $url->externalurl) || !url_appears_valid_url($url->externalurl)) {
            return $result;
        }

        $cm = get_coursemodule_from_instance('url', $url->id, $url->course, false, MUST_EXIST);
        $fullurl = url_get_full_url($url, $cm, $url->course);

        try {
            // Get the url content to pass to tika, only allows up to 5 redirects.
            $urlresponse = $this->client->request('GET', $fullurl);
            $stream = $urlresponse->getBody();

            $tikaresponse = $this->client->request('POST', "$this->baseuri/meta/form", [
                'headers' => ['Accept' => 'application/json'],
                'multipart' => [
                    [
                        'name' => $url->name,
                        'contents' => $stream,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            if (method_exists($e, 'getReasonPhrase')) {
                $debuginfo = $e->getReasonPhrase();
            } else {
                $debuginfo = $e->getMessage();
            }
            throw new extraction_exception('error:server:httprequest', 'metadataextractor_tika', '', null, $debuginfo);
        }

        $result = $this->extract_response_metadata($tikaresponse);

        return $result;
    }

    /**
     * Extract metadata string from content of response.
     *
     * @param \GuzzleHttp\Psr7\Response $response
     *
     * @return string|false json string of metadata or false if failed.
     */
    private function extract_response_metadata(\GuzzleHttp\Psr7\Response $response) {
        $result = false;

        if ($response->getStatusCode() == 200) {
            $result = $response->getBody()->getContents();
        }

        return $result;
    }

}