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
 * This module adds ajax functions for the metadataextractor_tika plugin.
 *
 * @package    metadataextractor_tika
 * @copyright  2023 Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
    'metadataextractor_tika_is_service_ready' => [
        'classname'     => 'metadataextractor_tika\external',
        'methodname'    => 'is_service_ready',
        'classpath'     => '',
        'description'   => 'Checks if Tika service is ready.',
        'type'          => 'read',
        'ajax'          => true
    ],
];