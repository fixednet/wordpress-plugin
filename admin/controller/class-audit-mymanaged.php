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
        function hook_events()
        {
            add_action('shutdown', array($this, 'events_admin_listener'));
            add_action('switch_theme', array($this, 'mm_audit_changes'));
            add_action('automatic_updates_complete', array($this, 'mm_upgrader_changes'), 10, 2);
        }

        /**
         * Admin listener events callback
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
            $is_themes = 'themes' === $actype;
            $is_plugins = 'plugins' === $actype;

            // Install/Uninstall theme/plugin.
            if (in_array($action, array('install-theme', 'upload-theme', 'delete-theme', 'install-plugin', 'upload-plugin'))) {
                $this->mm_audit_changes(array(
                    'initiator' => array(
                        'key' => $action,
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

                if ((isset($get_array['checked']) && !empty($get_array['checked'])) ||
                    (isset($post_array['checked']) && !empty($post_array['checked']))) {
                    $this->mm_audit_changes(array(
                        'initiator' => array(
                            'key' => $action . '-plugins',
                        )
                    ));
                }
            }

            // Delete plugin(s).
            if (in_array($action, array('delete-plugin', 'delete-selected'))) {
                if (isset($post_array['plugin']) || $post_array['checked']) {
                    $this->mm_audit_changes(array(
                        'initiator' => array(
                            'key' => $action === 'delete-selected' ? $action . '-plugins' : $action,
                        )
                    ));
                }
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
                    $this->mm_audit_changes(array(
                        'initiator' => array(
                            'key' => $action === 'update-selected' ? $action . '-plugins' : $action,
                        )
                    ));
                }
            }

            // Update theme(s).
            if (in_array($action, array('upgrade-theme', 'update-theme', 'update-selected-themes'))) {
                // Themes.
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
                    $this->mm_audit_changes(array(
                        'initiator' => array(
                            'key' => $action,
                        )
                    ));
                }
            }
        }

        /**
         * Init cron schedules for audit
         */
        function init_cron_schedules()
        {
            if (get_mm_access_token()) {
                add_filter('cron_schedules', array($this, 'audit_every_hourly'));
                add_action('audit_every_hourly', array($this, 'mm_audit_changes'));
                // Schedule an action if it's not already scheduled
                if (!wp_next_scheduled('audit_every_hourly') && get_mm_access_token()) {
                    wp_schedule_event(time(), 'mm_audit_changes', 'audit_every_hourly');
                }
            }
        }

        /**
         * Add a new interval of 60 minutes
         *
         * @param $schedules
         * @return mixed
         */
        function audit_every_hourly($schedules)
        {
            $schedules['mm_audit_changes'] = array(
                'interval' => 3600,
                'display' => __('Every 60 Minutes', MY_MANAGED_TEXT_DOMAIN)
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
                $themes{$theme->stylesheet} = array(
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
         * @param $upgrader_object
         * @param $hook_extra
         */
        private function mm_upgrader_changes($upgrader_object, $hook_extra)
        {
            $this->mm_audit_changes(array(
                'initiator' => array(
                    'key' => $hook_extra['action'] . '_' . $hook_extra['type'],
                )
            ));
        }

        /**
         * @param array $option
         * @return array|bool|mixed
         */
        function mm_audit_changes($option = array())
        {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-debug-data.php');
            require_once(ABSPATH . 'wp-admin/includes/update.php');
            require_once(ABSPATH . 'wp-admin/includes/misc.php');
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');

            wp_update_plugins();
            wp_update_themes();

            $params = array(
                'native_debug_data' => WP_Debug_Data::debug_data(),
                'plugins' => get_plugins(),
                'plugin_updates' => get_plugin_updates(),
                'themes' => $this->get_themes_list(),
                'theme_updates' => get_theme_updates(),
                'timestamp' => current_time('timestamp'),
            );

            if (!empty($option) && is_array($option))
                $params = array_merge($params, $option);

            elseif (current_action() && did_action(current_action()) === 1)
                $params = array_merge($params, array(
                    'initiator' => array(
                        'key' => current_action()
                    )
                ));

            return $this->api_call('POST',
                $this->get_route($this->get_changes_route()),
                $this->get_mm_auth_header(),
                json_encode($params));
        }
    }
}