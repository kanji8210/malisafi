/**
 * Single Page Registration Form JavaScript
 * 
 * @package MalisafiMLS
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const RegistrationForm = {
            selectedRole: '',
            currentStep: 1,

            init: function() {
                this.bindEvents();
                this.validateForm();
                this.initializePreselectedType();
                this.updateStepProgress();
            },
            
            initializePreselectedType: function() {
                // Check if dropdown has a preselected value
                const preselectedValue = $('#account_type_select').val();
                if (preselectedValue) {
                    // Set hidden field
                    $('#account_type_hidden').val(preselectedValue);
                    
                    // Map account type to role
                    const roleMapping = {
                        'client': 'malisafi_client',
                        'agent': 'malisafi_agent_basic',
                        'owner': 'malisafi_owner',
                        'developer': 'malisafi_developer',
                        'hunter': 'malisafi_client'
                    };
                    
                    this.selectedRole = roleMapping[preselectedValue] || 'malisafi_client';
                    $('#user_role').val(this.selectedRole);
                    
                    // Show agent fields if agent selected
                    if (preselectedValue === 'agent') {
                        $('.agent-fields').show();
                        $('.agent-required').prop('required', true);
                    }
                }
            },
            
            nextStep: function(e) {
                e.preventDefault();
                const nextStep = parseInt($(e.currentTarget).data('next'));
                
                // Validate current step before proceeding
                if (this.validateCurrentStep()) {
                    this.goToStep(nextStep);
                }
            },
            
            prevStep: function(e) {
                e.preventDefault();
                const prevStep = parseInt($(e.currentTarget).data('prev'));
                this.goToStep(prevStep);
            },
            
            goToStep: function(stepNumber) {
                // Hide all steps
                $('.form-step').hide();
                
                // Show target step
                $(`.form-step[data-step="${stepNumber}"]`).fadeIn(300);
                
                // Update current step
                this.currentStep = stepNumber;
                
                // Update progress indicator
                this.updateStepProgress();
                
                // Scroll to top
                $('html, body').animate({
                    scrollTop: $('.registration-header').offset().top - 50
                }, 300);
            },
            
            updateStepProgress: function() {
                $('.step-item').removeClass('active completed');
                
                $('.step-item').each((index, item) => {
                    const stepNum = parseInt($(item).data('step'));
                    if (stepNum < this.currentStep) {
                        $(item).addClass('completed');
                    } else if (stepNum === this.currentStep) {
                        $(item).addClass('active');
                    }
                });
            },
            
            validateCurrentStep: function() {
                const currentStepElement = $(`.form-step[data-step="${this.currentStep}"]`);
                const requiredFields = currentStepElement.find('input[required], select[required], textarea[required]');
                let isValid = true;
                
                requiredFields.each(function() {
                    if (!$(this).val() || !$(this)[0].checkValidity()) {
                        $(this).addClass('error');
                        isValid = false;
                    } else {
                        $(this).removeClass('error');
                    }
                });
                
                if (!isValid) {
                    alert('Please fill in all required fields before continuing.');
                }
                
                return isValid;
            },

            bindEvents: function() {
                // Step navigation
                $('.btn-next').on('click', this.nextStep.bind(this));
                $('.btn-prev').on('click', this.prevStep.bind(this));
                
                // Dropdown change - sync with hidden field and update role
                $('#account_type_select').on('change', this.handleDropdownChange.bind(this));

                // Form submission
                $('#malisafi-registration-form').on('submit', this.handleSubmit.bind(this));

                // Password toggle
                $('.toggle-password').on('click', this.togglePassword);

                // Password strength
                $('#password').on('input', this.checkPasswordStrength);

                // Password confirmation
                $('#password_confirm').on('input', this.checkPasswordMatch);

                // Real-time validation
                $('#email').on('blur', this.validateEmail);
                $('#username').on('blur', this.validateUsername);
                $('#phone').on('input', this.formatPhone);
                
                // Bio character count
                $('#agent_bio').on('input', this.updateCharCount);
                
                // Specialization validation
                $('.specialization-checkbox').on('change', this.validateSpecializations);

                // Enable/disable submit button based on validation
                $('input[required], select[required], textarea[required]').on('input change', this.validateForm.bind(this));
            },
            
            handleDropdownChange: function(e) {
                const selectedType = $(e.target).val();
                
                if (selectedType) {
                    // Update hidden field
                    $('#account_type_hidden').val(selectedType);
                    
                    // Map account type to role
                    const roleMapping = {
                        'client': 'malisafi_client',
                        'agent': 'malisafi_agent_basic',
                        'owner': 'malisafi_owner',
                        'developer': 'malisafi_developer',
                        'hunter': 'malisafi_client'
                    };
                    
                    this.selectedRole = roleMapping[selectedType] || 'malisafi_client';
                    $('#user_role').val(this.selectedRole);
                    
                    // Show/hide agent-specific fields
                    if (selectedType === 'agent') {
                        $('.agent-fields').slideDown(300);
                        $('.agent-required').prop('required', true);
                        $('.specialization-checkbox').prop('required', true);
                    } else {
                        $('.agent-fields').slideUp(300);
                        $('.agent-required').prop('required', false).val('');
                        $('.specialization-checkbox').prop('required', false).prop('checked', false);
                    }
                }
            },
            
            updateCharCount: function() {
                const text = $(this).val();
                const length = text.length;
                const $hint = $(this).siblings('.form-hint');
                $hint.text(`100-500 characters (${length}/500)`);
                
                if (length < 100) {
                    $(this).css('border-color', '#dc3545');
                } else if (length >= 100 && length <= 500) {
                    $(this).css('border-color', '#00c853');
                } else {
                    $(this).css('border-color', '#dc3545');
                }
            },
            
            validateSpecializations: function() {
                const checked = $('.specialization-checkbox:checked').length;
                if (checked > 0) {
                    $('.specialization-checkbox').prop('required', false);
                } else {
                    $('.specialization-checkbox').prop('required', true);
                }
            },

            validateForm: function() {
                const requiredInputs = $('#malisafi-registration-form input[required]');
                let isValid = true;

                requiredInputs.each(function() {
                    if ($(this).attr('type') === 'radio') {
                        const radioName = $(this).attr('name');
                        if (!$(`input[name="${radioName}"]:checked`).length) {
                            isValid = false;
                            return false;
                        }
                    } else if ($(this).attr('type') === 'checkbox') {
                        if (!$(this).is(':checked')) {
                            isValid = false;
                            return false;
                        }
                    } else {
                        if (!$(this).val() || !$(this)[0].validity.valid) {
                            isValid = false;
                            return false;
                        }
                    }
                });

                // Enable/disable submit button
                $('.btn-submit').prop('disabled', !isValid);

                return isValid;
            },

            togglePassword: function() {
                const input = $(this).siblings('input');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                
                // Update icon
                $(this).find('.eye-icon').text(type === 'password' ? '👁️' : '🙈');
            },

            checkPasswordStrength: function() {
                const password = $(this).val();
                const strengthContainer = $('.password-strength');
                
                if (password.length === 0) {
                    strengthContainer.removeClass('weak medium strong');
                    strengthContainer.find('.strength-text').text('');
                    return;
                }

                let strength = 0;
                
                // Length
                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;
                
                // Complexity
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;

                strengthContainer.removeClass('weak medium strong');
                
                if (strength <= 2) {
                    strengthContainer.addClass('weak');
                    strengthContainer.find('.strength-text').text('Weak password');
                } else if (strength <= 4) {
                    strengthContainer.addClass('medium');
                    strengthContainer.find('.strength-text').text('Medium strength');
                } else {
                    strengthContainer.addClass('strong');
                    strengthContainer.find('.strength-text').text('Strong password');
                }
            },

            checkPasswordMatch: function() {
                const password = $('#password').val();
                const confirm = $(this).val();
                
                if (confirm.length === 0) return;

                if (password !== confirm) {
                    $(this).css('border-color', '#dc3545');
                } else {
                    $(this).css('border-color', '#00c853');
                }
            },

            formatPhone: function() {
                let value = $(this).val().replace(/\D/g, '');
                
                // Remove leading zero if present
                if (value.startsWith('0')) {
                    value = value.substring(1);
                }
                
                // Limit to 9-10 digits
                value = value.substring(0, 10);
                
                $(this).val(value);
            },

            validateEmail: function() {
                const email = $(this).val();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (!emailRegex.test(email)) {
                    $(this).addClass('error');
                    return false;
                }
                
                // Check if email exists (AJAX)
                $.ajax({
                    url: malisafiRegistration.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'malisafi_check_email',
                        email: email,
                        nonce: malisafiRegistration.nonce
                    },
                    success: function(response) {
                        if (response.data && response.data.exists) {
                            $('#email').addClass('error');
                            RegistrationForm.showError('This email is already registered.');
                        } else {
                            $('#email').removeClass('error');
                        }
                    }
                });
                
                return true;
            },

            validateUsername: function() {
                const username = $(this).val();
                
                if (username.length < 4) {
                    $(this).addClass('error');
                    return false;
                }
                
                // Check if username exists (AJAX)
                $.ajax({
                    url: malisafiRegistration.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'malisafi_check_username',
                        username: username,
                        nonce: malisafiRegistration.nonce
                    },
                    success: function(response) {
                        if (response.data && response.data.exists) {
                            $('#username').addClass('error');
                            RegistrationForm.showError('This username is already taken.');
                        } else {
                            $('#username').removeClass('error');
                        }
                    }
                });
                
                return true;
            },

            handleSubmit: function(e) {
                e.preventDefault();

                // Final validation
                if (!this.validateForm()) {
                    this.showError('Please fill in all required fields correctly.');
                    return;
                }

                // Check password match
                const password = $('#password').val();
                const passwordConfirm = $('#password_confirm').val();
                
                if (password !== passwordConfirm) {
                    this.showError('Passwords do not match.');
                    return;
                }

                // Disable submit button
                $('.btn-submit').prop('disabled', true).html('<span class="spinner"></span> Creating account...');

                // Get form data
                const formData = {
                    action: 'malisafi_register_user',
                    nonce: $('#malisafi_registration_nonce').val(),
                    account_type: $('input[name="account_type"]:checked').val(),
                    user_role: $('#user_role').val(),
                    first_name: $('#first_name').val(),
                    last_name: $('#last_name').val(),
                    phone: '+254' + $('#phone').val(),
                    email: $('#email').val(),
                    username: $('#username').val(),
                    password: password,
                    agency_name: $('#agency_name').val(),
                    license_number: $('#license_number').val()
                };

                // Submit via AJAX
                $.ajax({
                    url: malisafiRegistration.ajaxUrl,
                    method: 'POST',
                    data: formData,
                    success: this.handleSuccess.bind(this),
                    error: this.handleError.bind(this)
                });
            },

            handleSuccess: function(response) {
                if (response.success) {
                    // Hide form
                    $('#malisafi-registration-form').fadeOut(300, function() {
                        // Show success message
                        $('.registration-success').fadeIn(300);
                        
                        // Redirect after 2 seconds
                        setTimeout(function() {
                            window.location.href = response.data.redirect || malisafiRegistration.dashboardUrl;
                        }, 2000);
                    });
                } else {
                    this.showError(response.data.message || 'Registration failed. Please try again.');
                    $('.btn-submit').prop('disabled', false).html('Create My Account ✨');
                }
            },

            handleError: function(xhr, status, error) {
                console.error('Registration error:', error);
                this.showError('An error occurred. Please try again.');
                $('.btn-submit').prop('disabled', false).html('Create My Account ✨');
            },

            showError: function(message) {
                const errorContainer = $('.registration-errors');
                errorContainer.html(`<p><strong>Error:</strong> ${message}</p>`).fadeIn(300);
                
                // Hide after 5 seconds
                setTimeout(function() {
                    errorContainer.fadeOut(300);
                }, 5000);

                // Scroll to error
                $('html, body').animate({
                    scrollTop: errorContainer.offset().top - 100
                }, 300);
            }
        };

        // Initialize the form
        RegistrationForm.init();
    });

})(jQuery);
