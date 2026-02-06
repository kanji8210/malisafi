/**
 * Single Property JavaScript
 * Handles favorites, reporting, contact display, and gallery
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Gallery Navigation
        var $thumbnails = $('.gallery-thumbnails .thumbnail');
        var totalImages = $thumbnails.length;
        
        function changeImage(index) {
            if (index < 0) index = totalImages - 1;
            if (index >= totalImages) index = 0;
            
            var $thumbnail = $thumbnails.eq(index);
            var imageUrl = $thumbnail.data('image');
            
            var $img = $('.gallery-main .main-image');
            $img.stop(true, true).fadeOut(150, function(){
                $img.attr('src', imageUrl).attr('data-current-index', index).fadeIn(150);
            });
            $thumbnails.removeClass('active').attr('aria-selected', 'false');
            $thumbnail.addClass('active').attr('aria-selected', 'true');
            
            // Update counter
            $('.gallery-counter .current').text(index + 1);
            
            // Scroll thumbnail into view
            if ($thumbnail.length) {
                var container = $('.gallery-thumbnails');
                var scrollLeft = $thumbnail.position().left + container.scrollLeft() - (container.width() / 2) + ($thumbnail.width() / 2);
                container.animate({scrollLeft: scrollLeft}, 300);
            }
        }
        
        // Thumbnail strip scroll buttons
        $('.thumbs-prev').on('click', function(){
            var $c = $('.gallery-thumbnails');
            $c.animate({ scrollLeft: Math.max(0, $c.scrollLeft() - ($c.width() || 300)) }, 250);
        });
        $('.thumbs-next').on('click', function(){
            var $c = $('.gallery-thumbnails');
            $c.animate({ scrollLeft: $c.scrollLeft() + ($c.width() || 300) }, 250);
        });

        // Gallery Thumbnails Click
        $thumbnails.on('click', function() {
            var index = $(this).data('index');
            changeImage(index);
        });
        
        // Previous button
        $('.gallery-nav-prev').on('click', function() {
            var currentIndex = parseInt($('.gallery-main .main-image').attr('data-current-index')) || 0;
            changeImage(currentIndex - 1);
        });
        
        // Next button
        $('.gallery-nav-next').on('click', function() {
            var currentIndex = parseInt($('.gallery-main .main-image').attr('data-current-index')) || 0;
            changeImage(currentIndex + 1);
        });
        
        // Keyboard and focus navigation
        $(document).on('keydown', function(e) {
            if ($('.property-gallery').length) {
                var currentIndex = parseInt($('.gallery-main .main-image').attr('data-current-index')) || 0;
                if (e.keyCode === 37) { // Left arrow
                    changeImage(currentIndex - 1);
                } else if (e.keyCode === 39) { // Right arrow
                    changeImage(currentIndex + 1);
                } else if (e.keyCode === 36) { // Home
                    changeImage(0);
                } else if (e.keyCode === 35) { // End
                    changeImage(totalImages - 1);
                }
            }
        });
        // Allow Enter/Space on thumbnails
        $thumbnails.attr('tabindex','0').on('keydown', function(e){
            if (e.keyCode === 13 || e.keyCode === 32) { // Enter or Space
                e.preventDefault();
                var index = $(this).data('index');
                changeImage(index);
            }
        });
        
        // Favorite Button
        $('.favorite-button').on('click', function(e) {
            e.preventDefault();
            var $button = $(this);
            var propertyId = $button.data('property-id');
            
            if (!malisafiProperty.user_logged_in) {
                showNotification('Register to add this property to your favorites.', 'info');
                if (window.confirm('Register now?')) {
                    window.location.href = malisafiProperty.register_client_url || '';
                }
                return;
            }
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: malisafiProperty.ajax_url,
                type: 'POST',
                data: {
                    action: 'malisafi_toggle_favorite',
                    property_id: propertyId,
                    nonce: malisafiProperty.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.favorited) {
                            $button.addClass('favorited');
                            $button.find('.action-text').text('Favorited');
                            showNotification('Added to favorites!', 'success');
                        } else {
                            $button.removeClass('favorited');
                            $button.find('.action-text').text('Favorite');
                            showNotification('Removed from favorites', 'info');
                        }
                    } else {
                        showNotification(response.data.message || 'Failed to update favorites', 'error');
                    }
                },
                error: function() {
                    showNotification('An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
        
        // Report Button - Show Modal
        $('.report-button').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#report-modal').addClass('active').css('display', 'flex');
            $('body').css('overflow', 'hidden');
        });
        
        // Close Modal Function
        function closeModal() {
            $('.malisafi-modal').removeClass('active').css('display', 'none');
            $('body').css('overflow', '');
        }
        
        // Modal Close Button (X)
        $('.modal-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeModal();
        });
        
        // Close modal on outside click (backdrop)
        $('.malisafi-modal').on('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Prevent clicks inside modal content from closing
        $('.modal-content').on('click', function(e) {
            e.stopPropagation();
        });
        
        // Close modal with Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                if ($('.malisafi-modal').hasClass('active')) {
                    closeModal();
                }
            }
        });
        
        // Report Form Submit
        $('#report-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var formData = $form.serialize();
            
            $submitButton.prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: malisafiProperty.ajax_url,
                type: 'POST',
                data: formData + '&action=malisafi_report_property&nonce=' + malisafiProperty.nonce,
                success: function(response) {
                    if (response.success) {
                        showNotification('Report submitted successfully. Thank you!', 'success');
                        closeModal();
                        $form[0].reset();
                    } else {
                        showNotification(response.data.message || 'Failed to submit report', 'error');
                    }
                },
                error: function() {
                    showNotification('An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text('Submit Report');
                }
            });
        });
        
        // Contact Agent Button - Toggle Contact Details
        $('.contact-agent-button').on('click', function() {
            var $button = $(this);
            var $details = $('.agent-contact-details');
            
            if ($details.hasClass('hidden')) {
                $details.removeClass('hidden').slideDown(300);
                $button.html('<span class="dashicons dashicons-phone"></span> Hide Contact Details');
            } else {
                $details.slideUp(300, function() {
                    $(this).addClass('hidden');
                });
                $button.html('<span class="dashicons dashicons-phone"></span> Show Contact Details');
            }
        });
        
        // Share Button
        $('.share-button').on('click', function(e) {
            e.preventDefault();
            var url = window.location.href;
            var title = document.title;
            
            // Try native share API first
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                }).catch(function(error) {
                    // Sharing cancelled or failed
                });
            } else {
                // Fallback: copy to clipboard
                copyToClipboard(url);
                showNotification('Link copied to clipboard!', 'success');
            }
        });
        
        // Quick Contact Form Submit
        $('.quick-contact-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('.submit-button');
            var formData = $form.serialize();
            
            $submitButton.prop('disabled', true).text('Sending...');
            
            $.ajax({
                url: malisafiProperty.ajax_url,
                type: 'POST',
                data: formData + '&action=malisafi_contact_agent&nonce=' + malisafiProperty.nonce,
                success: function(response) {
                    if (response.success) {
                        showNotification('Message sent successfully!', 'success');
                        $form[0].reset();
                    } else {
                        showNotification(response.data.message || 'Failed to send message', 'error');
                    }
                },
                error: function() {
                    showNotification('An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text('Send Message');
                }
            });
        });
        
        // Helper function to show notifications
        function showNotification(message, type) {
            var bgColor = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8';
            
            var $notification = $('<div class="malisafi-notification">')
                .css({
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    background: bgColor,
                    color: '#fff',
                    padding: '15px 20px',
                    borderRadius: '8px',
                    boxShadow: '0 4px 12px rgba(0,0,0,0.2)',
                    zIndex: 10000,
                    maxWidth: '300px',
                    fontWeight: '500'
                })
                .text(message)
                .appendTo('body')
                .hide()
                .fadeIn(300);
            
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        }
        
        // Helper function to copy to clipboard
        function copyToClipboard(text) {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
        }
        
    });
    
})(jQuery);
