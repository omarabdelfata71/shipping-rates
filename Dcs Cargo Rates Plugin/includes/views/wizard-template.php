<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="scr-wizard-container">
    <div class="scr-wizard-steps">
        <div class="scr-step active" data-step="1">
            <span class="step-number">1</span>
            <span class="step-title"><?php _e('Shipping Mode', 'dcs-cargo'); ?></span>
        </div>
        <div class="scr-step" data-step="2">
            <span class="step-number">2</span>
            <span class="step-title"><?php _e('Route', 'dcs-cargo'); ?></span>
        </div>
        <div class="scr-step" data-step="3">
            <span class="step-number">3</span>
            <span class="step-title"><?php _e('Cargo Details', 'dcs-cargo'); ?></span>
        </div>
    </div>

    <div class="scr-wizard-content">
        <!-- Step 1: Shipping Mode -->
        <div class="scr-step-content active" data-step="1">
            <div class="scr-mode-selection">
                <div class="scr-mode-option" data-mode="air">
                    <div class="mode-icon air-icon"></div>
                    <span><?php _e('Air Freight', 'dcs-cargo'); ?></span>
                </div>
                <div class="scr-mode-option" data-mode="sea">
                    <div class="mode-icon sea-icon"></div>
                    <span><?php _e('Sea Freight', 'dcs-cargo'); ?></span>
                </div>
            </div>
            <div class="scr-wizard-buttons">
                <button class="scr-next-step" disabled><?php _e('Next', 'dcs-cargo'); ?></button>
            </div>
        </div>

        <!-- Step 2: Route Selection -->
        <div class="scr-step-content" data-step="2">
            <div class="scr-route-selection">
                <div class="scr-input-group">
                    <label for="scr-origin"><?php _e('Origin', 'dcs-cargo'); ?></label>
                    <input type="text" id="scr-origin" name="origin" required placeholder="<?php _e('Enter origin location', 'dcs-cargo'); ?>">
                </div>
                <div class="scr-input-group">
                    <label for="scr-destination"><?php _e('Destination', 'dcs-cargo'); ?></label>
                    <input type="text" id="scr-destination" name="destination" required placeholder="<?php _e('Enter destination location', 'dcs-cargo'); ?>">
                </div>
            </div>
            <div class="scr-wizard-buttons">
                <button class="scr-prev-step"><?php _e('Previous', 'dcs-cargo'); ?></button>
                <button class="scr-next-step" disabled><?php _e('Next', 'dcs-cargo'); ?></button>
            </div>
        </div>

        <!-- Step 3: Cargo Details -->
        <div class="scr-step-content" data-step="3">
            <div class="scr-cargo-details">
                <div class="scr-input-group">
                    <label for="scr-weight"><?php _e('Weight (kg)', 'dcs-cargo'); ?></label>
                    <input type="number" id="scr-weight" name="weight" min="0" step="0.01" required>
                </div>
                <!-- Volume rate section removed -->
            </div>
            <div class="scr-wizard-buttons">
                <button class="scr-prev-step"><?php _e('Previous', 'dcs-cargo'); ?></button>
                <button class="scr-calculate-rate"><?php _e('Calculate Rate', 'dcs-cargo'); ?></button>
            </div>
        </div>
    </div>

    <!-- Rate Results -->
    <div class="scr-rate-results" style="display: none;">
        <h3><?php _e('Shipping Rate', 'dcs-cargo'); ?></h3>
        <div class="scr-rate-details"></div>
    </div>
</div>