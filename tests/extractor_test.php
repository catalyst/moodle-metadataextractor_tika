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
     * Test extracting metadata for a pdf file resource in Moodle.
     */
    public function test_extract_file_metadata_pdf() {
        global $CFG;

        $file = mock_file_builder::mock_pdf();
        $extractor = new extractor();

        // Skip if no valid tika executable or no tika server configured.
        if (empty($CFG->tikaservicetype) || !empty($extractor->get_missing_dependencies())) {
            $this->markTestSkipped('Test skipped as no valid tika service configuration or missing configuration dependencies.');
        } elseif ($CFG->tikaservicetype == extractor::SERVICETYPE_LOCAL && empty($CFG->tikalocalpath)) {
            $this->markTestSkipped('Test skipped as no valid path to tika install.');
        } elseif ($CFG->tikaservicetype == extractor::SERVICETYPE_SERVER && empty($CFG->tikaserverhost)) {
            $this->markTestSkipped('Test skipped as no valid tika server host URI or IP address.');
        }

        $result = $extractor->extract_file_metadata($file);

        $this->assertNotEmpty($result);
        $this->assertEquals('This has been generated by Moodle.', $result->subject);
        $this->assertEquals('Moodle ' . $CFG->release, $result->creator);
        $this->assertEquals('Test PDF', $result->title);
        $this->assertEquals('en', $result->language);
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
     * Test extracting metadata from a url resource.
     */
    public function test_extract_url_metadata() {
        global $CFG;

        $extractor = new extractor();

        $course = $this->getDataGenerator()->create_course();
        $url = $this->getDataGenerator()->create_module('url', ['course' => $course]);
        $url->externalurl = $CFG->dirroot . '/admin/tool/metadata/tests/fixtures/url_fixture.html';

        // Skip if no valid tika executable or no tika server configured.
        if (empty($CFG->tikaservicetype) || !empty($extractor->get_missing_dependencies())) {
            $this->markTestSkipped('Test skipped as no valid tika service configuration or missing configuration dependencies.');
        } elseif ($CFG->tikaservicetype == extractor::SERVICETYPE_LOCAL && empty($CFG->tikalocalpath)) {
            $this->markTestSkipped('Test skipped as no valid path to tika install.');
        } elseif ($CFG->tikaservicetype == extractor::SERVICETYPE_SERVER && empty($CFG->tikaserverhost)) {
            $this->markTestSkipped('Test skipped as no valid tika server host URI or IP address.');
        }

        $actual = $extractor->extract_url_metadata($url);
        $this->assertNotEmpty($actual);
        $this->assertInstanceOf(\metadataextractor_tika\metadata::class, $actual);

    }

    /**
     * Test getting missing dependencies.
     */
    public function test_get_missing_dependencies() {
        global $CFG;

        $extractor = new extractor();

        if (!empty($CFG->tikaservicetype)) {
            if ($CFG->tikaservicetype == $extractor::SERVICETYPE_SERVER && !class_exists('\GuzzleHttp\Client')) {
                // If in server configuration, require guzzle client.
                $this->assertSame('guzzle', $extractor->get_missing_dependencies());
            } else if ($CFG->tikaservicetype == $extractor::SERVICETYPE_LOCAL && empty(exec('which java'))) {
                // If in local configuration, require java installed.
                $this->assertSame('java', $extractor->get_missing_dependencies());
            } else {
                // If dependencies are installed, expect an empty string.
                $this->assertEmpty($extractor->get_missing_dependencies());
            }
        } else {
            // If service type is not configured, we don't know what dependencies we need, throw exception.
            $this->expectException(\tool_metadata\extraction_exception::class);
            $extractor->get_missing_dependencies();
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
