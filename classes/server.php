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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use stored_file;
use tool_metadata\extraction_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');
require_once($CFG->dirroot . '/mod/url/locallib.php');

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

        $host = get_config('metadataextractor_tika', 'tikaserverhost');
        $port = get_config('metadataextractor_tika', 'tikaserverport');

        if (!empty($host)) {
            $baseuri = $host;
            if (!empty($port)) {
                $baseuri .= ':' . $port;
            }
        } elseif (empty($handlerstack)) {
            throw new extraction_exception('error:server:nohostset', 'metadataextractor_tika');
        } else {
            // We have a handler, running tests so set base URI to default.
            $baseuri = $CFG->wwwroot;
        }

        // Check that local_aws plugin is installed as this is a dependency for tika server configuration.
        $dependencyinfo = \core_plugin_manager::instance()->get_plugin_info('local_aws');
        if (empty($dependencyinfo)) {
            throw new extraction_exception('error:missingdependency', 'metadataextractor_tika', '',
                'local_aws');
        }

        // We don't add the baseuri as a param because we may want to use this client
        // to make other external calls, instead pass it into client requests.
        $this->baseuri = $baseuri;
        $params = [];

        // Add handlerstack for testing and any middleware.
        if (!empty($handlerstack)) {
            $params['handler'] = $handlerstack;
        }

        $this->client = new \GuzzleHttp\Client($params);
    }

    /**
     * Test the connection to Tika server and get a response.
     *
     * @return \Psr\Http\Message\ResponseInterface $response HTTP request response.
     * @throws \tool_metadata\extraction_exception on connection error.
     */
    public function test_connection() {
        // This tika server api call should return HELLO message.
        try {
            $response = $this->client->request('GET', "$this->baseuri/tika");
        } catch (GuzzleException $ex) {
            throw new extraction_exception('error:connectionerror', 'metadataextractor_tika');
        }

        return $response;
    }

    /**
     * Test that server is ready to perform requests.
     */
    public function is_ready() {
        $result = false;

        $response = $this->test_connection();

        if ($response->getStatusCode() == 200) {
            $result = true;
        }

        return $result;
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

        $body = \GuzzleHttp\Psr7\stream_for($file->get_content_file_handle());

        try {
            $response = $this->client->request('PUT', "$this->baseuri/meta", [
                'headers' => ['Accept' => 'application/json'],
                'body' => $body,
            ]);
        } catch (\Exception $e) {
            if (method_exists($e, 'getReasonPhrase')) {
                $debuginfo = $e->getReasonPhrase();
            } else {
                $debuginfo = $e->getMessage();
            }
            throw new extraction_exception('error:server:httprequest', 'metadataextractor_tika', '', null, $debuginfo);
        }

        $result = $this->extract_response_content($response);

        return $result;
    }

    /**
     * Get json encoded url metadata from Tika server.
     *
     * @param $url object mod_url instance.
     *
     * @return object result object containing {'metadata' => string, 'contenthash' => string, 'success' => bool}
     * @throws \tool_metadata\extraction_exception
     */
    public function get_url_metadata($url) {

        $cm = get_coursemodule_from_instance('url', $url->id, $url->course, false, MUST_EXIST);
        $fullurl = url_get_full_url($url, $cm, $url->course);

        try {
            // Get the url content to pass to tika, only allows up to 5 redirects.
            $urlresponse = $this->client->request('GET', $fullurl);
            $stream = $urlresponse->getBody();

            // Use the Tika Server meta/form path, tika/form is not support for HTML docs.
            $tikaresponse = $this->client->request('PUT', "$this->baseuri/meta", [
                'headers' => ['Accept' => 'application/json'],
                'body' => $stream,
            ]);
        } catch (\Exception $e) {
            if (method_exists($e, 'getReasonPhrase')) {
                $debuginfo = $e->getReasonPhrase();
            } else {
                $debuginfo = $e->getMessage();
            }
            throw new extraction_exception('error:server:httprequest', 'metadataextractor_tika', '', null, $debuginfo);
        }

        $result = $this->extract_response_content($tikaresponse);

        return $result;
    }

    /**
     * Get Tika extracted content from file.
     *
     * @param \stored_file $file the file to extract content for.
     *
     * @return string|false $result content or false if content could not be extracted.
     * @throws \tool_metadata\extraction_exception
     */
    public function get_file_content(stored_file $file) {

        try {
            $response = $this->client->request('PUT', "$this->baseuri/tika", [
                'headers' => ['Accept' => 'text/plain'],
                'body' => $file->get_content_file_handle(),
            ]);
        } catch (\Exception $e) {
            if (method_exists($e, 'getReasonPhrase')) {
                $debuginfo = $e->getReasonPhrase();
            } else {
                $debuginfo = $e->getMessage();
            }
            throw new extraction_exception('error:server:httprequest', 'metadataextractor_tika', '', null, $debuginfo);
        }

        $result = $this->extract_response_content($response);

        return $result;
    }


    /**
     * Extract metadata string from content of response.
     *
     * @param \GuzzleHttp\Psr7\Response $response
     *
     * @return string|false json string of metadata or false if failed.
     */
    private function extract_response_content(\GuzzleHttp\Psr7\Response $response) {

        if ($response->getStatusCode() == 200) {
            $result = $response->getBody()->getContents();
        } else {
            throw new extraction_exception('error:server', 'metadataextractor_tika');
        }

        return $result;
    }

}