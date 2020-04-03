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
 * Helper class for metadataextrator_tika.
 *
 * Contains common functions used for extraction of metadata by subplugin and
 * constant definitions.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadataextractor_tika;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/metadata/constants.php');

/**
 * Helper class for metadataextrator_tika.
 *
 * @package    metadataextractor_tika
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tika_helper {

    /**
     * Entity header for mimetype, RFC 7233 and RFC 7231.
     */
    const MIMETYPE_KEY = 'Content-Type';

    /**
     * Mimetype is not known for resource.
     */
    const MIMETYPE_UNKNOWN = 'unknown';

    /**
     * File type - portable document format.
     */
    const FILETYPE_PDF = 'pdf';

    /**
     * File type - text document.
     */
    const FILETYPE_DOCUMENT = 'document';

    /**
     * File type - presentation (PowerPoint or similar).
     */
    const FILETYPE_PRESENTATION = 'presentation';

    /**
     * File type - presentation (PowerPoint or similar).
     */
    const FILETYPE_ARCHIVE = 'archive';

    /**
     * File type - spreadsheet (Excel or similar).
     */
    const FILETYPE_SPREADSHEET = 'spreadsheet';

    /**
     * File type - image (multiple formats).
     */
    const FILETYPE_IMAGE = 'image';

    /**
     * File type - image (multiple formats).
     */
    const FILETYPE_VIDEO = 'video';

    /**
     * File type - image (multiple formats).
     */
    const FILETYPE_AUDIO = 'audio';

    /**
     * File type - mock for testing only.
     */
    const FILETYPE_MOCK = 'mock';

    /**
     * File type - could not be determined.
     */
    const FILETYPE_OTHER = 'other';

    /**
     * File types currently supported by metadataextractor_tika.
     */
    const SUPPORTED_FILETYPES = [
        self::FILETYPE_DOCUMENT,
        self::FILETYPE_PDF,
        self::FILETYPE_IMAGE,
        self::FILETYPE_AUDIO,
        self::FILETYPE_VIDEO,
        self::FILETYPE_SPREADSHEET,
        self::FILETYPE_PRESENTATION,
    ];

    /**
     * Mapping of tika_helper filetypes to tika supported mimetypes.
     */
    const FILETYPE_MIMETYPE_MAP = [
        self::FILETYPE_PDF => [
            'application/pdf'
        ],
        self::FILETYPE_DOCUMENT => [
            'application/msword',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/x-abiword'
        ],
        self::FILETYPE_PRESENTATION => [
            'application/vnd.ms-powerpoint',
            'application/vnd.oasis.opendocument.presentation',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ],
        self::FILETYPE_SPREADSHEET => [
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.spreadsheet',
            'text/plain; charset=ISO-8859-1'
        ],
        self::FILETYPE_IMAGE => [
            'image/aces',
            'image/bmp',
            'image/cgm',
            'image/emf',
            'image/example',
            'image/fits',
            'image/g3fax',
            'image/gif',
            'image/icns',
            'image/ief',
            'image/jp2',
            'image/jpeg',
            'image/jpm',
            'image/jpx',
            'image/naplps',
            'image/nitf',
            'image/png',
            'image/prs.btif',
            'image/prs.pti',
            'image/svg+xml',
            'image/t38',
            'image/tiff',
            'image/tiff-fx',
            'image/vnd.adobe.photoshop',
            'image/vnd.adobe.premiere',
            'image/vnd.cns.inf2',
            'image/vnd.djvu',
            'image/vnd.dwg',
            'image/vnd.dxb',
            'image/vnd.dxf',
            'image/vnd.dxf; format=ascii',
            'image/vnd.dxf; format=binary',
            'image/vnd.fastbidsheet',
            'image/vnd.fpx',
            'image/vnd.fst',
            'image/vnd.fujixerox.edmics-mmr',
            'image/vnd.fujixerox.edmics-rlc',
            'image/vnd.globalgraphics.pgb',
            'image/vnd.microsoft.icon',
            'image/vnd.mix',
            'image/vnd.ms-modi',
            'image/vnd.net-fpx',
            'image/vnd.radiance',
            'image/vnd.sealed.png',
            'image/vnd.sealedmedia.softseal.gif',
            'image/vnd.sealedmedia.softseal.jpg',
            'image/vnd.svf',
            'image/vnd.wap.wbmp',
            'image/vnd.xiff',
            'image/vnd.zbrush.dcx',
            'image/vnd.zbrush.pcx',
            'image/webp',
            'image/wmf',
            'image/x-bpg',
            'image/x-cmu-raster',
            'image/x-cmx',
            'image/x-dpx',
            'image/x-emf-compressed',
            'image/x-freehand',
            'image/x-jbig2',
            'image/x-jp2-codestream',
            'image/x-jp2-container',
            'image/x-niff',
            'image/x-pict',
            'image/x-portable-anymap',
            'image/x-portable-bitmap',
            'image/x-portable-graymap',
            'image/x-portable-pixmap',
            'image/x-raw-adobe',
            'image/x-raw-canon',
            'image/x-raw-casio',
            'image/x-raw-epson',
            'image/x-raw-fuji',
            'image/x-raw-hasselblad',
            'image/x-raw-imacon',
            'image/x-raw-kodak',
            'image/x-raw-leaf',
            'image/x-raw-logitech',
            'image/x-raw-mamiya',
            'image/x-raw-minolta',
            'image/x-raw-nikon',
            'image/x-raw-olympus',
            'image/x-raw-panasonic',
            'image/x-raw-pentax',
            'image/x-raw-phaseone',
            'image/x-raw-rawzor',
            'image/x-raw-red',
            'image/x-raw-sigma',
            'image/x-raw-sony',
            'image/x-rgb',
            'image/x-tga',
            'image/x-xbitmap',
            'image/x-xcf',
            'image/x-xpixmap',
            'image/x-xwindowdump'
        ],
        self::FILETYPE_VIDEO => [
            'video/3gpp',
            'video/3gpp-tt',
            'video/3gpp2',
            'video/bmpeg',
            'video/bt656',
            'video/celb',
            'video/daala',
            'video/dv',
            'video/example',
            'video/h261',
            'video/h263',
            'video/h263-1998',
            'video/h263-2000',
            'video/h264',
            'video/jpeg',
            'video/jpeg2000',
            'video/mj2',
            'video/mp1s',
            'video/mp2p',
            'video/mp2t',
            'video/mp4',
            'video/mp4v-es',
            'video/mpeg',
            'video/mpeg4-generic',
            'video/mpv',
            'video/nv',
            'video/ogg',
            'video/parityfec',
            'video/pointer',
            'video/quicktime',
            'video/raw',
            'video/rtp-enc-aescm128',
            'video/rtx',
            'video/smpte292m',
            'video/theora',
            'video/ulpfec',
            'video/vc1',
            'video/vnd.cctv',
            'video/vnd.dlna.mpeg-tts',
            'video/vnd.fvt',
            'video/vnd.hns.video',
            'video/vnd.iptvforum.1dparityfec-1010',
            'video/vnd.iptvforum.1dparityfec-2005',
            'video/vnd.iptvforum.2dparityfec-1010',
            'video/vnd.iptvforum.2dparityfec-2005',
            'video/vnd.iptvforum.ttsavc',
            'video/vnd.iptvforum.ttsmpeg2',
            'video/vnd.motorola.video',
            'video/vnd.motorola.videop',
            'video/vnd.mpegurl',
            'video/vnd.ms-playready.media.pyv',
            'video/vnd.nokia.interleaved-multimedia',
            'video/vnd.nokia.videovoip',
            'video/vnd.objectvideo',
            'video/vnd.sealed.mpeg1',
            'video/vnd.sealed.mpeg4',
            'video/vnd.sealed.swf',
            'video/vnd.sealedmedia.softseal.mov',
            'video/vnd.vivo',
            'video/webm',
            'video/x-dirac',
            'video/x-f4v',
            'video/x-flc',
            'video/x-fli',
            'video/x-flv',
            'video/x-jng',
            'video/x-m4v',
            'video/x-matroska',
            'video/x-mng',
            'video/x-ms-asf',
            'video/x-ms-wm',
            'video/x-ms-wmv',
            'video/x-ms-wmx',
            'video/x-ms-wvx',
            'video/x-msvideo',
            'video/x-oggrgb',
            'video/x-ogguvs',
            'video/x-oggyuv',
            'video/x-ogm',
            'video/x-sgi-movie'
        ],
        self::FILETYPE_AUDIO => [
            'audio/3gpp',
            'audio/3gpp2',
            'audio/ac3',
            'audio/adpcm',
            'audio/amr',
            'audio/amr-wb',
            'audio/amr-wb+',
            'audio/asc',
            'audio/basic',
            'audio/bv16',
            'audio/bv32',
            'audio/clearmode',
            'audio/cn',
            'audio/dat12',
            'audio/dls',
            'audio/dsr-es201108',
            'audio/dsr-es202050',
            'audio/dsr-es202211',
            'audio/dsr-es202212',
            'audio/dvi4',
            'audio/eac3',
            'audio/evrc',
            'audio/evrc-qcp',
            'audio/evrc0',
            'audio/evrc1',
            'audio/evrcb',
            'audio/evrcb0',
            'audio/evrcb1',
            'audio/evrcwb',
            'audio/evrcwb0',
            'audio/evrcwb1',
            'audio/example',
            'audio/g719',
            'audio/g722',
            'audio/g7221',
            'audio/g723',
            'audio/g726-16',
            'audio/g726-24',
            'audio/g726-32',
            'audio/g726-40',
            'audio/g728',
            'audio/g729',
            'audio/g7291',
            'audio/g729d',
            'audio/g729e',
            'audio/gsm',
            'audio/gsm-efr',
            'audio/ilbc',
            'audio/l16',
            'audio/l20',
            'audio/l24',
            'audio/l8',
            'audio/lpc',
            'audio/midi',
            'audio/mobile-xmf',
            'audio/mp4',
            'audio/mp4a-latm',
            'audio/mpa',
            'audio/mpa-robust',
            'audio/mpeg',
            'audio/mpeg4-generic',
            'audio/ogg',
            'audio/opus',
            'audio/parityfec',
            'audio/pcma',
            'audio/pcma-wb',
            'audio/pcmu',
            'audio/pcmu-wb',
            'audio/prs.sid',
            'audio/qcelp',
            'audio/red',
            'audio/rtp-enc-aescm128',
            'audio/rtp-midi',
            'audio/rtx',
            'audio/smv',
            'audio/smv-qcp',
            'audio/smv0',
            'audio/sp-midi',
            'audio/speex',
            'audio/t140c',
            'audio/t38',
            'audio/telephone-event',
            'audio/tone',
            'audio/ulpfec',
            'audio/vdvi',
            'audio/vmr-wb',
            'audio/vnd.3gpp.iufp',
            'audio/vnd.4sb',
            'audio/vnd.adobe.soundbooth',
            'audio/vnd.audiokoz',
            'audio/vnd.celp',
            'audio/vnd.cisco.nse',
            'audio/vnd.cmles.radio-events',
            'audio/vnd.cns.anp1',
            'audio/vnd.cns.inf1',
            'audio/vnd.digital-winds',
            'audio/vnd.dlna.adts',
            'audio/vnd.dolby.heaac.1',
            'audio/vnd.dolby.heaac.2',
            'audio/vnd.dolby.mlp',
            'audio/vnd.dolby.mps',
            'audio/vnd.dolby.pl2',
            'audio/vnd.dolby.pl2x',
            'audio/vnd.dolby.pl2z',
            'audio/vnd.dts',
            'audio/vnd.dts.hd',
            'audio/vnd.everad.plj',
            'audio/vnd.hns.audio',
            'audio/vnd.lucent.voice',
            'audio/vnd.ms-playready.media.pya',
            'audio/vnd.nokia.mobile-xmf',
            'audio/vnd.nortel.vbk',
            'audio/vnd.nuera.ecelp4800',
            'audio/vnd.nuera.ecelp7470',
            'audio/vnd.nuera.ecelp9600',
            'audio/vnd.octel.sbc',
            'audio/vnd.qcelp',
            'audio/vnd.rhetorex.32kadpcm',
            'audio/vnd.sealedmedia.softseal.mpeg',
            'audio/vnd.vmx.cvsd',
            'audio/vnd.wave',
            'audio/vorbis',
            'audio/vorbis-config',
            'audio/x-aac',
            'audio/x-adpcm',
            'audio/x-aiff',
            'audio/x-caf',
            'audio/x-dec-adpcm',
            'audio/x-dec-basic',
            'audio/x-flac',
            'audio/x-matroska',
            'audio/x-mod',
            'audio/x-mpegurl',
            'audio/x-ms-wax',
            'audio/x-ms-wma',
            'audio/x-oggflac',
            'audio/x-oggpcm',
            'audio/x-pn-realaudio',
            'audio/x-pn-realaudio-plugin'
        ],
        self::FILETYPE_ARCHIVE => [
            'application/x-freearc',
            'application/x-bzip',
            'application/x-bzip2',
            'application/gzip',
            'application/x-tar',
            'application/zip',
            'application/x-7z-compressed'
        ]
    ];


    /**
     * Get the filetype based on mimetype.
     *
     * @param string $mimetype the mimetype of file.
     *
     * @return string constant file type.
     */
    public static function get_filetype(string $mimetype) {
        $filetype = self::FILETYPE_OTHER;

        if ($mimetype != static::MIMETYPE_UNKNOWN) {
            foreach (self::FILETYPE_MIMETYPE_MAP as $mappedfiletype => $mappedmimetypes) {
                foreach ($mappedmimetypes as $mappedmimetype) {
                    if ($mimetype == $mappedmimetype) {
                        // We found our filetype, end here.
                        $filetype = $mappedfiletype;
                        break;
                    }
                }
            }
        }

        return $filetype;
    }

    /**
     * Get the metadata class to instantiate based on the mimetype of
     * Tika returned metadata.
     *
     * @param string $mimetype
     *
     * @return string
     */
    public static function get_metadata_class(string $mimetype) {
        $filetype = self::get_filetype($mimetype);

        if (self::is_filetype_supported($filetype)) {
            $class = '\metadataextractor_tika\metadata_' . $filetype;
        } else {
            $class = '\metadataextractor_tika\metadata';
        }

        return $class;
    }

    /**
     * Get the mimetype of a resource from it's raw metadata.
     *
     * @param array $rawmetadata raw metadata extracted from Tika.
     *
     * @return string $mimetype the mimetype or
     */
    public static function get_raw_metadata_mimetype(array $rawmetadata) {

        if (in_array(static::MIMETYPE_KEY, array_keys($rawmetadata))) {
            $mimetype = $rawmetadata[static::MIMETYPE_KEY];
        } else {
            $mimetype = static::MIMETYPE_UNKNOWN;
        }

        return $mimetype;
    }

    /**
     * Is a file type supported?
     *
     * @param string $filetype the filetype of file.
     *
     * @return bool true if currently supported, false otherwise.
     */
    public static function is_filetype_supported(string $filetype) {
        $result = false;

        if (in_array($filetype, static::SUPPORTED_FILETYPES)) {
            $result = true;
        }

        return $result;
    }
}
