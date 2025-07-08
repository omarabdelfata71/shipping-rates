<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'scr_rates';
$rates = $wpdb->get_results("SELECT id, origin, destination, shipping_mode, unit_measure, currency, CAST(charge_general AS DECIMAL(10,2)) as charge_general, CAST(handling_fee AS DECIMAL(10,2)) as handling_fee, CAST(minimum_charge AS DECIMAL(10,2)) as minimum_charge, created_at, updated_at FROM {$table_name} ORDER BY id DESC");
?>

<div class="wrap scr-rates-page">
    <h1><?php _e('Shipping Rates Management', 'dcs-cargo'); ?></h1>

    <!-- Add New Rate Wizard -->
    <div class="scr-wizard-container">
        <div class="scr-progress-bar">
            <div class="scr-progress-bar-inner" style="width: 25%;"></div>
        </div>
        
        <form id="scr-new-rate-form" method="post">
            <!-- Step 1: Mode Selection -->
            <div class="scr-wizard-step active" data-step="1">
                <div class="scr-wizard-header">
                    <h2><?php _e('Select Shipping Mode', 'dcs-cargo'); ?></h2>
                    <p><?php _e('Choose the mode of transportation for your shipping rate', 'dcs-cargo'); ?></p>
                </div>
                
                <div class="scr-mode-selection">
                    <div class="scr-mode-option" data-mode="Air">
                        <h3><?php _e('AIR', 'dcs-cargo'); ?></h3>
                        <p><?php _e('Air freight transportation', 'dcs-cargo'); ?></p>
                    </div>
                    <div class="scr-mode-option" data-mode="Sea">
                        <h3><?php _e('SEA', 'dcs-cargo'); ?></h3>
                        <p><?php _e('Sea freight transportation', 'dcs-cargo'); ?></p>
                    </div>
                </div>
                
                <input type="hidden" id="shipping_mode" name="shipping_mode" required>
                
                <div class="scr-wizard-navigation">
                    <div></div>
                    <button type="button" class="button button-primary scr-next-step" disabled><?php _e('Next Step', 'dcs-cargo'); ?></button>
                </div>
            </div>

            <!-- Step 2: Origin and Destination -->
            <div class="scr-wizard-step" data-step="2">
                <div class="scr-wizard-header">
                    <h2><?php _e('Select Origin and Destination', 'dcs-cargo'); ?></h2>
                    <p><?php _e('Choose the pickup and delivery locations', 'dcs-cargo'); ?></p>
                </div>
                
                <div class="scr-location-selection">
                    <div class="scr-location-field">
                        <label for="origin"><?php _e('Cargo Origin', 'dcs-cargo'); ?></label>
                        <select id="origin" name="origin" required>
                            <option value=""><?php _e('Select Origin', 'dcs-cargo'); ?></option>
                            <?php
                            $origins = $wpdb->get_col("SELECT DISTINCT origin FROM {$table_name} ORDER BY origin ASC");
                            foreach ($origins as $origin) {
                                echo '<option value="' . esc_attr($origin) . '">' . esc_html($origin) . '</option>';
                            }
                            ?>
                            <option value="new"><?php _e('Add New Origin', 'dcs-cargo'); ?></option>
                        </select>
                        <input type="text" id="new_origin" name="new_origin" style="display: none; margin-top: 10px;" placeholder="<?php _e('Enter new origin', 'dcs-cargo'); ?>">
                    </div>
                    <div class="scr-location-field">
                        <label for="destination"><?php _e('Cargo Destination', 'dcs-cargo'); ?></label>
                        <select id="destination" name="destination" required>
                            <option value=""><?php _e('Select Destination', 'dcs-cargo'); ?></option>
                            <?php
                            $destinations = $wpdb->get_col("SELECT DISTINCT destination FROM {$table_name} ORDER BY destination ASC");
                            foreach ($destinations as $destination) {
                                echo '<option value="' . esc_attr($destination) . '">' . esc_html($destination) . '</option>';
                            }
                            ?>
                            <option value="new"><?php _e('Add New Destination', 'dcs-cargo'); ?></option>
                        </select>
                        <input type="text" id="new_destination" name="new_destination" style="display: none; margin-top: 10px;" placeholder="<?php _e('Enter new destination', 'dcs-cargo'); ?>">
                    </div>
                </div>

                <div class="scr-wizard-navigation">
                    <button type="button" class="button scr-prev-step"><?php _e('Previous Step', 'dcs-cargo'); ?></button>
                    <button type="button" class="button button-primary scr-next-step"><?php _e('Next Step', 'dcs-cargo'); ?></button>
                </div>
            </div>

            <!-- Step 3: Unit and Currency -->
            <div class="scr-wizard-step" data-step="3">
                <div class="scr-wizard-header">
                    <h2><?php _e('Set Measurement Units', 'dcs-cargo'); ?></h2>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="unit_measure"><?php _e('Unit Measure', 'dcs-cargo'); ?></label>
                        <select id="unit_measure" name="unit_measure" required>
                            <option value="kg" <?php selected($default_unit, 'kg'); ?>><?php _e('KG', 'dcs-cargo'); ?></option>
                            <option value="cbm" <?php selected($default_unit, 'cbm'); ?>><?php _e('CBM', 'dcs-cargo'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="currency"><?php _e('Currency', 'dcs-cargo'); ?></label>
                        <select id="currency" name="currency" required>
                            <option value="USD" <?php selected($default_currency, 'USD'); ?>><?php _e('USD', 'dcs-cargo'); ?></option>
                            <option value="GBP" <?php selected($default_currency, 'GBP'); ?>><?php _e('GBP', 'dcs-cargo'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="scr-wizard-navigation">
                    <button type="button" class="button scr-prev-step"><?php _e('Previous Step', 'dcs-cargo'); ?></button>
                    <button type="button" class="button button-primary scr-next-step"><?php _e('Next Step', 'dcs-cargo'); ?></button>
                </div>
            </div>

            <!-- Step 4: Rate Details -->
            <div class="scr-wizard-step" data-step="4">
                <div class="scr-wizard-header">
                    <h2><?php _e('Set Rate Details', 'dcs-cargo'); ?></h2>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="charge_general"><?php _e('Freight rate', 'dcs-cargo'); ?></label>
                        <input type="number" id="charge_general" name="charge_general" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="handling_fee"><?php _e('Handling Fee', 'dcs-cargo'); ?></label>
                        <input type="number" id="handling_fee" name="handling_fee" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="minimum_charge"><?php _e('Minimum Charge', 'dcs-cargo'); ?></label>
                        <input type="number" id="minimum_charge" name="minimum_charge" step="0.01" required>
                    </div>
                </div>

                <div class="scr-wizard-navigation">
                    <button type="button" class="button scr-prev-step"><?php _e('Previous Step', 'dcs-cargo'); ?></button>
                    <button type="submit" class="button button-primary"><?php _e('Save Rate', 'dcs-cargo'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Search Form -->
    <div class="scr-search-form">
        <h2><?php _e('Search Rates', 'dcs-cargo'); ?></h2>
        <form id="scr-search-form" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="search_origin"><?php _e('Origin', 'dcs-cargo'); ?></label>
                    <input type="text" id="search_origin" name="search_origin">
                </div>
                <div class="form-group">
                    <label for="search_destination"><?php _e('Destination', 'dcs-cargo'); ?></label>
                    <input type="text" id="search_destination" name="search_destination">
                </div>
                <div class="form-group">
                    <label for="search_mode"><?php _e('Shipping Mode', 'dcs-cargo'); ?></label>
                    <select id="search_mode" name="search_mode">
                        <option value=""><?php _e('All', 'dcs-cargo'); ?></option>
                        <option value="Air"><?php _e('Air', 'dcs-cargo'); ?></option>
                        <option value="Sea"><?php _e('Sea', 'dcs-cargo'); ?></option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <button type="submit" class="button"><?php _e('Search', 'dcs-cargo'); ?></button>
                <button type="reset" class="button"><?php _e('Clear', 'dcs-cargo'); ?></button>
            </div>
        </form>
    </div>

    <!-- Rates Table -->
    <div class="scr-rates-table-wrapper">
        <table class="wp-list-table widefat fixed striped scr-rates-table">
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
                    <th><?php _e('Created', 'dcs-cargo'); ?></th>
                    <th><?php _e('Updated', 'dcs-cargo'); ?></th>
                    <th><?php _e('Actions', 'dcs-cargo'); ?></th>
                </tr>
            </thead>
            <tbody id="scr-rates-tbody">
                <?php foreach ($rates as $rate): ?>
                <tr data-id="<?php echo esc_attr($rate->id); ?>">
                    <td class="editable" data-field="origin"><?php echo esc_html($rate->origin); ?></td>
                    <td class="editable" data-field="destination"><?php echo esc_html($rate->destination); ?></td>
                    <td class="editable" data-field="shipping_mode">
                        <select>
                            <option value="Air" <?php selected($rate->shipping_mode, 'Air'); ?>><?php _e('Air', 'dcs-cargo'); ?></option>
                            <option value="Sea" <?php selected($rate->shipping_mode, 'Sea'); ?>><?php _e('Sea', 'dcs-cargo'); ?></option>
                        </select>
                    </td>
                    <td class="editable" data-field="unit_measure">
                        <select>
                            <option value="kg" <?php selected($rate->unit_measure, 'kg'); ?>><?php _e('KG', 'dcs-cargo'); ?></option>
                            <option value="cbm" <?php selected($rate->unit_measure, 'cbm'); ?>><?php _e('CBM', 'dcs-cargo'); ?></option>
                        </select>
                    </td>
                    <td class="editable" data-field="currency">
                        <select>
                            <option value="USD" <?php selected($rate->currency, 'USD'); ?>><?php _e('USD', 'dcs-cargo'); ?></option>
                            <option value="GBP" <?php selected($rate->currency, 'GBP'); ?>><?php _e('GBP', 'dcs-cargo'); ?></option>
                        </select>
                    </td>
                    <td class="editable" data-field="charge_general"><?php echo esc_html(number_format($rate->charge_general, 2)); ?></td>
                    <td class="editable" data-field="handling_fee"><?php echo esc_html(number_format($rate->handling_fee, 2)); ?></td>
                    <td class="editable" data-field="minimum_charge"><?php echo esc_html(number_format($rate->minimum_charge, 2)); ?></td>
                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($rate->created_at))); ?></td>
                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($rate->updated_at))); ?></td>
                    <td>
                        <button class="button button-small scr-save-row" style="display:none;"><?php _e('Save', 'dcs-cargo'); ?></button>
                        <button class="button button-small button-link-delete scr-delete-row"><?php _e('Delete', 'dcs-cargo'); ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>