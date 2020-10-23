<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    MYMANAGED
 * @subpackage MYMANAGED/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    MYMANAGED
 * @subpackage MYMANAGED/public
 * @author     Your Name <email@example.com>
 */
class MyManaged_Public
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
     * @since    20180622
     * @var object      The main class.
     */
    public $main;
    // END ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE


    /*************************************************************
     * ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE
     *
     * @tutorial access_plugin_admin_public_methodes_from_inside.php
     */
    /**
     * Initialize the class and set its properties.
     *
     * @param $mymanaged
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
    // END ACCESS PLUGIN ADMIN PUBLIC METHODES FROM INSIDE

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts(){}
}
