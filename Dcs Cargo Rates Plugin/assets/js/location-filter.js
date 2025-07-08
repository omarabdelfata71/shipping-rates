jQuery(document).ready(function($) {
    // Function to update dropdowns based on selected mode
    function updateLocationDropdowns() {
        const selectedMode = $('.scr-mode-option.active').data('mode');
        if (!selectedMode) return;

        const $originSelect = $('#origin');
        const $destinationSelect = $('#destination');
        
        // Store currently selected values
        const currentOrigin = $originSelect.val();
        const currentDestination = $destinationSelect.val();

        // Make AJAX request to get filtered locations
        $.ajax({
            url: scrAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'scr_get_filtered_locations',
                security: scrAjax.nonce,
                mode: selectedMode
            },
            success: function(response) {
                if (!response.success) {
                    console.error('Error fetching locations:', response.data);
                    return;
                }

                const locations = response.data;

                // Clear existing options
                $originSelect.empty();
                $destinationSelect.empty();

                // Add default options
                $originSelect.append('<option value="">Select Origin</option>');
                $destinationSelect.append('<option value="">Select Destination</option>');

                // Add filtered options for origin
                locations.origins.forEach(origin => {
                    $originSelect.append(`<option value="${origin}">${origin}</option>`);
                });

                // Add filtered options for destination
                locations.destinations.forEach(destination => {
                    $destinationSelect.append(`<option value="${destination}">${destination}</option>`);
                });

                // Restore previously selected values if they exist in the new options
                if (locations.origins.includes(currentOrigin)) {
                    $originSelect.val(currentOrigin);
                }
                if (locations.destinations.includes(currentDestination)) {
                    $destinationSelect.val(currentDestination);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }

    // Update dropdowns when mode is selected
    $('.scr-mode-option').on('click', function() {
        updateLocationDropdowns();
    });

    // Initial update of dropdowns
    updateLocationDropdowns();
}));