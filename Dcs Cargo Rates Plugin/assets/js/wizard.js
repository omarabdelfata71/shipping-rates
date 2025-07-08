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

    // Initialize input fields for origin and destination
    $('#origin, #destination').on('input', function() {
        const origin = $('#origin').val();
        const destination = $('#destination').val();
        const mode = $('#shipping_mode').val();
        
        if (origin && destination && mode) {
            // Fetch rate details
            $.ajax({
                url: scrData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'scr_search_rates',
                    nonce: scrData.nonce,
                    origin: origin,
                    destination: destination,
                    shipping_mode: mode
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        currentRate = response.data[0];
                        updateTotalCost();
                    }
                }
            });
        }
    });

    // Mode selection handling
    $('.scr-mode-option').on('click', function() {
        // Remove selected class from all options and add to clicked one
        $('.scr-mode-option').removeClass('selected active');
        $(this).addClass('selected active');
        
        // Set the shipping mode value
        const mode = $(this).data('mode');
        $('#shipping_mode').val(mode);
        
        // Enable the next step button immediately after mode selection
        $('.scr-next-step').prop('disabled', false);
        
        // Update progress bar
        $('.scr-progress-bar-inner').css('width', '25%');
        
        // If origin and destination are already selected, fetch the rate
        const origin = $('#scr-origin').val();
        const destination = $('#scr-destination').val();
        if (origin && destination) {
            $.ajax({
                url: scrData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'scr_search_rates',
                    nonce: scrData.nonce,
                    origin: origin,
                    destination: destination,
                    shipping_mode: mode
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        currentRate = response.data[0];
                        updateTotalCost();
                    }
                }
            });
        }
    });

    // Form submission handling
    $('#scr-rate-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate required fields
        const origin = $('#scr-origin').val();
        const destination = $('#scr-destination').val();
        const shippingMode = $('.scr-mode-option.selected').data('mode');
        
        if (!origin || !destination || !shippingMode) {
            alert('Please fill in all required fields (Origin, Destination, and Shipping Mode)');
            return;
        }
        
        // Get all form data
        const formData = new FormData(this);
        formData.append('action', 'scr_save_rate');
        formData.append('nonce', scrData.nonce);
        formData.append('shipping_mode', $('.scr-mode-option.selected').data('mode'));
        
        // Disable submit button to prevent double submission
        const $submitButton = $(this).find('button[type="submit"]');
        $submitButton.prop('disabled', true);
        
        $.ajax({
            url: scrData.ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    alert('Rate saved successfully!');
                    location.reload();
                } else {
                    alert('Error saving rate: ' + (response.data || 'Unknown error'));
                    $submitButton.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                alert('Server error occurred: ' + error);
                $submitButton.prop('disabled', false);
            }
        });
    });


    // Location selection handling
    $('#origin, #destination').on('change', function() {
        const origin = $('#origin').val();
        const destination = $('#destination').val();
        const mode = $('#shipping_mode').val();
        
        if (origin && destination && mode) {
            // Fetch rate details
            $.ajax({
                url: scrData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'scr_search_rates',
                    nonce: scrData.nonce,
                    origin: origin,
                    destination: destination,
                    shipping_mode: mode
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        currentRate = response.data[0];
                        updateTotalCost();
                    }
                }
            });
        }
    });

    // Calculate volumetric weight (L x W x H / 6000 for air freight)
    function calculateVolumetricWeight(length, width, height, mode) {
        if (!length || !width || !height) return 0;
        const divisor = mode.toLowerCase() === 'air' ? 6000 : 1000;
        return (length * width * height) / divisor;
    }

    // Calculate cargo cost
    function calculateCargoCost(actualWeight, volumetricWeight) {
        if (!currentRate) return 0;

        const chargeableWeight = Math.max(actualWeight, volumetricWeight);
        const minimumCharge = parseFloat(currentRate.minimum_charge);
        const generalRate = parseFloat(currentRate.charge_general);
        const handlingFee = parseFloat(currentRate.handling_fee);

        let cost = chargeableWeight * generalRate;
        cost = Math.max(cost, minimumCharge);
        cost += handlingFee;

        return cost;
    }

    // Update cargo entry calculations
    function updateCargoEntry(index) {
        const entry = cargoEntries[index];
        const mode = $('#shipping_mode').val();

        entry.volumetricWeight = calculateVolumetricWeight(
            entry.length,
            entry.width,
            entry.height,
            mode
        );

        entry.cost = calculateCargoCost(entry.actualWeight, entry.volumetricWeight);

        // Update display
        const $cargoContainer = $('.cargo-entry').eq(index);
        $cargoContainer.find('.volumetric-weight').text(
            entry.volumetricWeight.toFixed(2) + ' ' + (currentRate ? currentRate.unit_measure : 'kg')
        );
        $cargoContainer.find('.item-freight-cost').text(
            (currentRate ? currentRate.currency : '') + ' ' + entry.cost.toFixed(2)
        );
    }

    // Update total cost
    function updateTotalCost() {
        if (!currentRate) return;

        const totalCost = cargoEntries.reduce((sum, entry) => sum + entry.cost, 0);
        $('.total-freight-cost').text(
            currentRate.currency + ' ' + totalCost.toFixed(2)
        );
    }

    // Handle dimension and weight inputs
    $(document).on('input', '.cargo-entry input', function() {
        const $cargoEntry = $(this).closest('.cargo-entry');
        const index = $('.cargo-entry').index($cargoEntry);

        cargoEntries[index] = {
            actualWeight: parseFloat($cargoEntry.find('[name="actual_weight[]"]').val()) || 0,
            length: parseFloat($cargoEntry.find('[name="length[]"]').val()) || 0,
            width: parseFloat($cargoEntry.find('[name="width[]"]').val()) || 0,
            height: parseFloat($cargoEntry.find('[name="height[]"]').val()) || 0,
            volumetricWeight: 0,
            cost: 0
        };

        updateCargoEntry(index);
        updateTotalCost();
    });

    // Add another cargo entry
    $('.add-another-cargo').on('click', function() {
        const newEntry = {
            actualWeight: 0,
            length: 0,
            width: 0,
            height: 0,
            volumetricWeight: 0,
            cost: 0
        };
        cargoEntries.push(newEntry);

        // Clone the first cargo entry form and clear its values
        const $newEntry = $('.cargo-entry').first().clone();
        $newEntry.find('input').val('');
        $newEntry.find('.volumetric-weight').text('0.00 kg');
        $newEntry.find('.item-freight-cost').text('0.00');

        // Add remove button if it's not the first entry
        if (!$newEntry.find('.remove-cargo').length) {
            $newEntry.append('<button type="button" class="remove-cargo">Remove</button>');
        }

        // Insert before the "Add another cargo" button
        $(this).before($newEntry);
    });

    // Remove cargo entry
    $(document).on('click', '.remove-cargo', function() {
        const $cargoEntry = $(this).closest('.cargo-entry');
        const index = $('.cargo-entry').index($cargoEntry);
        
        cargoEntries.splice(index, 1);
        $cargoEntry.remove();
        updateTotalCost();
    });

    // Step navigation
    $('.scr-next-step').on('click', function() {
        const currentStep = $('.scr-wizard-step.active');
        const nextStep = currentStep.next('.scr-wizard-step');
        
        if (nextStep.length) {
            currentStep.removeClass('active');
            nextStep.addClass('active');
            
            // Update progress bar
            const progress = (parseInt(nextStep.data('step')) / 4) * 100;
            $('.scr-progress-bar-inner').css('width', progress + '%');
        }
    });

    $('.scr-prev-step').on('click', function() {
        const currentStep = $('.scr-wizard-step.active');
        const prevStep = currentStep.prev('.scr-wizard-step');
        
        if (prevStep.length) {
            currentStep.removeClass('active');
            prevStep.addClass('active');
            
            // Update progress bar
            const progress = (parseInt(prevStep.data('step')) / 4) * 100;
            $('.scr-progress-bar-inner').css('width', progress + '%');
        }
    });
});