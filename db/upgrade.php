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
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
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

    return true;
}