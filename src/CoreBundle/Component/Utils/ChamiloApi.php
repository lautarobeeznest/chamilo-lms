<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

use ChamiloSession as Session;
use Database;
use DateInterval;
use DateTime;
use DateTimeZone;
use Display;
use Exception;
use Template;

class ChamiloApi
{
    public const COURSE_MANAGER = 1;
    public const SESSION_ADMIN = 3;
    public const DRH = 4;
    public const STUDENT = 5;
    public const ANONYMOUS = 6;

    private static array $configuration;

    public function __construct(array $configuration)
    {
        self::$configuration = $configuration;
    }

    public static function getConfigurationArray(): array
    {
        return self::$configuration;
    }

    /**
     * @return bool|string
     */
    public static function getConfigurationValue(string $variable)
    {
        $configuration = self::getConfigurationArray();
        if (array_key_exists($variable, $configuration)) {
            return $configuration[$variable];
        }

        return false;
    }

    /**
     * Returns an array of resolutions that can be used for the conversion of documents to images.
     *
     * @return array
     */
    public static function getDocumentConversionSizes()
    {
        return [
            '540x405' => '540x405 (3/4)',
            '640x480' => '640x480 (3/4)',
            '720x540' => '720x540 (3/4)',
            '800x600' => '800x600 (3/4)',
            '1024x576' => '1024x576 (16/9)',
            '1024x768' => '1000x750 (3/4)',
            '1280x720' => '1280x720 (16/9)',
            '1280x860' => '1280x960 (3/4)',
            '1400x1050' => '1400x1050 (3/4)',
            '1600x900' => '1600x900 (16/9)',
        ];
    }

    /**
     * Get the platform logo path.
     *
     * @return null|string
     */
    public static function getPlatformLogoPath(string $theme = '', bool $getSysPath = false, bool $forcedGetter = false)
    {
        static $logoPath;

        // If call from CLI it should be reload.
        if ('cli' === PHP_SAPI) {
            $logoPath = null;
        }

        if (!isset($logoPath) || $forcedGetter) {
            $theme = empty($theme) ? api_get_visual_theme() : $theme;
            $accessUrlId = api_get_current_access_url_id();
            if ('cli' === PHP_SAPI) {
                $accessUrl = api_get_configuration_value('access_url');
                if (!empty($accessUrl)) {
                    $accessUrlId = $accessUrl;
                }
            }
            $themeDir = Template::getThemeDir($theme);
            $customLogoPath = $themeDir.sprintf('images/header-logo-custom%s.png', $accessUrlId);

            $svgIcons = api_get_setting('icons_mode_svg');
            if ('true' === $svgIcons) {
                $customLogoPathSVG = substr($customLogoPath, 0, -3).'svg';
                if (file_exists(api_get_path(SYS_PUBLIC_PATH).sprintf('css/%s', $customLogoPathSVG))) {
                    if ($getSysPath) {
                        return api_get_path(SYS_PUBLIC_PATH).sprintf('css/%s', $customLogoPathSVG);
                    }

                    return api_get_path(WEB_CSS_PATH).$customLogoPathSVG;
                }
            }
            if (file_exists(api_get_path(SYS_PUBLIC_PATH).sprintf('css/%s', $customLogoPath))) {
                if ($getSysPath) {
                    return api_get_path(SYS_PUBLIC_PATH).sprintf('css/%s', $customLogoPath);
                }

                return api_get_path(WEB_CSS_PATH).$customLogoPath;
            }

            $originalLogoPath = $themeDir.'images/header-logo.png';
            if ('true' === $svgIcons) {
                $originalLogoPathSVG = $themeDir.'images/header-logo.svg';
                if (file_exists(api_get_path(SYS_CSS_PATH).$originalLogoPathSVG)) {
                    if ($getSysPath) {
                        return api_get_path(SYS_CSS_PATH).$originalLogoPathSVG;
                    }

                    return api_get_path(WEB_CSS_PATH).$originalLogoPathSVG;
                }
            }

            if (file_exists(api_get_path(SYS_CSS_PATH).$originalLogoPath)) {
                if ($getSysPath) {
                    return api_get_path(SYS_CSS_PATH).$originalLogoPath;
                }

                return api_get_path(WEB_CSS_PATH).$originalLogoPath;
            }
            $logoPath = '';
        }

        return $logoPath;
    }

