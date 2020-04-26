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
 * metadataextractor_tika metadata tests.
 *
 * @package    metadataextractor_tika
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/metadata/constants.php');
require_once(__DIR__ . '/metadata_mock.php');

/**
 * metadataextractor_tika metadata tests.
 *
 * @package    metadataextractor_tika
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      metadataextractor_tika
 */
class metadataextractor_tika_metadata_testcase extends advanced_testcase {

    public function setUp() {
        global $DB;

        $this->resetAfterTest();

        // Create a table for mock metadata subclass.
        $dbman = $DB->get_manager();
        $table = new \xmldb_table(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE);
        // Add mandatory fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resourcehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'id');
        // Add the fields used in metadata subclass.
        $table->add_field('wordcount', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'resourcehash');
        $table->add_field('pagecount', XMLDB_TYPE_INTEGER, '5', null, null, null, null, 'wordcount');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman->create_table($table);
    }

    public function tearDown() {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new \xmldb_table(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE);
        $dbman->drop_table($table);
    }

    public function test_get_supplementary_table() {

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        $metadata = new \metadataextractor_tika\metadata_mock(0, $resourcehash, $rawdata);
        $actual = $metadata->get_supplementary_table();

        $this->assertEquals(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE, $actual);
    }

    public function test_has_supplementary_data() {

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        // Extending classes may have supplementary data.
        $metadata = new \metadataextractor_tika\metadata_mock(0, $resourcehash, $rawdata);
        $this->assertTrue($metadata->has_supplementary_data());

        // Base class does not have supplementary data.
        $metadata = new \metadataextractor_tika\metadata(0, $resourcehash, $rawdata);
        $this->assertFalse($metadata->has_supplementary_data());
    }

    public function test_get_record() {

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        $metadata = new \metadataextractor_tika\metadata_mock(0, $resourcehash, $rawdata);

        $actual = $metadata->get_record();

        $this->assertEquals($metadata->id, $actual->id);
        $this->assertEquals($resourcehash, $actual->resourcehash);
        $this->assertEquals($rawdata['meta:creator'], $actual->creator);
        $this->assertEquals($rawdata['meta:title'], $actual->title);
        $this->assertEquals($rawdata['meta:word-count'], $actual->wordcount);
        $this->assertEquals($rawdata['meta:page-count'], $actual->pagecount);
    }

    public function test_populate_from_id() {
        global $DB;

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        // Insert existing records for testing metadata population from existing records.
        $id = $DB->insert_record(\metadataextractor_tika\metadata_mock::TABLE,
            ['creator' => $rawdata['meta:creator'], 'title' => $rawdata['meta:title'], 'resourcehash' => $resourcehash]);
        $DB->insert_record(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE,
            [
                'wordcount' => $rawdata['meta:word-count'],
                'pagecount' => $rawdata['meta:page-count'],
                'resourcehash' => $resourcehash,
            ]);

        $metadata = new \metadataextractor_tika\metadata_mock($id);

        $this->assertInstanceOf(\metadataextractor_tika\metadata_mock::class, $metadata);
        $this->assertEquals($id, $metadata->get('id'));
        $this->assertEquals($resourcehash, $metadata->get_resourcehash());
        $this->assertEquals($rawdata['meta:creator'], $metadata->get('creator'));
        $this->assertEquals($rawdata['meta:title'], $metadata->get('title'));
        $this->assertEquals($rawdata['meta:word-count'], $metadata->get('wordcount'));
        $this->assertEquals($rawdata['meta:page-count'], $metadata->get('pagecount'));

        $metadata = new \metadataextractor_tika\metadata($id);

        $this->assertInstanceOf(\metadataextractor_tika\metadata::class, $metadata);
        $this->assertEquals($id, $metadata->get('id'));
        $this->assertEquals($resourcehash, $metadata->get_resourcehash());
        $this->assertEquals($rawdata['meta:creator'], $metadata->get('creator'));
        $this->assertEquals($rawdata['meta:title'], $metadata->get('title'));
        $this->assertClassNotHasAttribute('wordcount', \metadataextractor_tika\metadata::class);
        $this->assertClassNotHasAttribute('pagecount', \metadataextractor_tika\metadata::class);
    }

