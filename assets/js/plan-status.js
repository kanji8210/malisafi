/**
 * Plan Status Frontend Scripts
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Add animations to plan status widget
        $('.malisafi-plan-status-widget').each(function() {
            $(this).css('opacity', '0').animate({ opacity: 1 }, 400);
        });
        
        // Smooth scroll to pricing section
        $('.plan-actions a[href*="#"]').on('click', function(e) {
            var target = $(this.hash);
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 600);
            }
        });
    });
    
})(jQuery);
