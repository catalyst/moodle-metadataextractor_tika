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
 * The metadata model for image files.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

defined('MOODLE_INTERNAL') || die();

/**
 * The metadata model for image files.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata_image extends \metadataextractor_tika\metadata {

    /**
     * @var string the vertical height of the image, may include unit of measure (eg. pixels).
     */
    public $height;

    /**
     * @var string the horizontal width of the image, may include unit of measure (eg. pixels).
     */
    public $width;

    /**
     * @var string the amount of bits per sample, (if multi-plane image then bits per sample per plane).
     */
    public $bitspersample;

    /**
     * @var string name of location or stringified GPS information.
     */
    public $location;

    const SUPPLEMENTARY_TABLE = 'tika_image_metadata';

    protected function supplementary_key_map() {
        return [
            'height' => ['tiff:ImageLength', 'Image Height', 'height'],
            'width' => ['tiff:ImageWidth', 'Image Width', 'width'],
            'bitspersample' => ['tiff:BitsPerSample', 'Data Precision'],
            'location' => ['xmpDM:shotLocation', 'tiff:GPSAreaInformation']
        ];
    }
}