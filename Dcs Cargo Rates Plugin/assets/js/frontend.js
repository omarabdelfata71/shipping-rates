jQuery(document).ready(function($) {
    // Make the rates table responsive
    function makeTableResponsive() {
        $('.scr-rates-table-wrapper').each(function() {
            var $wrapper = $(this);
            var $table = $wrapper.find('.scr-rates-table');
            
            // Add horizontal scroll indicator if table is wider than wrapper
            if ($table.width() > $wrapper.width()) {
                if (!$wrapper.find('.scr-scroll-indicator').length) {
                    $wrapper.append('<div class="scr-scroll-indicator">Scroll horizontally to view more →</div>');
                }
            } else {
                $wrapper.find('.scr-scroll-indicator').remove();
            }
        });
    }

    // Initialize responsive features
    makeTableResponsive();

    // Update on window resize
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            makeTableResponsive();
        }, 250);
    });

    // Add print functionality
    $('.scr-print-rates').on('click', function(e) {
        e.preventDefault();
        window.print();
    });
});