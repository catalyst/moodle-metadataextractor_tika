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
 * Upgrade database for metadataextractor_tika.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/metadata/db/upgradelib.php');

function xmldb_metadataextractor_tika_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020020600) {
        $table = new xmldb_table('metadataextractor_tika');

        $key = new xmldb_key('contenthash', XMLDB_KEY_UNIQUE, ['contenthash']);

        // Launch drop key contenthash.
        $dbman->drop_key($table, $key);

        // Rename field contenthash on table metadataextractor_tika to resourcehash.
        $field = new xmldb_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch rename field resourcehash.
        $dbman->rename_field($table, $field, 'resourcehash');

        $key = new xmldb_key('resourcehash', XMLDB_KEY_UNIQUE, ['resourcehash']);

        // Launch add key resourcehash.
        $dbman->add_key($table, $key);

        // Tika savepoint reached.
        upgrade_plugin_savepoint(true, 2020020600, 'metadataextractor', 'tika');
    }

    if ($oldversion < 2020030201) {
        $table = new xmldb_table('metadataextractor_tika');
        // Lengthen field title on table metadataextractor_tika.
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, '1000', null, null, null, null, 'description');

        // Launch change field type title.
        $dbman->change_field_type($table, $field);

        // Tika savepoint reached.
        upgrade_plugin_savepoint(true, 2020030201, 'metadataextractor', 'tika');
    }

    if ($oldversion < 2020031101) {

        // Define table tika_document to be created.
        $table = new xmldb_table('tika_document_metadata');

        // Adding fields to table tika_document_metadata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resourcehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pagecount', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('paragraphcount', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('linecount', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('wordcount', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('charactercount', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('charactercountwithspaces', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('manager', XMLDB_TYPE_CHAR, '500', null, null, null, null);
        $table->add_field('company', XMLDB_TYPE_CHAR, '500', null, null, null, null);

        // Adding keys to table tika_document_metadata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('resourcehash', XMLDB_KEY_UNIQUE, ['resourcehash']);

        // Conditionally launch create table for tika_document_metadata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Tika savepoint reached.
        upgrade_plugin_savepoint(true, 2020031101, 'metadataextractor', 'tika');
    }

    if ($oldversion < 2020031301) {

        // Define table tika_pdf_metadata to be created.
        $table = new xmldb_table('tika_pdf_metadata');

        // Adding fields to table tika_pdf_metadata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resourcehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pagecount', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('creationtool', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('pdfversion', XMLDB_TYPE_CHAR, '30', null, null, null, null);

        // Adding keys to table tika_pdf_metadata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('resourcehash', XMLDB_KEY_UNIQUE, ['resourcehash']);

        // Conditionally launch create table for tika_pdf_metadata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Tika savepoint reached.
        upgrade_plugin_savepoint(true, 2020031301, 'metadataextractor', 'tika');
    }

    if ($oldversion < 2020031302) {
        $table = new xmldb_table('metadataextractor_tika');
        // Lengthen field creator on table metadataextractor_tika.
        $field = new xmldb_field('creator', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'subject');

        // Launch change field type title.
        $dbman->change_field_type($table, $field);

        // Tika savepoint reached.
        upgrade_plugin_savepoint(true, 2020031302, 'metadataextractor', 'tika');
    }

    if ($oldversion < 2020032301) {

        // Define field resourcecreated to be added to metadataextractor_tika.
        $table = new xmldb_table('metadataextractor_tika');
        $field = new xmldb_field('resourcecreated', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'date');

        // Conditionally launch add field resourcecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field resourcemodifed to be added to metadataextractor_tika.
        $table = new xmldb_table('metadataextractor_tika');
        $field = new xmldb_field('resourcemodifed', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'resourcecreated');

        // Conditionally launch add field resourcemodifed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Tika savepoint reached.
        upgrade_plugin_savepoint(true, 2020032301, 'metadataextractor', 'tika');
    }

    if ($oldversion < 2020032401) {

        // Define table tika_image_metadata to be created.
        $table = new xmldb_table('tika_image_metadata');

        // Adding fields to table tika_image_metadata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resourcehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('height', XMLDB_TYPE_CHAR, '15', null, null, null, null);
        $table->add_field('width', XMLDB_TYPE_CHAR, '15', null, null, null, null);
        $table->add_field('bitspersample', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('location', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table tika_image_metadata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('resourcehash', XMLDB_KEY_UNIQUE, ['resourcehash']);

        // Conditionally launch create table for tika_image_metadata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Tika savepoint reached.
        upgrade_plugin_savepoint(true, 2020032401, 'metadataextractor', 'tika');
    }

    if ($oldversion < 2020032501) {

        // Define table tika_audio_metadata to be created.
        $table = new xmldb_table('tika_audio_metadata');

        // Adding fields to table tika_audio_metadata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resourcehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('duration', XMLDB_TYPE_CHAR, '25', null, null, null, null);
        $table->add_field('samplerate', XMLDB_TYPE_CHAR, '25', null, null, null, null);
        $table->add_field('location', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table tika_audio_metadata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('resourcehash', XMLDB_KEY_UNIQUE, ['resourcehash']);

        // Conditionally launch create table for tika_audio_metadata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tika_video_metadata to be created.
        $table = new xmldb_table('tika_video_metadata');

        // Adding fields to table tika_video_metadata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resourcehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('height', XMLDB_TYPE_CHAR, '15', null, null, null, null);
        $table->add_field('width', XMLDB_TYPE_CHAR, '15', null, null, null, null);
        $table->add_field('duration', XMLDB_TYPE_CHAR, '25', null, null, null, null);
        $table->add_field('samplerate', XMLDB_TYPE_CHAR, '25', null, null, null, null);
        $table->add_field('framesize', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('location', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table tika_video_metadata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('resourcehash', XMLDB_KEY_UNIQUE, ['resourcehash']);

        // Conditionally launch create table for tika_video_metadata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Tika savepoint reached.
        upgrade_plugin_savepoint(true, 2020032501, 'metadataextractor', 'tika');
    }

    if ($oldversion < 2020032502) {

        // Define table tika_spreadsheet_metadata to be created.
        $table = new xmldb_table('tika_spreadsheet_metadata');

        // Adding fields to table tika_spreadsheet_metadata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resourcehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('revisionnumber', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('application', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('appversion', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('lastauthor', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('manager', XMLDB_TYPE_CHAR, '500', null, null, null, null);
        $table->add_field('company', XMLDB_TYPE_CHAR, '500', null, null, null, null);

        // Adding keys to table tika_spreadsheet_metadata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('resourcehash', XMLDB_KEY_UNIQUE, ['resourcehash']);

        // Conditionally launch create table for tika_spreadsheet_metadata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Tika savepoint reached.
        upgrade_plugin_savepoint(true, 2020032502, 'metadataextractor', 'tika');
    }

    if ($oldversion < 2020032601) {

        // Define table tika_presentation_metadata to be created.
        $table = new xmldb_table('tika_presentation_metadata');

        // Adding fields to table tika_presentation_metadata.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resourcehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('slidecount', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('paragraphcount', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('wordcount', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('lastauthor', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('application', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('appversion', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('edittime', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('revisionnumber', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('notecount', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('format', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('manager', XMLDB_TYPE_CHAR, '500', null, null, null, null);
        $table->add_field('company', XMLDB_TYPE_CHAR, '500', null, null, null, null);

        // Adding keys to table tika_presentation_metadata.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('resourcehash', XMLDB_KEY_UNIQUE, ['resourcehash']);

        // Conditionally launch create table for tika_presentation_metadata.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Tika savepoint reached.
        upgrade_plugin_savepoint(true, 2020032601, 'metadataextractor', 'tika');
    }

    return true;
}
