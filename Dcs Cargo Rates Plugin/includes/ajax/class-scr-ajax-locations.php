<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SCR_AJAX_Locations {
    public static function init() {
        add_action('wp_ajax_scr_get_filtered_locations', array(__CLASS__, 'get_filtered_locations'));
        add_action('wp_ajax_nopriv_scr_get_filtered_locations', array(__CLASS__, 'get_filtered_locations'));
    }

    public static function get_filtered_locations() {
        check_ajax_referer('scr_frontend_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'scr_rates';
        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : '';
        $selected_origin = isset($_POST['origin']) ? sanitize_text_field($_POST['origin']) : '';

        if (empty($mode)) {
            wp_send_json_error(array('message' => 'Shipping mode is required'));
            return;
        }

        // Validate shipping mode exists in the database
        $mode_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE mode = %s OR shipping_mode = %s",
            $mode, $mode
        ));

        if (!$mode_exists) {
            wp_send_json_error(array(
                'message' => 'Invalid shipping mode selected',
                'debug' => array(
                    'mode' => $mode,
                    'query' => "SELECT COUNT(*) FROM {$table_name} WHERE mode = '{$mode}' OR shipping_mode = '{$mode}'"
                )
            ));
            return;
        }

        // Get all origins for the selected shipping mode with proper sanitization
        $origins_query = $wpdb->prepare(
            "SELECT DISTINCT origin FROM {$table_name} WHERE (mode = %s OR shipping_mode = %s) AND origin IS NOT NULL AND origin != '' AND origin != 'NULL' ORDER BY origin ASC",
            $mode, $mode
        );
        $origins = $wpdb->get_col($origins_query);

        if (empty($origins)) {
            wp_send_json_error(array(
                'message' => 'No origins found for the selected shipping mode',
                'debug' => array(
                    'mode' => $mode,
                    'query' => $origins_query,
                    'table_exists' => $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'")
                )
            ));
            return;
        }

        // Build destinations query with proper conditions
        $where_conditions = array(
            '(mode = %s OR shipping_mode = %s)',
            'destination IS NOT NULL',
            'destination != ""',
            'destination != "NULL"'
        );
        $query_params = array($mode, $mode);

        if (!empty($selected_origin)) {
            // Verify if the selected origin exists in our database
            if (!in_array($selected_origin, $origins)) {
                wp_send_json_error(array(
                    'message' => 'Invalid origin selected',
                    'debug' => array(
                        'selected_origin' => $selected_origin,
                        'available_origins' => $origins
                    )
                ));
                return;
            }
            $where_conditions[] = 'origin = %s';
            $query_params[] = $selected_origin;
        }

        $where_clause = implode(' AND ', $where_conditions);
        $destinations_query = $wpdb->prepare(
            "SELECT DISTINCT destination FROM {$table_name} WHERE {$where_clause} ORDER BY destination ASC",
            ...$query_params
        );
        $destinations = $wpdb->get_col($destinations_query);

        if (empty($destinations)) {
            wp_send_json_error(array(
                'message' => !empty($selected_origin) ? 
                    'No destinations found for the selected origin' : 
                    'No destinations found for the selected shipping mode'
            ));
            return;
        }

        // Add table structure information to help debug
        $table_structure = $wpdb->get_results("DESCRIBE {$table_name}");
        $columns = array();
        if ($table_structure) {
            foreach ($table_structure as $column) {
                $columns[] = $column->Field;
            }
        }
        
        wp_send_json_success(array(
            'origins' => array_values(array_filter($origins)),
            'destinations' => array_values(array_filter($destinations)),
            'debug' => array(
                'columns' => $columns,
                'mode' => $mode,
                'table_name' => $table_name
            )
        ));
    }
}

SCR_AJAX_Locations::init();