    /**
     * Get the platform logo.
     * Return a <img> if the logo image exists.
     * Otherwise return a <h2> with the institution name.
     *
     * @return string
     */
    public static function getPlatformLogo(
        string $theme = '',
        array $imageAttributes = [],
        bool $getSysPath = false,
        bool $forcedGetter = false
    ) {
        $logoPath = self::getPlatformLogoPath($theme, $getSysPath, $forcedGetter);
        $institution = api_get_setting('Institution');
        $institutionUrl = api_get_setting('InstitutionUrl');
        $siteName = api_get_setting('siteName');

        if (null === $logoPath) {
            $headerLogo = Display::url($siteName, api_get_path(WEB_PATH).'index.php');

            if (!empty($institutionUrl) && !empty($institution)) {
                $headerLogo .= ' - '.Display::url($institution, $institutionUrl);
            }

            $courseInfo = api_get_course_info();
            if (isset($courseInfo['extLink']) && !empty($courseInfo['extLink']['name'])) {
                $headerLogo .= '<span class="extLinkSeparator"> - </span>';

                if (!empty($courseInfo['extLink']['url'])) {
                    $headerLogo .= Display::url(
                        $courseInfo['extLink']['name'],
                        $courseInfo['extLink']['url'],
                        [
                            'class' => 'extLink',
                        ]
                    );
                } elseif (!empty($courseInfo['extLink']['url'])) {
                    $headerLogo .= $courseInfo['extLink']['url'];
                }
            }

            return Display::tag('h2', $headerLogo, [
                'class' => 'text-left',
            ]);
        }

        $image = Display::img($logoPath, $institution, $imageAttributes);

        return Display::url($image, api_get_path(WEB_PATH).'index.php');
    }

    /**
     * Like strip_tags(), but leaves an additional space and removes only the given tags.
     *
     * @param array $tags Tags to be removed
     *
     * @return string The original string without the given tags
     */
    public static function stripGivenTags(string $string, array $tags)
    {
        foreach ($tags as $tag) {
            $string2 = preg_replace('#</\b'.$tag.'\b[^>]*>#i', ' ', $string);
            if ($string2 !== $string) {
                $string = preg_replace('/<\b'.$tag.'\b[^>]*>/i', ' ', $string2);
            }
        }

        return $string;
    }

