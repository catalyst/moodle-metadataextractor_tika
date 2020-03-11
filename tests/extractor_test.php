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
 * Unit tests for tool_metadata extractor class.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use metadataextractor_tika\extractor;
use tool_metadata\mock_file_builder;

defined('MOODLE_INTERNAL') || die();



/**
 * Unit tests for tool_metadata extractor class.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      metadataextractor_tika
 */
class extractor_test extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test method for checking that configuration and dependencies required for
     * testing extraction methods exist, otherwise skip the test.
     *
     * @param extractor $extractor the extractor instance to check.
     */
    public function can_test_extraction(extractor $extractor) {
        $servicetype = $extractor->get_servicetype_set();
        if (empty($servicetype)) {
            $this->markTestSkipped('Test skipped as no valid tika service configuration.');
        } elseif ($servicetype == extractor::SERVICETYPE_LOCAL && !$extractor->is_local_tika_ready()) {
            $this->markTestSkipped('Test skipped as not missing configuration or dependencies for local tika.');
        } elseif ($servicetype == extractor::SERVICETYPE_SERVER) {
            try {
                $server = new \metadataextractor_tika\server();
                $ready = $server->is_ready();
            } catch (\tool_metadata\extraction_exception $ex) {
                $this->markTestSkipped('Test skipped as server configuration incorrect or connection error to server.');
            }
            if (!$ready) {
                $this->markTestSkipped('Tests skipped as tika server is not ready.');
            }
        }
    }

    /**
     * Test extracting metadata for a pdf file resource in Moodle.
     */
    public function test_extract_file_metadata_pdf() {
        global $CFG;

        $file = mock_file_builder::mock_pdf();
        $extractor = new extractor();

        // Make sure we have the correct configuration and dependencies to carry out this test.
        $this->can_test_extraction($extractor);

        $result = $extractor->extract_file_metadata($file);

        $this->assertNotEmpty($result);
        $this->assertEquals('Moodle ' . $CFG->release, $result->creator);
        $this->assertEquals('Test PDF', $result->title);
        $this->assertEquals('en', $result->language);
    }

    /**
     * Test extracting metadata from a url resource.
     */
    public function test_extract_url_metadata() {

        $extractor = new extractor();
        $course = $this->getDataGenerator()->create_course();
        $url = $this->getDataGenerator()->create_module('url', ['course' => $course]);

        // Make sure we have the correct configuration and dependencies to carry out this test.
        $this->can_test_extraction($extractor);

        $actual = $extractor->extract_url_metadata($url);
        $this->assertNotEmpty($actual);
        $this->assertInstanceOf(\metadataextractor_tika\metadata::class, $actual);
    }

    /**
     * Test getting missing dependencies.
     */
    public function test_get_missing_dependencies() {

        $extractor = new extractor();
        $servicetype = $extractor->get_servicetype_set();

        if (!empty($servicetype)) {
            if ($servicetype == $extractor::SERVICETYPE_SERVER && !class_exists('\GuzzleHttp\Client')) {
                // If in server configuration, require guzzle client.
                $this->assertTrue(in_array('guzzle', $extractor->get_missing_dependencies($servicetype)));
            } else if ($servicetype == $extractor::SERVICETYPE_LOCAL && empty(exec('which java'))) {
                // If in local configuration, require java installed.
                $this->assertTrue(in_array('java', $extractor->get_missing_dependencies($servicetype)));
            } else {
                // If dependencies are installed, expect an empty array.
                $actual = $extractor->get_missing_dependencies($servicetype);
                $this->assertIsArray($actual);
                $this->assertEmpty($actual);
            }
        } else {
            $this->expectException(\tool_metadata\extraction_exception::class);
            $extractor->get_missing_dependencies('somerandomvalue');
        }
    }

    /**
     * Test validation of file resources.
     */
    public function test_validate_resource_file() {
        $extractor = new extractor();

        $fs = get_file_storage();
        $syscontext = context_system::instance();

        // Create a test directory.
        $directory = $fs->create_directory($syscontext->id, 'tool_metadata', 'unittest', 0, '/');

        // Create a test document file.
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'tool_metadata',
            'filearea'  => 'unittest',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => 'test.doc',
        );
        $file = $fs->create_file_from_string($filerecord, 'Test file');

        $this->assertFalse($extractor->validate_resource($directory, TOOL_METADATA_RESOURCE_TYPE_FILE));
        $this->assertTrue($extractor->validate_resource($file, TOOL_METADATA_RESOURCE_TYPE_FILE));
    }

    /**
     * Provider for test_get_url_metadata.
     *
     * @return array
     */
    public function url_provider() {
        return [
            'A valid URL' => ['https://www.moodle.org', true],
            'An invalid URL' => ['http://user:@www.example.com', false],
            'An ftp URL' => ['ftp://speedtest.tele2.net/', false],
            'A malicious file URL' => ['file://home/root/.ssh/id_rsa', false]
        ];
    }

    /**
     * @dataProvider url_provider
     *
     * Test validation of url resources.
     */
    public function test_validate_resource_url($externalurl, $isvalid) {
        $extractor = new extractor();

        $course = $this->getDataGenerator()->create_course();
        $url = $this->getDataGenerator()->create_module('url', ['course' => $course]);
        $url->externalurl = $externalurl;

        $this->assertEquals($isvalid, $extractor->validate_resource($url, TOOL_METADATA_RESOURCE_TYPE_URL));
    }
}
