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
 * The metadata model for audio files.
 *
 * @package    metadataextractor_tika
 * @copyright  2020 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

defined('MOODLE_INTERNAL') || die();

/**
 * The metadata model for audio files.
 *
 * This model follows a modified version of Dublin Core tailored for Moodle.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata_audio extends \metadataextractor_tika\metadata {

    /**
     * @var mixed the duration of the media file.
     */
    public $duration;

    /**
     * @var mixed the audio sample rate, can be any value, but commonly 32000, 44100, or 48000.
     */
    public $samplerate;

    /**
     * @var int the count of audio channels in media file.
     */
    public $channels;

    /**
     * @var string name of location or stringified GPS information.
     */
    public $location;

    /**
     * The table name where supplementary metadata is stored.
     */
    const SUPPLEMENTARY_TABLE = 'tika_audio_metadata';

    /**
     * Return the mapping of additional variables which are supplementary to parent
     * metadata class's variables.
     *
     * @return array
     */
    protected function supplementary_key_map() {
        return [
            'duration' => ['xmpDM:duration'],
            'samplerate' => ['xmpDM:audioSampleRate'],
            'channels' => ['channels'],
            'location' => ['xmpDM:shotLocation'],
        ];
    }
}