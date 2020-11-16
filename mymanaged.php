<?php
/**
 * Plugin Name:       MyManagedSite
 * Description:       Connects your WordPress site to My Managed Site.
 * Version:           1.2.0
 * Author:            MyManagedSite
 * Author URI:        https://mymanaged.site/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) die();

define('MY_MANAGED_TITLE', 'My Managed Site');
define('MY_MANAGED_TEXT_DOMAIN', 'my-managed-site');
/**
 * Store plugin base dir, for easier access later from other classes.
 * (eg. Include, pubic or admin)
 */
define('MY_MANAGED_BASE_DIR', plugin_dir_path(__FILE__));
/**
 * Store plugin base url, for easier access later from other classes.
 * (eg. Include, pubic or admin)
 */
define('MY_MANAGED_BASE_URL', plugin_dir_url(__FILE__));
/**
 * Store plugin backups dir, for easier access later from other classes.
 * (eg. Include, pubic or admin)
 */
define('MY_MANAGED_BACKUP_DIR', WP_CONTENT_DIR . '/my-managed-site/backups/');

/********************************************
 * RUN CODE ON PLUGIN UPGRADE AND ADMIN NOTICE
 *
 * @tutorial run_code_on_plugin_upgrade_and_admin_notice.php
 */
define('MY_MANAGED_BASE_NAME', plugin_basename(__FILE__));
// RUN CODE ON PLUGIN UPGRADE AND ADMIN NOTICE

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mymanaged-activator.php
 */
function activate_mymanage()
{
    require_once MY_MANAGED_BASE_DIR . 'includes/class-mymanaged-activator.php';
    MyManaged_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mymanaged-deactivator.php
 */
function deactivate_mymanage()
{
    require_once MY_MANAGED_BASE_DIR . 'includes/class-mymanaged-deactivator.php';
    MyManaged_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_mymanage');
register_deactivation_hook(__FILE__, 'deactivate_mymanage');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require MY_MANAGED_BASE_DIR . 'includes/class-mymanaged.php';

/********************************************
 * THIS ALLOW YOU TO ACCESS YOUR PLUGIN CLASS
 * eg. in your template/outside of the plugin.
 *
 * Of course you do not need to use a global,
 * you could wrap it in singleton too,
 * or you can store it in a static class,
 * etc...
 *
 * @tutorial access_plugin_and_its_methodes_later_from_outside_of_plugin.php
 */
global $pbt_prefix_mymanage;
$pbt_prefix_mymanage = new MyManaged();
$pbt_prefix_mymanage->run();

$audit_class = new MyManaged_Audit();
$audit_class->hook_events();

new MyManaged_Auto_Login();
new MyManaged_Rest_API();

$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/mymanagedsite/wordpress-plugin/',
    __FILE__,
    'mymanaged'
);

// END THIS ALLOW YOU TO ACCESS YOUR PLUGIN CLASS