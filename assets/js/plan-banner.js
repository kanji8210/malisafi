/**
 * Plan Banner Notification - Shows for users without active plans
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Check if user has plan (only if logged in)
        if (typeof malisafiPlanNotification !== 'undefined' && malisafiPlanNotification.checkPlan) {
            checkUserPlan();
        }
        
        // Dismiss banner
        $(document).on('click', '.malisafi-plan-banner-close', function(e) {
            e.preventDefault();
            $('.malisafi-plan-banner').slideUp(300, function() {
                $(this).remove();
            });
            
            // Set cookie to remember dismissal for 7 days
            document.cookie = 'malisafi_plan_banner_dismissed=1; max-age=' + (7 * 24 * 60 * 60) + '; path=/';
        });
    });
    
    function checkUserPlan() {
        $.ajax({
            url: malisafiPlanNotification.ajax_url,
            type: 'POST',
            data: {
                action: 'malisafi_check_user_plan'
            },
            success: function(response) {
                if (response.success && !response.data.has_plan) {
                    // User has no plan - check if banner was dismissed
                    if (!getCookie('malisafi_plan_banner_dismissed')) {
                        showPlanBanner();
                    }
                }
            }
        });
    }
    
    function showPlanBanner() {
        var banner = $('<div class="malisafi-plan-banner"></div>');
        var bannerHTML = '<div class="plan-banner-content">' +
            '<div class="plan-banner-icon"><span class="dashicons dashicons-warning"></span></div>' +
            '<div class="plan-banner-message">' +
            '<strong>' + (malisafiPlanNotification.i18n.title || 'Get Started with a Plan!') + '</strong>' +
            '<p>' + (malisafiPlanNotification.i18n.message || 'You don\'t have an active subscription. Choose a plan to unlock all features.') + '</p>' +
            '</div>' +
            '<div class="plan-banner-actions">' +
            '<a href="' + malisafiPlanNotification.pricing_url + '" class="button button-primary">' +
            (malisafiPlanNotification.i18n.button || 'View Plans') +
            '</a>' +
            '</div>' +
            '<button class="malisafi-plan-banner-close" aria-label="Close"><span class="dashicons dashicons-no-alt"></span></button>' +
            '</div>';
        
        banner.html(bannerHTML);
        banner.hide().prependTo('body').slideDown(400);
    }
    
    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    
})(jQuery);
