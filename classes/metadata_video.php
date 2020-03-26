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
 * The metadata model for video files.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

defined('MOODLE_INTERNAL') || die();

/**
 * The metadata model for video files.
 *
 * This model follows a modified version of Dublin Core tailored for Moodle.
 *
 * @package    tool_metadata
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata_video extends \metadataextractor_tika\metadata {

    /**
     * @var mixed the height of the media.
     */
    public $height;

    /**
     * @var mixed the width of the media.
     */
    public $width;

    /**
     * @var mixed the duration of the media file.
     */
    public $duration;

    /**
     * @var mixed the audio sample rate, can be any value, but commonly 32000, 44100, or 48000.
     */
    public $samplerate;

    /**
     * @var string the frame size, for example: 'w:720, h:480, unit:pixels'.
     */
    public $framesize;

    /**
     * @var string name of location or stringified GPS information.
     */
    public $location;

    const SUPPLEMENTARY_TABLE = 'tika_video_metadata';

    protected function supplementary_key_map() {
        return [
            'height' => ['tiff:ImageLength'],
            'width' => ['tiff:ImageWidth'],
            'duration' => ['xmpDM:duration'],
            'samplerate' => ['xmpDM:audioSampleRate', 'samplerate'],
            'framesize' => ['xmpDM:videoFrameSize'],
            'location' => ['xmpDM:shotLocation', 'tiff:GPSAreaInformation'],
        ];
    }
}