    public function test_populate_from_resourcehash() {
        global $DB;

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        // Insert existing records for testing metadata population from existing records.
        $id = $DB->insert_record(\metadataextractor_tika\metadata_mock::TABLE,
            ['creator' => $rawdata['meta:creator'], 'title' => $rawdata['meta:title'], 'resourcehash' => $resourcehash]);
        $DB->insert_record(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE,
            [
                'wordcount' => $rawdata['meta:word-count'],
                'pagecount' => $rawdata['meta:page-count'],
                'resourcehash' => $resourcehash,
            ]);

        $metadata = new \metadataextractor_tika\metadata_mock(0, $resourcehash);

        $this->assertInstanceOf(\metadataextractor_tika\metadata_mock::class, $metadata);
        $this->assertEquals($id, $metadata->get('id'));
        $this->assertEquals($resourcehash, $metadata->get_resourcehash());
        $this->assertEquals($rawdata['meta:creator'], $metadata->get('creator'));
        $this->assertEquals($rawdata['meta:title'], $metadata->get('title'));
        $this->assertEquals($rawdata['meta:word-count'], $metadata->get('wordcount'));
        $this->assertEquals($rawdata['meta:page-count'], $metadata->get('pagecount'));

        $metadata = new \metadataextractor_tika\metadata(0, $resourcehash);

        $this->assertInstanceOf(\metadataextractor_tika\metadata::class, $metadata);
        $this->assertEquals($id, $metadata->get('id'));
        $this->assertEquals($resourcehash, $metadata->get_resourcehash());
        $this->assertEquals($rawdata['meta:creator'], $metadata->get('creator'));
        $this->assertEquals($rawdata['meta:title'], $metadata->get('title'));
        $this->assertClassNotHasAttribute('wordcount', \metadataextractor_tika\metadata::class);
        $this->assertClassNotHasAttribute('pagecount', \metadataextractor_tika\metadata::class);

        $resourcehash = sha1(random_string());

        $this->expectException(\tool_metadata\metadata_exception::class);
        $unused = new \metadataextractor_tika\metadata_mock(0, $resourcehash);
    }

    public function test_populate_from_raw() {

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        $metadata = new \metadataextractor_tika\metadata_mock(0, $resourcehash, $rawdata);

        $this->assertInstanceOf(\metadataextractor_tika\metadata_mock::class, $metadata);
        $this->assertEquals(0, $metadata->get('id'));
        $this->assertEquals($resourcehash, $metadata->get_resourcehash());
        $this->assertEquals($rawdata['meta:creator'], $metadata->get('creator'));
        $this->assertEquals($rawdata['meta:title'], $metadata->get('title'));
        $this->assertEquals($rawdata['meta:word-count'], $metadata->get('wordcount'));
        $this->assertEquals($rawdata['meta:page-count'], $metadata->get('pagecount'));

        $metadata = new \metadataextractor_tika\metadata(0, $resourcehash, $rawdata);

        $this->assertInstanceOf(\metadataextractor_tika\metadata::class, $metadata);
        $this->assertEquals(0, $metadata->get('id'));
        $this->assertEquals($resourcehash, $metadata->get_resourcehash());
        $this->assertEquals($rawdata['meta:creator'], $metadata->get('creator'));
        $this->assertEquals($rawdata['meta:title'], $metadata->get('title'));
        $this->assertClassNotHasAttribute('wordcount', \metadataextractor_tika\metadata::class);
        $this->assertClassNotHasAttribute('pagecount', \metadataextractor_tika\metadata::class);
    }

    public function test_create() {
        global $DB;

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        $metadata = new \metadataextractor_tika\metadata_mock(0, $resourcehash, $rawdata);
        $metadata->create();

        // Metadata instance variable values should be correctly stored in database.
        $baserecord = $DB->get_record(\metadataextractor_tika\metadata_mock::TABLE, ['id' => $metadata->id]);
        $this->assertEquals($metadata->get_resourcehash(), $baserecord->resourcehash);
        $this->assertEquals($metadata->get('creator'), $baserecord->creator);
        $this->assertEquals($metadata->get('title'), $baserecord->title);
        $supplementaryrecord = $DB->get_record(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE,
            ['resourcehash' => $metadata->get_resourcehash()]);
        $this->assertEquals($metadata->get_resourcehash(), $supplementaryrecord->resourcehash);
        $this->assertEquals($metadata->get('wordcount'), $supplementaryrecord->wordcount);
        $this->assertEquals($metadata->get('pagecount'), $supplementaryrecord->pagecount);

        // Cannot create metadata records if record already exists.
        $this->expectException(\tool_metadata\metadata_exception::class);
        $metadata->create();
    }

