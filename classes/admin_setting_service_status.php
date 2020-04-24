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
 * The admin setting for displaying the status of an external service.
 *
 * @package    metadataextractor_tika
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

use admin_setting;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * The admin setting for displaying the status of an external service.
 *
 * @package    metadataextractor_tika
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_service_status extends admin_setting {

    /**
     * The ready state of the service.
     *
     * @var bool $ready true if ready, false otherwise.
     */
    protected $ready;

    /**
     * admin_setting_status constructor.
     *
     * @param string $name unique ascii name 'myplugin/mysetting'.
     * @param string $visiblename localised name
     * @param string $description localised long description
     * @param mixed $defaultsetting string or array depending on implementation
     * @param bool $ready is the service this status represents ready?
     */
    public function __construct($name, $visiblename, $ready) {
        $this->ready = $ready;
        parent::__construct($name, $visiblename, '', '');
    }

    /**
     * Always true, does nothing.
     *
     * @return bool true.
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always empty string, no setting to store.
     *
     * @param mixed $data unused.
     *
     * @return string empty string.
     */
    public function write_setting($data) {
        return '';
    }

    /**
     * Returns the rendered output for status indication.
     *
     * @param string $data unused.
     * @param string $query unused.
     * @return string XHTML field
     */
    public function output_html($data, $query='') {
        global $OUTPUT;

        $context = new stdClass();
        $context->ready = $this->ready;
        $context->servicename = $this->visiblename;

        $element = $OUTPUT->render_from_template('metadataextractor_tika/service_status', $context);

        return format_admin_setting($this, $this->visiblename, $element);
    }
}