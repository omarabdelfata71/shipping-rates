<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="scr-calculator-container">
    <!-- Shipping Mode Selection -->
    <div class="scr-section">
        <h2>Select Shipping Mode</h2>
        <div class="scr-mode-options">
            <div class="scr-mode-option" data-mode="AIR">
                <span>AIR</span>
            </div>
            <div class="scr-mode-option" data-mode="SEA">
                <span>SEA</span>
            </div>
        </div>
    </div>

    <!-- Origin and Destination -->
    <div class="scr-section">
        <div class="scr-location-group">
            <div class="scr-input-group">
                <label>Cargo Origin</label>
                <select id="scr-origin" name="origin" required>
                    <option value="">Select origin</option>
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'scr_rates';
                    $origins = $wpdb->get_col("SELECT DISTINCT origin FROM {$table_name} ORDER BY origin ASC");
                    foreach ($origins as $origin) {
                        echo '<option value="' . esc_attr($origin) . '">' . esc_html($origin) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="scr-input-group">
                <label>Cargo Destination</label>
                <select id="scr-destination" name="destination" required>
                    <option value="">Select destination</option>
                    <?php
                    $destinations = $wpdb->get_col("SELECT DISTINCT destination FROM {$table_name} ORDER BY destination ASC");
                    foreach ($destinations as $destination) {
                        echo '<option value="' . esc_attr($destination) . '">' . esc_html($destination) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Rate Information -->
    <div class="scr-section scr-rate-info" style="display: none;">
        <div class="scr-info-item">
            <span class="info-label">Minimum chargeable weight:</span>
            <span class="info-value" id="min-weight">0 kgs</span>
        </div>
        <div class="scr-info-item">
            <span class="info-label">Freight rate per kg:</span>
            <span class="info-value" id="rate-per-kg">USD 0</span>
        </div>
        <div class="scr-info-item">
            <span class="info-label">Handling Charges:</span>
            <span class="info-value" id="handling-charges">USD 0</span>
        </div>
    </div>

    <!-- Cargo Details -->
    <div class="scr-section">
        <div class="scr-cargo-entries">
            <div class="scr-cargo-entry" data-index="1">
                <h3>Cargo 1</h3>
                <div class="scr-weight-group">
                    <label>Actual Weight in Kgs</label>
                    <input type="number" class="scr-actual-weight" min="0" step="0.01" required>
                </div>
                <div class="scr-dimensions-group">
                    <div class="scr-dimension">
                        <label>Length in cm</label>
                        <input type="number" class="scr-length" min="0" step="0.01">
                    </div>
                    <div class="scr-dimension">
                        <label>Width in cm</label>
                        <input type="number" class="scr-width" min="0" step="0.01">
                    </div>
                    <div class="scr-dimension">
                        <label>Height in cm</label>
                        <input type="number" class="scr-height" min="0" step="0.01">
                    </div>
                </div>
                <div class="scr-cargo-results">
                    <div class="scr-result-item">
                        <span class="result-label">Volumetric Weight:</span>
                        <span class="volumetric-weight">0 kg</span>
                    </div>
                    <div class="scr-result-item">
                        <span class="result-label">Item freight cost:</span>
                        <span class="item-cost">USD 0</span>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" class="scr-add-cargo">Add another cargo</button>
    </div>

    <!-- Total Cost -->
    <div class="scr-section scr-total-section">
        <div class="scr-total-cost">
            <h3>Total freight cost:</h3>
            <span class="total-amount">USD 0</span>
        </div>
    </div>
</div>