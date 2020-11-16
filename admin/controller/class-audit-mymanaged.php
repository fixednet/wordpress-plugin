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

if (!class_exists('MyManaged_Audit')) {

    class MyManaged_Audit extends MyManaged_Auth
    {
        protected $old_themes;
        protected $old_plugins;

        function hook_events()
        {
            if (get_mm_access_token()) {
                $this->init_cron_schedules();

                add_action('admin_init', array($this, 'event_admin_init'));
                add_action('shutdown', array($this, 'events_admin_listener'));
                add_action('switch_theme', array($this, 'event_switch_theme'), 10, 3);
                add_action('automatic_updates_complete', array($this, 'event_automatic_updates'));
            }
        }

        /**
         * Triggered when a user accesses the admin area.
         */
        public function event_admin_init()
        {
            $this->old_themes = wp_get_themes();
            $this->old_plugins = get_plugins();
        }

        /**
         * Get removed themes.
         *
         * @return array of WP_Theme objects
         */
        protected function get_deleted_themes()
        {
            $result = $this->old_themes;
            foreach ($result as $i => $theme) {
                if (file_exists($theme->get_template_directory())) {
                    unset($result[$i]);
                }
            }
            return $result;
        }

        /**
         * Admin listener events callback
         * @throws ImagickException
         */
        function events_admin_listener()
        {
            $post_array = filter_input_array(INPUT_POST);
            $get_array = filter_input_array(INPUT_GET);
            $script_name = isset($_SERVER['SCRIPT_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SCRIPT_NAME'])) : false;

            $action = '';

            if (isset($get_array['action']) && '-1' != $get_array['action']) {
                $action = $get_array['action'];
            } elseif (isset($post_array['action']) && '-1' != $post_array['action']) {
                $action = $post_array['action'];
            }

            if (isset($get_array['action2']) && '-1' != $get_array['action2']) {
                $action = $get_array['action2'];
            } elseif (isset($post_array['action2']) && '-1' != $post_array['action2']) {
                $action = $post_array['action2'];
            }

            $actype = '';
            if (!empty($script_name)) {
                $actype = basename($script_name, '.php');
            }
            $is_plugins = 'plugins' === $actype;

            // Install plugin(s).
            if (in_array($action, array('install-plugin', 'upload-plugin'))) {
                $plugin = array_values(array_diff(array_keys(get_plugins()), array_keys($this->old_plugins)));
                if (count($plugin) != 1) {
                    return;
                }

                $installed_plugins = array();
                $plugin_path = $plugin[0];
                $plugin = get_plugins();
                $plugin = $plugin[$plugin_path];

                $installed_plugins[$plugin_path] = $plugin;

                $this->audit_changes(array(
                    'initiator' => array(
                        'key' => $action,
                        'plugins' => $installed_plugins,
                        'user' => $this->get_user_data(),
                    )
                ));
            }

            // Install theme.
            if (in_array($action, array('install-theme', 'upload-theme'))) {
                $installed_themes = array();
                $themes = array_diff(wp_get_themes(), $this->old_themes);

                if (empty($themes))
                    return;

                foreach ($themes as $name => $theme) {
                    $installed_themes[$name] = $theme;
                }

                $this->audit_changes(array(
                    'initiator' => array(
                        'key' => $action,
                        'themes' => $installed_themes,
                        'user' => $this->get_user_data(),
                    )
                ));
            }

            // Activate/Deactivate plugin(s).
            if ($is_plugins && (in_array($action, array('activate', 'activate-selected')) ||
                    in_array($action, array('deactivate', 'deactivate-selected')))) {
                // Check $_GET array case.
                if (isset($get_array['plugin'])) {
                    if (!isset($get_array['checked'])) {
                        $get_array['checked'] = array();
                    }
                    $get_array['checked'][] = $get_array['plugin'];
                }

                // Check $_POST array case.
                if (isset($post_array['plugin'])) {
                    if (!isset($post_array['checked'])) {
                        $post_array['checked'] = array();
                    }
                    $post_array['checked'][] = $post_array['plugin'];
                }

                $plugins = array();

                if (isset($get_array['checked']) && !empty($get_array['checked'])) {
                    foreach ($get_array['checked'] as $plugin_file) {
                        $plugin_data = get_plugin_data(
                            WP_PLUGIN_DIR . '/' . $plugin_file,
                            false,
                            false);
                        $plugins[$plugin_file] = $plugin_data;
                    }

                    $this->audit_changes(array(
                        'initiator' => array(
                            'key' => $action . '-plugins',
                            'plugins' => $plugins,
                            'user' => $this->get_user_data(),
                        )
                    ));
                } elseif (isset($post_array['checked']) && !empty($post_array['checked'])) {
                    foreach ($post_array['checked'] as $plugin_file) {
                        $plugin_data = get_plugin_data(
                            WP_PLUGIN_DIR . '/' . $plugin_file,
                            false,
                            false);
                        $plugins[$plugin_file] = $plugin_data;
                    }

                    $this->audit_changes(array(
                        'initiator' => array(
                            'key' => $action . '-plugins',
                            'plugins' => $plugins,
                            'user' => $this->get_user_data(),
                        )
                    ));
                }
            }

            // Delete plugin(s).
            if (in_array($action, array('delete-plugin', 'delete-selected'))) {
                $deleted_plugins = array();

                if (isset($post_array['plugin'])) {
                    $deleted_plugins[$post_array['plugin']] = 'Removed';

                    $this->audit_changes(array(
                        'initiator' => array(
                            'key' => $action === 'delete-selected' ? $action . '-plugins' : $action,
                            'plugins' => $deleted_plugins,
                            'user' => $this->get_user_data(),
                        )
                    ));

                } elseif (isset($post_array['checked'])) {
                    foreach ($post_array['checked'] as $plugin_file) {
                        $deleted_plugins[$plugin_file] = 'Removed';
                    }

                    $this->audit_changes(array(
                        'initiator' => array(
                            'key' => $action === 'delete-selected' ? $action . '-plugins' : $action,
                            'plugins' => $deleted_plugins,
                            'user' => $this->get_user_data(),
                        )
                    ));
                }
            }

            // Uninstall theme.
            if (in_array($action, array('delete-theme'))) {
                $deleted_themes = array();

                foreach ($this->get_deleted_themes() as $index => $theme) {
                    $deleted_themes[$index] = $theme;
                }

                $this->audit_changes(array(
                    'initiator' => array(
                        'key' => $action,
                        'themes' => $deleted_themes,
                        'user' => $this->get_user_data(),
                    )
                ));
            }

            // Upgrade plugin(s).
            if (in_array($action, array('upgrade-plugin', 'update-plugin', 'update-selected'))) {
                $plugins = array();

                // Check $_GET array cases.
                if (isset($get_array['plugins'])) {
                    $plugins = explode(',', $get_array['plugins']);
                } elseif (isset($get_array['plugin'])) {
                    $plugins[] = $get_array['plugin'];
                }

                // Check $_POST array cases.
                if (isset($post_array['plugins'])) {
                    $plugins = explode(',', $post_array['plugins']);
                } elseif (isset($post_array['plugin'])) {
                    $plugins[] = $post_array['plugin'];
                }

                if (isset($plugins)) {
                    $plugins_upgraded = array();

                    foreach ($plugins as $plugin_file) {
                        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file, false, true);
                        $plugins_upgraded[$plugin_file] = $plugin_data;
                    }

                    $this->audit_changes(array(
                        'initiator' => array(
                            'key' => $action === 'update-selected' ? $action . '-plugins' : $action,
                            'plugins' => $plugins_upgraded,
                            'user' => $this->get_user_data(),
                        )
                    ));
                }
            }

            // Upgrade theme(s).
            if (in_array($action, array('upgrade-theme', 'update-theme', 'update-selected-themes'))) {
                $themes = array();

                // Check $_GET array cases.
                if (isset($get_array['slug']) || isset($get_array['theme'])) {
                    $themes[] = isset($get_array['slug']) ? $get_array['slug'] : $get_array['theme'];
                } elseif (isset($get_array['themes'])) {
                    $themes = explode(',', $get_array['themes']);
                }

                // Check $_POST array cases.
                if (isset($post_array['slug']) || isset($post_array['theme'])) {
                    $themes[] = isset($post_array['slug']) ? $post_array['slug'] : $post_array['theme'];
                } elseif (isset($post_array['themes'])) {
                    $themes = explode(',', $post_array['themes']);
                }
                if (isset($themes)) {
                    $themes_upgraded = array();

                    foreach ($themes as $theme) {
                        $themes_upgraded[$theme] = wp_get_theme($theme);
                    }

                    $this->audit_changes(array(
                        'initiator' => array(
                            'key' => $action,
                            'themes' => $themes_upgraded,
                            'user' => $this->get_user_data(),
                        )
                    ));
                }
            }
        }

        /**
         * @return array|string
         */
        function get_user_data()
        {
            $current_user = wp_get_current_user();

            if (!$current_user) {
                return 'unknown';
            }

            return array(
                'user_email' => $current_user->user_email,
                'user_ip' => $this->get_user_ip(),
            );
        }

        /**
         * @return mixed|void
         */
        private function get_user_ip()
        {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                //check ip from share internet
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                //to check ip is pass from proxy
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            return apply_filters('wpb_get_ip', $ip);
        }

        /**
         * Init cron schedules for audit
         */
        function init_cron_schedules()
        {
            add_filter('cron_schedules', array($this, 'audit_every_hourly'));
            add_action('audit_every_hourly', array($this, 'request_state_every_hourly'));
            // Schedule an action if it's not already scheduled
            if (!wp_next_scheduled('audit_every_hourly')) {
                wp_schedule_event(time(), 'request_state_every_hourly', 'audit_every_hourly');
            }
        }

        /**
         * @throws ImagickException
         */
        function request_state_every_hourly()
        {
            $this->audit_changes(array(
                'initiator' => array(
                    'key' => 'audit_every_hourly',
                )
            ));
        }

        /**
         * Add a new interval of 60 minutes
         *
         * @param $schedules
         * @return mixed
         */
        function audit_every_hourly($schedules)
        {
            $schedules['request_state_every_hourly'] = array(
                'interval' => 3600,
                'display' => __('Once Hourly', MY_MANAGED_TEXT_DOMAIN)
            );

            return $schedules;
        }

        /**
         * Generate list of themes with all data
         *
         * @return array
         */
        private function get_themes_list()
        {
            $themes = array();
            $all_themes = wp_get_themes();

            foreach ($all_themes as $theme) {
                $themes[$theme->get_stylesheet()] = array(
                    'Name' => $theme->get('Name'),
                    'Description' => $theme->get('Description'),
                    'Author' => $theme->get('Author'),
                    'AuthorURI' => $theme->get('AuthorURI'),
                    'Version' => $theme->get('Version'),
                    'Template' => $theme->get('Template'),
                    'Status' => $theme->get('Status'),
                    'Tags' => $theme->get('Tags'),
                    'TextDomain' => $theme->get('TextDomain'),
                    'DomainPath' => $theme->get('DomainPath')
                );
            }

            return $themes;
        }

        /**
         * @param $update_results
         * @throws ImagickException
         */
        function event_automatic_updates($update_results)
        {
            $this->audit_changes(array(
                'initiator' => array(
                    'key' => 'automatic-' . $update_results . '-update',
                )
            ));
        }

        /**
         * @param $new_name
         * @param $new_theme
         * @param $old_theme
         * @throws ImagickException
         */
        function event_switch_theme($new_name, $new_theme, $old_theme)
        {
            $switch_theme[$new_name] = $new_theme;

            $this->audit_changes(array(
                'initiator' => array(
                    'key' => 'switch-theme',
                    'themes' => $switch_theme,
                    'user' => $this->get_user_data(),
                )
            ));
        }

        /**
         * @param array $option
         * @return array|bool|mixed
         * @throws ImagickException
         */
        function audit_changes($option = array())
        {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-debug-data.php');
            require_once(ABSPATH . 'wp-admin/includes/update.php');
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            require_once(ABSPATH . 'wp-admin/includes/misc.php');

            wp_update_plugins();
            wp_update_themes();

            $params = array(
                'native_debug_data' => WP_Debug_Data::debug_data(),
                'plugins' => (object) get_plugins(),
                'plugin_updates' => (object) get_plugin_updates(),
                'themes' => (object) $this->get_themes_list(),
                'theme_updates' => (object)get_theme_updates(),
                'timestamp' => current_time('timestamp'),
            );

            if (!empty($option))
                $params = array_merge($params, $option);

            return $this->api_call('POST',
                $this->get_route($this->get_changes_route()),
                $this->get_auth_header(),
                json_encode($params));
        }
    }
}