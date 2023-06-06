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
 * Unit tests for tool_metadata tika_helper class.
 *
 * @package    metadataextractor_tika
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for tool_metadata tika_helper class.
 *
 * @package    metadataextractor_tika
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      metadataextractor_tika
 */
class metadataextractor_tika_helper_test extends advanced_testcase {

    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Provider of test mimetypes for file types.
     *
     * @return array
     */
    public function filetype_provider() {
        return [
            'Text document' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'document'],
            'PDF' => ['application/pdf', 'pdf'],
            'Presentation' => ['application/vnd.ms-powerpoint', 'presentation'],
            'Spreadsheet' => ['application/vnd.ms-excel', 'spreadsheet'],
            'Image' => ['image/jpeg', 'image'],
            'Video' => ['video/mpeg', 'video'],
            'Audio' => ['audio/mp4', 'audio'],
            'Archive' => ['application/zip', 'archive'],
            'Other' => ['application/faketestmimetype', 'other']
        ];
    }

    /**
     * Test getting file type from mimetype.
     *
     * @dataProvider filetype_provider
     *
     * @param string $mimetype mimetype to test.
     * @param string $expected expected file type to return.
     */
    public function test_get_filetype(string $mimetype, string $expected) {
        $actual = \metadataextractor_tika\tika_helper::get_filetype($mimetype);

        $this->assertEquals($expected, $actual);
    }


    /**
     * Provider of supported file types.
     *
     * @return array
     */
    public function supported_filetypes_provider() {
        return [
            [\metadataextractor_tika\tika_helper::FILETYPE_DOCUMENT, true],
            [\metadataextractor_tika\tika_helper::FILETYPE_PDF, true],
            [\metadataextractor_tika\tika_helper::FILETYPE_IMAGE, true],
            [\metadataextractor_tika\tika_helper::FILETYPE_AUDIO, true],
            [\metadataextractor_tika\tika_helper::FILETYPE_VIDEO, true],
            [\metadataextractor_tika\tika_helper::FILETYPE_SPREADSHEET, true],
            [\metadataextractor_tika\tika_helper::FILETYPE_PRESENTATION, true],
            [\metadataextractor_tika\tika_helper::FILETYPE_ARCHIVE, false],
        ];
    }

    /**
     * Test checking if file type supported.
     *
     * @dataProvider supported_filetypes_provider
     *
     * @param string $filetype the file type to test.
     * @param bool $supported should the file type be supported.
     */
    public function test_is_filetype_supported(string $filetype, bool $supported) {
        $this->assertEquals($supported, \metadataextractor_tika\tika_helper::is_filetype_supported($filetype));
    }

    /**
     * Test getting metadata class based on mimetype.
     *
     * @dataProvider filetype_provider
     *
     * @param string $mimetype the IANA mimetype.
     * @param string $classsubstring the class substring expected.
     */
    public function test_get_metadata_class(string $mimetype, string $classsubstring) {
        $actual = \metadataextractor_tika\tika_helper::get_metadata_class($mimetype);

        if (!in_array($classsubstring, \metadataextractor_tika\tika_helper::SUPPORTED_FILETYPES)) {
            $expected = '\metadataextractor_tika\metadata';
        } else {
            $expected = '\metadataextractor_tika\metadata_' . $classsubstring;
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for testing extraction of 'type/subtype' from a mimetype.
     *
     * @return array string[][] each element in shape of:
     * [
     *     string complete mimetype with encoding,
     *     string mimetype within header,
     * ]
     */
    public function mimetype_provider() {
        return [
            ['multipart/form-data; boundary=---------------------------8721656041911415653955004498', 'multipart/form-data'],
            ['application/json; charset=utf-8', 'application/json'],
            ['application/octet-stream', 'application/octet-stream'],
            ['text/html; charset=utf-8', 'text/html'],
            ['text/plain; charset=ISO-8859-1', 'text/plain'],
            ['text/plain', 'text/plain'],
            ['text/html', 'text/html'],
            ['application/pdf', 'application/pdf'],
            ['application/msword', 'application/msword'],
            ['application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.text'],
            ['video/mp4; codecs="avc1.4d002a"', 'video/mp4'],
            ['audio/ogg; codecs=vorbis', 'audio/ogg'],

        ];
    }

    /**
     * Test extracting the subtype/type from a full mimetype with parameters, as may be found in
     * a 'Content-Type' header.
     *
     * @dataProvider mimetype_provider
     *
     * @param string $mimetype the raw mimetype value with any parameters included.
     * @param string $expected the expected mimetype without parameters.
     */
    public function test_get_mimetype_without_parameters(string $mimetype, string $expected) {
        $actual = \metadataextractor_tika\tika_helper::get_mimetype_without_parameters($mimetype);

        $this->assertIsString($actual);
        $this->assertEquals($expected, $actual);
    }
}
