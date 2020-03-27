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
class metadataextractor_tika_server_test extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();

        set_config('tikaserverhost', 'localhost', 'metadataextractor_tika');
        set_config('tikaserverport', 9998, 'metadataextractor_tika');

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

        unset_config('tikaserverhost', 'metadataextractor_tika');
        unset_config('tikaserverport', 'metadataextractor_tika');

        // Expect an exception to be thrown when there is no tika server hostname configured.
        $this->expectException(\tool_metadata\extraction_exception::class);
        new metadataextractor_tika\server();
    }

    /**
     * Test extracting file metadata from Tika Server.
     */
    public function test_get_metadata_file() {

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
            // Mock a standard JSON encoded successful response.
            new \GuzzleHttp\Psr7\Response(200,
                ['Content-Type' => ['application/json']],
                $responsecontent
            ),
            // Mock an empty response.
            new \GuzzleHttp\Psr7\Response(204,
                ['Content-Length' => 0]
            ),
            // Mock a connection error exception response.
            new \GuzzleHttp\Exception\ConnectException('connection error',
                new \GuzzleHttp\Psr7\Request('GET', 'localhost:9998/tika'))
        ]);
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);

        $server = new \metadataextractor_tika\server($handlerstack);

        // A successful call should return JSON encoded content.
        $stream = \tool_metadata\helper::get_resource_stream($file, TOOL_METADATA_RESOURCE_TYPE_FILE);
        $actual = $server->get_metadata($stream);
        $this->assertEquals($responsecontent, $actual);
        $this->assertJson($actual);

        // Any status other than 'OK' (200) should throw an extraction exception.
        try {
            $server->get_metadata($stream);
        }
        catch (\Exception $exception) {
            $this->assertInstanceOf(\tool_metadata\extraction_exception::class, $exception);
        }

        // A connection error should throw an extraction exception.
        try {
            $server->get_metadata($stream);
        }
        catch (\Exception $exception) {
            $this->assertInstanceOf(\tool_metadata\extraction_exception::class, $exception);
        }
    }

    /**
     * Test extraction of metadata from a mod_url resource.
     *
     * @throws \tool_metadata\extraction_exception
     */
    public function test_get_metadata_url() {
        global $CFG;

        $fixture = fopen($CFG->dirroot . '/admin/tool/metadata/tests/fixtures/url_fixture.html', 'rb');
        $responsecontent = json_encode(['Content-Type' => 'text/html; charset=UTF-8', 'Content-Encoding' => 'UTF-8']);

        // Add mock responses to the handlerstack.
        $mock = new \GuzzleHttp\Handler\MockHandler([
            // Mock tika server returned metadata for the html retrieved.
            new \GuzzleHttp\Psr7\Response(200,
                ['Content-Type' => ['application/json']],
                $responsecontent
            ),
        ]);
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);

        $server = new \metadataextractor_tika\server($handlerstack);
        $stream = new \GuzzleHttp\Psr7\Stream($fixture);

        $actual = $server->get_metadata($stream);

        $this->assertNotEmpty($actual);
        $this->assertIsString($actual);
        $this->assertEquals($responsecontent, $actual);
        $this->assertJson($actual);
    }

    /**
     * Test using Tika server to detect the mimetype for a file resource.
     */
    public function test_get_mimetype_file() {
        [$unused, $file] = \tool_metadata\mock_file_builder::mock_document();

        // Add mock responses to the handlerstack.
        $mock = new \GuzzleHttp\Handler\MockHandler([
            // Mock a standard mimetype extraction.
            new \GuzzleHttp\Psr7\Response(200,
                ['Content-Type' => ['application/json']],
                $file->get_mimetype()
            ),
            // Mock an empty response.
            new \GuzzleHttp\Psr7\Response(204,
                ['Content-Length' => 0]
            ),
            // Mock a connection error exception response.
            new \GuzzleHttp\Exception\ConnectException('connection error',
                new \GuzzleHttp\Psr7\Request('GET', 'localhost:9998/tika'))
        ]);
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);

        $server = new \metadataextractor_tika\server($handlerstack);
        $stream = \tool_metadata\helper::get_resource_stream($file, TOOL_METADATA_RESOURCE_TYPE_FILE);

        // A successful call should return string of file mimetype.
        $this->assertEquals($file->get_mimetype(), $server->get_mimetype($stream));

        // An empty response should return a null value.
        $this->assertNull($server->get_mimetype($stream));

        // A connection error should throw an extraction exception.
        try {
            $server->get_mimetype($stream);
            $this->fail('Exception expected, none thrown');
        } catch(\Exception $exception) {
            $this->assertInstanceOf(\tool_metadata\extraction_exception::class, $exception);
        }
    }

    /**
     * Test using Tika server to detect the mimetype for a URL resource.
     */
    public function test_get_mimetype_url() {
        global $CFG;

        $fixture = fopen($CFG->dirroot . '/admin/tool/metadata/tests/fixtures/url_fixture.html', 'rb');

        $course = $this->getDataGenerator()->create_course();
        $url = $this->getDataGenerator()->create_module('url', ['course' => $course]);
        $mockmimetype = 'text/html; charset=UTF-8';

        // Add mock responses to the handlerstack.
        $mock = new \GuzzleHttp\Handler\MockHandler([
            // Mock a standard mimetype extraction.
            new \GuzzleHttp\Psr7\Response(200,
                ['Content-Type' => ['application/json']],
                $mockmimetype
            ),
            // Mock an empty response.
            new \GuzzleHttp\Psr7\Response(204,
                ['Content-Length' => 0]
            ),
            // Mock a connection error exception response.
            new \GuzzleHttp\Exception\ConnectException('connection error',
                new \GuzzleHttp\Psr7\Request('GET', 'localhost:9998/tika'))
        ]);
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);

        $server = new \metadataextractor_tika\server($handlerstack);
        $stream = new \GuzzleHttp\Psr7\Stream($fixture);

        // A successful call should return string of file mimetype.
        $this->assertEquals($mockmimetype, $server->get_mimetype($stream));

        // An empty response should return a null value.
        $this->assertNull($server->get_mimetype($stream));

        // A connection error should throw an extraction exception.
        try {
            $server->get_mimetype($stream);
            $this->fail('Exception expected, none thrown');
        } catch(\Exception $exception) {
            $this->assertInstanceOf(\tool_metadata\extraction_exception::class, $exception);
        }
    }

    /**
     * Test Tika Server connection test.
     */
    public function test_test_connection() {
        // Add mock responses to the handlerstack.
        $mock = new \GuzzleHttp\Handler\MockHandler([
            // Mock a successful OK test response.
            new \GuzzleHttp\Psr7\Response(200),
            // Mock a successful non-OK response.
            new \GuzzleHttp\Psr7\Response(204),
            // Mock a client error response.
            new \GuzzleHttp\Psr7\Response(404),
            // Mock a connection error exception response.
            new \GuzzleHttp\Exception\ConnectException('connection error',
                new \GuzzleHttp\Psr7\Request('GET', 'localhost:9998/tika'))
        ]);
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);

        $server = new \metadataextractor_tika\server($handlerstack);

        // Expect a 200 response status to return a response.
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $server->test_connection());

        // Expect a non-200 response status to return a response.
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $server->test_connection());

        // Expect a client error to throw an exception.
        try {
            $server->test_connection();
            $this->fail('Exception expected, none thrown');
        } catch(\Exception $exception) {
            $this->assertInstanceOf(\tool_metadata\extraction_exception::class, $exception);
        }

        // Expect a connection error to throw an exception.
        try {
            $server->test_connection();
            $this->fail('Exception expected, none thrown');
        } catch(\Exception $exception) {
            $this->assertInstanceOf(\tool_metadata\extraction_exception::class, $exception);
        }
    }

    /**
     * Test establishing if Tika Server is ready to process resources.
     */
    public function test_is_ready() {

        // Add mock responses to the handlerstack.
        $mock = new \GuzzleHttp\Handler\MockHandler([
            // Mock a successful OK test response.
            new \GuzzleHttp\Psr7\Response(200),
            // Mock a successful non-OK response.
            new \GuzzleHttp\Psr7\Response(204),
            // Mock a client error response.
            new \GuzzleHttp\Psr7\Response(404),
            // Mock a connection error exception response.
            new \GuzzleHttp\Exception\ConnectException('connection error',
                new \GuzzleHttp\Psr7\Request('GET', 'localhost:9998/tika'))
        ]);
        $handlerstack = \GuzzleHttp\HandlerStack::create($mock);

        $server = new \metadataextractor_tika\server($handlerstack);

        // Expect a 200 response status to return true.
        $this->assertTrue($server->is_ready());
        // Expect a non-200 response status to return false.
        $this->assertFalse($server->is_ready());
        // Expect a client error to return false.
        $this->assertFalse($server->is_ready());
        // Expect a connection error to return false.
        $this->assertFalse($server->is_ready());
    }
}
