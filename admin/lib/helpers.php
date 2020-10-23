<?php

if (!defined('WPINC')) die('Silly human what are you doing here');

if (!function_exists('get_mm_access_token')) {
    /**
     * Get settings array
     * @return mixed
     */
    function get_mm_access_token()
    {
        return get_option('mymanaged_plugin_options')['access_token'];
    }
}

if (!function_exists('get_mm_jwt_payloads')) {
    /**
     * Parser for jwt payloads
     * @return mixed
     */
    function get_mm_jwt_payloads()
    {
        if (empty(get_mm_access_token())) {
            return false;
        }

        return json_decode(base64_decode(str_replace('_', '/',
            str_replace('-','+',explode('.', get_mm_access_token())[1]))));
    }
}

if (!function_exists('mm_get_plugin_title')) {
    /**
     * Get plugin title
     * @return mixed
     */
    function mm_get_plugin_title()
    {
        $title = get_option('mm_white_label')['title'];
        return $title ? $title : MY_MANAGED_TITLE;
    }
}

if (!function_exists('mm_get_plugin_slug')) {
    /**
     * Get plugin slug
     * @return mixed
     */
    function mm_get_plugin_slug()
    {
        return mm_slugify(mm_get_plugin_title());
    }
}

if (!function_exists('mm_slugify')) {
    /**
     * Generate slug from text
     * @param $text
     * @return mixed
     */
    function mm_slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}

if (!function_exists('mm_is_plugins_writable')) {
    /**
     * @param $slug
     * @return bool
     */
    function mm_is_plugins_writable($slug)
    {
        return is_writable(WP_PLUGIN_DIR) &&
            is_writable(WP_PLUGIN_DIR . '/' . $slug);
    }
}

if (!function_exists('mm_is_themes_writable')) {
    /**
     * @param $slug
     * @return bool
     */
    function mm_is_themes_writable($slug)
    {
        return is_writable(get_theme_root()) &&
            is_writable(get_theme_root() . '/' . $slug);
    }
}

if (!function_exists('mm_remove_http')) {
    /**
     * @param string $url
     * @return string|string[]|null
     */
    function mm_remove_http($url = '')
    {
        if ($url == 'http://' or $url == 'https://') {
            return $url;
        }
        return preg_replace('/^(http|https)\:\/\/(www.)?/i', '', $url);
    }
}