    /**
     * Test edge case of creating metadata record when supplementary metadata
     * record already exists.
     */
    public function test_create_supplementary_record_exists() {
        global $DB;

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        // Create existing supplementary record.
        $record = new stdClass();
        $record->resourcehash = $resourcehash;
        $record->wordcount = 2000;
        $record->pagecount = 2;
        $existingsupplementaryid = $DB->insert_record(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE, $record);

        $metadata = new \metadataextractor_tika\metadata_mock(0, $resourcehash, $rawdata);
        $metadata->create();

        // Metadata instance variable values should be correctly stored in database.
        $baserecord = $DB->get_record(\metadataextractor_tika\metadata_mock::TABLE, ['id' => $metadata->id]);
        $this->assertEquals($metadata->get_resourcehash(), $baserecord->resourcehash);
        $this->assertEquals($metadata->get('creator'), $baserecord->creator);
        $this->assertEquals($metadata->get('title'), $baserecord->title);
        $supplementaryrecord = $DB->get_record(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE,
            ['id' => $existingsupplementaryid]);
        $this->assertEquals($metadata->get_resourcehash(), $supplementaryrecord->resourcehash);
        $this->assertEquals($metadata->get('wordcount'), $supplementaryrecord->wordcount);
        $this->assertEquals($metadata->get('pagecount'), $supplementaryrecord->pagecount);

        // Cannot create metadata records if record already exists.
        $this->expectException(\tool_metadata\metadata_exception::class);
        $metadata->create();
    }

    public function test_update() {
        global $DB;

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        $metadata = new \metadataextractor_tika\metadata_mock(0, $resourcehash, $rawdata);
        $metadata->create();

        // Update metadata.
        $metadata->set('creator', 'Moodle 2.0');
        $metadata->set('title', 'Updated title');
        $metadata->set('wordcount', 4000);
        $metadata->set('pagecount', 4);

        $result = $metadata->update();

        $this->assertTrue($result);

        // Metadata instance variable values should be correctly stored in database.
        $baserecord = $DB->get_record(\metadataextractor_tika\metadata_mock::TABLE, ['id' => $metadata->id]);
        $this->assertEquals($metadata->get_resourcehash(), $baserecord->resourcehash);
        $this->assertEquals($metadata->get('creator'), $baserecord->creator);
        $this->assertEquals($metadata->get('title'), $baserecord->title);
        $supplementaryrecord = $DB->get_record(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE,
            ['resourcehash' => $metadata->get_resourcehash()]);
        $this->assertEquals($metadata->get_resourcehash(), $supplementaryrecord->resourcehash);
        $this->assertEquals($metadata->get('wordcount'), $supplementaryrecord->wordcount);
        $this->assertEquals($metadata->get('pagecount'), $supplementaryrecord->pagecount);
    }

    /**
     * Test edge case of updating metadata record when supplementary metadata
     * record does not exist.
     */
    public function test_update_supplementary_record_not_exists() {
        global $DB;

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        $metadata = new \metadataextractor_tika\metadata_mock(0, $resourcehash, $rawdata);
        $metadata->create();

        // Manually delete the supplementary record only, to test that it is recreated at update time.
        $DB->delete_records($metadata->get_supplementary_table(), ['id' => $metadata->get_supplementary_id()]);

        // Update metadata.
        $metadata->set('creator', 'Moodle 2.0');
        $metadata->set('title', 'Updated title');
        $metadata->set('wordcount', 4000);
        $metadata->set('pagecount', 4);

        $result = $metadata->update();

        $this->assertTrue($result);

        // Metadata instance variable values should be correctly stored in database.
        $baserecord = $DB->get_record(\metadataextractor_tika\metadata_mock::TABLE, ['id' => $metadata->id]);
        $this->assertEquals($metadata->get_resourcehash(), $baserecord->resourcehash);
        $this->assertEquals($metadata->get('creator'), $baserecord->creator);
        $this->assertEquals($metadata->get('title'), $baserecord->title);
        $supplementaryrecord = $DB->get_record(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE,
            ['resourcehash' => $metadata->get_resourcehash()]);
        $this->assertEquals($metadata->get_resourcehash(), $supplementaryrecord->resourcehash);
        $this->assertEquals($metadata->get('wordcount'), $supplementaryrecord->wordcount);
        $this->assertEquals($metadata->get('pagecount'), $supplementaryrecord->pagecount);
    }

    public function test_delete() {
        global $DB;

        // Emulate tika extracted raw metadata.
        $rawdata = [];
        // Fixed tika fields.
        $rawdata['meta:creator'] = 'Moodle';
        $rawdata['meta:title'] = 'Test title';
        // Supplementary fields.
        $rawdata['meta:word-count'] = 3000;
        $rawdata['meta:page-count'] = 3;

        // Emulate resourcehash from resource content.
        $resourcehash = sha1(random_string());

        $metadata = new \metadataextractor_tika\metadata_mock(0, $resourcehash, $rawdata);
        $metadata->create();

        $id = $metadata->id;
        $resourcehash = $metadata->get_resourcehash();

        $metadata->delete();

        // Records should be deleted.
        $baserecord = $DB->get_record(\metadataextractor_tika\metadata_mock::TABLE, ['id' => $id]);
        $this->assertFalse($baserecord);
        $supplementaryrecord = $DB->get_record(\metadataextractor_tika\metadata_mock::SUPPLEMENTARY_TABLE,
            ['resourcehash' => $resourcehash]);
        $this->assertFalse($supplementaryrecord);
    }
}