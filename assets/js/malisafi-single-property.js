/**
 * Malisafi MLS - Single Property Page JavaScript
 * Handles gallery navigation, favorite toggle, agent rating, modals, etc.
 */

console.log('Malisafi Single Property JS file loaded');

(function($) {
    'use strict';

    // DOM Ready
    $(document).ready(function() {
        console.log('Malisafi Single Property JS loaded');
        
        // Ensure no modals are open by default
        $('.malisafi-modal').removeClass('open').hide();
        $('body').removeClass('modal-open');
        
        // ===== GALLERY NAVIGATION =====
        var currentImageIndex = 0;
        var totalImages = 0;
        
        // Initialize components after a short delay to ensure DOM is ready
        setTimeout(function() {
            console.log('Initializing gallery...');
            initGallery();
            console.log('Gallery initialization complete');
        }, 100);
        
        // Helper: Safely extract a user-facing message from AJAX responses
        function getResponseMessage(response, fallback) {
            fallback = fallback || 'An error occurred. Please try again.';
            if (!response) {
                return fallback;
            }
            if (typeof response === 'string') {
                return response;
            }
            if (response.data) {
                if (typeof response.data === 'string') {
                    return response.data;
                }
                if (response.data.message) {
                    return response.data.message;
                }
                try {
                    return JSON.stringify(response.data);
                } catch (e) {
                    return fallback;
                }
            }
            return fallback;
        }
        // Initialize gallery
        function initGallery() {
            var $thumbnails = $('.gallery-thumbnails .thumbnail');
            var $mainImage = $('.main-image');
            totalImages = $thumbnails.length;
            
            console.log('Gallery init - thumbnails found:', totalImages);
            console.log('Gallery init - main image found:', $mainImage.length);
            
            if (totalImages > 0) {
                // Update counter
                $('.gallery-counter .current').text(currentImageIndex + 1);
                $('.gallery-counter .total').text(totalImages);
                
                // Thumbnail click
                $thumbnails.on('click', function() {
                    var index = $(this).data('index');
                    console.log('Thumbnail clicked, index:', index);
                    navigateToImage(index);
                });
                
                // Previous button
                $('.gallery-nav-prev').on('click', function() {
                    var prevIndex = (currentImageIndex - 1 + totalImages) % totalImages;
                    navigateToImage(prevIndex);
                });
                
                // Next button
                $('.gallery-nav-next').on('click', function() {
                    var nextIndex = (currentImageIndex + 1) % totalImages;
                    navigateToImage(nextIndex);
                });
                
                // Keyboard navigation
                $(document).on('keydown', function(e) {
                    if ($('.property-gallery').is(':visible')) {
                        if (e.key === 'ArrowLeft') {
                            var prevIndex = (currentImageIndex - 1 + totalImages) % totalImages;
                            navigateToImage(prevIndex);
                        } else if (e.key === 'ArrowRight') {
                            var nextIndex = (currentImageIndex + 1) % totalImages;
                            navigateToImage(nextIndex);
                        }
                    }
                });
            } else {
                console.log('No thumbnails found - gallery not initialized');
            }
        }
        
        function navigateToImage(index) {
            var $thumbnails = $('.gallery-thumbnails .thumbnail');
            var $mainImage = $('.main-image');
            
            if (index >= 0 && index < totalImages) {
                // Update active thumbnail
                $thumbnails.removeClass('active');
                $thumbnails.eq(index).addClass('active');
                
                // Update main image with fade effect
                var newImageUrl = $thumbnails.eq(index).data('image');
                $mainImage.fadeOut(200, function() {
                    $(this).attr('src', newImageUrl);
                    $(this).data('current-index', index);
                    $(this).fadeIn(200);
                });
                
                // Update counter
                currentImageIndex = index;
                $('.gallery-counter .current').text(index + 1);
            }
        }
        
        // ===== FAVORITE BUTTON =====
        $('.favorite-button').on('click', function(e) {
            e.preventDefault();
            var $button = $(this);
            var propertyId = $button.data('property-id');
            
            // Toggle visual state immediately for better UX
            $button.toggleClass('favorited');
            var isFavorited = $button.hasClass('favorited');
            
            // Update button text
            var $actionText = $button.find('.action-text');
            if ($actionText.length) {
                $actionText.text(isFavorited ? 'Favorited' : 'Favorite');
            }
            
            // Send AJAX request
            $.ajax({
                url: malisafi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'malisafi_toggle_favorite',
                    property_id: propertyId,
                    nonce: malisafi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Success - button state already updated
                        // Favorite updated successfully
                    } else {
                        // Revert if failed
                        $button.toggleClass('favorited');
                        if ($actionText.length) {
                            $actionText.text($button.hasClass('favorited') ? 'Favorited' : 'Favorite');
                        }
                        alert('Failed to update favorite. Please try again.');
                    }
                },
                error: function() {
                    // Revert on error
                    $button.toggleClass('favorited');
                    if ($actionText.length) {
                        $actionText.text($button.hasClass('favorited') ? 'Favorited' : 'Favorite');
                    }
                    alert('Network error. Please try again.');
                }
            });
        });
        
        // ===== SHARE BUTTON =====
        $('.share-button').on('click', function() {
            var propertyTitle = $('.property-title').text();
            var propertyUrl = window.location.href;
            
            if (navigator.share) {
                // Web Share API
                navigator.share({
                    title: propertyTitle,
                    url: propertyUrl
                }).catch(console.error);
            } else {
                // Fallback: copy to clipboard
                var shareText = propertyTitle + '\n' + propertyUrl;
                
                navigator.clipboard.writeText(shareText).then(function() {
                    alert('Link copied to clipboard!');
                }).catch(function() {
                    // Fallback for older browsers
                    var textArea = document.createElement('textarea');
                    textArea.value = shareText;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('Link copied to clipboard!');
                });
            }
        });
        
        // ===== REPORT MODAL =====
        var $reportModal = $('#report-modal');
        var $reportForm = $('#report-form');

        // Open report modal
        $('.report-button').on('click', function(e) {
            e.preventDefault();
            
            // Check if user is logged in
            if (!malisafi_ajax.user_logged_in) {
                alert('You must be logged in to report a property. Please log in first.');
                window.location.href = malisafi_ajax.login_url;
                return;
            }
            
            var propertyId = $(this).data('property-id');
            $reportForm.find('input[name="property_id"]').val(propertyId);
            $reportModal.addClass('open');
            $('body').addClass('modal-open');
        });
        
        // Close modal - Multiple ways to ensure it works
        $('.modal-close, .button-secondary.modal-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $modal = $(this).closest('.malisafi-modal');
            
            // Force close with multiple methods
            $modal.removeClass('open').hide();
            $('body').removeClass('modal-open');
            
            // Double-check after a short delay
            setTimeout(function() {
                if ($modal.hasClass('open') || $modal.is(':visible')) {
                    $modal.removeClass('open').hide();
                    $('body').removeClass('modal-open');
                }
            }, 100);
            
            return false;
        });

        // Also bind to the modal header close button specifically
        $('.malisafi-modal .modal-header .modal-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $modal = $(this).closest('.malisafi-modal');
            $modal.removeClass('open').hide();
            $('body').removeClass('modal-open');
            return false;
        });
        
        // Submit report form
        $reportForm.on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: malisafi_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=malisafi_report_property&nonce=' + malisafi_ajax.report_nonce,
                beforeSend: function() {
                    $reportForm.find('.button-primary').prop('disabled', true).text('Submitting...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Thank you for your report. We will review it shortly.');
                        $reportModal.removeClass('open');
                        $('body').removeClass('modal-open');
                        $reportForm[0].reset();
                    } else {
                        alert(getResponseMessage(response, 'Failed to submit report. Please try again.'));
                    }
                },
                error: function() {
                    // Log detailed XHR info for debugging
                    try {
                        console.error('Malisafi: Inquiry AJAX network error', arguments);
                        var xhr = arguments[0];
                        var textStatus = arguments[1];
                        var errorThrown = arguments[2];
                        console.error('Malisafi AJAX Error Details:', {
                            status: xhr && xhr.status,
                            statusText: xhr && xhr.statusText,
                            responseText: xhr && xhr.responseText,
                            textStatus: textStatus,
                            errorThrown: errorThrown
                        });
                        alert('Network error. See console (DevTools) for details.');
                    } catch (e) {
                        alert('Network error. Please try again.');
                    }
                },
                complete: function() {
                    $reportForm.find('.button-primary').prop('disabled', false).text('Submit Report');
                }
            });
        });
        
        // ===== INQUIRY MODAL =====
        var $inquiryModal = $('#inquiry-modal');
        var $inquiryForm = $('#inquiry-form');

        // Open inquiry modal - ONLY when button is clicked
        $('.inquiry-button').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var propertyId = $(this).data('property-id');

            // Ensure no other modals are open
            $('.malisafi-modal').removeClass('open').hide();
            $('body').removeClass('modal-open');

            $inquiryForm.find('input[name="property_id"]').val(propertyId);
            $inquiryModal.addClass('open');
            $('body').addClass('modal-open');
            return false;
        });
        
        // Submit inquiry form
        $inquiryForm.on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: malisafi_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=malisafi_send_inquiry&nonce=' + malisafi_ajax.nonce,
                beforeSend: function() {
                    $inquiryForm.find('.button-primary').prop('disabled', true).text('Sending...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Thank you! Your inquiry has been sent successfully.');
                        $inquiryModal.removeClass('open');
                        $('body').removeClass('modal-open');
                        $inquiryForm[0].reset();
                    } else {
                        alert(getResponseMessage(response, 'Failed to send inquiry. Please try again.'));
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                },
                complete: function() {
                    $inquiryForm.find('.button-primary').prop('disabled', false).text('Send Inquiry');
                }
            });
        });
        
        // ===== AGENT RATING MODAL =====
        var $rateAgentModal = $('#rate-agent-modal');
        var $rateAgentForm = $('#rate-agent-form');
        
        // Open rate agent modal
        $('.rate-agent-button').on('click', function() {
            $rateAgentModal.addClass('open');
            $('body').addClass('modal-open');
        });
        
        // Submit agent rating form
        if ($rateAgentForm.length) {
            $rateAgentForm.on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                
                $.ajax({
                    url: malisafi_ajax.ajax_url,
                    type: 'POST',
                    data: formData + '&action=malisafi_rate_agent&nonce=' + malisafi_ajax.nonce,
                    beforeSend: function() {
                        $rateAgentForm.find('.button-primary').prop('disabled', true).text('Envoi en cours...');
                    },
                    success: function(response) {
                        if (response.success) {
                            $rateAgentForm.hide();
                            $('#rate-agent-success').show().text('Merci pour votre avis !');
                            
                            // Close modal after 2 seconds
                            setTimeout(function() {
                                $rateAgentModal.removeClass('open');
                                $('body').removeClass('modal-open');
                            }, 2000);
                        } else {
                            alert(response.data?.message || 'Erreur lors de l\'envoi.');
                        }
                    },
                    error: function() {
                        alert('Erreur réseau. Veuillez réessayer.');
                    },
                    complete: function() {
                        $rateAgentForm.find('.button-primary').prop('disabled', false).text('Envoyer');
                    }
                });
            });
        }
        
        // ===== AGENT CONTACT TOGGLE =====
        $('.contact-agent-button').on('click', function() {
            var $contactDetails = $(this).siblings('.agent-contact-details');
            var $contactForm = $(this).siblings('.contact-form');
            
            if ($contactDetails.hasClass('hidden')) {
                $contactDetails.removeClass('hidden');
                if ($contactForm.length) {
                    $contactForm.removeClass('hidden');
                }
                $(this).html('<span class="dashicons dashicons-phone"></span> Hide Contact Details');
            } else {
                $contactDetails.addClass('hidden');
                if ($contactForm.length) {
                    $contactForm.addClass('hidden');
                }
                $(this).html('<span class="dashicons dashicons-phone"></span> Show Contact Details');
            }
        });
        
        // ===== VIEW AGENT DETAILS MODAL =====
        var $viewAgentModal = $('#view-agent-modal');
        
        // Open view agent modal (if you have a button for this)
        $('.view-agent-details-button').on('click', function() {
            $viewAgentModal.addClass('open');
            $('body').addClass('modal-open');
        });
        
        // ===== QUICK CONTACT FORM =====
        $('.quick-contact-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = $form.serialize();
            
            $.ajax({
                url: malisafi_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=malisafi_contact_agent&nonce=' + malisafi_ajax.nonce,
                beforeSend: function() {
                    $form.find('.submit-button').prop('disabled', true).text('Sending...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Message sent successfully!');
                        $form[0].reset();
                    } else {
                        alert(getResponseMessage(response, 'Failed to send message. Please try again.'));
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                },
                complete: function() {
                    $form.find('.submit-button').prop('disabled', false).text('Send Message');
                }
            });
        });
        
        // ===== MODAL CLICK OUTSIDE =====
        $(document).on('click', function(e) {
            if ($(e.target).hasClass('malisafi-modal')) {
                $(e.target).removeClass('open').hide();
                $('body').removeClass('modal-open');
            }
        });

        // Prevent modal content clicks from closing modal
        $(document).on('click', '.modal-content', function(e) {
            e.stopPropagation();
        });

        // ===== ESC KEY TO CLOSE MODAL =====
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.malisafi-modal.open').removeClass('open').hide();
                $('body').removeClass('modal-open');
            }
        });

        // Add a global function to force close modals (for emergency use)
        window.forceCloseModals = function() {
            $('.malisafi-modal').removeClass('open').hide();
            $('body').removeClass('modal-open');
        };
        
    });
    
    // ===== AJAX HANDLERS =====
    
    // Toggle favorite
    $(document).on('malisafi_toggle_favorite', function(e, propertyId, callback) {
        $.ajax({
            url: malisafi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'malisafi_toggle_favorite',
                property_id: propertyId,
                nonce: malisafi_ajax.nonce
            },
            success: function(response) {
                if (typeof callback === 'function') {
                    callback(response);
                }
            }
        });
    });
    
    // Report property
    $(document).on('malisafi_report_property', function(e, data, callback) {
        $.ajax({
            url: malisafi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'malisafi_report_property',
                data: data,
                nonce: malisafi_ajax.nonce
            },
            success: function(response) {
                if (typeof callback === 'function') {
                    callback(response);
                }
            }
        });
    });
    
    // Rate agent
    $(document).on('malisafi_rate_agent', function(e, data, callback) {
        $.ajax({
            url: malisafi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'malisafi_rate_agent',
                data: data,
                nonce: malisafi_ajax.nonce
            },
            success: function(response) {
                if (typeof callback === 'function') {
                    callback(response);
                }
            }
        });
    });
    
    // Contact agent
    $(document).on('malisafi_contact_agent', function(e, data, callback) {
        $.ajax({
            url: malisafi_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'malisafi_contact_agent',
                data: data,
                nonce: malisafi_ajax.nonce
            },
            success: function(response) {
                if (typeof callback === 'function') {
                    callback(response);
                }
            }
        });
    });
    
})(jQuery);