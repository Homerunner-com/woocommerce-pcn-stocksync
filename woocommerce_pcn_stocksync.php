<?php
/**
 * Plugin Name: HomeRunner: PCN StockSync
 * Plugin URI: http://homerunner.com
 * Description: This plugin keeps stock updated with PakkecenterNord in WooCommerce
 * Version: 1.0
 * Author: HomeRunner
 * Author URI: http://homerunner.com
 * Developer: Kevin Steen Hansen / HomeRunner
 * Developer URI: http://homerunner.com
 * Text Domain: homerunner-pcn-stocksync
 * Domain Path: /languages
 *
 * Developed with: Wordpress 5.3.2
 * Developed with: WooCommerce 3.8.1
 *
 * Copyright: © 2023- HomeRunner.com
 * License: MIT
 */

// Check if absolute path of wordpress directory else exit
if (!defined('ABSPATH')) {
    exit;
}

// Define version of plugin
define('PCN_WOOCOMMERCE_STOCK', '1.0');
define('PLUGIN_FILE_URL', __FILE__);

add_action('plugins_loaded', 'pcn_stocksync_load_textdomain');
function pcn_stocksync_load_textdomain() {
    load_plugin_textdomain('homerunner-pcn-stocksync', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

// Check if woocommerce is active
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (!is_plugin_active('woocommerce/woocommerce.php')) {

    // If WooCommerce isn't active then give admin a warning
    add_action('admin_notices', function () {
        ?>
        <div class="notice notice-warning">
            <p><?php echo __('PCN StockSync requires that WooCommerce is installed.', 'homerunner-pcn-stocksync'); ?></p>
            <p><?php echo __('You can download WooCommerce here: ', 'homerunner-pcn-stocksync') . sprintf('<a href="%s/wp-admin/plugin-install.php?s=WooCommerce&tab=search&type=term">Download</a>', get_site_url()) ?></p>
        </div>
        <?php
    });
    return;

} else {

    // Define plugin path
    if (!defined('PCN_STOCKSYNC_DIR')) {
        define('PCN_STOCKSYNC_DIR', plugin_dir_path(__FILE__));
    }

    // Add settings link to plugin in overview of plugins
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pcnstocksync_action_links');
    function pcnstocksync_action_links($links) {
        $links[] = '<a href="' . admin_url('admin.php?page=wc-settings&tab=pcnstocksync') . '">Indstillinger</a>';
        return $links;
    }

    include(PCN_STOCKSYNC_DIR . 'includes/curl.php');
    include(PCN_STOCKSYNC_DIR . 'includes/functions.php');
    include(PCN_STOCKSYNC_DIR . 'includes/admin/class-pcnstocksync-settings.php');

}
