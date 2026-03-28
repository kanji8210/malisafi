/**
 * Property Submission Wizard JavaScript
 * Handles multi-step form, auto-save, image upload, and validation
 */

(function(){
    // Silence console output unless debugging enabled for Malisafi.
    var debugEnabled = false;
    try {
        if (typeof malisafi_ajax !== 'undefined' && malisafi_ajax && malisafi_ajax.debug) {
            debugEnabled = true;
        } else if (typeof malisafiPublicChat !== 'undefined' && malisafiPublicChat && malisafiPublicChat.debug) {
            debugEnabled = true;
        } else if (window.malisafiDebug) {
            debugEnabled = true;
        }
    } catch (e) {}
    if (!debugEnabled) {
        try {
            console.log = function(){};
            console.debug = function(){};
            console.info = function(){};
            console.warn = function(){};
            console.error = function(){};
        } catch (e) {}
    } else {
        window.malisafiDebug = true;
    }
})();

(function($) {
    'use strict';

    const PropertySubmission = {
        currentStep: 1, // Start on first step
        totalSteps: 6,
        propertyId: 0,
        formData: {},
        autoSaveTimeout: null,
        initialized: false,
        uploadedImages: [],
        isSubmitting: false, // DUPLICATE PREVENTION: Track submit state

        init: function() {
            if (this.initialized) {
                return;
            }
            
            if (!$('#property-submission-form').length) {
                return;
            }
            
            this.propertyId = $('#property_id').val() || 0;
            this.cacheElements();
            this.bindEvents();

            // Apply preset values from data attributes (only for new forms, not edits)
            if (!this.propertyId || this.propertyId === '0' || this.propertyId === 0) {
                this.applyPresets();
            }

            // Adjust wizard fields and steps based on property type and listing type
            this.refreshFieldsVisibility();
            
            // Load draft if editing
            if (this.propertyId && this.propertyId !== '0' && this.propertyId !== 0) {
                this.loadDraft();
            }
            
            // Show the first step
            this.updateStep();
            
            // Initialize image upload
            this.initImageUpload();
            
            this.initialized = true;
        },

        cacheElements: function() {
            this.$form = $('#property-submission-form');
            this.$steps = $('.wizard-step');
            this.$progressSteps = $('.progress-step');
            this.$btnPrev = $('.btn-prev');
            this.$btnNext = $('.btn-next');
            this.$btnSubmit = $('.btn-submit');
            this.$btnSaveDraft = $('.btn-save-draft');
            this.$autoSave = $('.autosave-indicator');
            
            // Image upload elements - cache if they exist
            this.$gallery = $('#image-gallery');
            this.$featuredPreview = $('#featured-preview');
        },

        bindEvents: function() {
            const self = this;

            // Navigation
            this.$btnNext.on('click', function() {
                self.nextStep();
            });

            this.$btnPrev.on('click', function() {
                self.prevStep();
            });

            this.$btnSubmit.on('click', function() {
                self.submitProperty();
            });

            this.$btnSaveDraft.on('click', function() {
                self.saveDraft();
            });

            // Auto-save on input change
            this.$form.on('input change', 'input, textarea, select', function() {
                self.scheduleAutoSave();
            });

            // Listing type toggle for sale/lease details - use registry for all visibility
            this.$form.on('change', '#listing_type', function() {
                self.refreshFieldsVisibility();
            });

            // Property type change - adjust wizard fields dynamically
            this.$form.on('change', '#property_type', function() {
                self.refreshFieldsVisibility();
            });

            // County -> Subcounty
            this.$form.on('change', '#property_county', function() {
                self.fetchSubcounties($(this).val(), '');
            });

            // GPS location
            $('.btn-get-location').on('click', function() {
                self.getLocation();
            });

            // Extract coordinates from Google Maps URL
            $('.btn-extract-coords').on('click', function() {
                self.extractCoordsFromMapsURL();
            });

            // Generate Reference ID
            $('.btn-generate-ref').on('click', function() {
                self.generateReferenceId();
            });

            // Prevent form submission
            this.$form.on('submit', function(e) {
                e.preventDefault();
            });

            // Progress steps click navigation
            this.$progressSteps.on('click', function() {
                const targetStep = parseInt($(this).data('step'));
                self.goToStep(targetStep);
            });
        },

        goToStep: function(stepNum) {
            if (stepNum === this.currentStep) return;

            // If going forward, validate current step
            if (stepNum > this.currentStep) {
                if (!this.validateStep(this.currentStep)) {
                    return;
                }
                
                // If jumping ahead multiple steps, we could validate intermediate steps,
                // but usually for UX we just validate the one the user is leaving.
            }

            // Check if target step is skipped
            if ($('#step-' + stepNum).hasClass('step-skipped')) {
                return;
            }

            this.currentStep = stepNum;
            this.updateStep();
            this.saveStep();
        },

        nextStep: function() {
            if (!this.validateStep(this.currentStep)) {
                return;
            }

            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                // Skip hidden steps (logic-specific skips via .step-skipped)
                while ($('#step-' + this.currentStep).hasClass('step-skipped') && this.currentStep < this.totalSteps) {
                    this.currentStep++;
                }
                this.updateStep();
                this.saveStep();
            }
        },

        prevStep: function() {
            if (this.currentStep > 1) {
                this.currentStep--;
                // Skip hidden steps backwards
                while (this.currentStep > 1 && $('#step-' + this.currentStep).hasClass('step-skipped')) {
                    this.currentStep--;
                }
                this.updateStep();
            }
        },

        updateStep: function(shouldScroll = true) {
            // Update wizard steps display
            this.$steps.removeClass('active');
            $('#step-' + this.currentStep).addClass('active');

            // Update progress
            this.$progressSteps.removeClass('active completed');
            this.$progressSteps.each(function(index) {
                const stepNum = index + 1;
                const $step = $('#step-' + stepNum);
                const isSkipped = $step.hasClass('step-skipped');
                
                $(this).toggle(!isSkipped);
                
                if (stepNum < PropertySubmission.currentStep) {
                    $(this).addClass('completed');
                } else if (stepNum === PropertySubmission.currentStep) {
                    $(this).addClass('active');
                }
            });

            // Update navigation buttons
            this.$btnPrev.toggle(this.currentStep > 1);
            this.$btnNext.toggle(this.currentStep < this.totalSteps);
            this.$btnSubmit.toggle(this.currentStep === this.totalSteps);

            // Update preview if on last step
            if (this.currentStep === this.totalSteps) {
                this.updatePreview();
            }

            // Scroll to top
            if (shouldScroll) {
                $('html, body').animate({ scrollTop: 0 }, 300);
            }
        },

        validateStep: function(step) {
            const stepName = this.getStepName(step);
            const registry = malisafiSubmission && malisafiSubmission.fieldRegistry ? malisafiSubmission.fieldRegistry : {};
            const fields = registry[stepName] || {};
            let isValid = true;
            let firstErrorField = null;
            let missingFieldLabels = [];

            // Clear previous errors
            this.$form.find('.error').removeClass('error');
            this.$form.find('.malisafi-form-error, .malisafi-form-success').hide().text('');

            for (const fieldKey in fields) {
                const config = fields[fieldKey];
                const selector = `[name="${fieldKey}"], [name="${fieldKey}[]"]`;
                const $field = this.$form.find(selector);
                
                // Skip if field is hidden or disabled
                if (!$field.length || $field.is(':disabled') || !$field.is(':visible')) {
                    continue;
                }

                // Validation based on registry
                if (config.required) {
                    let value = $field.val();
                    let isEmpty = false;

                    if ($field.attr('type') === 'checkbox') {
                        isEmpty = !this.$form.find(`[name="${fieldKey}[]"]:checked`).length;
                    } else if (Array.isArray(value)) {
                        isEmpty = value.length === 0;
                    } else {
                        isEmpty = !value || value.toString().trim() === '';
                    }

                    if (isEmpty) {
                        isValid = false;
                        $field.addClass('error');
                        if (!firstErrorField) firstErrorField = $field;

                        // Get field label
                        let label = '';
                        const id = $field.attr('id');
                        if (id) {
                            label = $(`label[for="${id}"]`).text().replace('*', '').trim();
                        }
                        if (!label) {
                            label = $field.closest('.form-row').find('label').text().replace('*', '').trim();
                        }
                        if (!label) {
                            label = fieldKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        }

                        if (missingFieldLabels.indexOf(label) === -1) {
                            missingFieldLabels.push(label);
                        }
                    }
                }
            }

            // Special check for images step
            if (stepName === 'images' && malisafiSubmission.uploadsEnabled === true) {
                if (!$('#featured_image_id').val()) {
                    isValid = false;
                    $('#featured-dropzone').addClass('error');
                    missingFieldLabels.push('Featured Image');
                }
            }

            if (!isValid) {
                if (firstErrorField) firstErrorField.focus();
                
                const message = missingFieldLabels.length > 0 
                    ? 'Please fill the following fields to continue: ' + missingFieldLabels.join(', ')
                    : 'Please fill the following fields to continue';
                
                this.showError(message);
            }

            return isValid;
        },

        scheduleAutoSave: function() {
            clearTimeout(this.autoSaveTimeout);
            const self = this;
            
            this.autoSaveTimeout = setTimeout(function() {
                // Only auto-save if we have some basic data
                const stepData = self.getStepData(self.currentStep);
                const hasData = Object.keys(stepData).length > 0 && 
                               Object.values(stepData).some(value => value !== '' && value !== null && value !== undefined);
                
                if (hasData) {
                    self.saveStep();
                }
            }, 2000); // Save 2 seconds after last change
        },

        saveStep: function() {
            const self = this;
            const stepData = this.getStepData(this.currentStep);
            
            // Don't save if we don't have any data
            const hasData = Object.keys(stepData).length > 0 && 
                           Object.values(stepData).some(value => value !== '' && value !== null && value !== undefined);
            
            if (!hasData) {
                return;
            }
            
            this.showAutoSave('saving');

            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_save_property_step',
                    nonce: malisafiSubmission.nonce,
                    property_id: this.propertyId,
                    step: this.getStepName(this.currentStep),
                    data: stepData
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.property_id) {
                            self.propertyId = response.data.property_id;
                            $('#property_id').val(self.propertyId);
                        }
                        self.showAutoSave('saved');
                    } else {
                        self.showAutoSave('error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Save step AJAX error:', status, error, xhr);
                    if (xhr.status === 403) {
                        self.showAutoSave('error');
                        self.showError('Your session has expired. Please refresh the page and log in again.');
                    } else {
                        self.showAutoSave('error');
                    }
                }
            });
        },

        getStepName: function(step) {
            const stepNames = {
                1: 'basic',
                2: 'details',
                3: 'location',
                4: 'features',
                5: 'images',
                6: 'review'
            };
            return stepNames[step] || 'basic';
        },

        getStepData: function(step) {
            const stepName = this.getStepName(step);
            const registry = malisafiSubmission && malisafiSubmission.fieldRegistry ? malisafiSubmission.fieldRegistry : {};
            const fields = registry[stepName] || {};
            const data = {};

            for (const fieldKey in fields) {
                const selector = `[name="${fieldKey}"], [name="${fieldKey}[]"]`;
                const $field = this.$form.find(selector);
                
                if (!$field.length || $field.is(':disabled')) {
                    continue;
                }

                if ($field.attr('type') === 'checkbox') {
                    const values = [];
                    this.$form.find(`[name="${fieldKey}[]"]:checked`).each(function() {
                        values.push($(this).val());
                    });
                    data[fieldKey] = values;
                } else {
                    data[fieldKey] = $field.val();
                }
            }

            return data;
        },

        showUploadProgress: function(message) {
            let $progress = $('.upload-progress');
            if (!$progress.length) {
                $progress = $('<div class="upload-progress"><div class="progress-text"></div></div>');
                $('body').append($progress);
            }
            $progress.find('.progress-text').text(message);
            $progress.show();
        },

        hideUploadProgress: function() {
            $('.upload-progress').hide();
        },

        showUploadSuccess: function(message) {
            this.showUploadMessage(message, 'success');
        },

        showUploadError: function(message) {
            this.showUploadMessage(message, 'error');
        },

        showUploadMessage: function(message, type) {
            // Remove any existing messages
            $('.upload-message').remove();
            
            const $message = $('<div class="upload-message ' + type + '"><div class="message-content">' + message + '</div><button class="close-btn">×</button></div>');
            
            // Add close functionality
            $message.find('.close-btn').on('click', function() {
                $message.fadeOut(function() { $(this).remove(); });
            });
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() { $(this).remove(); });
            }, 5000);
            
            $('body').append($message);
            $message.fadeIn();
        },

        showAutoSave: function(status) {
            const $indicator = $('.autosave-indicator');
            $indicator.removeClass('saving saved error').addClass(status + ' show');

            const messages = {
                saving: malisafiSubmission.strings.saving || 'Saving...',
                saved: malisafiSubmission.strings.saved || 'Saved',
                error: malisafiSubmission.strings.error || 'Error saving'
            };

            $indicator.find('.status-text').text(messages[status] || '');

            if (status === 'saved' || status === 'error') {
                setTimeout(function() {
                    $indicator.removeClass('show');
                }, 2000);
            }
        },

        // Image Upload Functions - RECREATED
        initImageUpload: function() {
            const $dropzone = $('#dropzone');
            const $featuredDropzone = $('#featured-dropzone');
            
            if ($dropzone.length) {
                // Gallery image upload
                $dropzone.on('dragover', function(e) {
                    e.preventDefault();
                    $(this).addClass('drag-over').removeClass('dropzone-error');
                });
                
                $dropzone.on('dragleave', function(e) {
                    e.preventDefault();
                    $(this).removeClass('drag-over dropzone-error');
                });
                
                $dropzone.on('drop', function(e) {
                    e.preventDefault();
                    $(this).removeClass('drag-over');
                    
                    const files = e.originalEvent.dataTransfer.files;
                    const currentCount = $('#image-gallery .gallery-item').length;
                    
                    // Check if any files are images
                    const imageFiles = Array.from(files).filter(file => file.type.match('image.*'));
                    
                    if (imageFiles.length === 0) {
                        $(this).addClass('dropzone-error');
                        this.showUploadError('Please drop image files only. Supported formats: JPEG, PNG, WebP');
                        setTimeout(() => {
                            $(this).removeClass('dropzone-error');
                        }, 2000);
                        return;
                    }
                    
                    // Check size limits
                    const oversizedFiles = imageFiles.filter(file => file.size > 15 * 1024 * 1024);
                    if (oversizedFiles.length > 0) {
                        $(this).addClass('dropzone-error');
                        this.showUploadError('Some files are too large. Maximum size is 15MB per image.');
                        setTimeout(() => {
                            $(this).removeClass('dropzone-error');
                        }, 2000);
                        return;
                    }
                    
                    // Check total count
                    if (currentCount >= 15) {
                        $(this).addClass('dropzone-error');
                        this.showUploadError('Maximum 15 images allowed. Please remove some images first.');
                        setTimeout(() => {
                            $(this).removeClass('dropzone-error');
                        }, 2000);
                        return;
                    }
                    
                    this.handleGalleryFiles(files);
                }.bind(this));
                
                // Browse button for gallery
                $('.btn-browse-images').on('click', function() {
                    $('#image-file-input').click();
                });
                
                $('#image-file-input').on('change', function(e) {
                    this.handleGalleryFiles(e.target.files);
                }.bind(this));
                
                $('#replace-image-input').on('change', function(e) {
                    if (e.target.files.length > 0) {
                        this.handleReplaceFile(e.target.files[0]);
                    }
                }.bind(this));
            }
            
            if ($featuredDropzone.length) {
                // Featured image upload
                $featuredDropzone.on('dragover', function(e) {
                    e.preventDefault();
                    $(this).addClass('drag-over').removeClass('dropzone-error');
                });
                
                $featuredDropzone.on('dragleave', function(e) {
                    e.preventDefault();
                    $(this).removeClass('drag-over dropzone-error');
                });
                
                $featuredDropzone.on('drop', function(e) {
                    e.preventDefault();
                    $(this).removeClass('drag-over');
                    
                    const files = e.originalEvent.dataTransfer.files;
                    if (files.length === 0) return;
                    
                    const file = files[0];
                    
                    // Validate file type
                    if (!file.type.match('image.*')) {
                        $(this).addClass('dropzone-error');
                        this.showUploadError('Please drop an image file. Supported formats: JPEG, PNG, WebP');
                        setTimeout(() => {
                            $(this).removeClass('dropzone-error');
                        }, 2000);
                        return;
                    }
                    
                    // Validate file size
                    if (file.size > 15 * 1024 * 1024) {
                        $(this).addClass('dropzone-error');
                        this.showUploadError('File is too large. Maximum size is 15MB. Your file is ' + (file.size / 1024 / 1024).toFixed(1) + 'MB');
                        setTimeout(() => {
                            $(this).removeClass('dropzone-error');
                        }, 2000);
                        return;
                    }
                    
                    this.handleFeaturedFile(file);
                }.bind(this));
                
                // Browse button for featured
                $('.btn-browse-featured').on('click', function() {
                    $('#featured-file-input').click();
                });
                
                $('#featured-file-input').on('change', function(e) {
                    this.handleFeaturedFile(e.target.files[0]);
                }.bind(this));
                
                // Remove featured image
                $('.btn-remove-featured').on('click', function() {
                    this.removeFeaturedImage();
                }.bind(this));
                
                // Replace featured image
                $('.btn-replace-featured').on('click', function() {
                    $('#featured-preview').hide();
                    $('#featured-dropzone').show();
                }.bind(this));
            }
        },

        handleGalleryFiles: function(files) {
            if (!files || files.length === 0) return;
            
            // Limit to 15 images total
            const currentCount = $('#image-gallery .gallery-item').length;
            const maxNew = Math.min(files.length, 15 - currentCount);
            
            if (maxNew <= 0) {
                this.showUploadError('Maximum 15 images allowed per property. You already have ' + currentCount + ' images. Please remove some before adding more.');
                return;
            }
            
            if (files.length > maxNew) {
                this.showUploadError('Too many files selected. Only the first ' + maxNew + ' files will be uploaded (maximum 15 total).');
            }
            
            const filesToUpload = Array.from(files).slice(0, maxNew);
            
            filesToUpload.forEach(function(file) {
                this.uploadGalleryImage(file);
            }.bind(this));
        },

        handleFeaturedFile: function(file) {
            if (!file) return;
            this.uploadFeaturedImage(file);
        },

        uploadGalleryImage: function(file) {
            // Validate file type
            if (!file.type.match('image.*')) {
                this.showUploadError('Please select image files only. Supported formats: JPEG, PNG, WebP');
                return;
            }
            
            // Validate file size (15MB max)
            if (file.size > 15 * 1024 * 1024) {
                this.showUploadError('File size must be less than 15MB. Your file is ' + (file.size / 1024 / 1024).toFixed(1) + 'MB');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'malisafi_upload_property_images');
            formData.append('nonce', malisafiSubmission.uploadNonce);
            formData.append('property_id', this.propertyId || 0);
            formData.append('images[]', file);
            
            // Show shimmer in gallery
            const $shimmer = $('<div class="gallery-item shimmer"></div>');
            this.$gallery.append($shimmer);
            
            this.showUploadProgress('Uploading ' + file.name + '...');
            
            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $shimmer.remove();
                    this.hideUploadProgress();
                    if (response.success && response.data.images) {
                        response.data.images.forEach(function(image) {
                            this.addGalleryImage(image);
                        }.bind(this));
                        this.showUploadSuccess('Images added to gallery');
                    } else {
                        this.showUploadError(response.data.message || 'Upload failed');
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    this.hideUploadProgress();
                    let errorMessage = 'Upload failed due to network error';
                    
                    if (xhr.status === 413) {
                        errorMessage = 'File is too large for server limits. Try a smaller image.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred. Please try again later.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Permission denied. Please refresh the page and try again.';
                    } else if (status === 'timeout') {
                        errorMessage = 'Upload timed out. Please check your connection and try again.';
                    }
                    
                    this.showUploadError(errorMessage);
                }.bind(this)
            });
        },

        uploadFeaturedImage: function(file) {
            // Validate file type
            if (!file.type.match('image.*')) {
                this.showUploadError('Please select an image file. Supported formats: JPEG, PNG, WebP');
                return;
            }
            
            // Validate file size (15MB max)
            if (file.size > 15 * 1024 * 1024) {
                this.showUploadError('File size must be less than 15MB. Your file is ' + (file.size / 1024 / 1024).toFixed(1) + 'MB');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'malisafi_upload_featured_image');
            formData.append('nonce', malisafiSubmission.uploadNonce);
            formData.append('property_id', this.propertyId || 0);
            formData.append('image', file);
            
            this.showUploadProgress('Uploading featured image...');
            $('#featured-dropzone').addClass('uploading');
            
            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    this.hideUploadProgress();
                    $('#featured-dropzone').removeClass('uploading');
                    if (response.success && response.data.image) {
                        this.setFeaturedImage(response.data.image);
                        this.showUploadSuccess('Featured image uploaded successfully');
                    } else {
                        this.showUploadError(response.data.message || 'Upload failed');
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    this.hideUploadProgress();
                    let errorMessage = 'Upload failed due to network error';
                    
                    if (xhr.status === 413) {
                        errorMessage = 'File is too large for server limits. Try a smaller image.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred. Please try again later.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Permission denied. Your session may have expired. Please refresh the page and try again.';
                    } else if (status === 'timeout') {
                        errorMessage = 'Upload timed out. Please check your connection and try again.';
                    }
                    
                    this.showUploadError(errorMessage);
                }.bind(this)
            });
        },

        replaceGalleryImage: function(imageId) {
            this.replaceImageId = imageId;
            $('#replace-image-input').click();
        },

        handleReplaceFile: function(file) {
            if (!file || !this.replaceImageId) return;
            
            const formData = new FormData();
            formData.append('action', 'malisafi_upload_property_images');
            formData.append('nonce', malisafiSubmission.uploadNonce);
            formData.append('property_id', this.propertyId || 0);
            formData.append('replace_id', this.replaceImageId);
            formData.append('images[]', file);
            
            this.showUploadProgress('Replacing image...');
            
            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    this.hideUploadProgress();
                    if (response.success && response.data.images) {
                        // Remove old image from UI
                        $('.gallery-item[data-id="' + this.replaceImageId + '"]').remove();
                        // Add new image
                        response.data.images.forEach(function(image) {
                            this.addGalleryImage(image);
                        }.bind(this));
                        this.showUploadSuccess('Image replaced successfully');
                    } else {
                        this.showUploadError(response.data.message || 'Replace failed');
                    }
                    this.replaceImageId = null;
                }.bind(this),
                error: function(xhr, status, error) {
                    this.hideUploadProgress();
                    let errorMessage = 'Replace failed due to network error';
                    if (xhr.status === 403) {
                        errorMessage = 'Permission denied. Your session may have expired. Please refresh the page and try again.';
                    }
                    this.showUploadError(errorMessage);
                    this.replaceImageId = null;
                }.bind(this)
            });
        },

        deleteGalleryImage: function(imageId) {
            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_delete_property_image',
                    nonce: malisafiSubmission.uploadNonce,
                    property_id: this.propertyId,
                    image_id: imageId
                },
                success: function(response) {
                    if (response.success) {
                        $('.gallery-item[data-id="' + imageId + '"]').remove();
                        this.updateGalleryOrder();
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Delete image AJAX error:', status, error, xhr);
                    if (xhr.status === 403) {
                        this.showError('Permission denied. Your session may have expired. Please refresh the page and try again.');
                    }
                }
            });
        },

        setFeaturedImage: function(image) {
            $('#featured_image_id').val(image.id);
            $('#featured-preview img').attr('src', image.url);
            $('#featured-preview').show();
            $('#featured-dropzone').hide();
        },

        removeFeaturedImage: function() {
            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_clear_featured_image',
                    nonce: malisafiSubmission.uploadNonce,
                    property_id: this.propertyId
                },
                success: function(response) {
                    if (response.success) {
                        $('#featured_image_id').val('');
                        $('#featured-preview').hide();
                        $('#featured-dropzone').show();
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Clear featured image AJAX error:', status, error, xhr);
                    if (xhr.status === 403) {
                        this.showError('Permission denied. Your session may have expired. Please refresh the page and try again.');
                    }
                }
            });
        },

        addGalleryImage: function(image) {
            const $gallery = $('#image-gallery');
            const $item = $('<div class="gallery-item">')
                .attr('data-id', image.id)
                .html('<img src="' + image.url + '" alt=""><button class="replace-btn" data-id="' + image.id + '">🔄</button><button class="delete-btn" data-id="' + image.id + '">×</button>');
            
            $gallery.append($item);
            
            // Bind replace
            $item.find('.replace-btn').on('click', function() {
                this.replaceGalleryImage($(this).data('id'));
            }.bind(this));
            
            // Bind delete
            $item.find('.delete-btn').on('click', function() {
                if (confirm('Delete this image?')) {
                    this.deleteGalleryImage($(this).data('id'));
                }
            }.bind(this));
            
            // Update order
            this.updateGalleryOrder();
        },

        generateReferenceId: function() {
            const self = this;
            const $btn = $('.btn-generate-ref');
            const $input = $('#reference_id');

            if (!this.propertyId || this.propertyId === '0') {
                this.showAutoSave('saving');
                this.saveStep();
                
                setTimeout(function() {
                    if (self.propertyId && self.propertyId !== '0') {
                        self.generateReferenceId();
                    } else {
                        self.showError('Unable to generate ID. Please try again after the draft is saved.');
                    }
                }, 1000);
                return;
            }

            $btn.prop('disabled', true).text('Generating...');

            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_generate_reference_id',
                    nonce: malisafiSubmission.refNonce,
                    property_id: this.propertyId
                },
                success: function(response) {
                    if (response.success && response.data.reference_id) {
                        $input.val(response.data.reference_id);
                        self.showSuccess(response.data.message || 'Reference ID generated successfully!');
                    } else {
                        self.showError(response.data.message || 'Failed to generate Reference ID');
                    }
                    $btn.prop('disabled', false).text('Generate ID');
                },
                error: function() {
                    self.showError('An error occurred while generating Reference ID');
                    $btn.prop('disabled', false).text('Generate ID');
                }
            });
        },

        updateGalleryOrder: function() {
            const order = [];
            $('#image-gallery .gallery-item').each(function() {
                order.push($(this).data('id'));
            });
            $('#gallery_ids').val(order.join(','));
            
            // Save order if we have a property ID
            if (this.propertyId && order.length > 0) {
                $.ajax({
                    url: malisafiSubmission.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'malisafi_reorder_property_images',
                        nonce: malisafiSubmission.uploadNonce,
                        property_id: this.propertyId,
                        order: order
                    },
                    success: function(response) {
                        // Optional: handle success
                    },
                    error: function(xhr, status, error) {
                        console.error('Reorder AJAX error:', status, error, xhr);
                        if (xhr.status === 403) {
                            console.warn('Session may have expired during reorder');
                        }
                    }
                });
            }
        },

        getLocation: function() {
            if (!navigator.geolocation) {
                this.showError('Geolocation is not supported by your browser');
                return;
            }

            $('.btn-get-location').text('Getting location...').prop('disabled', true);

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude.toFixed(6);
                    const lng = position.coords.longitude.toFixed(6);
                    $('#property_gps').val(lat + ', ' + lng);
                    $('.btn-get-location').html('<span class="icon">\u{1F4CD}</span> Get My Location').prop('disabled', false);
                },
                function() {
                    self.showError('Unable to retrieve your location');
                    $('.btn-get-location').html('<span class="icon">\u{1F4CD}</span> Get My Location').prop('disabled', false);
                }
            );
        },

        extractCoordsFromMapsURL: function() {
            const mapsUrl = $('#google_maps_url').val().trim();
            if (!mapsUrl) {
                this.showError('Please enter a Google Maps URL first');
                return;
            }

            $('.btn-extract-coords').text('Extracting...').prop('disabled', true);

            try {
                // Extract coordinates from various Google Maps URL formats
                let coords = null;

                // Try to match different Google Maps URL patterns
                const patterns = [
                    // https://maps.google.com/?q=-1.2921,36.8219
                    /q=([-+]?\d*\.?\d+),\s*([-+]?\d*\.?\d+)/,
                    // https://www.google.com/maps/@-1.2921,36.8219,15z
                    /@([-+]?\d*\.?\d+),\s*([-+]?\d*\.?\d+)/,
                    // https://maps.google.com/maps?q=-1.2921,36.8219
                    /\?q=([-+]?\d*\.?\d+),\s*([-+]?\d*\.?\d+)/,
                    // Direct coordinates
                    /^([-+]?\d*\.?\d+),\s*([-+]?\d*\.?\d+)$/
                ];

                for (const pattern of patterns) {
                    const match = mapsUrl.match(pattern);
                    if (match) {
                        const lat = parseFloat(match[1]);
                        const lng = parseFloat(match[2]);
                        if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                            coords = lat.toFixed(6) + ', ' + lng.toFixed(6);
                            break;
                        }
                    }
                }

                if (coords) {
                    $('#property_gps').val(coords);
                    this.showSuccess('Coordinates extracted successfully!');
                } else {
                    this.showError('Could not extract coordinates from this URL. Please make sure it contains latitude and longitude.');
                }

            } catch (error) {
                console.error('Error extracting coordinates:', error);
                this.showError('Error processing the URL. Please check the format.');
            }

            $('.btn-extract-coords').html('<span class="icon">\u{1F4CC}</span> Extract Coordinates').prop('disabled', false);
        },

        updatePreview: function() {
            // Basic info
            $('#preview-title').text($('#property_title').val() || '-');
            $('#preview-price').text(
                ($('#property_currency').val() || 'KES') + ' ' +
                (parseFloat($('#property_price').val()) || 0).toLocaleString()
            );
            $('#preview-type').text($('#property_type option:selected').text() || '-');
            $('#preview-listing').text($('#listing_type option:selected').text() || '-');

            // Details
            $('#preview-bedrooms').text($('#bedrooms').val() || '0');
            $('#preview-bathrooms').text($('#bathrooms').val() || '0');
            $('#preview-size').text(
                ($('#property_size').val() || '-') + ' ' +
                ($('#size_unit option:selected').text() || '')
            );
            $('#preview-condition').text($('#condition option:selected').text() || '-');

            // Location
            $('#preview-county').text($('#property_county').val() || '-');
            $('#preview-subcounty').text($('#property_subcounty').val() || '-');
            $('#preview-city').text($('#property_city').val() || '-');

            // Featured image
            const $previewFeatured = $('#preview-featured');
            $previewFeatured.empty();
            const featuredUrl = this.$featuredPreview.find('img').attr('src');

            if (featuredUrl) {
                $previewFeatured.append($('<img>').attr('src', featuredUrl));
            } else {
                $previewFeatured.html('<p class="no-images">No featured image uploaded</p>');
            }

            // Gallery images
            const $previewImages = $('#preview-images');
            $previewImages.empty();

            if (this.$gallery.find('.gallery-item').length > 0) {
                this.$gallery.find('.gallery-item img').each(function() {
                    $previewImages.append(
                        $('<img>').attr('src', $(this).attr('src'))
                    );
                });
            } else {
                $previewImages.html('<p class="no-images">No images uploaded</p>');
            }
        },

        saveDraft: function() {
            this.saveStep();
            this.showSuccess('Draft saved. You can continue later.');
        },

        applyPresets: function() {
            const $form = this.$form;
            const listingType  = $form.data('preset-listing-type');
            const propertyType = $form.data('preset-property-type');
            const county       = $form.data('preset-county');

            if (listingType) {
                $('#listing_type').val(listingType);
            }
            if (propertyType) {
                $('#property_type').val(propertyType);
            }
            if (county) {
                $('#property_county').val(county);
                this.fetchSubcounties(county, '');
            }
        },

        fetchSubcounties: function(county, selected) {
            const $subcounty = $('#property_subcounty');
            $subcounty.html('<option value="">Select subcounty...</option>');

            if (!county) {
                return;
            }

            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_get_subcounties',
                    nonce: malisafiSubmission.nonce,
                    county: county
                },
                success: function(response) {
                    if (response.success && Array.isArray(response.data.subcounties)) {
                        response.data.subcounties.forEach(function(name) {
                            const $opt = $('<option>').val(name).text(name);
                            if (selected && selected === name) {
                                $opt.prop('selected', true);
                            }
                            $subcounty.append($opt);
                        });
                    }
                }
            });
        },

        /**
         * Refresh field visibility based on entire registry and current state
         */
        refreshFieldsVisibility: function() {
            const propertyType = this.getPropertyType();
            const listingType = $('#listing_type').val();
            const registry = malisafiSubmission.fieldRegistry;

            // Iterate through every step and field in the registry
            for (const stepName in registry) {
                const fields = registry[stepName];
                let stepHasVisibleFields = false;

                for (const fieldKey in fields) {
                    const config = fields[fieldKey];
                    const selector = `[name="${fieldKey}"], [name="${fieldKey}[]"]`;
                    const $field = this.$form.find(selector);
                    
                    if (!$field.length) continue;

                    let isVisible = true;

                    // Check show_for (property type)
                    if (config.show_for) {
                        let showForArr = [];
                        if (typeof config.show_for === 'string') {
                            showForArr = config.show_for.split(',').map(function(s) { return s.trim(); });
                        } else if (Array.isArray(config.show_for)) {
                            showForArr = config.show_for;
                        }
                        
                        if (propertyType && showForArr.indexOf(propertyType) === -1) {
                            isVisible = false;
                        }
                    }

                    // Check show_for_listing (listing type)
                    if (isVisible && config.show_for_listing) {
                        let showForListingArr = [];
                        if (typeof config.show_for_listing === 'string') {
                            showForListingArr = config.show_for_listing.split(',').map(function(s) { return s.trim(); });
                        } else if (Array.isArray(config.show_for_listing)) {
                            showForListingArr = config.show_for_listing;
                        }
                        
                        if (listingType && showForListingArr.indexOf(listingType) === -1) {
                            isVisible = false;
                        }
                    }

                    // Toggle visibility of the field's container
                    // We target form-row, type-field, features-section, or closest grouping
                    const $container = $field.closest('.form-row, .form-row-group, .type-field, .features-section, .sale-lease-details');
                    
                    if ($container.length) {
                        $container.toggle(isVisible);
                    } else {
                        $field.toggle(isVisible);
                    }

                    // Disable hidden fields so they aren't validated by browser or our script
                    $field.prop('disabled', !isVisible);

                    if (isVisible) {
                        stepHasVisibleFields = true;
                    }
                }

                // Toggle visibility of the step itself if it has no visible fields
                // Note: basic, images, and review are always visible
                if (stepName !== 'basic' && stepName !== 'images' && stepName !== 'review') {
                    const stepNum = this.getStepNumberFromName(stepName);
                    $('#step-' + stepNum).toggleClass('step-skipped', !stepHasVisibleFields);
                }
            }

            // Update step headings for Step 2
            const propertyTypeConfigMap = {
                house:      { title: 'Residential Details', desc: 'Add details about your house' },
                apartment:  { title: 'Apartment Details',   desc: 'Add details about your apartment' },
                land:       { title: 'Land Details',        desc: 'Add details about your land' },
                commercial: { title: 'Commercial Details',  desc: 'Add details about your commercial property' },
                industrial: { title: 'Industrial Details',  desc: 'Add details about your industrial property' }
            };

            if (propertyTypeConfigMap[propertyType]) {
                $('#step-2-title').text(propertyTypeConfigMap[propertyType].title);
                $('#step-2-desc').text(propertyTypeConfigMap[propertyType].desc);
            }

            this.updateStep(false);
        },

        getStepNumberFromName: function(name) {
            const names = { basic: 1, details: 2, location: 3, features: 4, images: 5, review: 6 };
            return names[name] || 1;
        },

        getPropertyType: function() {
            return (this.$form.find('#property_type').val() || '').toString().toLowerCase().trim();
        },

        submitProperty: function() {
            const self = this;

            // DUPLICATE PREVENTION: Check if already submitting
            if (this.isSubmitting) {
                return;
            }

            if (!this.validateStep(this.currentStep)) {
                return;
            }

            if (malisafiSubmission.uploadsEnabled === true) {
                if (!$('#featured_image_id').val()) {
                    this.showError('Please upload a featured image');
                    return;
                }
            }

            // Set submitting flag
            this.isSubmitting = true;
            this.$btnSubmit.prop('disabled', true).text(malisafiSubmission.strings.submitting);

            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_submit_property',
                    nonce: malisafiSubmission.nonce,
                    property_id: this.propertyId
                },
                success: function(response) {
                    if (response.success) {
                        self.showSubmitSuccess(response.data);
                        // Keep isSubmitting true to prevent re-submission after success
                    } else {
                        self.showError(response.data.message);
                        self.isSubmitting = false;
                        self.$btnSubmit.prop('disabled', false).text(malisafiSubmission.strings.submitProperty);
                    }
                },
                error: function() {
                    self.showError('An error occurred. Please try again.');
                    self.isSubmitting = false;
                    self.$btnSubmit.prop('disabled', false).text(malisafiSubmission.strings.submitProperty);
                }
            });
        },

        loadDraft: function() {
            const self = this;

            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_get_property_draft',
                    nonce: malisafiSubmission.nonce,
                    property_id: this.propertyId
                },
                success: function(response) {
                    if (response.success && response.data && response.data.data) {
                        self.populateForm(response.data.data);
                    }
                }
            });
        },

        populateForm: function(data) {
            const registry = malisafiSubmission.fieldRegistry;
            
            // Populate fields based on registry to ensure we cover everything
            for (const step in registry) {
                const fields = registry[step];
                for (const fieldKey in fields) {
                    if (data.hasOwnProperty(fieldKey)) {
                        const value = data[fieldKey];
                        let $field = this.$form.find(`[name="${fieldKey}"]`);
                        if (!$field.length) {
                            $field = this.$form.find(`[name="${fieldKey}[]"]`);
                        }
                        
                        if ($field.length) {
                            if ($field.attr('type') === 'checkbox') {
                                const values = Array.isArray(value) ? value : [];
                                $field.each(function() {
                                    $(this).prop('checked', values.indexOf($(this).val()) !== -1);
                                });
                            } else {
                                $field.val(value);
                            }
                        }
                    }
                }
            }

            if (data.county) {
                this.fetchSubcounties(data.county, data.subcounty || '');
            }

            this.refreshFieldsVisibility();

            if (data.featured_image) {
                this.setFeaturedImage(data.featured_image);
            }

            if (data.gallery_images && Array.isArray(data.gallery_images)) {
                this.renderGalleryImages(data.gallery_images);
            }
        },

        renderGalleryImages: function(images) {
            if (!Array.isArray(images) || images.length === 0) {
                return;
            }

            const $gallery = $('#image-gallery');
            if (!$gallery.length) {
                return;
            }

            const self = this;
            $gallery.empty();
            this.uploadedImages = [];

            images.forEach(function(image, index) {
                if (!image || !image.id || !image.url) {
                    return;
                }
                self.addGalleryImage(image);
            });

            this.updateGalleryOrder();
        },

        showError: function(message) {
            const $error = this.$form.find('.malisafi-form-error');
            if ($error.length) {
                $error.text(message).fadeIn();
                $('html, body').animate({
                    scrollTop: $error.offset().top - 100
                }, 300);
            } else {
                // Fallback to old dynamic way if container missing
                const $dynError = $('<div class="malisafi-form-error" style="display:block;">' + message + '</div>');
                this.$form.prepend($dynError);
                $('html, body').animate({ scrollTop: 0 }, 300);
            }
        },

        showSuccess: function(message) {
            const $success = this.$form.find('.malisafi-form-success');
            if ($success.length) {
                $success.text(message).fadeIn();
                $('html, body').animate({
                    scrollTop: $success.offset().top - 100
                }, 300);
                setTimeout(() => $success.fadeOut(), 5000);
            } else {
                const $dynSuccess = $('<div class="malisafi-form-success" style="display:block;">' + message + '</div>');
                this.$form.prepend($dynSuccess);
                $('html, body').animate({ scrollTop: 0 }, 300);
                setTimeout(() => $dynSuccess.fadeOut(), 5000);
            }
        },

        showSubmitSuccess: function(data) {
            this.$form.find('.success-message').remove();
            const message = data && data.message ? data.message : (malisafiSubmission.strings.success || 'Property submitted successfully!');
            const viewUrl = data && data.view_url ? data.view_url : '';
            const editUrl = data && data.edit_url ? data.edit_url : '';
            const dashboardUrl = malisafiSubmission.dashboardUrl || '';

            // Create Lighthouse Popup Overlay
            const $overlay = $('<div class="lighthouse-popup-overlay"></div>');
            const $card = $('<div class="lighthouse-popup-card"></div>');

            // Success Icon (SVG)
            const iconSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            const $icon = $('<div class="lighthouse-success-icon">' + iconSvg + '</div>');

            // Content
            const $title = $('<h2>' + (malisafiSubmission.strings.saved || 'Success!') + '</h2>');
            const $message = $('<p>' + message + '</p>');

            // Actions
            const $actions = $('<div class="lighthouse-actions"></div>');
            
            if (viewUrl) {
                $actions.append('<a href="' + viewUrl + '" target="_blank" class="btn btn-primary">View Property</a>');
            }
            
            if (editUrl) {
                $actions.append('<a href="' + editUrl + '" class="btn btn-secondary">Edit Property</a>');
            }
            
            if (dashboardUrl) {
                $actions.append('<a href="' + dashboardUrl + '" class="btn btn-link">Go to Dashboard</a>');
            }

            // Assemble
            $card.append($icon, $title, $message, $actions);
            $overlay.append($card);
            $('body').append($overlay);

            // Trigger Animation
            setTimeout(() => {
                $overlay.addClass('active');
            }, 10);

            // Re-enable submit button
            this.$btnSubmit.prop('disabled', false).text(malisafiSubmission.strings.submitProperty);
            
            // Auto-scroll to top
            $('html, body').animate({ scrollTop: 0 }, 300);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PropertySubmission.init();
        
        // Also check periodically in case form is loaded dynamically
        let checkCount = 0;
        const checkInterval = setInterval(function() {
            if ($('#property-submission-form').length && !PropertySubmission.initialized) {
                PropertySubmission.init();
                clearInterval(checkInterval);
            }
            checkCount++;
            if (checkCount > 20) { // Stop checking after 10 seconds
                clearInterval(checkInterval);
            }
        }, 500);
    });

})(jQuery);
