jQuery(document).ready(function($) {
    // Add New Rate Form Submission
    $('#scr-new-rate-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        
        $submitButton.prop('disabled', true);
        
        var destination = $('#destination').val();
        if (destination === 'new') {
            destination = $('#new_destination').val();
        }
        
        var formData = {
            action: 'scr_save_rate',
            nonce: scrAjax.nonce,
            origin: $('#origin').val() === 'new' ? $('#new_origin').val() : $('#origin').val(),
            destination: destination,
            shipping_mode: $('#shipping_mode').val(),
            unit_measure: $('#unit_measure').val(),
            currency: $('#currency').val(),
            charge_general: $('#charge_general').val(),
            handling_fee: $('#handling_fee').val(),
            minimum_charge: $('#minimum_charge').val()
        };
        
        $.post(scrAjax.ajaxurl, formData, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error saving rate: ' + response.data);
            }
        }).fail(function() {
            alert('Server error occurred');
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // Search Form Submission
    $('#scr-search-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        
        $submitButton.prop('disabled', true);
        
        var formData = {
            action: 'scr_search_rates',
            nonce: scrAjax.nonce,
            origin: $('#search_origin').val(),
            destination: $('#search_destination').val(),
            shipping_mode: $('#search_mode').val()
        };
        
        $.post(scrAjax.ajaxurl, formData, function(response) {
            if (response.success) {
                updateRatesTable(response.data);
            } else {
                alert('Error searching rates: ' + response.data);
            }
        }).fail(function() {
            alert('Server error occurred');
        }).always(function() {
            $submitButton.prop('disabled', false);
        });
    });

    // Clear Search Form
    $('#scr-search-form button[type="reset"]').on('click', function() {
        setTimeout(function() {
            $('#scr-search-form').submit();
        }, 10);
    });

    // Inline Editing
    $('.scr-rates-table').on('click', 'td.editable', function() {
        var $td = $(this);
        if ($td.hasClass('editing')) return;

        var value = $td.text().trim();
        var field = $td.data('field');
        var $row = $td.closest('tr');
        
        // Create input field based on the column type
        var $input;
        if (field === 'shipping_mode') {
            $input = $td.find('select').clone();
        } else if (field === 'unit_measure') {
            $input = $td.find('select').clone();
        } else if (field === 'currency') {
            $input = $td.find('select').clone();
        } else if (field.includes('charge') || field.includes('fee') || field === 'minimum_charge') {
            $input = $('<input type="number" step="0.01" />');
            $input.val(parseFloat(value.replace(/[^0-9.-]+/g, '')));
        } else {
            $input = $('<input type="text" />');
            $input.val(value);
        }

        var $originalContent = $td.html();
        
        $td.html($input);
        $td.addClass('editing');
        $row.find('.scr-save-row').show();
        
        $input.focus();
        
        // Handle input blur
        $input.on('blur', function() {
            setTimeout(function() {
                if (!$td.find('input:focus, select:focus').length) {
                    $td.html($originalContent);
                    $td.removeClass('editing');
                }
            }, 100);
        });
    });

    // Save Row Changes
    $('.scr-rates-table').on('click', '.scr-save-row', function() {
        var $row = $(this).closest('tr');
        var id = $row.data('id');
        
        var rowData = {
            action: 'scr_update_rate',
            nonce: scrAjax.nonce,
            id: id,
            origin: getFieldValue($row, 'origin'),
            destination: getFieldValue($row, 'destination'),
            shipping_mode: getFieldValue($row, 'shipping_mode'),
            unit_measure: getFieldValue($row, 'unit_measure'),
            currency: getFieldValue($row, 'currency'),
            charge_general: getFieldValue($row, 'charge_general'),
            handling_fee: getFieldValue($row, 'handling_fee'),
            minimum_charge: getFieldValue($row, 'minimum_charge'),
            created_at: $row.find('td:nth-last-child(3)').text(),
            updated_at: $row.find('td:nth-last-child(2)').text()
        };
        
        $.post(scrAjax.ajaxurl, rowData, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error updating rate: ' + response.data);
            }
        }).fail(function() {
            alert('Server error occurred');
        });
    });

    // Delete Row
    $('.scr-rates-table').on('click', '.scr-delete-row', function() {
        if (!confirm('Are you sure you want to delete this rate?')) return;
        
        var $row = $(this).closest('tr');
        var id = $row.data('id');
        
        $.post(scrAjax.ajaxurl, {
            action: 'scr_delete_rate',
            nonce: scrAjax.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                $row.fadeOut(function() {
                    $(this).remove();
                });
            } else {
                alert('Error deleting rate: ' + response.data);
            }
        }).fail(function() {
            alert('Server error occurred');
        });
    });

    // Helper function to get field value
    function getFieldValue($row, field) {
        var $td = $row.find('td[data-field="' + field + '"]');
        var $input = $td.find('input');
        var $select = $td.find('select');
        
        if ($input.length) {
            return $input.val();
        } else if ($select.length) {
            return $select.val();
        } else {
            var value = $td.text().trim();
            if (field.includes('charge') || field.includes('fee') || field === 'minimum_charge') {
                return parseFloat(value.replace(/[^0-9.-]+/g, ''));
            }
            return value;
        }
    }

    // Helper function to update rates table
    function updateRatesTable(rates) {
        var $tbody = $('#scr-rates-tbody');
        $tbody.empty();
        
        rates.forEach(function(rate) {
            var $row = $('<tr>');
            $row.attr('data-id', rate.id);
            
            $row.append('<td class="editable" data-field="origin">' + escapeHtml(rate.origin) + '</td>');
            $row.append('<td class="editable" data-field="destination">' + escapeHtml(rate.destination) + '</td>');
            
            var modeSelect = '<td class="editable" data-field="shipping_mode"><select>' +
                '<option value="Air"' + (rate.shipping_mode === 'Air' ? ' selected' : '') + '>Air</option>' +
                '<option value="Sea"' + (rate.shipping_mode === 'Sea' ? ' selected' : '') + '>Sea</option>' +
                '</select></td>';
            $row.append(modeSelect);
            
            var unitSelect = '<td class="editable" data-field="unit_measure"><select>' +
                '<option value="kg"' + (rate.unit_measure === 'kg' ? ' selected' : '') + '>KG</option>' +
                '<option value="cbm"' + (rate.unit_measure === 'cbm' ? ' selected' : '') + '>CBM</option>' +
                '</select></td>';
            $row.append(unitSelect);
            
            var currencySelect = '<td class="editable" data-field="currency"><select>' +
                '<option value="USD"' + (rate.currency === 'USD' ? ' selected' : '') + '>USD</option>' +
                '<option value="GBP"' + (rate.currency === 'GBP' ? ' selected' : '') + '>GBP</option>' +
                '</select></td>';
            $row.append(currencySelect);
            
            $row.append('<td class="editable" data-field="charge_general">' + formatNumber(rate.charge_general) + '</td>');
            // Volume rate column removed
            $row.append('<td class="editable" data-field="handling_fee">' + formatNumber(rate.handling_fee) + '</td>');
            $row.append('<td class="editable" data-field="minimum_charge">' + formatNumber(rate.minimum_charge) + '</td>');
            $row.append('<td>' + (rate.created_at ? new Date(rate.created_at).toLocaleString() : '') + '</td>');
            $row.append('<td>' + (rate.updated_at ? new Date(rate.updated_at).toLocaleString() : '') + '</td>');
            
            var actions = '<td>' +
                '<button class="button button-small scr-save-row" style="display:none;">Save</button>' +
                '<button class="button button-small button-link-delete scr-delete-row">Delete</button>' +
                '</td>';
            $row.append(actions);
            
            $tbody.append($row);
        });
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Helper function to format numbers
    function formatNumber(number) {
        return parseFloat(number).toFixed(2);
    }
});

// Handle origin and destination select changes - moved to a single document.ready handler


// Handle origin and destination select changes
jQuery(document).ready(function($) {
    $('#origin').on('change', function() {
        if ($(this).val() === 'new') {
            $('#new_origin').show();
        } else {
            $('#new_origin').hide();
        }
    });

    $('#destination').on('change', function() {
        if ($(this).val() === 'new') {
            $('#new_destination').show();
        } else {
            $('#new_destination').hide();
        }
    });
});