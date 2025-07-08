<?php

if (!defined('ABSPATH')) {
    exit;
}

class SCR_Shortcode {
    public function __construct() {
        add_shortcode('cargo_rates', array($this, 'render_rates_table'));
        add_shortcode('cargo_calculator', array($this, 'render_calculator_wizard'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_ajax_scr_search_rates', array($this, 'search_rates'));
        add_action('wp_ajax_nopriv_scr_search_rates', array($this, 'search_rates'));
        add_action('wp_ajax_scr_get_locations', array($this, 'get_locations'));
        add_action('wp_ajax_nopriv_scr_get_locations', array($this, 'get_locations'));
        
        // Add viewport meta tag in the head for mobile responsiveness
        add_action('wp_head', array($this, 'add_viewport_meta'));
    }

    public function enqueue_frontend_assets() {
        // Enqueue with higher priority to ensure our styles aren't overridden
        wp_enqueue_style('scr-frontend-style', SCR_PLUGIN_URL . 'assets/css/frontend.css', array(), SCR_VERSION . '.' . time());
        wp_enqueue_style('scr-wizard-style', SCR_PLUGIN_URL . 'assets/css/wizard-new.css', array(), SCR_VERSION . '.' . time());
        
        // Enqueue desktop and tablet styles
        wp_enqueue_style('scr-desktop-tablet-styles', SCR_PLUGIN_URL . 'assets/css/desktop-tablet-styles.css', array('scr-wizard-style'), SCR_VERSION . '.' . time());
        
        // Enqueue mobile-specific fixes with highest priority
        wp_enqueue_style('scr-mobile-fixes', SCR_PLUGIN_URL . 'assets/css/mobile-fixes.css', array('scr-wizard-style', 'scr-desktop-tablet-styles'), SCR_VERSION . '.' . time());
        
        wp_enqueue_script('scr-frontend-script', SCR_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SCR_VERSION, true);
        wp_enqueue_script('scr-wizard-script', SCR_PLUGIN_URL . 'assets/js/wizard-new.js', array('jquery'), SCR_VERSION, true);

        // Add inline CSS to ensure mobile styles are applied
        wp_add_inline_style('scr-mobile-fixes', '@media screen and (max-width: 768px) { .scr-calculator-container { width: 100% !important; max-width: 100% !important; } }');
        
        // Localize script data for both frontend and wizard scripts
        $script_data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scr_frontend_nonce'),
            'is_mobile' => wp_is_mobile()
        );
        
        wp_localize_script('scr-frontend-script', 'scrData', $script_data);
        wp_localize_script('scr-wizard-script', 'scrData', $script_data);
    }

    public function add_viewport_meta() {
        if (!did_action('wp_head')) {
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
        }
    }
    
    public function render_calculator_wizard($atts) {
        ob_start();
        include SCR_PLUGIN_DIR . 'includes/views/wizard-template-new.php';
        return ob_get_clean();
    }

    public function get_locations() {
        try {
            check_ajax_referer('scr_frontend_nonce', 'nonce');

            global $wpdb;
            $table_name = $wpdb->prefix . 'scr_rates';
            
            // Get the shipping mode from the request and convert to uppercase for consistency
            $shipping_mode = isset($_POST['shipping_mode']) ? strtoupper(sanitize_text_field($_POST['shipping_mode'])) : '';
            
            // Validate shipping mode
            if ($shipping_mode && !in_array($shipping_mode, array('AIR', 'SEA'))) {
                wp_send_json_error(array('message' => 'Invalid shipping mode'));
                return;
            }
            
            // Prepare the WHERE clause based on shipping mode
            $where_clause = '';
            if ($shipping_mode) {
                $where_clause = $wpdb->prepare(" WHERE shipping_mode = %s", $shipping_mode);
            }
            
            // Get filtered origins and destinations
            $origins = $wpdb->get_col("SELECT DISTINCT origin FROM {$table_name}{$where_clause} ORDER BY origin ASC");
            
            if ($wpdb->last_error) {
                wp_send_json_error(array(
                    'message' => 'Database error while fetching origins',
                    'error' => $wpdb->last_error
                ));
                return;
            }
            
            $destinations = $wpdb->get_col("SELECT DISTINCT destination FROM {$table_name}{$where_clause} ORDER BY destination ASC");
            
            if ($wpdb->last_error) {
                wp_send_json_error(array(
                    'message' => 'Database error while fetching destinations',
                    'error' => $wpdb->last_error
                ));
                return;
            }

            if (empty($origins) || empty($destinations)) {
                wp_send_json_error(array(
                    'message' => 'No routes found for the selected shipping mode.',
                    'shipping_mode' => $shipping_mode
                ));
                return;
            }

            wp_send_json_success(array(
                'origins' => $origins,
                'destinations' => $destinations,
                'shipping_mode' => $shipping_mode
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error processing request: ' . $e->getMessage()
            ));
        }
    }

    public function search_rates() {
        try {
            check_ajax_referer('scr_frontend_nonce', 'nonce');
            
            if (!isset($_POST['origin']) || !isset($_POST['destination']) || !isset($_POST['shipping_mode'])) {
                wp_send_json_error(array('message' => 'Missing required parameters'));
                return;
            }
            
            $origin = sanitize_text_field($_POST['origin']);
            $destination = sanitize_text_field($_POST['destination']);
            $shipping_mode = strtoupper(sanitize_text_field($_POST['shipping_mode']));

            global $wpdb;
            $table_name = $wpdb->prefix . 'scr_rates';

            // Validate shipping mode
            if (!in_array($shipping_mode, array('AIR', 'SEA'))) {
                wp_send_json_error(array('message' => 'Invalid shipping mode'));
                return;
            }

            $query = $wpdb->prepare(
                "SELECT id, origin, destination, shipping_mode, unit_measure, currency, 
                CAST(charge_general AS DECIMAL(10,2)) as charge_general, 
                CAST(handling_fee AS DECIMAL(10,2)) as handling_fee, 
                CAST(minimum_charge AS DECIMAL(10,2)) as minimum_charge, 
                created_at, updated_at 
                FROM {$table_name} 
                WHERE origin = %s AND destination = %s AND shipping_mode = %s",
                $origin,
                $destination,
                $shipping_mode
            );

            if ($wpdb->last_error) {
                wp_send_json_error(array(
                    'message' => 'Database query error',
                    'error' => $wpdb->last_error
                ));
                return;
            }

            $rates = $wpdb->get_results($query);

            if ($rates) {
                $formatted_rates = array_map(function($rate) {
                    return array(
                        'id' => $rate->id,
                        'origin' => $rate->origin,
                        'destination' => $rate->destination,
                        'shipping_mode' => $rate->shipping_mode,
                        'unit_measure' => $rate->unit_measure,
                        'currency' => $rate->currency,
                        'charge_general' => floatval($rate->charge_general),
                        'handling_fee' => floatval($rate->handling_fee),
                        'minimum_charge' => floatval($rate->minimum_charge),
                        'created_at' => $rate->created_at,
                        'updated_at' => $rate->updated_at
                    );
                }, $rates);
                wp_send_json_success($formatted_rates);
            } else {
                wp_send_json_error(array(
                    'message' => 'No rates found for the selected route.',
                    'debug' => array(
                        'origin' => $origin,
                        'destination' => $destination,
                        'shipping_mode' => $shipping_mode
                    )
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error processing request: ' . $e->getMessage()
            ));
        }
    }

    public function render_rates_table($atts) {
        $atts = shortcode_atts(array(
            'origin' => '',
            'destination' => '',
            'mode' => ''
        ), $atts, 'cargo_rates');

        global $wpdb;
        $table_name = $wpdb->prefix . 'scr_rates';

        $where = array('1=1');
        $values = array();

        if (!empty($atts['origin'])) {
            $where[] = 'origin LIKE %s';
            $values[] = '%' . $wpdb->esc_like($atts['origin']) . '%';
        }

        if (!empty($atts['destination'])) {
            $where[] = 'destination LIKE %s';
            $values[] = '%' . $wpdb->esc_like($atts['destination']) . '%';
        }

        if (!empty($atts['mode'])) {
            $where[] = 'shipping_mode = %s';
            $values[] = $atts['mode'];
        }

        $query = "SELECT * FROM {$table_name} WHERE " . implode(' AND ', $where);
        $rates = $wpdb->get_results($wpdb->prepare($query, $values));

        ob_start();
        ?>
        <div class="scr-rates-table-wrapper">
            <table class="scr-rates-table">
                <thead>
                    <tr>
                        <th><?php _e('Origin', 'dcs-cargo'); ?></th>
                        <th><?php _e('Destination', 'dcs-cargo'); ?></th>
                        <th><?php _e('Mode', 'dcs-cargo'); ?></th>
                        <th><?php _e('Unit', 'dcs-cargo'); ?></th>
                        <th><?php _e('Currency', 'dcs-cargo'); ?></th>
                        <th><?php _e('Freight Rate', 'dcs-cargo'); ?></th>
                        <th><?php _e('Handling', 'dcs-cargo'); ?></th>
                        <th><?php _e('Minimum', 'dcs-cargo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rates as $rate): ?>
                    <tr>
                        <td><?php echo esc_html($rate->origin); ?></td>
                        <td><?php echo esc_html($rate->destination); ?></td>
                        <td><?php echo esc_html($rate->shipping_mode); ?></td>
                        <td><?php echo esc_html($rate->unit_measure); ?></td>
                        <td><?php echo esc_html($rate->currency); ?></td>
                        <td><?php echo esc_html(number_format($rate->charge_general, 2)); ?></td>
                        <td><?php echo esc_html(number_format($rate->handling_fee, 2)); ?></td>
                        <td><?php echo esc_html(number_format($rate->minimum_charge, 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}