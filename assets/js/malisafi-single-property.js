/**
 * Malisafi MLS - Single Property Page JavaScript
 * Handles gallery navigation, favorite toggle, agent rating, modals, etc.
 */

(function($) {
    'use strict';

    // DOM Ready
    $(document).ready(function() {
        console.log ("js single prperty loaded");
        
        // ===== GALLERY NAVIGATION =====
        var currentImageIndex = 0;
        var totalImages = 0;
        
        // Initialize gallery
        function initGallery() {
            var $thumbnails = $('.gallery-thumbnails .thumbnail');
            var $mainImage = $('.main-image');
            totalImages = $thumbnails.length;
            
            if (totalImages > 0) {
                // Update counter
                $('.gallery-counter .current').text(currentImageIndex + 1);
                $('.gallery-counter .total').text(totalImages);
                
                // Thumbnail click
                $thumbnails.on('click', function() {
                    var index = $(this).data('index');
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
                        console.log('Favorite updated');
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
        $('.report-button').on('click', function() {
            var propertyId = $(this).data('property-id');
            $reportForm.find('input[name="property_id"]').val(propertyId);
            $reportModal.addClass('open');
            $('body').addClass('modal-open');
        });
        
        // Close modal
        $('.modal-close, .button-secondary.modal-close').on('click', function() {
            $(this).closest('.malisafi-modal').removeClass('open');
            $('body').removeClass('modal-open');
        });
        
        // Submit report form
        $reportForm.on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: malisafi_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=malisafi_report_property&nonce=' + malisafi_ajax.nonce,
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
                        alert(response.data || 'Failed to submit report. Please try again.');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                },
                complete: function() {
                    $reportForm.find('.button-primary').prop('disabled', false).text('Submit Report');
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
                        alert(response.data || 'Failed to send message. Please try again.');
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
                $(e.target).removeClass('open');
                $('body').removeClass('modal-open');
            }
        });
        
        // ===== ESC KEY TO CLOSE MODAL =====
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.malisafi-modal.open').removeClass('open');
                $('body').removeClass('modal-open');
            }
        });
        
        // Initialize gallery
        initGallery();
        
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