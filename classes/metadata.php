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
 * Define the core metadata model for all resources.
 *
 * @package    tool_metadata
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

defined('MOODLE_INTERNAL') || die();

/**
 * The core metadata model for all resources.
 *
 * This model follows a modified version of Dublin Core tailored for Moodle.
 *
 * @package    tool_metadata
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata extends \tool_metadata\metadata {

    /**
     * The table name where metadata records are stored.
     */
    const TABLE = 'metadataextractor_tika';

    /**
     * @var string The person or organization primarily responsible for creating the
     * intellectual content of the resource this metadata represents.
     */
    public $creator;
    /**
     * @var array (string) Persons or organizations not specified in the creator who have
     * made significant intellectual contributions to the resource but whose contribution
     * is secondary to any person or organization specified in creator.
     */
    public $contributor;
    /**
     * @var int A UNIX epoch for date/time associated with the creation or availability of the resource.
     */
    public $creationdate;
    /**
     * @var string The MIME type of the resource, in accordance with IANA Media Types.
     * https://www.iana.org/assignments/media-types/media-types.xhtml
     */
    public $format;
    /**
     * @var string One of the Dublic Core Metadata Initiative types available in the DCMI_TYPE constants.
     * https://www.dublincore.org/specifications/dublin-core/dcmi-type-vocabulary/2010-10-11/
     */
    public $type;
    /**
     * @var string The name given to the resource, usually by the creator or publisher.
     */
    public $title;
    /**
     * @var string The topic of the resource.  Typically, subject will be expressed as
     * keywords or phrases that describe the subject or content of the resource.
     */
    public $subject;
    /**
     * @var string A textual description of the content of the resource.
     */
    public $description;
    /**
     * @var string The entity responsible for making the resource available in its
     * present form, such as a publishing house, a university department, or a corporate entity.
     */
    public $publisher;
    /**
     * @var string A rights management statement, an identifier that links to a rights management statement,
     * or an identifier that links to a service providing information about rights management for the resource.
     */
    public $rights;

    /**
     * @var string The language of the resource.
     */
    public $language;

    protected function metadata_key_map() {

        return [
            'format' => ['dc:format', 'Content-Type'],
            'type' => ['dc:type'],
            'description' => ['dc:description', 'Description', 'description'],
            'title' => ['dc:title', 'Title', 'title', 'meta:title', 'resourceName', 'pdf:title'],
            'creator' => ['dc:creator', 'Creator', 'creator', 'meta:creator', 'Author', 'author', 'meta:author'],
            'date' => ['dc:date', 'Creation-Date', 'meta:creation-date'],
            'contributor' => ['dc:contributor', 'meta:last-author'],
            'subject' => ['dc:subject', 'Subject', 'subject', 'meta:subject', 'Keywords', 'meta:keyword'],
            'publisher' => ['dc:publisher', 'Publisher', 'publisher', 'meta:publisher'],
            'rights' => ['dc:rights', 'Rights', 'rights', 'meta:rights', 'License', 'license', 'meta:license'],
            'language' => ['dc:language', 'Language', 'language', 'meta:language'],
        ];
    }
}