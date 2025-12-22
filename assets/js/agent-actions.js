/**
 * Agent Actions JavaScript
 * Handles rating and reporting agents
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // ==========================
        // Rate Agent
        // ==========================
        $('.agent-rating-form').on('submit', function(e) {
            e.preventDefault();
            
            if (!malisafiAgentAjax.isLoggedIn) {
                alert(malisafiAgentAjax.messages.loginRequired);
                return;
            }
            
            const $form = $(this);
            const $submitBtn = $form.find('.submit-rating');
            const originalText = $submitBtn.text();
            
            // Get form data
            const formData = {
                action: 'malisafi_rate_agent',
                nonce: malisafiAgentAjax.nonce,
                agent_id: $form.find('input[name="agent_id"]').val(),
                rating: $form.find('input[name="rating"]:checked').val(),
                review_title: $form.find('input[name="review_title"]').val(),
                review_text: $form.find('textarea[name="review_text"]').val(),
                property_id: $form.find('input[name="property_id"]').val()
            };
            
            // Disable button
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: malisafiAgentAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $form.before(`<div class="notice notice-success"><p>${response.data.message}</p></div>`);
                        
                        // Update rating display
                        if (response.data.average_rating) {
                            $('.agent-average-rating').text(response.data.average_rating);
                            $('.agent-total-ratings').text(response.data.total_ratings);
                        }
                        
                        // Reset form
                        $form[0].reset();
                        
                        // Hide form after 2 seconds
                        setTimeout(function() {
                            $('.rating-form-toggle').text('Update Your Rating');
                        }, 2000);
                    } else {
                        alert(response.data.message || malisafiAgentAjax.messages.error);
                    }
                },
                error: function() {
                    alert(malisafiAgentAjax.messages.error);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // ==========================
        // Star Rating Selection
        // ==========================
        $('.star-rating-input .star').on('click', function() {
            const rating = $(this).data('rating');
            const $container = $(this).closest('.star-rating-input');
            
            // Update radio button
            $container.find('input[value="' + rating + '"]').prop('checked', true);
            
            // Update visual stars
            $container.find('.star').each(function() {
                if ($(this).data('rating') <= rating) {
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
        });
        
        // ==========================
        // Report Agent
        // ==========================
        $('.report-agent-form').on('submit', function(e) {
            e.preventDefault();
            
            if (!malisafiAgentAjax.isLoggedIn) {
                alert(malisafiAgentAjax.messages.loginRequired);
                return;
            }
            
            const $form = $(this);
            const $submitBtn = $form.find('.submit-report');
            const originalText = $submitBtn.text();
            
            const formData = {
                action: 'malisafi_report_agent',
                nonce: malisafiAgentAjax.nonce,
                agent_id: $form.find('input[name="agent_id"]').val(),
                report_type: $form.find('select[name="report_type"]').val(),
                report_reason: $form.find('textarea[name="report_reason"]').val()
            };
            
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: malisafiAgentAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $form[0].reset();
                        $('.report-modal').fadeOut();
                    } else {
                        alert(response.data.message || malisafiAgentAjax.messages.error);
                    }
                },
                error: function() {
                    alert(malisafiAgentAjax.messages.error);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // ==========================
        // Mark Review Helpful
        // ==========================
        $('.helpful-btn').on('click', function(e) {
            e.preventDefault();
            
            if (!malisafiAgentAjax.isLoggedIn) {
                alert(malisafiAgentAjax.messages.loginRequired);
                return;
            }
            
            const $btn = $(this);
            const reviewId = $btn.data('review-id');
            const helpful = $btn.hasClass('helpful-yes');
            
            $.ajax({
                url: malisafiAgentAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_helpful_review',
                    nonce: malisafiAgentAjax.nonce,
                    review_id: reviewId,
                    helpful: helpful
                },
                success: function(response) {
                    if (response.success) {
                        // Update counts
                        $btn.closest('.review-helpful').find('.helpful-yes-count').text(response.data.helpful_count);
                        $btn.closest('.review-helpful').find('.helpful-no-count').text(response.data.not_helpful_count);
                        
                        // Disable both buttons for this review
                        $btn.closest('.review-helpful').find('.helpful-btn').prop('disabled', true).addClass('voted');
                    }
                }
            });
        });
        
        // ==========================
        // Toggle Rating Form
        // ==========================
        $('.rating-form-toggle').on('click', function() {
            $('.agent-rating-form').slideToggle();
        });
        
        // ==========================
        // Show Report Modal
        // ==========================
        $('.show-report-modal').on('click', function() {
            $('.report-modal').fadeIn();
        });
        
        $('.close-modal, .modal-overlay').on('click', function() {
            $('.report-modal').fadeOut();
        });
        
        // Prevent modal content click from closing
        $('.modal-content').on('click', function(e) {
            e.stopPropagation();
        });
        
    });

})(jQuery);
