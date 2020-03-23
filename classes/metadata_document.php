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
 * The metadata model for document files.
 *
 * @package    tool_metadata
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

defined('MOODLE_INTERNAL') || die();

/**
 * The metadata model for document files.
 *
 * This model follows a modified version of Dublin Core tailored for Moodle.
 *
 * @package    tool_metadata
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata_document extends \metadataextractor_tika\metadata {

    /**
     * @var int the page count of document.
     */
    public $pagecount;

    /**
     * @var int the paragraph count of document.
     */
    public $paragraphcount;

    /**
     * @var int the line count of document.
     */
    public $linecount;

    /**
     * @var int the word count of document.
     */
    public $wordcount;

    /**
     * @var int the character count of document (excluding spaces).
     */
    public $charactercount;

    /**
     * @var int the character count of document (including spaces).
     */
    public $charactercountwithspaces;

    /**
     * @var string the name of document manager.
     */
    public $manager;

    /**
     * @var string the name of company document belongs to or was authored by.
     */
    public $company;

    const SUPPLEMENTARY_TABLE = 'tika_document_metadata';

    protected function supplementary_key_map() {
        return [
            'pagecount' => ['Page-Count', 'meta:page-count', 'xmpTPg:NPages'],
            'linecount' => ['Line-Count', 'meta:line-count'],
            'paragraphcount' => ['Paragraph-Count', 'meta:paragraph-count'],
            'wordcount' => ['Word-Count', 'meta:word-count'],
            'charactercount' => ['Character-Count', 'meta:character-count'],
            'charactercountwithspaces' => ['Character-Count-With-Spaces', 'meta:character-count-with-spaces'],
            'manager' => ['Manager', 'meta:manager', 'meta:Manager', 'custom:manager', 'custom:Manager',
                'extended-properties:Manager', 'extended-properties:manager'],
            'company' => ['Company', 'meta:company', 'meta:Company', 'custom:Company', 'custom:company',
                'extended-properties:Company', 'extended-properties:company']
        ];
    }
}