<?php

if (!defined('ABSPATH')) {
    exit;
}

class SCR_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_scr_save_rate', array($this, 'save_rate'));
        add_action('wp_ajax_scr_delete_rate', array($this, 'delete_rate'));
        add_action('wp_ajax_scr_update_rate', array($this, 'update_rate'));
        add_action('wp_ajax_scr_search_rates', array($this, 'search_rates'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'DCS Cargo',
            'DCS Cargo',
            'manage_options',
            'dcs-cargo-rates',
            array($this, 'render_rates_page'),
            'dashicons-money-alt',
            30
        );

        add_submenu_page(
            'dcs-cargo-rates',
            'Rates',
            'Rates',
            'manage_options',
            'dcs-cargo-rates',
            array($this, 'render_rates_page')
        );

        add_submenu_page(
            'dcs-cargo-rates',
            'Settings',
            'Settings',
            'manage_options',
            'dcs-cargo-settings',
            array($this, 'render_settings_page')
        );
    }

    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, array('toplevel_page_dcs-cargo-rates', 'dcs-cargo_page_dcs-cargo-settings'))) {
            return;
        }

        wp_enqueue_style('scr-admin-style', SCR_PLUGIN_URL . 'assets/css/admin.css', array(), SCR_VERSION);
        wp_enqueue_style('scr-wizard-style', SCR_PLUGIN_URL . 'assets/css/wizard.css', array(), SCR_VERSION);
        wp_enqueue_script('scr-admin-script', SCR_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SCR_VERSION, true);
        wp_enqueue_script('scr-wizard-script', SCR_PLUGIN_URL . 'assets/js/wizard.js', array('jquery'), SCR_VERSION, true);

        wp_localize_script('scr-admin-script', 'scrAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scr_frontend_nonce')
        ));

        wp_localize_script('scr-wizard-script', 'scrData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scr_frontend_nonce')
        ));
    }

    public function render_rates_page() {
        include SCR_PLUGIN_DIR . 'includes/admin/views/html-rates-page.php';
    }

    public function render_settings_page() {
        include SCR_PLUGIN_DIR . 'includes/admin/views/html-settings-page.php';
    }

    public function register_settings() {
        register_setting('scr_settings', 'scr_default_currency');
        register_setting('scr_settings', 'scr_default_unit');
        // Volume charge setting removed
    }

    public function save_rate() {
        check_ajax_referer('scr_frontend_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $data = array(
            'origin' => sanitize_text_field($_POST['origin']),
            'destination' => sanitize_text_field($_POST['destination']),
            'shipping_mode' => sanitize_text_field($_POST['shipping_mode']),
            'unit_measure' => sanitize_text_field($_POST['unit_measure']),
            'currency' => sanitize_text_field($_POST['currency']),
            'charge_general' => floatval($_POST['charge_general']),
            'handling_fee' => floatval($_POST['handling_fee']),
            'minimum_charge' => floatval($_POST['minimum_charge'])
        );

        global $wpdb;
        $table_name = $wpdb->prefix . 'scr_rates';

        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            wp_send_json_error('Failed to save rate');
        }

        wp_send_json_success(array(
            'message' => 'Rate saved successfully',
            'id' => $wpdb->insert_id
        ));
    }

    public function update_rate() {
        check_ajax_referer('scr_frontend_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $id = intval($_POST['id']);
        $data = array(
            'origin' => sanitize_text_field($_POST['origin']),
            'destination' => sanitize_text_field($_POST['destination']),
            'shipping_mode' => sanitize_text_field($_POST['shipping_mode']),
            'unit_measure' => sanitize_text_field($_POST['unit_measure']),
            'currency' => sanitize_text_field($_POST['currency']),
            'charge_general' => floatval($_POST['charge_general']),
            'handling_fee' => floatval($_POST['handling_fee']),
            'minimum_charge' => floatval($_POST['minimum_charge'])
        );

        global $wpdb;
        $table_name = $wpdb->prefix . 'scr_rates';

        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $id)
        );

        if ($result === false) {
            wp_send_json_error('Failed to update rate');
        }

        wp_send_json_success('Rate updated successfully');
    }

    public function delete_rate() {
        check_ajax_referer('scr_frontend_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(-1);
        }

        $id = intval($_POST['id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'scr_rates';

        $result = $wpdb->delete(
            $table_name,
            array('id' => $id)
        );

        if ($result === false) {
            wp_send_json_error('Failed to delete rate');
        }

        wp_send_json_success('Rate deleted successfully');
    }

    public function search_rates() {
        check_ajax_referer('scr_frontend_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'scr_rates';

        $where = array('1=1');
        $values = array();

        if (!empty($_POST['origin'])) {
            $where[] = 'origin LIKE %s';
            $values[] = '%' . $wpdb->esc_like(sanitize_text_field($_POST['origin'])) . '%';
        }

        if (!empty($_POST['destination'])) {
            $where[] = 'destination LIKE %s';
            $values[] = '%' . $wpdb->esc_like(sanitize_text_field($_POST['destination'])) . '%';
        }

        if (!empty($_POST['shipping_mode'])) {
            $where[] = 'shipping_mode = %s';
            $values[] = sanitize_text_field($_POST['shipping_mode']);
        }

        $query = "SELECT * FROM {$table_name} WHERE " . implode(' AND ', $where);
        $results = $wpdb->get_results($wpdb->prepare($query, $values));

        wp_send_json_success($results);
    }
}