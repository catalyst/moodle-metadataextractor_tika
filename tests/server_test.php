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
 * Tests for server class.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for server class.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      metadataextractor_tika
 */
class server_test extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();

        set_config('tikaserverhost', 'localhost');
        set_config('tikaserverport', 9998);

        $dependencyinfo = \core_plugin_manager::instance()->get_plugin_info('local_aws');
        // Skip server tests if local_aws plugin dependency isn't installed as exceptions will be thrown.
        if (empty($dependencyinfo)) {
            $this->markTestSkipped(get_string('error:missingdependency', 'metadataextractor_tika', 'local_aws'));
        }
    }

    /**
     * Test creating a new server instance.
     */
    public function test_new_server() {

        unset_config('tikaserverhost');
        unset_config('tikaserverport');

        // Expect an exception to be thrown when there is no tika server hostname configured.
        $this->expectException(\tool_metadata\extraction_exception::class);
        $server = new metadataextractor_tika\server();
    }

    /**
     * Test extracting file metadata from Tika Server.
     */
    public function test_get_file_metadata() {
        global $CFG;

        // Mock a json metadata resource.
        $context = \context_system::instance();
        $fs = get_file_storage();
        $filerecord = (object) [
            'contextid' => $context->id,
            'filename' => 'test.pdf',
            'component' => 'metadataextractor_tika',
            'filearea' => 'testarea',
            'itemid' => 0,
            'filepath' => '/',
        ];
        $file = $fs->create_file_from_string($filerecord, 'Test PDF');

        // Mock the content of successful response from tika for a pdf file.
        $responsecontent = json_encode(['Content-Type' => 'application/pdf']);

        // Add mock responses to the handlerstack.
        $mock = new \GuzzleHttp\Handler\MockHandler([
            // Mock the server test response.
            new \GuzzleHttp\Psr7\Response(200),
            // Mock a standard JSON encoded successful response.
            new \GuzzleHttp\Psr7\Response(200,
                ['Content-Type' => ['application/json']],
                $responsecontent
            ),
            // Mock an empty response.
            new \GuzzleHttp\Psr7\Response(202,
                ['Content-Length' => 0]
            ),
            // Mock a connection error exception response.
            new \GuzzleHttp\Exception\ConnectException('connection error',
                new \GuzzleHttp\Psr7\Request('POST',
                $CFG->tikaserverhost . ':' . $CFG->tikaserverport . '/meta/form'))
        ]);
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);

        $server = new \metadataextractor_tika\server($handlerstack);

        // A successful call should return JSON encoded content.
        $actual = $server->get_file_metadata($file);
        $this->assertEquals($responsecontent, $actual);
        $this->assertJson($actual);
        // Any status other than 'OK' (200) should throw an extraction exception.
        $this->expectException(\tool_metadata\extraction_exception::class);
        $this->assertFalse($server->get_file_metadata($file));
        // A failed call should throw an extraction exception.
        $this->expectException(\tool_metadata\extraction_exception::class);
        $server->get_file_metadata($file);
    }

    /**
     * Test extraction of metadata from a mod_url resource.
     *
     * @throws \tool_metadata\extraction_exception
     */
    public function test_get_url_metadata() {
        global $CFG;

        $fixturecontent = file_get_contents($CFG->dirroot .
            '/admin/tool/metadata/tests/fixtures/url_fixture.html');
        $responsecontent = json_encode(['Content-Type' => 'text/html; charset=UTF-8', 'Content-Encoding' => 'UTF-8']);

        // Add mock responses to the handlerstack.
        $mock = new \GuzzleHttp\Handler\MockHandler([
            // Mock the server test response.
            new \GuzzleHttp\Psr7\Response(200),
            // Return the fixture first, to emulate retrieving html via GET request.
            new \GuzzleHttp\Psr7\Response(200, [], $fixturecontent),
            // Mock tika server returned metadata for the html retrieved.
            new \GuzzleHttp\Psr7\Response(200,
                ['Content-Type' => ['application/json']],
                $responsecontent
            ),
        ]);
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);

        $course = $this->getDataGenerator()->create_course();
        $url = $this->getDataGenerator()->create_module('url', ['course' => $course]);

        $server = new \metadataextractor_tika\server($handlerstack);
        $actual = $server->get_url_metadata($url);

        $this->assertNotEmpty($actual);
        $this->assertIsString($actual);
        $this->assertEquals($responsecontent, $actual);
        $this->assertJson($actual);
    }
}
