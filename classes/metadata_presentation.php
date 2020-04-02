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
 * The metadata model for presentation files.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

defined('MOODLE_INTERNAL') || die();

/**
 * The metadata model for presentation files.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata_presentation extends \metadataextractor_tika\metadata {

    /**
     * int count of slides in presentation.
     */
    public $slidecount;

    /**
     * @var int the paragraph count of presentation.
     */
    public $paragraphcount;

    /**
     * @var int the word count of presentation.
     */
    public $wordcount;

    /**
     * @var string the name of the last author to edit presentation.
     */
    public $lastauthor;

    /**
     * @var string the application used to produce presentation.
     */
    public $application;

    /**
     * @var mixed the version of the application used to produce presentation.
     */
    public $appversion;

    /**
     * @var mixed the time spent editing the presentation.
     */
    public $edittime;

    /**
     * @var int the revision number of presentation.
     */
    public $revisionnumber;

    /**
     * @var int count of how many notes on presentation.
     */
    public $notecount;

    /**
     * @var string presentation format, (On-screen Show (4:3), Widescreen etc.)
     */
    public $format;

    /**
     * @var string the name of presentation manager.
     */
    public $manager;

    /**
     * @var string the name of company presentation belongs to or was authored by.
     */
    public $company;

    const SUPPLEMENTARY_TABLE = 'tika_presentation_metadata';

    /**
     * @inheritDoc
     */
    protected function supplementary_key_map() {
        return [
            'slidecount' => ['Slide-Count', 'meta:slide-count', 'xmpTPg:NPages'],
            'paragraphcount' => ['Paragraph-Count', 'meta:paragraph-count'],
            'wordcount' => ['Word-Count', 'meta:word-count'],
            'lastauthor' => ['Last-Author', 'meta:last-author'],
            'application' => ['Application-Name', 'extended-properties:Application', 'generator'],
            'appversion' => ['Application-Version', 'extended-properties:AppVersion'],
            'edittime' => ['Edit-Time', 'extended-properties:EditTime'],
            'revisionnumber' => ['Revision-Number', 'cp:revision', 'editing-cycles'],
            'notecount' => ['Notes', 'extended-properties:Notes'],
            'format' => ['Presentation-Format', 'extended-properties:PresentationFormat'],
            'manager' => ['Manager', 'meta:manager', 'meta:Manager', 'custom:manager', 'custom:Manager',
                'extended-properties:Manager', 'extended-properties:manager'],
            'company' => ['Company', 'meta:company', 'meta:Company', 'custom:Company', 'custom:company',
                'extended-properties:Company', 'extended-properties:company']
        ];
    }
}
