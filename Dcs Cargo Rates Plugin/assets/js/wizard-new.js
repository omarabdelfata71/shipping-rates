jQuery(document).ready(function($) {
    let currentRate = null;
    let cargoEntries = [{
        actualWeight: 0,
        length: 0,
        width: 0,
        height: 0,
        volumetricWeight: 0,
        cost: 0
    }];
    
    // Check if we're on a mobile device
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 768;
    
    // Add mobile class to body if on mobile device
    if (isMobile) {
        $('body').addClass('scr-mobile-device');
        $('.scr-calculator-container').addClass('scr-mobile');
    }
    
    // Remove the 'new' option from destination dropdown if it exists
    $('#scr-destination option').each(function() {
        if ($(this).text().toLowerCase() === 'new' || $(this).val().toLowerCase() === 'new') {
            $(this).remove();
        }
    });
    
    // Also remove any option with empty text but value 'new'
    $('#scr-destination option[value="new"]').remove();

    // Mode selection handling
    $('.scr-mode-option').on('click', function() {
        $('.scr-mode-option').removeClass('active');
        $(this).addClass('active');
        // Show loading state
        $('.scr-section').show().find('select').prop('disabled', true);
        $('#scr-origin, #scr-destination').empty().append('<option value="">Loading...</option>');
        // Clear existing selections
        $('#scr-origin, #scr-destination').val('');
        // Update location dropdowns based on selected mode
        updateLocationDropdowns();
        // Store selected mode in hidden input
        $('#shipping_mode').val($(this).data('mode'));
    });

    // Function to update dropdowns based on selected mode
    function updateLocationDropdowns() {
        const selectedMode = $('.scr-mode-option.active').data('mode');
        if (!selectedMode) return;

        const $originSelect = $('#scr-origin');
        const $destinationSelect = $('#scr-destination');
        
        // Store currently selected values
        const currentOrigin = $originSelect.val();
        const currentDestination = $destinationSelect.val();

        // Remove any existing error messages
        $('.notice-error').remove();

        // Show loading state
        $originSelect.prop('disabled', true).empty().append('<option value="">Loading...</option>');
        $destinationSelect.prop('disabled', true).empty().append('<option value="">Loading...</option>');

        // Make AJAX request to get filtered locations
        $.ajax({
            url: scrData.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'scr_get_locations',
                nonce: scrData.nonce,
                shipping_mode: selectedMode
            },
            beforeSend: function(xhr) {
                // Add request headers
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                if (!response.success) {
                    console.error('Error fetching locations:', response.data);
                    // Show error state in dropdowns and display error message
                    $originSelect.empty().append('<option value="">Please try again</option>');
                    $destinationSelect.empty().append('<option value="">Please try again</option>');
                    
                    // Add error message above the dropdowns
                    const errorHTML = '<div class="notice notice-error" style="display: block !important; margin: 15px 0 !important; padding: 15px !important; background-color: #f8d7da !important; color: #721c24 !important; border: 1px solid #f5c6cb !important; border-radius: 4px !important; text-align: center !important;"><p style="margin: 0 !important; font-size: 16px !important;"><strong>Unable to load locations. Please refresh the page and try again.</strong></p></div>';
                    $('.scr-location-selection').before(errorHTML);
                    return;
                }

                const locations = response.data;

                // Clear existing options
                $originSelect.empty();
                $destinationSelect.empty();

                // Add default options
                $originSelect.append('<option value="">Select origin</option>');
                $destinationSelect.append('<option value="">Select destination</option>');

                // Add filtered options
                if (Array.isArray(locations.origins)) {
                    locations.origins.forEach(origin => {
                        $originSelect.append(`<option value="${origin}">${origin}</option>`);
                    });
                }

                if (Array.isArray(locations.destinations)) {
                    locations.destinations.forEach(destination => {
                        $destinationSelect.append(`<option value="${destination}">${destination}</option>`);
                    });
                }

                // Restore previously selected values if they exist in the new options
                if (currentOrigin && locations.origins && locations.origins.includes(currentOrigin)) {
                    $originSelect.val(currentOrigin);
                }
                if (currentDestination && locations.destinations && locations.destinations.includes(currentDestination)) {
                    $destinationSelect.val(currentDestination);
                }

                // Re-enable the selects
                $originSelect.prop('disabled', false);
                $destinationSelect.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                // Show error state in dropdowns
                $originSelect.empty().append('<option value="">Please try again</option>');
                $destinationSelect.empty().append('<option value="">Please try again</option>');
                
                // Add error message with retry button
                const errorHTML = '<div class="notice notice-error" style="display: block !important; margin: 15px 0 !important; padding: 15px !important; background-color: #f8d7da !important; color: #721c24 !important; border: 1px solid #f5c6cb !important; border-radius: 4px !important; text-align: center !important;"><p style="margin: 0 !important; font-size: 16px !important;"><strong>Unable to load locations.</strong></p><button type="button" class="retry-locations-btn button" style="margin-top: 10px !important;">Retry</button></div>';
                $('.scr-location-selection').before(errorHTML);
                
                // Add click handler for retry button
                $('.retry-locations-btn').on('click', function() {
                    $(this).closest('.notice-error').remove();
                    updateLocationDropdowns();
                });
                
                // Re-enable the selects
                $originSelect.prop('disabled', false);
                $destinationSelect.prop('disabled', false);
            }
        });
    }

    // Initially hide all sections except mode selection
    $(document).ready(function() {
        $('.scr-section:not(:first)').hide();
    });

    // Location selection handling
    $('#scr-origin, #scr-destination').on('change', function() {
        // Only fetch rates if both origin and destination are selected
        if ($('#scr-origin').val() && $('#scr-destination').val()) {
            fetchRateForRoute();
        } else {
            // Hide rate info if either field is empty
            $('.scr-rate-info').hide();
        }
    });

    function fetchRateForRoute() {
        // Remove any existing warning messages
        $('#scr-wizard-form .notice-warning').remove();
        
        const origin = $('#scr-origin').val();
        const destination = $('#scr-destination').val();
        let mode = $('.scr-mode-option.active').data('mode');

        // Convert mode to title case to match database format (AIR -> Air, SEA -> Sea)
        if (mode) {
            mode = mode.charAt(0).toUpperCase() + mode.slice(1).toLowerCase();
        }

        // Validate all required fields are present
        if (!origin || !destination || !mode) {
            console.log("Missing required fields for rate fetch:", { origin, destination, mode });
            $('.scr-rate-info').hide();
            return; // Exit the function early if any required field is missing
        }

        console.log("Fetching rate for:", origin, destination, mode);
        console.log("Using nonce:", scrData.nonce);
        $.ajax({
            url: scrData.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'scr_search_rates',
                nonce: scrData.nonce,
                origin: origin,
                destination: destination,
                shipping_mode: mode
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                console.log("AJAX Response:", response);
                if (response.success && response.data.length > 0) {
                    currentRate = response.data[0];
                    console.log("Current Rate set to:", currentRate);
                    updateRateInfo();
                    updateAllCargoEntries();
                } else {
                    console.log("No rates found or response error");
                    // Hide rate info, cargo entries and total cost
                    $('.scr-rate-info').hide();
                    $('.scr-cargo-entry').hide();
                    $('.scr-add-cargo').hide();
                    $('.total-freight-cost').hide();
                    $('.scr-total-section').hide();
                    // Remove any existing warning messages first
                    $('.notice-warning').remove();
                    // Display message for no rates found
                    // Create warning message HTML
                    const warningHTML = '<div class="notice notice-warning" style="display: block !important; margin: 15px 0 !important; padding: 15px !important; background-color: #fff3cd !important; color: #856404 !important; border: 1px solid #ffeeba !important; border-radius: 4px !important; text-align: center !important;"><p style="margin: 0 !important; font-size: 16px !important;"><strong>The rate for that route appears to be missing</strong></p></div>';
                    
                    // Insert inline warning only
                    $('.scr-location-group').after($(warningHTML));
                    
                    // Scroll to the inline warning message
                    $('html, body').animate({
                        scrollTop: $('.scr-location-group').offset().top + $('.scr-location-group').height() + 20
                    }, 800, 'swing');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                console.error("Response Text:", xhr.responseText);
                console.error("Status Code:", xhr.status);
                console.error("Request Data:", {
                    action: 'scr_search_rates',
                    nonce: scrData.nonce,
                    origin: origin,
                    destination: destination,
                    shipping_mode: mode
                });
                
                // Hide rate info, cargo entries and total cost
                $('.scr-rate-info').hide();
                $('.scr-cargo-entry').hide();
                $('.scr-add-cargo').hide();
                $('.total-freight-cost').hide();
                $('.scr-total-section').hide();
                
                // Remove any existing error messages
                $('.notice-error').remove();
                
                // Display error message with more details
                const errorHTML = '<div class="notice notice-error" style="display: block !important; margin: 15px 0 !important; padding: 15px !important; background-color: #f8d7da !important; color: #721c24 !important; border: 1px solid #f5c6cb !important; border-radius: 4px !important; text-align: center !important;"><p style="margin: 0 !important; font-size: 16px !important;"><strong>Error fetching shipping rates.</strong></p><button type="button" class="retry-rates-btn button" style="margin-top: 10px !important;">Retry</button></div>';
                $('.scr-location-group').after($(errorHTML));
                
                // Add click handler for retry button
                $('.retry-rates-btn').on('click', function() {
                    $(this).closest('.notice-error').remove();
                    fetchRateForRoute();
                });
            }
        });
    }

    function updateRateInfo() {
        if (!currentRate) return;

        // Make sure we're parsing the values as floats and handling potential null/undefined values
        const minWeight = parseFloat(currentRate.minimum_charge) || 0;
        const ratePerKg = parseFloat(currentRate.charge_general) || 0;
        const handlingFee = parseFloat(currentRate.handling_fee) || 0;
        
        $('#min-weight').text(minWeight.toFixed(2) + ' kgs');
        $('#rate-per-kg').text(ratePerKg.toFixed(2) + ' ' + currentRate.currency);
        $('#handling-charges').text(handlingFee.toFixed(2) + ' ' + currentRate.currency);
        $('.scr-rate-info').show();
    }

    // Calculate volumetric weight
    function calculateVolumetricWeight(length, width, height, mode) {
        if (!length || !width || !height) return 0;
        // Case-insensitive comparison for mode
        const divisor = (mode || '').toUpperCase() === 'AIR' ? 6000 : 1000;
        return (length * width * height) / divisor;
    }

    // Calculate cargo cost
    function calculateCargoCost(actualWeight, volumetricWeight) {
        if (!currentRate) return 0;
        
        // If actual weight is 0 or not provided, return 0 cost
        if (!actualWeight || actualWeight <= 0) {
            return 0;
        }

        // Get the minimum chargeable weight from the rate
        const minChargeableWeight = parseFloat(currentRate.minimum_charge) || 0;
        
        // First determine the chargeable weight (max of actual, volumetric, and minimum weight)
        const chargeableWeight = Math.max(actualWeight, volumetricWeight, minChargeableWeight);
        
        // Ensure we're properly parsing all values and providing fallbacks
        const generalRate = parseFloat(currentRate.charge_general) || 0;
        const handlingFee = parseFloat(currentRate.handling_fee) || 0;

        // Calculate cost based on the chargeable weight
        let cost = chargeableWeight * generalRate;
        
        // Add handling fee
        cost += handlingFee;

        return cost;
    }

    // Update single cargo entry calculations
    function updateCargoEntry($entry) {
        const index = $entry.data('index') - 1;
        const mode = $('.scr-mode-option.active').data('mode');

        const actualWeight = parseFloat($entry.find('.scr-actual-weight').val()) || 0;
        const length = parseFloat($entry.find('.scr-length').val()) || 0;
        const width = parseFloat($entry.find('.scr-width').val()) || 0;
        const height = parseFloat($entry.find('.scr-height').val()) || 0;

        const volumetricWeight = calculateVolumetricWeight(length, width, height, mode);
        const cost = calculateCargoCost(actualWeight, volumetricWeight);

        cargoEntries[index] = {
            actualWeight,
            length,
            width,
            height,
            volumetricWeight,
            cost
        };

        // Update display
        $entry.find('.volumetric-weight').text(volumetricWeight.toFixed(2) + ' kg');
        $entry.find('.item-cost').text(currentRate ? cost.toFixed(2) + ' ' + currentRate.currency : '0 USD');

        updateTotalCost();
    }

    // Update all cargo entries
    function updateAllCargoEntries() {
        $('.scr-cargo-entry').each(function() {
            updateCargoEntry($(this));
        });
    }

    // Update total cost
    function updateTotalCost() {
        // Check if any cargo entry has an actual weight greater than 0
        const hasActualWeight = cargoEntries.some(entry => entry.actualWeight > 0);
        
        // If no actual weight has been entered, show 0 as the total cost
        if (!hasActualWeight) {
            $('.total-amount').text('0.00 ' + (currentRate ? currentRate.currency : 'USD'));
            return;
        }
        
        // Otherwise calculate the total cost as normal
        const totalCost = cargoEntries.reduce((sum, entry) => sum + entry.cost, 0);
        $('.total-amount').text(
            totalCost.toFixed(2) + ' ' + (currentRate ? currentRate.currency : 'USD')
        );
    }

    // Handle dimension and weight inputs
    $(document).on('input', '.scr-cargo-entry input', function() {
        const $cargoEntry = $(this).closest('.scr-cargo-entry');
        updateCargoEntry($cargoEntry);
    });

    // Add another cargo entry
    $('.scr-add-cargo').on('click', function() {
        const newIndex = $('.scr-cargo-entry').length + 1;
        const $lastEntry = $('.scr-cargo-entry').last();
        const $newEntry = $lastEntry.clone();

        // Update entry index and title
        $newEntry.attr('data-index', newIndex);
        $newEntry.find('h3').text('Cargo ' + newIndex);

        // Clear input values
        $newEntry.find('input').val('');
        $newEntry.find('.volumetric-weight').text('0 kg');
        $newEntry.find('.item-cost').text(currentRate ? '0 ' + currentRate.currency : '0 USD');

        // Add remove button if not exists
        if (!$newEntry.find('.scr-remove-cargo').length) {
            $newEntry.append('<button type="button" class="scr-remove-cargo">Remove</button>');
        }

        // Add new entry to cargoEntries array
        cargoEntries.push({
            actualWeight: 0,
            length: 0,
            width: 0,
            height: 0,
            volumetricWeight: 0,
            cost: 0
        });

        // Insert before the "Add another cargo" button
        $(this).before($newEntry);
    });

    // Remove cargo entry
    $(document).on('click', '.scr-remove-cargo', function() {
        const $cargoEntry = $(this).closest('.scr-cargo-entry');
        const index = $cargoEntry.data('index') - 1;
        
        cargoEntries.splice(index, 1);
        $cargoEntry.remove();

        // Update remaining entries' indices
        $('.scr-cargo-entry').each(function(idx) {
            const newIndex = idx + 1;
            $(this).attr('data-index', newIndex);
            $(this).find('h3').text('Cargo ' + newIndex);
        });

        updateTotalCost();
    });
    // Debug current rate in console
    console.log("Current Rate:", currentRate);
});
