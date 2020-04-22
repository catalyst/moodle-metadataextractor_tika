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
 * Mock metadataextractor_tika metadata subclass.
 *
 * @package    metadataextractor_tika
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

defined('MOODLE_INTERNAL') || die();

/**
 * Mock metadataextractor_tika metadata subclass.
 *
 * @package    metadataextractor_tika
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata_mock extends \metadataextractor_tika\metadata {

    /**
     * @var int test instance variable.
     */
    public $wordcount;

    /**
     * @var int test instance variable.
     */
    public $pagecount;

    /**
     * The table name where supplementary metadata is stored.
     */
    public const SUPPLEMENTARY_TABLE = 'metadataextractor_tika_mock';

    /**
     * Return the mapping of additional variables which are supplementary to parent
     * metadata class's variables.
     *
     * @return array
     */
    protected function supplementary_key_map() {
        return [
            'wordcount' => ['Word-Count', 'meta:word-count'],
            'pagecount' => ['Page-Count', 'meta:page-count', 'xmpTPg:NPages'],
        ];
    }
}
