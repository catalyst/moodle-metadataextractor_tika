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
 * The metadata model for spreadsheet files.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

defined('MOODLE_INTERNAL') || die();

/**
 * The metadata model for spreadsheet files.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata_spreadsheet extends \metadataextractor_tika\metadata {

    /**
     * @var int the revision number of the spreadsheet.
     */
    public $revisionnumber;

    /**
     * @var string the application used to produce spreadsheet.
     */
    public $application;

    /**
     * @var mixed the version of the application used to produce spreadsheet.
     */
    public $appversion;

    /**
     * @var string the name of the last author to edit spreadsheet.
     */
    public $lastauthor;

    const SUPPLEMENTARY_TABLE = 'tika_spreadsheet_metadata';

    /**
     * @inheritDoc
     */
    protected function supplementary_key_map() {
        return [
            'revisionnumber' => ['Revision-Number', 'cp:revision'],
            'application' => ['Application-Name', 'extended-properties:Application'],
            'appversion' => ['Application-Version', 'extended-properties:AppVersion'],
            'lastauthor' => ['Last-Author', 'meta:last-author'],
            'manager' => ['Manager', 'meta:manager', 'meta:Manager', 'custom:manager', 'custom:Manager',
                'extended-properties:Manager', 'extended-properties:manager'],
            'company' => ['Company', 'meta:company', 'meta:Company', 'custom:Company', 'custom:company',
                'extended-properties:Company', 'extended-properties:company'],
        ];
    }
}