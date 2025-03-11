<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink\helpers;

use craft\helpers\UrlHelper as CraftUrlHelper;

/**
 * UrlHelper
 *
 * @author      CraftPulse
 * @package     Shortlink
 *
 */
class UrlHelper extends CraftUrlHelper
{
    /**
     * Returns a query string that is a combination of al of the query strings from
     * the passed in $urls
     *
     * @param ...$urls
     * @return string
     */
    public static function combineQueryStringsFromUrls(...$urls): string
    {
        $queryParams = [];
        foreach ($urls as $url) {
            $parsedUrl = parse_url($url);
            $params = [];
            parse_str($parsedUrl['query'] ?? '', $params);
            $queryParams[] = $params;
        }
        $queryParams = array_unique(array_merge([], ...$queryParams), SORT_REGULAR);

        return http_build_query($queryParams);
    }

    /**
     * Clean up the passed in text by converting it to UTF-8, stripping tags,
     * removing whitespace, and decoding HTML entities
     *
     * @param string $path
     * @return string
     */
    public static function cleanPath(string $path): string
    {
        if (empty($path)) {
            return '';
        }
        // Convert to UTF-8
        if (function_exists('iconv')) {
            $path = iconv(mb_detect_encoding($path, mb_detect_order(), true), 'UTF-8//IGNORE', $path);
        } else {
            ini_set('mbstring.substitute_character', 'none');
            $path = mb_convert_encoding($path, 'UTF-8', 'UTF-8');
        }
        // Strip HTML tags
        $path = strip_tags($path);

        // Remove whitespace
        $path = preg_replace('/\s{2,}/u', ' ', $path);

        // Decode HTML entities
        $path = html_entity_decode($path);

        return $path;
    }

    /**
     * Return a sanitized URL
     *
     * @param string $url
     * @return string
     */
    public static function sanitizeUrl(string $url): string
    {
        // HTML decode the entities, then strip out any tags
        $url = html_entity_decode($url, ENT_NOQUOTES, 'UTF-8');
        $url = urldecode($url);
        $url = strip_tags($url);
        // Remove any Twig tags that somehow are present in the incoming URL
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $url = preg_replace('/{.*}/', '', $url);
        // Remove any linebreaks that may be errantly in the URL
        $url = (string)str_replace([
                PHP_EOL,
                "\r",
                "\n",
            ]
            , '', $url
        );

        return $url;
    }
}
