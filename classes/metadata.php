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
 * The base metadata model for all tika extracted metadata.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

use tool_metadata\extraction_exception;
use tool_metadata\metadata_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * The base metadata model for all tika extracted metadata.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata extends \tool_metadata\metadata {

    /**
     * The table name where metadata records are stored.
     */
    const TABLE = 'metadataextractor_tika';

    /**
     * The table name where supplementary metadata is stored.
     */
    const SUPPLEMENTARY_TABLE = '';

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

    /**
     * @var string date/time the resource metadata represents was created.
     */
    public $resourcecreated;

    /**
     * @var string date/time the resource metadata represents was modified.
     */
    public $resourcemodified;

    /**
     * Get the supplementary table for metadata instance.
     *
     * @return string
     */
    public function get_supplementary_table() {
        return static::SUPPLEMENTARY_TABLE;
    }

    /**
     * Does this metadata class have supplementary data in addition
     * to base metadata data?
     *
     * @return bool
     */
    public function has_supplementary_data() {
        $result = false;

        if (!empty(static::SUPPLEMENTARY_TABLE)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Return the mapping of instantiating class variables to potential raw metadata keys
     * in order of priority from highest to lowest.
     *
     * See parent class docs for further information on shape of data.
     *
     * @return array
     */
    protected function metadata_key_map() {

        // Use late static binding for supplementary_key_map only as we always
        // want base_key_map to reflect the key map defined in this class.
        return array_merge(self::base_key_map(), static::supplementary_key_map());
    }

    /**
     * Return the mapping of base metadata variables for this metadata class which
     * all extending metadata classes will inherit.
     *
     * Example:
     *  [
     *      'author' => [
     *           'Author', 'meta:author', 'Creator', 'meta:creator', 'dc:creator',
     *       ],
     *      'title' => [
     *           'Title', 'meta:title', 'dc:title',
     *       ]
     *  ]
     *
     * @return array
     */
    protected function base_key_map() {
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
            'rights' => ['dc:rights', 'Rights', 'rights', 'meta:rights', 'License', 'license', 'meta:license',
                'Copyright', 'copyright', 'meta:copyright',  'custom:Rights', 'custom:rights',
                'custom:Copyright', 'custom:copyright', 'custom:License', 'custom:licence',
                'extended-properties:Rights', 'extended-properties:rights', 'extended-properties:Copyright',
                'extended-properties:copyright', 'extended-properties:License', 'extended-properties:licence'],
            'language' => ['dc:language', 'Language', 'language', 'meta:language'],
            'resourcecreated' => ['dcterms:created', 'Creation-Date', 'created', 'meta:creation-date'],
            'resourcemodified' => ['dcterms:modified', 'Last-Modified', 'modified', 'meta:save-date'],
        ];
    }

    /**
     * Return the mapping of extending class additional variables which are supplementary to this
     * metadata class's variables.
     *
     * Example:
     *  [
     *      'pagecount' => [
     *           'Page-Count', 'meta:page-count', 'xmpTPg:NPages',
     *       ],
     *      'wordcount' => [
     *           'Word-Count', 'meta:word-count',
     *       ]
     *  ]
     *
     * @return array
     */
    protected function supplementary_key_map() {
        return [];
    }

    /**
     * Populate this instance by ID of base metadata record.
     *
     * @param int $id the id of the base metadata record to populate metadata from.
     *
     * @return bool $success true if populated successfully, false otherwise.
     */
    protected function populate_from_id(int $id): bool {
        global $DB;

        // Populate base variables.
        $success = parent::populate_from_id($id);

        // Populate supplementary data variables if instance has any.
        if ($success && $this->has_supplementary_data()) {
            $record = $DB->get_record($this->get_supplementary_table(), ['resourcehash' => $this->resourcehash]);
            if (!empty($record)) {
                foreach ((array) $record as $property => $value) {
                    // Prevent overriding of instance ID with supplementary record ID.
                    if ($property != 'id') {
                        $this->$property = $value;
                    }
                }
                $success = true;
            } else {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Populate the variables of this metadata instance from an existing database records by resourcehash.
     *
     * @param string $resourcehash the resourcehash of the resource to populate metadata for.
     *
     * @return bool $success true if populated successfully, false otherwise.
     */
    public function populate_from_resourcehash(string $resourcehash): bool {
        global $DB;

        // Populate base variables.
        $success = parent::populate_from_resourcehash($resourcehash);

        // Populate supplementary data variables if instance has any.
        if ($success && $this->has_supplementary_data()) {
            $record = $DB->get_record($this->get_supplementary_table(), ['resourcehash' => $resourcehash]);
            if (!empty($record)) {
                foreach ((array) $record as $property => $value) {
                    // Prevent overriding of instance ID with supplementary record ID.
                    if ($property != 'id') {
                        $this->$property = $value;
                    }
                }
                $success = true;
            } else {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Get this metadata object as a standard object for database use.
     *
     * @return \stdClass
     */
    public function get_record() {
        $recordarray = array_merge((array) $this->get_base_record(), (array) $this->get_supplementary_record());
        $record = (object) $recordarray;

        return $record;
    }

    /**
     * Get the base data of this metadata instance as a
     * record for database handling.
     *
     * @return \stdClass
     */
    protected function get_base_record() {
        $record = new \stdClass();

        if (!empty($this->id)) {
            $record->id = $this->id;
        } else {
            $record->id = 0;
        }

        $record->resourcehash = $this->resourcehash;
        $record->timecreated = $this->timecreated;
        $record->timemodified = $this->timemodified;

        $keys = array_keys(self::base_key_map());

        foreach ($keys as $key) {
            $record->$key = $this->$key;
        }

        return $record;
    }

    /**
     * Get the supplementary data of this metadata instance as
     * a record for database handling.
     *
     * @return \stdClass this instances supplementary data
     */
    protected function get_supplementary_record() {
        $record = new \stdClass();
        $record->id = $this->get_supplementary_id();
        $record->resourcehash = $this->resourcehash;

        $keys = array_keys(static::supplementary_key_map());

        foreach ($keys as $key) {
            $record->$key = $this->$key;
        }

        return $record;
    }

    /**
     * Get the ID of the supplementary data record for this metadata instance.
     *
     * @return int the ID of the supplementary record for this metadata instance.
     */
    public function get_supplementary_id() {
        global $DB;

        $result = $DB->get_field($this->get_supplementary_table(), 'id', ['resourcehash' => $this->resourcehash]);

        if (!empty($result)) {
            $id = $result;
        } else {
            $id = 0;
        }

        return $id;
    }

    /**
     * Create the record for this instance in database.
     *
     * @return $this
     * @throws \tool_metadata\metadata_exception if record already exists.
     */
    public function create() {
        global $DB;

        $baserecord = $this->get_base_record();

        if (!empty($baserecord->id)) {
            $exists = $DB->get_record($this->get_table(), ['id' => $baserecord->id]);
            if ($exists) {
                throw new metadata_exception('error:metadata:recordalreadyexists');
            }
        }

        if (empty($baserecord->timecreated)) {
            $baserecord->timecreated = $this->timecreated = time();
        }
        $baserecord->timemodified = $this->timemodified = time();

        $transaction = $DB->start_delegated_transaction();
        $id = $DB->insert_record($this->get_table(), $baserecord);

        if ($this->has_supplementary_data()) {
            $supplementaryrecord = $this->get_supplementary_record();
            if (empty($supplementaryrecord->id)) {
                $DB->insert_record($this->get_supplementary_table(), $supplementaryrecord);
            } else {
                $DB->update_record($this->get_supplementary_table(), $supplementaryrecord);
            }
        }

        $transaction->allow_commit();

        $this->id = $id;

        return $this;
    }

    /**
     * Update the record for this metadata instance in database.
     *
     * @return bool true on success.
     */
    public function update() {
        global $DB;

        $baserecord = $this->get_base_record();

        if (!empty($baserecord->id)) {
            $baserecord->timemodified = time();
            $transaction = $DB->start_delegated_transaction();
            $success = $DB->update_record($this->get_table(), $baserecord);
        } else {
            throw new metadata_exception('error:metadata:noid');
        }

        if ($success && $this->has_supplementary_data()) {
            $supplementaryrecord = $this->get_supplementary_record();
            if (empty($supplementaryrecord->id)) {
                $DB->insert_record($this->get_supplementary_table(), $supplementaryrecord);

            } else {
                $success = $DB->update_record($this->get_supplementary_table(), $supplementaryrecord);
            }
        }

        if ($success) {
            $transaction->allow_commit();
        }

        return $success;
    }

    /**
     * Delete the data for this instance from database.
     *
     * @return bool true on success.
     *
     * @throws \tool_metadata\metadata_exception if unable to get supplementary record id.
     */
    public function delete() {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $success = parent::delete();

        if ($success && $this->has_supplementary_data()) {
            $id = $this->get_supplementary_id();
            if (empty($id)) {
                throw new metadata_exception('error:metadata:noid');
            }
            $success = $DB->delete_records($this->get_supplementary_table(), ['id' => $id]);
        }

        if ($success) {
            $transaction->allow_commit();
        }

        return $success;
    }
}