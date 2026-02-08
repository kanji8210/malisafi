/**
 * Property Submission Wizard JavaScript
 * Handles multi-step form, auto-save, image upload, and validation
 */

(function($) {
    'use strict';

    const PropertySubmission = {
        currentStep: 5, // Start on images step for debugging
        totalSteps: 6,
        propertyId: 0,
        formData: {},
        autoSaveTimeout: null,
        uploadedImages: [],
        initialized: false,

        init: function() {
            console.log('PropertySubmission.init called');
            if (this.initialized) {
                console.log('Already initialized, skipping');
                return;
            }
            
            if (!$('#property-submission-form').length) {
                console.log('Property submission form not found, skipping initialization');
                return;
            }
            
            this.propertyId = $('#property_id').val() || 0;
            console.log('Property ID:', this.propertyId);
            this.cacheElements();
            this.bindEvents();
            this.initImageUpload();
            this.toggleSaleLeaseDetails();
            
            // Load draft if editing
            if (this.propertyId) {
                this.loadDraft();
            }
            
            this.initialized = true;
            console.log('PropertySubmission initialized successfully');
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
            this.$dropzone = $('#dropzone');
            this.$gallery = $('#image-gallery');
            this.$featuredDropzone = $('#featured-dropzone');
            this.$featuredPreview = $('#featured-preview');
            this.$featuredInput = $('#featured-file-input');
            this.$featuredId = $('#featured_image_id');
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
                console.log('Save draft button clicked - handler called');
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

            // Image browse button
            $('.btn-browse-images').on('click', function() {
                console.log('Browse images button clicked');
                $('#image-file-input').click();
            });

            // Featured image browse button
            $('.btn-browse-featured').on('click', function() {
                console.log('Browse featured image button clicked');
                $('#featured-file-input').click();
            });

            $('.btn-browse-featured').on('click', function() {
                console.log('Browse featured button clicked');
                $('#featured-file-input').click();
            });

            // File input change
            $('#image-file-input').on('change', function(e) {
                console.log('Image file input changed, files:', e.target.files);
                self.handleFileSelect(e.target.files);
            });

            $('#featured-file-input').on('change', function(e) {
                console.log('Featured file input changed, files:', e.target.files);
                self.handleFeaturedFileSelect(e.target.files);
            });

            $('.btn-remove-featured').on('click', function() {
                self.clearFeaturedImage();
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
                this.updateStep();
                this.saveStep();
            }
        },

        prevStep: function() {
            if (this.currentStep > 1) {
                this.currentStep--;
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
                if (!this.$featuredId.val()) {
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
                self.saveStep();
            }, 2000); // Save 2 seconds after last change
        },

        saveStep: function() {
            const self = this;
            const stepData = this.getStepData(this.currentStep);

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
                    console.log('Save step AJAX response:', response);
                    if (response.success) {
                        if (response.data.property_id) {
                            self.propertyId = response.data.property_id;
                            $('#property_id').val(self.propertyId);
                        }
                        self.showAutoSave('saved');
                    } else {
                        console.error('Save step failed:', response);
                        self.showAutoSave('error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Save step AJAX error:', status, error, xhr);
                    self.showAutoSave('error');
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
                const name = $field.attr('name');
                
                if (!name) return;

                if ($field.attr('type') === 'checkbox') {
                    if (!data[name]) data[name] = [];
                    if ($field.is(':checked')) {
                        data[name].push($field.val());
                    }
                } else {
                    data[name] = $field.val();
                }
            });

            return data;
        },

        showAutoSave: function(status) {
            const $indicator = this.$autoSave;
            $indicator.removeClass('saving saved error').addClass(status + ' show');

            const messages = {
                saving: malisafiSubmission.strings.saving,
                saved: malisafiSubmission.strings.saved,
                error: malisafiSubmission.strings.error
            };

            $indicator.find('.status-text').text(messages[status] || '');

            if (status === 'saved' || status === 'error') {
                setTimeout(function() {
                    $indicator.removeClass('show');
                }, 2000);
            }
        },

        // Image Upload Functions
        initImageUpload: function() {
            console.log('initImageUpload called, uploadsEnabled:', malisafiSubmission ? malisafiSubmission.uploadsEnabled : 'malisafiSubmission not defined');
            if (malisafiSubmission.uploadsEnabled !== true) {
                console.log('Uploads disabled, hiding upload UI');
                // Hide upload UI and show a notice if a placeholder area exists
                $('#dropzone').hide();
                $('#featured-dropzone').hide();
                $('#featured-preview').hide();
                $('.upload-progress').hide();
                $('#image-gallery').hide();
                return;
            }
            console.log('Uploads enabled, setting up image upload handlers');
            const self = this;

            // Drag and drop
            this.$dropzone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            this.$dropzone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            this.$dropzone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                const files = e.originalEvent.dataTransfer.files;
                self.handleFileSelect(files);
            });

            this.$featuredDropzone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            this.$featuredDropzone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            this.$featuredDropzone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                const files = e.originalEvent.dataTransfer.files;
                self.handleFeaturedFileSelect(files);
            });

            // Make gallery sortable
            this.$gallery.sortable({
                update: function() {
                    // Enforce main badge and ensure not exceeding 15 items
                    const $items = self.$gallery.find('.gallery-item');
                    if ($items.length > 15) {
                        $items.slice(15).remove();
                        self.uploadedImages = self.uploadedImages.slice(0, 15);
                        self.showError('Maximum 15 images allowed. Extra images were ignored.');
                    }

                    self.updateImageOrder();
                }
            });
        },

        handleFeaturedFileSelect: function(files) {
            if (!files || files.length === 0) return;

            const selectedFile = Array.from(files).slice(0, 1);
            const self = this;

            this.validateImageFiles(selectedFile, { minWidth: 1600, minHeight: 900, landscape: true })
                .then(function(validFiles) {
                    if (!validFiles.length) {
                        self.showError('Featured image should be landscape and at least 1600x900.');
                        return;
                    }
                    const formData = new FormData();
                    formData.append('action', 'malisafi_upload_featured_image');
                    formData.append('nonce', malisafiSubmission.uploadNonce);
                    formData.append('property_id', self.propertyId || 0);
                    formData.append('image', validFiles[0]);
                    self.uploadFeaturedImage(formData);
                })
                .catch(function() {
                    self.showError('Could not validate featured image.');
                });
        },

        handleFileSelect: function(files) {
            console.log('Files selected:', files);
            if (!files || files.length === 0) return;

            const remainingSlots = Math.max(0, 15 - this.uploadedImages.length);
            if (remainingSlots === 0) {
                this.showError('You can upload up to 15 images per listing.');
                return;
            }

            const inputFiles = Array.from(files).slice(0, remainingSlots);
            const self = this;
            this.validateImageFiles(inputFiles, { minWidth: 1200, minHeight: 800, landscape: true })
                .then(function(validFiles) {
                    if (!validFiles.length) {
                        self.showError('No valid images to upload. Use landscape images at least 1200x800.');
                        return;
                    }
                    const formData = new FormData();
                    formData.append('action', 'malisafi_upload_property_images');
                    formData.append('nonce', malisafiSubmission.uploadNonce);
                    validFiles.forEach(function(file) { formData.append('images[]', file); });
                    self.uploadImages(formData);
                })
                .catch(function() {
                    self.showError('Could not validate selected images.');
                });
        },

       validateImageFiles: function(files, opts) {
           const minWidth = opts && opts.minWidth ? opts.minWidth : 0;
           const minHeight = opts && opts.minHeight ? opts.minHeight : 0;
           const requireLandscape = opts && opts.landscape === true;

           const checks = files.map(function(file) {
               return new Promise(function(resolve) {
                   const img = new Image();
                   const url = URL.createObjectURL(file);
                   img.onload = function() {
                       const isLandscape = img.width > img.height;
                       const okLandscape = !requireLandscape || isLandscape;
                       const okSize = img.width >= minWidth && img.height >= minHeight;
                       URL.revokeObjectURL(url);
                       resolve(okLandscape && okSize ? file : null);
                   };
                   img.onerror = function() {
                       URL.revokeObjectURL(url);
                       resolve(null);
                   };
                   img.src = url;
               });
           });
           return Promise.all(checks).then(function(results) { return results.filter(Boolean); });
       },

        uploadImages: function(formData) {
            const self = this;

            $('.upload-progress').show();
            $('.progress-fill').css('width', '0%');

            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            $('.progress-fill').css('width', percent + '%');
                            $('.progress-text').text(percent + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    console.log('Image upload response:', response);
                    $('.upload-progress').hide();
                    
                    if (response.success && response.data.images) {
                        response.data.images.forEach(function(image) {
                            self.addImageToGallery(image);
                        });
                        self.updateImageOrder();
                    } else {
                        self.showError(response.data.message || malisafiSubmission.strings.uploadError);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Image upload error:', status, error, xhr);
                    $('.upload-progress').hide();
                    self.showError(malisafiSubmission.strings.uploadError);
                }
            });
        },

        uploadFeaturedImage: function(formData) {
            const self = this;

            this.showAutoSave('saving');

            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success && response.data.image) {
                        self.setFeaturedImage(response.data.image);
                        self.showAutoSave('saved');
                    } else {
                        self.showError(response.data.message || malisafiSubmission.strings.uploadError);
                        self.showAutoSave('error');
                    }
                },
                error: function() {
                    self.showError(malisafiSubmission.strings.uploadError);
                    self.showAutoSave('error');
                }
            });
        },

        setFeaturedImage: function(image) {
            if (!image || !image.url || !image.id) {
                return;
            }

            this.$featuredId.val(image.id);
            this.$featuredPreview.find('img').attr('src', image.url);
            this.$featuredPreview.show();
            $('#featured-dropzone').removeClass('error').hide();
        },

        clearFeaturedImage: function() {
            const self = this;

            if (!this.$featuredId.val()) {
                return;
            }

            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_clear_featured_image',
                    nonce: malisafiSubmission.nonce,
                    property_id: this.propertyId
                },
                success: function(response) {
                    if (response.success) {
                        self.$featuredId.val('');
                        self.$featuredPreview.hide();
                        self.$featuredPreview.find('img').attr('src', '');
                        $('#featured-dropzone').show();
                    }
                }
            });
        },

        addImageToGallery: function(image) {
            // Enforce cap at 15 items
            if (this.uploadedImages.length >= 15) {
                this.showError('Maximum 15 images allowed. Extra images were ignored.');
                return;
            }
            const isFirst = this.uploadedImages.length === 0;
            this.uploadedImages.push(image.id);

            const $item = $('<div>')
                .addClass('gallery-item')
                .attr('data-id', image.id)
                .html(
                    '<img src="' + image.url + '" alt="">' +
                    '<button type="button" class="delete-btn" data-id="' + image.id + '">×</button>' +
                    (isFirst ? '<span class="main-badge">Gallery Cover</span>' : '')
                );

            this.$gallery.append($item);

            // Bind delete
            $item.find('.delete-btn').on('click', function() {
                if (confirm(malisafiSubmission.strings.confirmDelete)) {
                    PropertySubmission.deleteImage(image.id);
                }
            });
        },

        deleteImage: function(imageId) {
            const self = this;

            $.ajax({
                url: malisafiSubmission.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_delete_property_image',
                    nonce: malisafiSubmission.nonce,
                    property_id: this.propertyId,
                    image_id: imageId
                },
                success: function(response) {
                    if (response.success) {
                        $('.gallery-item[data-id="' + imageId + '"]').fadeOut(function() {
                            $(this).remove();
                            self.uploadedImages = self.uploadedImages.filter(id => id !== imageId);
                            
                            // Update main badge
                            if (self.uploadedImages.length > 0) {
                                self.$gallery.find('.main-badge').remove();
                                self.$gallery.find('.gallery-item').first().append('<span class="main-badge">Main Photo</span>');
                            }
                        });
                    }
                }
            });
        },

        updateImageOrder: function() {
            const order = [];
            this.$gallery.find('.gallery-item').each(function() {
                order.push($(this).attr('data-id'));
            });

            this.uploadedImages = order;

            $('#gallery_ids').val(order.join(','));

            // Update main badge
            this.$gallery.find('.main-badge').remove();
            if (order.length > 0) {
                this.$gallery.find('.gallery-item').first().append('<span class="main-badge">Gallery Cover</span>');
            }

            // Save order
            if (this.propertyId && order.length > 0) {
                $.ajax({
                    url: malisafiSubmission.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'malisafi_reorder_property_images',
                        nonce: malisafiSubmission.nonce,
                        property_id: this.propertyId,
                        order: order
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

            if (this.uploadedImages.length > 0) {
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
            console.log('Save draft button clicked');
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

        submitProperty: function() {
            const self = this;

            if (!this.validateStep(this.currentStep)) {
                return;
            }

            if (malisafiSubmission.uploadsEnabled !== true) {
                // Skip image requirement when uploads are disabled
            } else if (!this.$featuredId.val()) {
                this.showError('Please upload a featured image');
                return;
            }

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
                    } else {
                        self.showError(response.data.message);
                        self.$btnSubmit.prop('disabled', false).text(malisafiSubmission.strings.submitProperty);
                    }
                },
                error: function() {
                    self.showError('An error occurred. Please try again.');
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
                    if (response.success && response.data.data) {
                        self.populateForm(response.data.data);
                    }
                }
            });
        },

        populateForm: function(data) {
            // Populate all fields
            for (const key in data) {
                const $field = $('[name="' + key + '"]');
                if ($field.length) {
                    if ($field.attr('type') === 'checkbox') {
                        if (Array.isArray(data[key])) {
                            data[key].forEach(function(val) {
                                $('[name="' + key + '"][value="' + val + '"]').prop('checked', true);
                            });
                        }
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

            if (Array.isArray(data.gallery_images)) {
                this.renderGalleryImages(data.gallery_images);
            }
        },

        renderGalleryImages: function(images) {
            if (!Array.isArray(images)) {
                return;
            }

            const self = this;
            this.$gallery.empty();
            this.uploadedImages = [];

            images.forEach(function(image, index) {
                if (!image || !image.id || !image.url) {
                    return;
                }
                self.addImageToGallery(image);
                if (index === 0) {
                    self.$gallery.find('.gallery-item').first().append('<span class="main-badge">Gallery Cover</span>');
                }
            });

            this.updateImageOrder();
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
        console.log('Document ready, checking for property submission form');
        PropertySubmission.init();
        
        // Also check periodically in case form is loaded dynamically
        let checkCount = 0;
        const checkInterval = setInterval(function() {
            if ($('#property-submission-form').length && !PropertySubmission.initialized) {
                console.log('Property submission form found (delayed), initializing...');
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
