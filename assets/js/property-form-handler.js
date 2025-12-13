/**
 * Property Form Handler - Preserve Data on Validation Errors
 * Handles form submissions and preserves data when validation fails
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Save form data to sessionStorage before submit
    var $propertyForm = $('#post');
    
    if ($propertyForm.length) {
        // Restore saved data if exists
        restoreFormData();
        
        // Save data before form submission
        $propertyForm.on('submit', function() {
            saveFormData();
        });
        
        // Auto-save every 30 seconds
        setInterval(saveFormData, 30000);
        
        // Clear saved data after successful save
        if (window.location.search.indexOf('message=') > -1) {
            clearSavedData();
        }
    }
    
    function saveFormData() {
        var formData = {};
        
        // Save all input fields
        $propertyForm.find('input[type="text"], input[type="number"], input[type="email"], input[type="tel"], input[type="url"], textarea, select').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            
            if (name && !name.match(/^(post_ID|_wpnonce|_wp_http_referer|action)/)) {
                formData[name] = $field.val();
            }
        });
        
        // Save checkboxes
        $propertyForm.find('input[type="checkbox"]').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            
            if (name && !name.match(/^(post_ID|_wpnonce|_wp_http_referer|action)/)) {
                formData[name] = $field.is(':checked');
            }
        });
        
        // Save radio buttons
        $propertyForm.find('input[type="radio"]:checked').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            
            if (name) {
                formData[name] = $field.val();
            }
        });
        
        // Save gallery IDs
        var galleryIds = [];
        $('input[name="malisafi_gallery_ids[]"]').each(function() {
            galleryIds.push($(this).val());
        });
        if (galleryIds.length > 0) {
            formData['malisafi_gallery_ids'] = galleryIds;
        }
        
        // Save to sessionStorage
        try {
            sessionStorage.setItem('malisafi_property_form_data', JSON.stringify(formData));
            sessionStorage.setItem('malisafi_property_form_timestamp', Date.now());
        } catch (e) {
            console.warn('Could not save form data to sessionStorage:', e);
        }
    }
    
    function restoreFormData() {
        try {
            var savedData = sessionStorage.getItem('malisafi_property_form_data');
            var timestamp = sessionStorage.getItem('malisafi_property_form_timestamp');
            
            if (!savedData || !timestamp) {
                return;
            }
            
            // Check if data is less than 1 hour old
            var age = Date.now() - parseInt(timestamp);
            if (age > 3600000) { // 1 hour
                clearSavedData();
                return;
            }
            
            var formData = JSON.parse(savedData);
            
            // Check if there are validation errors
            var hasErrors = $('.error, .notice-error').length > 0;
            
            // Only restore if there are errors or if form is empty
            if (!hasErrors && $('#title').val().trim() !== '') {
                return;
            }
            
            // Restore text inputs, numbers, emails, etc.
            $.each(formData, function(name, value) {
                if (name === 'malisafi_gallery_ids') {
                    // Skip gallery IDs, handled separately
                    return;
                }
                
                var $field = $('[name="' + name + '"]');
                
                if ($field.length) {
                    if ($field.is(':checkbox')) {
                        $field.prop('checked', value === true);
                    } else if ($field.is(':radio')) {
                        $field.filter('[value="' + value + '"]').prop('checked', true);
                    } else {
                        $field.val(value);
                    }
                }
            });
            
            // Show restoration notice
            if (hasErrors) {
                var $notice = $('<div class="notice notice-success is-dismissible" style="margin: 10px 0;"><p><strong>Form data restored.</strong> Your previous entries have been recovered.</p></div>');
                $('.wrap h1').after($notice);
                
                // Make notice dismissible
                $notice.on('click', '.notice-dismiss', function() {
                    $notice.fadeOut();
                });
            }
            
        } catch (e) {
            console.warn('Could not restore form data:', e);
        }
    }
    
    function clearSavedData() {
        try {
            sessionStorage.removeItem('malisafi_property_form_data');
            sessionStorage.removeItem('malisafi_property_form_timestamp');
        } catch (e) {
            console.warn('Could not clear saved data:', e);
        }
    }
    
    // Clear saved data when explicitly requested
    $('#clear-saved-data').on('click', function(e) {
        e.preventDefault();
        clearSavedData();
        alert('Saved form data cleared.');
    });
});
