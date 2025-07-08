<?php
if (!defined('ABSPATH')) {
    exit;
}

$default_currency = get_option('scr_default_currency', 'USD');
$default_unit = get_option('scr_default_unit', 'kg');
?>

<div class="wrap scr-settings-page">
    <h1><?php _e('DCS Cargo Settings', 'dcs-cargo'); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('scr_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="scr_default_currency"><?php _e('Default Currency', 'dcs-cargo'); ?></label>
                </th>
                <td>
                    <select name="scr_default_currency" id="scr_default_currency">
                        <option value="USD" <?php selected($default_currency, 'USD'); ?>><?php _e('USD', 'dcs-cargo'); ?></option>
                        <option value="GBP" <?php selected($default_currency, 'GBP'); ?>><?php _e('GBP', 'dcs-cargo'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="scr_default_unit"><?php _e('Default Unit', 'dcs-cargo'); ?></label>
                </th>
                <td>
                    <select name="scr_default_unit" id="scr_default_unit">
                        <option value="kg" <?php selected($default_unit, 'kg'); ?>><?php _e('KG', 'dcs-cargo'); ?></option>
                        <option value="cbm" <?php selected($default_unit, 'cbm'); ?>><?php _e('CBM', 'dcs-cargo'); ?></option>
                    </select>
                </td>
            </tr>

            <!-- Volume Charge option removed -->
        </table>

        <?php submit_button(); ?>
    </form>

    <div class="scr-shortcodes-info">
        <h2><?php _e('Available Shortcodes', 'dcs-cargo'); ?></h2>
        <div class="scr-shortcode-box">
            <h3><?php _e('Shipping Calculator Wizard', 'dcs-cargo'); ?></h3>
            <code>[cargo_calculator]</code>
            <p><?php _e('Use this shortcode to display the shipping calculator wizard on any page or post.', 'dcs-cargo'); ?></p>
        </div>
        
        <div class="scr-shortcode-box">
            <h3><?php _e('Rates Table', 'dcs-cargo'); ?></h3>
            <code>[cargo_rates]</code>
            <p><?php _e('Use this shortcode to display a table of all available shipping rates.', 'dcs-cargo'); ?></p>
        </div>
    </div>

    <style>
        .scr-shortcodes-info {
            margin-top: 40px;
            padding: 20px;
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .scr-shortcode-box {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #0073aa;
        }
        .scr-shortcode-box code {
            display: inline-block;
            padding: 8px 12px;
            margin: 10px 0;
            background: #f0f0f1;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 13px;
        }
    </style>
</div>