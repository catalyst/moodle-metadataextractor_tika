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
 * External API.
 *
 * @package    block_leganto
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

/**
 * External API class.
 *
 * @package    metadataextractor_tika
 * @copyright  2023 Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function is_service_ready_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get if the service is ready or not.
     *
     * @return array If the service is ready
     */
    public static function is_service_ready() {
        $extractor = new extractor();
        $ready = $extractor->is_ready();

        return ['ready' => $ready];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function is_service_ready_returns() {
        return new external_single_structure([
            'ready' => new external_value(PARAM_BOOL, 'If the service is ready or not'),
        ]);
    }
}
