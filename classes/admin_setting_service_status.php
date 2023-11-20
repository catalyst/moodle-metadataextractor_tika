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
     * The label for the button.
     *
     * @var string $buttonlabel button label.
     */
    protected $buttonlabel;

    /**
     * admin_setting_status constructor.
     *
     * @param string $name unique ascii name 'myplugin/mysetting'.
     * @param string $visiblename localised name
     * @param string $buttonlabel the button label
     */
    public function __construct(string $name, string $visiblename, string $buttonlabel) {
        $this->buttonlabel = $buttonlabel;
        parent::__construct($name, $visiblename, '', '');
    }

    /**
     * Always true, does nothing.
     *
     * @return bool true.
     */
    public function get_setting() : bool {
        return true;
    }

    /**
     * Always empty string, no setting to store.
     *
     * @param mixed $data unused.
     *
     * @return string empty string.
     */
    public function write_setting($data) : string {
        return '';
    }

    /**
     * Returns the rendered output for status indication.
     *
     * @param mixed $data unused.
     * @param string $query unused.
     * @return string XHTML field
     */
    public function output_html($data, $query='') : string {
        global $PAGE;

        $attributes = ['id' => 'tika-test-service-button', 'class' => 'btn btn-primary'];
        $html  = \html_writer::tag('button', $this->buttonlabel, $attributes);
        $html .= \html_writer::tag('div', '', ['id' => 'tika-test-service-container']);

        $args = ['tika-test-service-container', 'tika-test-service-button', $this->visiblename];
        $PAGE->requires->js_call_amd('metadataextractor_tika/test_service', 'init', $args);

        return format_admin_setting($this, $this->visiblename, $html);
    }
}
