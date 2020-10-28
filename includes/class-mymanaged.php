<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    MYMANAGED
 * @subpackage MYMANAGED/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    MYMANAGED
 * @subpackage MYMANAGED/includes
 * @author     Your Name <email@example.com>
 */
class MyManaged
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      MyManaged_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $mymanaged The string used to uniquely identify this plugin.
     */
    protected $mymanaged;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /*************************************************************
     * ACCESS PLUGIN AND ITS METHODES LATER FROM OUTSIDE OF PLUGIN
     *
     * @tutorial access_plugin_and_its_methodes_later_from_outside_of_plugin.php
     */
    /**
     * Store plugin admin class to allow public access.
     *
     * @since    20180622
     * @var object      The admin class.
     */
    public $admin;

    /**
     * Store plugin public class to allow public access.
     *
     * @since    20180622
     * @var object      The admin class.
     */
    public $public;
    // END ACCESS PLUGIN AND ITS METHODES LATER FROM OUTSIDE OF PLUGIN

    /*************************************************************
     * ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE
     *
     * @tutorial access_plugin_admin_public_methodes_from_inside.php
     */
    /**
     * Store plugin main class to allow public access.
     *
     * @since    20180622
     * @var object      The main class.
     */
    public $main;
    // ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->mymanaged = 'my-managed-site';
        $this->version = '1.1.0';

        /*************************************************************
         * ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE
         *
         * @tutorial access_plugin_admin_public_methodes_from_inside.php
         */
        $this->main = $this;
        // ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - MyManaged_Loader. Orchestrates the hooks of the plugin.
     * - MyManaged_i18n. Defines internationalization functionality.
     * - MyManaged_Admin. Defines all hooks for the admin area.
     * - MyManaged_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once MY_MANAGED_BASE_DIR . 'includes/class-mymanaged-loader.php';
        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once MY_MANAGED_BASE_DIR . 'includes/class-mymanaged-i18n.php';
        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once MY_MANAGED_BASE_DIR . 'admin/class-mymanaged-admin.php';
        /**
         * The class responsible for router.
         */
        require_once MY_MANAGED_BASE_DIR . 'admin/controller/class-router-mymanaged.php';
        /**
         * The class responsible for auth.
         */
        require_once MY_MANAGED_BASE_DIR . 'admin/controller/class-auth-mymanaged.php';
        /**
         * The class responsible for auto login.
         */
        require_once MY_MANAGED_BASE_DIR . 'admin/controller/class-auto-login-mymanaged.php';
        /**
         * The class responsible for audit.
         */
        require_once MY_MANAGED_BASE_DIR . 'admin/controller/class-audit-mymanaged.php';
        /**
         * The class responsible for updater skin.
         */
        require_once MY_MANAGED_BASE_DIR . 'admin/controller/class-updater-skin-mymanaged.php';
        /**
         * The class responsible for rest api.
         */
        require_once MY_MANAGED_BASE_DIR . 'admin/controller/class-rest-api-mymanaged.php';
        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once MY_MANAGED_BASE_DIR . 'public/class-mymanaged-public.php';
        /**
         * The class responsible for defining all actions for AJAX
         */
        require_once MY_MANAGED_BASE_DIR . 'includes/class-mymanaged-ajax.php';
        /**
         * The class responsible for defining all helpers functions
         */
        require_once MY_MANAGED_BASE_DIR . 'admin/lib/helpers.php';
        /**
         * The class responsible for update checker
         */
        require MY_MANAGED_BASE_DIR . 'plugin-update-checker/plugin-update-checker.php';

        $this->loader = new MyManaged_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the MyManaged_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new MyManaged_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new MyManaged_Admin($this->get_mymanage(), $this->get_version(), $this->get_mymanage());

        // Add menu item
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        /*************************************************************
         * ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE
         * (COMBINED WITH ACCESS PLUGIN AND ITS METHODES LATER FROM OUTSIDE OF PLUGIN)
         *
         *
         * @tutorial access_plugin_admin_public_methodes_from_inside.php
         */
        $this->admin = new MyManaged_Admin($this->get_mymanage(), $this->get_version(), $this->main);
        // END ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE

        /*************************************************************
         * ACCESS PLUGIN AND ITS METHODES LATER FROM OUTSIDE OF PLUGIN
         *
         * @tutorial access_plugin_and_its_methodes_later_from_outside_of_plugin.php
         */
        // $this->admin = new MyManaged_Admin( $this->get_mymanage(), $this->get_version() );

        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_scripts');
        // END ACCESS PLUGIN AND ITS METHODES LATER FROM OUTSIDE OF PLUGIN

        /**
         * This function runs when WordPress completes its upgrade process
         * It iterates through each plugin updated to see if ours is included
         */
        $this->loader->add_action( 'upgrader_process_complete', $plugin_admin, 'upgrader_process_complete', 10, 2 );

        /**
         * Redirect to settings page after plugin activated
         */
        add_action('activated_plugin', function ($plugin) {
            if ($plugin == MY_MANAGED_BASE_NAME) {
                exit(wp_redirect(admin_url('options-general.php?page=' . MY_MANAGED_TEXT_DOMAIN)));
            }
        });
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        /*************************************************************
         * ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE
         * (COMBINED WITH ACCESS PLUGIN AND ITS METHODES LATER FROM OUTSIDE OF PLUGIN)
         */
        $this->public = new MyManaged_Public($this->get_mymanage(), $this->get_version(), $this->main);
        // END ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE

        /*************************************************************
         * ACCESS PLUGIN AND ITS METHODES LATER FROM OUTSIDE OF PLUGIN
         */
        $this->loader->add_action('wp_enqueue_scripts', $this->public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $this->public, 'enqueue_scripts', 999);
        // END ACCESS PLUGIN AND ITS METHODES LATER FROM OUTSIDE OF PLUGIN
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }
    /**
     * Validate fields from admin area plugin settings form ('exopite-lazy-load-xt-admin-display.php')
     * @param mixed $input as field form settings form
     * @return mixed as validated fields
     */

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_mymanage()
    {
        return $this->mymanaged;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }
}