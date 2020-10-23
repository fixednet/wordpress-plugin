<?php

/**
 * The file that defines the router class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    MYMANAGED
 * @subpackage MYMANAGED/admin
 */

if (!class_exists('MyManaged_Router')) {

    class MyManaged_Router
    {
        const API_URL = 'https://mymanaged.site';
        const LOGIN_ROUTE = '/api/v1/wordpress/login';
        const CHANGES_ROUTE = '/api/v1/wordpress/changes/';
        const SIGNED_URL_ROUTE = '/api/v1/wordpress/signedUrl/';

        public static function get_api_url()
        {
            return self::API_URL;
        }

        public static function get_login_route()
        {
            return self::LOGIN_ROUTE;
        }

        public static function get_changes_route()
        {
            return self::CHANGES_ROUTE;
        }

        public static function get_signed_url_route()
        {
            return self::SIGNED_URL_ROUTE;
        }
    }
}