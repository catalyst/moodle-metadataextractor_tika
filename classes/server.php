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
     * Server constructor.
     *
     * @param null|\GuzzleHttp\HandlerStack $handlerstack optional stack of handlers for middleware or testing mocks.
     *
     * @throws \tool_metadata\extraction_exception
     */
    public function __construct($handlerstack = null) {
        $tikaserverhost = get_config('tool_metadata_tika', 'tikaserverhost');
        if (!empty($tikaserverhost)) {
            $baseuri = $tikaserverhost;
            $tikaserverport = get_config('tool_metadata_tika', 'tikaserverport');
            if (!empty($tikaserverport)) {
                $baseuri .= ':' . $tikaserverport;
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

        $params = ['base_uri' => $baseuri];

        if (!empty($handlerstack)) {
            $params['handler'] = $handlerstack;
        }

        $this->client = new \GuzzleHttp\Client($params);
    }

    /**
     * Get json encoded file metadata from Tika server.
     *
     * @param \stored_file $file
     *
     * @return string|false $result json encoded metadata or false if metadata could not be extracted.
     * @throws \tool_metadata\extraction_exception
     */
    public function get_file_metadata(stored_file $file) {

        try {
            $response = $this->client->request('POST', '/meta/form', [
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

        if ($response->getStatusCode() == 200) {
            $result = $response->getBody()->getContents();
        } else {
            $result = false;
        }
        return $result;
    }

}
