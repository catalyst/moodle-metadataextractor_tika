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

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;
use tool_metadata\extraction_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/url/locallib.php');
require_once($CFG->dirroot . '/admin/tool/metadata/constants.php');
require_once($CFG->dirroot . '/admin/tool/metadata/vendor/autoload.php');

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

        try {
            $response = $this->test_connection();

            if ($response->getStatusCode() == 200) {
                $result = true;
            }
        } catch (extraction_exception $exception) {
            $result = false;
        }

        return $result;
    }

    /**
     * Handle an HTTP request exception thrown when attempting to extract metadata using Tika Server.
     *
     * @param \Exception $exception the exception caught when attempting to make HTTP request.
     *
     * @throws \tool_metadata\extraction_exception informative exception to assist in troubleshooting
     * Tika Server issues.
     */
    protected function handle_extraction_request_exception(\Exception $exception) {
        if (method_exists($exception, 'getResponse')) {
            $response = $exception->getResponse();
        } else {
            $response = null;
        }
        $status = !empty($response) ? $response->getStatusCode() : 500;
        $reason = !empty($response) ? $response->getReasonPhrase() : 'Internal Server Error';

        throw new extraction_exception('error:server:httprequest', 'metadataextractor_tika', '',
            ['status' => $status, 'reason' => $reason], $exception->getMessage());
    }

    /**
     * Get json encoded file metadata from Tika server.
     *
     * @param \Psr\Http\Message\StreamInterface $stream the stream to get metadata for.
     *
     * @return string|null $result json encoded metadata or false if metadata could not be extracted.
     */
    public function get_metadata(StreamInterface $stream) {
        try {
            $response = $this->client->request('PUT', "$this->baseuri/meta", [
                'headers' => ['Accept' => 'application/json'],
                'timeout' => \tool_metadata\helper::get_request_timeout_setting(),
                'body' => $stream
            ]);
        } catch (\Exception $exception) {
            $this->handle_extraction_request_exception($exception);
        }

        $result = $this->extract_response_content($response);

        return $result;
    }

    /**
     * Get the Tika parsed content of a stream resource.
     *
     * @param \Psr\Http\Message\StreamInterface $stream the stream to get content for.
     *
     * @return string|null $result the Tika parsed content of stream or null if no content.
     */
    public function get_content(StreamInterface $stream) {

        try {
            $response = $this->client->request('PUT', "$this->baseuri/tika", [
                'headers' => ['Accept' => 'text/plain'],
                'timeout' => \tool_metadata\helper::get_request_timeout_setting(),
                'body' => $stream,
            ]);
        } catch (\Exception $exception) {
            $this->handle_extraction_request_exception($exception);
        }

        $result = $this->extract_response_content($response);

        return $result;
    }

    /**
     * Get the Tika parsed mimetype for a stream resource.
     *
     * @param \Psr\Http\Message\StreamInterface $stream the stream to get mimetype of content for.
     *
     * @return string|null $result mimetype or null if mimetype could not be determined.
     */
    public function get_mimetype(StreamInterface $stream) {

        try {
            $response = $this->client->request('PUT', "$this->baseuri/detect/stream", [
                'timeout' => \tool_metadata\helper::get_request_timeout_setting(),
                'body' => $stream,
            ]);
        } catch (\Exception $exception) {
            $this->handle_extraction_request_exception($exception);
        }

        $result = $this->extract_response_content($response);

        return $result;
    }

    /**
     * Extract metadata string from content of response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return string|null string of metadata or null if no content.
     * @throws \tool_metadata\extraction_exception if extraction was not successful.
     */
    private function extract_response_content($response) {

        if ($response->getStatusCode() == 200) {
            $result = $response->getBody()->getContents();
        } else if ($response->getStatusCode() == 204) {
            $result = null;
        } else {
            throw new extraction_exception('error:server', 'metadataextractor_tika');
        }

        if (empty($result)) {
            $result = null;
        }

        return $result;
    }
}
