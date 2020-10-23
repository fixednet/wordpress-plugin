<?php

/**
 * The file that defines the auto-login class
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

    class MyManaged_Auto_Login extends MyManaged_Auth
    {
        public function __construct()
        {
            if (isset($_GET['nonce']) && !empty($_GET['nonce']) &&
                isset($_GET['autologin']) && $_GET['autologin'] === 'true' &&
                $this->check_nonce($_GET['nonce'])) {
                add_action('init', array($this, 'auto_login'));
            }
        }

        /**
         * Log in as a my_managed admin user remotely
         */
        function auto_login()
        {
            $user = get_user_by('login', 'my_managed_support');

            if (!is_user_logged_in() && !$user) {
                /* Create user in db */
                $random_password = wp_generate_password(12);
                $user_id = wp_create_user('my_managed_support', $random_password, 'support@mymanaged.site');
                $user = new WP_User($user_id);
                $user->set_role('administrator');
            }

            wp_set_auth_cookie($user->ID);
            wp_redirect(admin_url());
            exit;
        }
    }
}