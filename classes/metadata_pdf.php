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
 * The metadata model for pdf files.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

defined('MOODLE_INTERNAL') || die();

/**
 * The metadata model for pdf files.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata_pdf extends \metadataextractor_tika\metadata {

    /**
     * @var int the page count of pdf.
     */
    public $pagecount;

    /**
     * @var string the software tool used to create the pdf.
     */
    public $creationtool;

    /**
     * @var string the version number of the pdf file format used by the pdf.
     */
    public $pdfversion;

    const SUPPLEMENTARY_TABLE = 'tika_pdf_metadata';

    protected function supplementary_key_map() {
        return [
            'pagecount' => ['Page-Count', 'meta:page-count', 'xmpTPg:NPages'],
            'creationtool' => ['pdf:docinfo:creator_tool'],
            'pdfversion' => ['pdf:PDFVersion'],
        ];
    }
}