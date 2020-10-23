<?php

/**
 * The file that defines the rest api class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    MYMANAGED
 * @subpackage MYMANAGED/admin
 */

if (!class_exists('MyManaged_Rest_API')) {

    class MyManaged_Rest_API extends MyManaged_Auth
    {
        private $base_route;
        private $audit_class;

        protected $upgrade_error_keys;
        protected $upgrade_wp_flow_keys;
        protected $upgrade_success_keys;

        public function __construct()
        {
            $this->upgrade_error_keys = array
            (
                'bad_request',
                'fs_unavailable',
                'fs_error',
                'fs_no_root_dir',
                'fs_no_content_dir',
                'fs_no_plugins_dir',
                'fs_no_themes_dir',
                'fs_no_folder',
                'download_failed',
                'no_package',
                'no_files',
                'folder_exists',
                'mkdir_failed',
                'incompatible_archive',
                'files_not_writable',
                'remove_old_failed',
                'process_failed'
            );
            $this->upgrade_wp_flow_keys = array
            (
                'installing_package',
                'maintenance_start',
                'maintenance_end',
                'downloading_package',
                'unpack_package',
                'remove_old'
            );
            $this->upgrade_success_keys = array(
                'up_to_date',
                'process_success'
            );

            $this->base_route = 'mymanagedsite/v1';
            $this->audit_class = new MyManaged_Audit();

            $this->request_state_route();
            $this->update_plugin_route();
            $this->update_theme_route();
            $this->get_plugins_list_route();
//            $this->white_label_route();
        }

        private function require_functions()
        {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/misc.php');
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            require_once(ABSPATH . 'wp-admin/includes/update.php');
        }

        /**
         * Generate initiator request
         *
         * @param $initiator_key
         * @param $parameters
         * @param $response
         */
        function initiator_upgrade_request($initiator_key, $parameters, $response)
        {
            $this->audit_class->mm_audit_changes(array(
                'initiator' => array(
                    'key' => $initiator_key,
                    'slug' => $parameters->slug,
                    'code' => $response->get_error_data(),
                    'status' => $response->get_error_code(),
                    'message' => $response->get_error_message()
                )
            ));
        }

        /**
         * @return WP_Error
         */
        private function error_missed_nonce()
        {
            return new WP_Error('missed_nonce', __('Missed nonce.'), array('status' => 403));
        }

        /**
         * @return WP_Error
         */
        private function error_maintenance_mode()
        {
            return new WP_Error('wp_maintenance_mode',
                __('Briefly unavailable for scheduled maintenance. Check back in a minute.'),
                array('status' => 503));
        }

        function request_state_route()
        {
            add_action('rest_api_init', function () {
                register_rest_route($this->base_route, '/request-state', array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'request_state_callback'),
                ));
            });
        }

        function request_state_callback()
        {
            return $this->audit_class->mm_audit_changes(array(
                'initiator' => array(
                    'key' => 'request_state',
                )));
        }

        function update_plugin_route()
        {
            add_action('rest_api_init', function () {
                register_rest_route($this->base_route, '/update-plugin', array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'update_plugin_callback'),
                ));
            });
        }

        /**
         * 'example-folder/main-file.php' - slug example
         *
         * @param $request_data
         * @return string[]|WP_Error
         */
        function update_plugin_callback($request_data)
        {
            $parameters = json_decode($request_data->get_body());

            if (!isset($parameters->slug) || empty($parameters->slug))
                return new WP_Error('no_plugin_files_for_upgrade',
                    __('No plugin files for upgrade.'),
                    array('status' => 404));

            if (!isset($parameters->nonce) || empty($parameters->nonce))
                return $this->error_missed_nonce();

            $nonce = $this->check_nonce($parameters->nonce);
            if ($nonce !== true)
                return $nonce;

            if (wp_is_maintenance_mode())
                return $this->error_maintenance_mode();

            if (!mm_is_plugins_writable($parameters->slug)) {
                $error = new WP_Error('files_not_writable',
                    __('Plugin files not writable.'),
                    array('status' => 403));
                $this->initiator_upgrade_request('update-plugin', $parameters, $error);
                return $error;
            }

            $this->require_functions();

            $upgrader = new Plugin_Upgrader(new MyManaged_Updater_TraceableUpdaterSkin());
            $upgrader->upgrade($parameters->slug);
            $response = $this->parse_upgrade_response($upgrader->skin->get_upgrade_messages());

            $this->initiator_upgrade_request('update-plugin', $parameters, $response);

            return $response;
        }

        function update_theme_route()
        {
            add_action('rest_api_init', function () {
                register_rest_route($this->base_route, '/update-theme', array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'update_theme_callback'),
                ));
            });
        }

        /**
         *'twentynineteen' - slug example
         *
         * @param $request_data
         * @return array|bool|mixed|string[]
         */
        function update_theme_callback($request_data)
        {
            $parameters = json_decode($request_data->get_body());

            if (!isset($parameters->slug) || empty($parameters->slug))
                return new WP_Error('no_theme_files_for_upgrade',
                    __('No theme files for upgrade.'),
                    array('status' => 404));

            if (!isset($parameters->nonce) || empty($parameters->nonce))
                return $this->error_missed_nonce();

            $nonce = $this->check_nonce($parameters->nonce);
            if ($nonce !== true)
                return $nonce;

            if (wp_is_maintenance_mode())
                return $this->error_maintenance_mode();

            if (!mm_is_themes_writable($parameters->slug)) {
                $error = new WP_Error('files_not_writable',
                    __('Theme files not writable.'),
                    array('status' => 403));
                $this->initiator_upgrade_request('update-theme', $parameters, $error);
                return $error;
            }

            $this->require_functions();

            $upgrader = new Theme_Upgrader(new MyManaged_Updater_TraceableUpdaterSkin());
            $upgrader->upgrade($parameters->slug);
            $response = $this->parse_upgrade_response($upgrader->skin->get_upgrade_messages());

            $this->initiator_upgrade_request('update-theme', $parameters, $response);

            return $response;
        }

        function white_label_route()
        {
            add_action('rest_api_init', function () {
                register_rest_route($this->base_route, '/white-label', array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'white_label_callback'),
                ));
            });
        }

        /**
         * Save data for white label
         *
         * @param $request_data
         */
        function white_label_callback($request_data)
        {
//            $parameters = json_decode($request_data->get_body());
//
//            if (!isset($parameters->nonce) || empty($parameters->nonce))
//                return $this->error_missed_nonce();
//
//            $nonce = $this->check_nonce($parameters->nonce);
//            if ($nonce !== true)
//                return $nonce;

            update_option('mm_white_label', json_decode($request_data->get_body()));
        }

        function get_plugins_list_route()
        {
            add_action('rest_api_init', function () {
                register_rest_route($this->base_route, '/plugins-list', array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_plugins_list_callback'),
                ));
            });
        }

        /**
         * Get plugins list with data of available updates
         *
         * @param $request_data
         * @return array|bool|false|float|int|mixed|string|WP_Error
         */
        function get_plugins_list_callback($request_data)
        {
            if (!isset($request_data['nonce']) || empty($request_data['nonce']))
                return $this->error_missed_nonce();

            $nonce = $this->check_nonce($request_data['nonce']);
            if ($nonce !== true)
                return $nonce;

            $this->require_functions();
            wp_update_plugins();

            return array(
                'plugins' => get_plugins(),
                'plugin_updates' => get_plugin_updates(),
            );
        }

        /**
         * Parser for Upgrader class with custom skin
         *
         * @param $response
         * @return mixed|string
         */
        function parse_upgrade_response($response)
        {
            foreach ($response as $key => $message) {
                if (in_array($message['key'], $this->upgrade_success_keys) !== false &&
                    in_array($message['key'], $this->upgrade_wp_flow_keys) === false) {
                    return new WP_Error($message['key'], __($message['message']), array('status' => 200));
                }
                if (strpos($message['key'], 'Could not remove the old plugin.') !== false) {
                    return new WP_Error('files_not_writable',
                        __('Could not remove the old plugin.'),
                        array('status' => 403)
                    );
                }
                if (strpos($message['key'], 'Could not remove the old theme.') !== false) {
                    return new WP_Error('files_not_writable',
                        __('Could not remove the old theme.'),
                        array('status' => 403)
                    );
                }
                if (strpos($message['key'], 'The update cannot be installed because we will be unable to copy some files') !== false) {
                    return new WP_Error('files_not_writable',
                        __('The update cannot be installed because we will be unable to copy some files'),
                        array('status' => 400)
                    );
                }
                if (in_array($message['key'], $this->upgrade_error_keys) !== false) {
                    return new WP_Error($message['key'], __($message['message']), array('status' => 400));
                }
            }

            return new WP_Error('upgrade_wp_error',
                __('Could not find error from response : ' . serialize($response)), array('status' => 400));
        }
    }
}
