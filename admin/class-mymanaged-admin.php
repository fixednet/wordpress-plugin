<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    MYMANAGED
 * @subpackage MYMANAGED/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    MYMANAGED
 * @subpackage MYMANAGED/admin
 * @author     Your Name <email@example.com>
 */
class MyManaged_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $mymanaged The ID of this plugin.
     */
    private $mymanaged;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /*************************************************************
     * ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE
     *
     * @tutorial access_plugin_admin_public_methodes_from_inside.php
     */
    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @var object      The main class.
     */
    public $main;

    /*************************************************************
     * ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE
     *
     * @tutorial access_plugin_admin_public_methodes_from_inside.php
     */
    /**
     * Initialize the class and set its properties.
     *
     * @param string $mymanaged The name of this plugin.
     * @param string $version The version of this plugin.
     * @param $plugin_main
     * @since    1.0.0
     */
    public function __construct($mymanaged, $version, $plugin_main)
    {
        $this->mymanaged = $mymanaged;
        $this->version = $version;
        $this->main = $plugin_main;
    }

    /**
     * Register the StyleSheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->mymanaged, MY_MANAGED_BASE_URL . 'admin/css/mymanaged-admin.min.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->mymanaged, MY_MANAGED_BASE_URL . 'admin/js/mymanaged-admin.js', array('jquery'), $this->version, false);
    }

    public function add_plugin_admin_menu()
    {
        $icon_url = plugins_url('img/mymanaged_icon.png', __FILE__);
        $this->mymanaged = mm_get_plugin_slug();

        add_menu_page(mm_get_plugin_title(), mm_get_plugin_title(), 'manage_options', $this->mymanaged, '', $icon_url, 99);

        add_submenu_page($this->mymanaged, '', __(mm_get_plugin_title(), MY_MANAGED_TEXT_DOMAIN),
            'manage_options', $this->mymanaged, array($this, 'display_plugin_settings_page'));

//        add_submenu_page($this->mymanaged, '', __('Settings', MY_MANAGED_TEXT_DOMAIN),
//            'manage_options', mm_get_plugin_slug() . '-settings', array($this, 'display_plugin_settings_page'));

        add_action('admin_init', array($this, 'mymanaged_register_settings'));
    }

//    public function display_plugin_setup_page()
//    {
//        include_once('view/mymanaged-admin-display.php');
//    }

    public function display_plugin_settings_page()
    {
        include_once('view/mymanaged-admin-settings.php');
    }

    function mymanaged_register_settings()
    {
        register_setting('mymanaged_plugin_options', 'mymanaged_plugin_options');
        add_settings_section('access_token_settings', '', '', 'mymanaged_plugin');

        add_settings_field('mymanaged_setting_access_token', 'Access Token', array($this, 'mymanaged_setting_access_token'), 'mymanaged_plugin', 'access_token_settings');
    }

    function mymanaged_setting_access_token()
    {
        $options = get_option('mymanaged_plugin_options');
        echo "<textarea id='mymanaged_setting_access_token' 
                    name = 'mymanaged_plugin_options[access_token]'
                    rows='8' cols='60'
                    placeholder = '" . esc_attr('Unique Access Token', MY_MANAGED_TEXT_DOMAIN) . "'
                    >" . esc_attr($options['access_token']) . "</textarea>";
    }

    /**
     * This function runs when WordPress completes its upgrade process
     * It iterates through each plugin updated to see if ours is included
     *
     * @param $upgrader_object Array
     * @param $options Array
     * @link https://catapultthemes.com/wordpress-plugin-update-hook-upgrader_process_complete/
     * @link https://codex.wordpress.org/Plugin_API/Action_Reference/upgrader_process_complete
     */
    public function upgrader_process_complete($upgrader_object, $options)
    {
        // If an update has taken place and the updated type is plugins and the plugins element exists
        if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins'])) {

            // Iterate through the plugins being updated and check if ours is there
            foreach ($options['plugins'] as $plugin) {
                if ($plugin == MY_MANAGED_BASE_NAME) {

                    set_transient($this->mymanaged . '_updated', 1);

                }
            }
        }
    }
}