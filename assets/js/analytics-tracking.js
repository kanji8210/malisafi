/**
 * Malisafi Analytics - Frontend Tracking
 * 
 * Tracks user interactions and engagement metrics
 * 
 * @package MalisafiMLS
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Track property view duration
    if (malisafiTracking.propertyId > 0) {
        let startTime = Date.now();
        let maxScroll = 0;
        let galleryViewed = false;
        let mapViewed = false;

        // Track scroll depth
        $(window).on('scroll', function() {
            const scrollTop = $(window).scrollTop();
            const docHeight = $(document).height() - $(window).height();
            const scrollPercent = Math.round((scrollTop / docHeight) * 100);
            
            if (scrollPercent > maxScroll) {
                maxScroll = scrollPercent;
            }
        });

        // Track gallery views
        $('.property-gallery, .property-slider, .gallery-item').on('click', function() {
            galleryViewed = true;
        });

        // Track map views
        $('.property-map, #property-map, .map-container').on('click', function() {
            mapViewed = true;
        });

        // Track contact button clicks
        $('.contact-agent, .send-inquiry, .phone-number, .email-agent').on('click', function() {
            $.post(malisafiTracking.ajaxurl, {
                action: 'malisafi_track_interaction',
                nonce: malisafiTracking.nonce,
                property_id: malisafiTracking.propertyId,
                interaction_type: 'contact_clicked',
                data: {
                    button: $(this).attr('class')
                }
            });

            // Update view record
            const currentDuration = Math.round((Date.now() - startTime) / 1000);
            $.post(malisafiTracking.ajaxurl, {
                action: 'malisafi_track_view_duration',
                nonce: malisafiTracking.nonce,
                property_id: malisafiTracking.propertyId,
                duration: currentDuration,
                scroll_depth: maxScroll,
                gallery_viewed: galleryViewed,
                map_viewed: mapViewed
            });
        });

        // Track phone clicks
        $('a[href^="tel:"]').on('click', function() {
            $.post(malisafiTracking.ajaxurl, {
                action: 'malisafi_track_interaction',
                nonce: malisafiTracking.nonce,
                property_id: malisafiTracking.propertyId,
                interaction_type: 'phone_click'
            });
        });

        // Track email clicks
        $('a[href^="mailto:"]').on('click', function() {
            $.post(malisafiTracking.ajaxurl, {
                action: 'malisafi_track_interaction',
                nonce: malisafiTracking.nonce,
                property_id: malisafiTracking.propertyId,
                interaction_type: 'email_click'
            });
        });

        // Track WhatsApp clicks
        $('a[href*="wa.me"], a[href*="whatsapp"]').on('click', function() {
            $.post(malisafiTracking.ajaxurl, {
                action: 'malisafi_track_interaction',
                nonce: malisafiTracking.nonce,
                property_id: malisafiTracking.propertyId,
                interaction_type: 'whatsapp_click'
            });
        });

        // Track share buttons
        $('.share-button, .social-share').on('click', function() {
            const shareMethod = $(this).data('network') || $(this).attr('class').match(/facebook|twitter|whatsapp|email/)?.[0] || 'unknown';
            
            $.post(malisafiTracking.ajaxurl, {
                action: 'malisafi_track_interaction',
                nonce: malisafiTracking.nonce,
                property_id: malisafiTracking.propertyId,
                interaction_type: 'share_social',
                data: {
                    method: shareMethod
                }
            });
        });

        // Send analytics before page unload
        $(window).on('beforeunload', function() {
            const duration = Math.round((Date.now() - startTime) / 1000);
            
            // Use sendBeacon for reliable tracking
            if (navigator.sendBeacon) {
                const formData = new FormData();
                formData.append('action', 'malisafi_track_view_duration');
                formData.append('nonce', malisafiTracking.nonce);
                formData.append('property_id', malisafiTracking.propertyId);
                formData.append('duration', duration);
                formData.append('scroll_depth', maxScroll);
                formData.append('gallery_viewed', galleryViewed ? 1 : 0);
                formData.append('map_viewed', mapViewed ? 1 : 0);
                
                navigator.sendBeacon(malisafiTracking.ajaxurl, formData);
            } else {
                // Fallback to synchronous AJAX
                $.ajax({
                    url: malisafiTracking.ajaxurl,
                    type: 'POST',
                    async: false,
                    data: {
                        action: 'malisafi_track_view_duration',
                        nonce: malisafiTracking.nonce,
                        property_id: malisafiTracking.propertyId,
                        duration: duration,
                        scroll_depth: maxScroll,
                        gallery_viewed: galleryViewed,
                        map_viewed: mapViewed
                    }
                });
            }
        });

        // Periodic updates every 30 seconds
        setInterval(function() {
            const currentDuration = Math.round((Date.now() - startTime) / 1000);
            
            $.post(malisafiTracking.ajaxurl, {
                action: 'malisafi_track_view_duration',
                nonce: malisafiTracking.nonce,
                property_id: malisafiTracking.propertyId,
                duration: currentDuration,
                scroll_depth: maxScroll,
                gallery_viewed: galleryViewed,
                map_viewed: mapViewed
            });
        }, 30000);
    }

    // Track form submission funnel
    $('.malisafi-property-submit-form').each(function() {
        const $form = $(this);
        let formStartTime = Date.now();

        // Track form loaded
        $.post(malisafiTracking.ajaxurl, {
            action: 'malisafi_track_funnel',
            nonce: malisafiTracking.nonce,
            section: 'form_loaded',
            field: 'initial',
            has_value: 1
        });

        // Track section interactions
        $('.form-section').on('blur', ':input', function() {
            const $section = $(this).closest('.form-section');
            const sectionTitle = $section.find('.form-section-title, h3, h2').first().text().trim();
            const fieldName = $(this).attr('name') || $(this).attr('id');
            const hasValue = $(this).val() ? 1 : 0;

            $.post(malisafiTracking.ajaxurl, {
                action: 'malisafi_track_funnel',
                nonce: malisafiTracking.nonce,
                section: sectionTitle,
                field: fieldName,
                has_value: hasValue
            });
        });

        // Track form submission attempt
        $form.on('submit', function() {
            const timeSpent = Math.round((Date.now() - formStartTime) / 1000);
            
            $.post(malisafiTracking.ajaxurl, {
                action: 'malisafi_track_funnel',
                nonce: malisafiTracking.nonce,
                section: 'submit_attempt',
                field: 'form_submit',
                has_value: 1,
                time_spent: timeSpent
            });
        });
    });

    // Track favorite button
    $(document).on('click', '.favorite-button, .add-to-favorites', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const propertyId = $button.data('property-id') || malisafiTracking.propertyId;
        
        $.post(malisafiTracking.ajaxurl, {
            action: 'malisafi_track_interaction',
            nonce: malisafiTracking.nonce,
            property_id: propertyId,
            interaction_type: 'favorite'
        });
        
        // Toggle button state
        $button.toggleClass('favorited');
    });

})(jQuery);
