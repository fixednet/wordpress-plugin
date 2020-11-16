<?php

/**
 * The file that defines the core auth class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    MYMANAGED
 * @subpackage MYMANAGED/admin
 */

if (!class_exists('MyManaged_Auth')) {

    class MyManaged_Auth extends MyManaged_Router
    {
        /**
         * Get route for api connection
         *
         * @param $route
         * @return string
         */
        public function get_route($route)
        {
            return $this->get_api_url() . $route;
        }

        /**
         * Check nonce from mymanaged app
         *
         * @param $nonce
         * @return array|bool|mixed
         */
        function check_nonce($nonce)
        {
            $response = $this->api_call('POST',
                $this->get_route($this->get_login_route()),
                $this->get_auth_header(),
                json_encode(array('nonce' => $nonce)));

            if (!is_wp_error($response) && !empty($response['response']) && $response['response']['code'] == 200) {
                return true;
            }

            return $response;
        }

        /**
         * Method: POST, PUT, GET etc
         *
         * @param $method
         * @param $request_url
         * @param array $headers
         * @param array $options
         * @return bool|mixed
         */
        function api_call($method, $request_url, $headers = array(), $options = array())
        {
            $url = $request_url;
            $args = array('method' => $method);

            if ($method == 'GET' && $options) {
                $url = sprintf("%s?%s", $request_url, http_build_query($options));
            } else if ($method == 'GET') {
                $url = $request_url;
            }

            if ($headers) {
                $args['headers'] = $headers;
            }

            if (($method == 'POST' || $method == 'PUT') && $options) {
                $args['body'] = $options;
            }

            return wp_remote_request($url, $args);
        }


        /**
         * Get authorization header
         *
         * @return array
         */
        function get_auth_header()
        {
            return array(
                'Content-Type' => 'application/json',
                'Authorization' => sprintf('Bearer %s', get_mm_access_token())
            );
        }
    }
}