    /**
     * Adds or Subtract a time in hh:mm:ss to a datetime.
     *
     * @param string $time      Time to add or substract in hh:mm:ss format
     * @param string $datetime  Datetime to be modified as accepted by the Datetime class constructor
     * @param bool   $operation True for Add, False to Subtract
     *
     * @return string
     */
    public static function addOrSubTimeToDateTime(string $time, string $datetime = 'now', bool $operation = true)
    {
        $date = new DateTime($datetime);
        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        sscanf($time, '%d:%d:%d', $hours, $minutes, $seconds);
        $timeSeconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
        if ($operation) {
            $date->add(new DateInterval('PT'.$timeSeconds.'S'));
        } else {
            $date->sub(new DateInterval('PT'.$timeSeconds.'S'));
        }

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Returns the course id (integer) for the given course directory or the current ID if no directory is defined.
     *
     * @param string $directory The course directory/path that appears in the URL
     *
     * @return int
     */
    public static function getCourseIdByDirectory(string $directory = null)
    {
        if (!empty($directory)) {
            $directory = Database::escape_string($directory);
            $row = Database::select(
                'id',
                Database::get_main_table(TABLE_MAIN_COURSE),
                [
                    'where' => [
                        'directory = ?' => [$directory],
                    ],
                ],
                'first'
            );

            if (is_array($row) && isset($row['id'])) {
                return $row['id'];
            }

            return 0;
        }

        return (int) Session::read('_real_cid', 0);
    }

    /**
     * Check if the current HTTP request is by AJAX.
     *
     * @return bool
     */
    public static function isAjaxRequest()
    {
        $requestedWith = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : null;

        return 'XMLHttpRequest' === $requestedWith;
    }

    /**
     * Get a variable name for language file from a text.
     *
     * @return string
     */
    public static function getLanguageVar(string $text, string $prefix = '')
    {
        $text = api_replace_dangerous_char($text);
        $text = str_replace(['-', ' ', '.'], '_', $text);
        $text = preg_replace('/\_{1,}/', '_', $text);
        //$text = str_replace('_', '', $text);
        $text = api_underscore_to_camel_case($text);

        return $prefix.$text;
    }

    /**
     * Get the stylesheet path for HTML documents created with CKEditor.
     *
     * @return string
     */
    public static function getEditorDocStylePath()
    {
        $visualTheme = api_get_visual_theme();

        $cssFile = api_get_path(SYS_CSS_PATH).sprintf('themes/%s/document.css', $visualTheme);

        if (is_file($cssFile)) {
            return api_get_path(WEB_CSS_PATH).sprintf('themes/%s/document.css', $visualTheme);
        }

        return api_get_path(WEB_CSS_PATH).'document.css';
    }

    /**
     * Get the stylesheet path for HTML blocks created with CKEditor.
     *
     * @return string
     */
    public static function getEditorBlockStylePath()
    {
        $visualTheme = api_get_visual_theme();

        $cssFile = api_get_path(SYS_CSS_PATH).sprintf('themes/%s/editor_content.css', $visualTheme);

        if (is_file($cssFile)) {
            return api_get_path(WEB_CSS_PATH).sprintf('themes/%s/editor_content.css', $visualTheme);
        }

        return api_get_path(WEB_CSS_PATH).'editor_content.css';
    }

    /**
     * Get a list of colors from the palette at main/palette/pchart/default.color
     * and return it as an array of strings.
     *
     * @param bool $decimalOpacity Whether to return the opacity as 0..100 or 0..1
     * @param bool $wrapInRGBA     Whether to return it as 1,1,1,100 or rgba(1,1,1,100)
     * @param int  $fillUpTo       If the number of colors is smaller than this number, generate more colors
     *
     * @return array An array of string colors
     */
    public static function getColorPalette(
        bool $decimalOpacity = false,
        bool $wrapInRGBA = false,
        int $fillUpTo = null
    ) {
        // Get the common colors from the palette used for pchart
        $paletteFile = api_get_path(SYS_CODE_PATH).'palettes/pchart/default.color';
        $palette = file($paletteFile);
        if ($decimalOpacity) {
            // Because the pchart palette has transparency as integer values
            // (0..100) and chartjs uses percentage (0.0..1.0), we need to divide
            // the last value by 100, which is a bit overboard for just one chart
            foreach ($palette as $index => $color) {
                $components = preg_split('/,/', trim($color));
                $components[3] = round($components[3] / 100, 1);
                $palette[$index] = implode(',', $components);
            }
        }
        if ($wrapInRGBA) {
            foreach ($palette as $index => $color) {
                $color = trim($color);
                $palette[$index] = 'rgba('.$color.')';
            }
        }
        // If we want more colors, loop through existing colors
        $count = count($palette);
        if (isset($fillUpTo) && $fillUpTo > $count) {
            for ($i = $count; $i < $fillUpTo; $i++) {
                $palette[$i] = $palette[$i % $count];
            }
        }

        return $palette;
    }

    /**
     * Get the local time for the midnight.
     *
     * @param null|string $utcTime Optional. The time to ve converted.
     *                             See api_get_local_time.
     *
     * @throws Exception
     *
     * @return DateTime
     */
    public static function getServerMidnightTime(?string $utcTime = null)
    {
        $localTime = api_get_local_time($utcTime);
        $localTimeZone = api_get_timezone();

        $localMidnight = new DateTime($localTime, new DateTimeZone($localTimeZone));
        $localMidnight->modify('midnight');

        return $localMidnight;
    }

    /**
     * Get JavaScript code necessary to load quiz markers-rolls in medialement's Markers Rolls plugin.
     *
     * @return string
     */
    public static function getQuizMarkersRollsJS()
    {
        $webCodePath = api_get_path(WEB_CODE_PATH);
        $cidReq = api_get_cidreq(true, true, 'embeddable');
        $colorPalette = self::getColorPalette(false, true);

        return "
            var \$originalNode = $(originalNode),
                    qMarkersRolls = \$originalNode.data('q-markersrolls') || [],
                    qMarkersColor = \$originalNode.data('q-markersrolls-color') || '{$colorPalette[0]}';

                if (0 == qMarkersRolls.length) {
                    return;
                }

                instance.options.markersRollsColor = qMarkersColor;
                instance.options.markersRollsWidth = 2;
                instance.options.markersRolls = {};

                qMarkersRolls.forEach(function (qMarkerRoll) {
                    var url = '{$webCodePath}exercise/exercise_submit.php?{$cidReq}&'
                        + $.param({
                            exerciseId: qMarkerRoll[1],
                            learnpath_id: 0,
                            learnpath_item_id: 0,
                            learnpath_item_view_id: 0
                        });

                    instance.options.markersRolls[qMarkerRoll[0]] = url;
                });

                instance.buildmarkersrolls(instance, instance.controls, instance.layers, instance.media);
        ";
    }
}
