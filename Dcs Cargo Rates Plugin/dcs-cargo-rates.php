<?php
/**
 * Plugin Name: DCS Cargo Rates
 * Description: A custom WordPress plugin for managing and displaying shipping rates
 * Version: 1.0.0
 * Author: Omar Helal
 * Text Domain: dcs-cargo
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('SCR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCR_VERSION', '1.0.0');

// Plugin activation hook
register_activation_hook(__FILE__, 'scr_activate_plugin');

function scr_activate_plugin() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Create rates table
    $table_name = $wpdb->prefix . 'scr_rates';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        origin varchar(255) NOT NULL,
        destination varchar(255) NOT NULL,
        shipping_mode varchar(50) NOT NULL,
        unit_measure varchar(50) NOT NULL,
        currency varchar(50) NOT NULL,
        charge_general decimal(10,2) NOT NULL,
        handling_fee decimal(10,2) NOT NULL,
        minimum_charge decimal(10,2) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add default settings
    add_option('scr_default_currency', 'USD');
    add_option('scr_default_unit', 'kg');
}

// Include required files
require_once SCR_PLUGIN_DIR . 'includes/admin/class-scr-admin.php';
require_once SCR_PLUGIN_DIR . 'includes/class-scr-shortcode.php';

// Initialize plugin
function scr_init() {
    // Initialize admin
    new SCR_Admin();
    
    // Initialize shortcode
    new SCR_Shortcode();
}
add_action('plugins_loaded', 'scr_init');

// Add plugin action links
function scr_plugin_action_links($links) {
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=dcs-cargo-rates') . '">Rates</a>',
        '<a href="' . admin_url('admin.php?page=dcs-cargo-settings') . '">Settings</a>'
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'scr_plugin_action_links');