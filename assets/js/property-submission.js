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
            this.toggleSaleLeaseDetails();

            // Adjust wizard UI if the draft's property type is land
            this.adjustForLandUI();
            // Adjust UI for land properties (hide details/features steps)
            this.adjustForLandUI();
            
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

            // Listing type toggle for sale/lease details
            this.$form.on('change', '#listing_type', function() {
                self.toggleSaleLeaseDetails();
            });

            // Property type change - adjust wizard for land
            this.$form.on('change', '#property_type', function() {
                self.adjustForLandUI();
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

            // Prevent form submission
            this.$form.on('submit', function(e) {
                e.preventDefault();
            });
        },

        nextStep: function() {
            if (!this.validateStep(this.currentStep)) {
                return;
            }

            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                // Skip hidden steps (land-specific skips)
                while ($('#step-' + this.currentStep).is(':hidden') && this.currentStep < this.totalSteps) {
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
                while (this.currentStep > 1 && $('#step-' + this.currentStep).is(':hidden')) {
                    this.currentStep--;
                }
                this.updateStep();
            }
        },

        updateStep: function() {
            // Update wizard steps display
            this.$steps.removeClass('active');
            $('#step-' + this.currentStep).addClass('active');

            // Update progress
            this.$progressSteps.removeClass('active completed');
            this.$progressSteps.each(function(index) {
                const stepNum = index + 1;
                // Hide progress markers for steps that are hidden in the wizard
                const $step = $('#step-' + stepNum);
                $(this).toggle(!$step.is(':hidden'));
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
            $('html, body').animate({ scrollTop: 0 }, 300);
        },

        validateStep: function(step) {
            const $currentStep = $('#step-' + step);
            // If the step is hidden (skipped for land), it's considered valid
            if ($currentStep.is(':hidden')) {
                return true;
            }
            const $required = $currentStep.find('[required]');
            let isValid = true;

            $required.each(function() {
                if (!this.checkValidity()) {
                    isValid = false;
                    $(this).addClass('error');
                    $(this).focus();
                    return false;
                }
                $(this).removeClass('error');
            });

            if (step === 5 && malisafiSubmission.uploadsEnabled === true) {
                if (!$('#featured_image_id').val()) {
                    isValid = false;
                    $('#featured-dropzone').addClass('error');
                    this.showError('Featured image is required before you continue.');
                } else {
                    $('#featured-dropzone').removeClass('error');
                }
            }

            if (!isValid) {
                this.showError(malisafiSubmission.strings.error || 'Please fill in all required fields');
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
                        alert('Your session has expired. Please refresh the page and log in again.');
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
            const $step = $('#step-' + step);
            const data = {};

            $step.find('input, textarea, select').each(function() {
                const $field = $(this);
                let name = $field.attr('name');
                
                if (!name) return;

                // Remove array notation from name for data key
                const dataKey = name.replace(/\[\]$/, '');
                
                if ($field.attr('type') === 'checkbox') {
                    if (!data[dataKey]) data[dataKey] = [];
                    if ($field.is(':checked')) {
                        data[dataKey].push($field.val());
                    }
                } else {
                    data[dataKey] = $field.val();
                }
            });

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
                        alert('Permission denied. Your session may have expired. Please refresh the page and try again.');
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
                        alert('Permission denied. Your session may have expired. Please refresh the page and try again.');
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
                alert('Geolocation is not supported by your browser');
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
                    alert('Unable to retrieve your location');
                    $('.btn-get-location').html('<span class="icon">\u{1F4CD}</span> Get My Location').prop('disabled', false);
                }
            );
        },

        extractCoordsFromMapsURL: function() {
            const mapsUrl = $('#google_maps_url').val().trim();
            if (!mapsUrl) {
                alert('Please enter a Google Maps URL first');
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
                    alert('Could not extract coordinates from this URL. Please make sure it contains latitude and longitude.');
                }

            } catch (error) {
                console.error('Error extracting coordinates:', error);
                alert('Error processing the URL. Please check the format.');
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

        toggleSaleLeaseDetails: function() {
            const listingType = $('#listing_type').val();
            const show = listingType === 'sale' || listingType === 'lease';
            $('.sale-lease-details').toggle(show);
        },

        isLandSelected: function() {
            const val = (this.$form.find('#property_type').val() || '').toString().toLowerCase();
            return val === 'land';
        },

        adjustForLandUI: function() {
            const isLand = this.isLandSelected();
            
            // Step 2 (Details) should be visible for Land (to set Size), 
            // but specific house-centric fields should be hidden.
            const $step2 = $('#step-2');
            const $houseFields = $step2.find('#bedrooms, #bathrooms, #year_built, #condition, #floor_plan_urls').closest('.form-row, .form-row-group');
            
            if (isLand) {
                $houseFields.hide();
                // Step 4 (Features) is usually house-centric, but keep it if there are land features?
                // For now, keep the existing logic of hiding Step 4 for Land.
                $('#step-4').hide().attr('data-land-skip', '1');
                $step2.show().removeAttr('data-land-skip');
            } else {
                $houseFields.show();
                $('#step-4').show().removeAttr('data-land-skip');
            }

            // Adjust progress markers visibility
            this.$progressSteps.each(function(index) {
                const stepNum = index + 1;
                const $step = $('#step-' + stepNum);
                $(this).toggle(!$step.is(':hidden'));
            });

            // If current step is hidden, move to next visible step
            if ($('#step-' + this.currentStep).is(':hidden')) {
                let next = this.currentStep;
                while ($('#step-' + next).is(':hidden') && next < this.totalSteps) {
                    next++;
                }
                if (!$('#step-' + next).is(':hidden')) {
                    this.currentStep = next;
                }
            }

            this.updateStep();
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
            // Populate all fields
            for (const key in data) {
                // Handle array fields (checkboxes) - try both with and without []
                let $field = $('[name="' + key + '"]');
                if (!$field.length) {
                    $field = $('[name="' + key + '[]"]');
                }
                
                if ($field.length) {
                    // Skip file inputs as they cannot be set programmatically
                    if ($field.attr('type') === 'file') {
                        continue;
                    }
                    
                    if ($field.attr('type') === 'checkbox') {
                        // Handle checkboxes - check/uncheck based on array values
                        const values = Array.isArray(data[key]) ? data[key] : [];
                        $field.each(function() {
                            const $checkbox = $(this);
                            const isChecked = values.includes($checkbox.val());
                            $checkbox.prop('checked', isChecked);
                        });
                    } else {
                        $field.val(data[key]);
                    }
                }
            }

            if (data.county) {
                this.fetchSubcounties(data.county, data.subcounty || '');
            }

            this.toggleSaleLeaseDetails();

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
            const $error = $('<div class="error-message">' + message + '</div>');
            this.$form.prepend($error);
            $('html, body').animate({ scrollTop: 0 }, 300);
            setTimeout(function() {
                $error.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        showSuccess: function(message) {
            const $success = $('<div class="success-message">' + message + '</div>');
            this.$form.prepend($success);
            $('html, body').animate({ scrollTop: 0 }, 300);
        },

        showSubmitSuccess: function(data) {
            const message = data && data.message ? data.message : 'Property submitted successfully!';
            const addUrl = data && data.add_new_url ? data.add_new_url : '';
            const viewUrl = data && data.view_url ? data.view_url : '';

            const $success = $(
                '<div class="success-message success-actions">' +
                    '<p>' + message + '</p>' +
                    '<div class="success-buttons">' +
                        (addUrl ? '<a class="btn btn-secondary" href="' + addUrl + '">Continue Adding</a>' : '') +
                        (viewUrl ? '<a class="btn btn-primary" href="' + viewUrl + '" target="_blank">View Property</a>' : '') +
                    '</div>' +
                '</div>'
            );

            this.$form.prepend($success);
            $('html, body').animate({ scrollTop: 0 }, 300);
            this.$btnSubmit.prop('disabled', false).text(malisafiSubmission.strings.submitProperty);
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
