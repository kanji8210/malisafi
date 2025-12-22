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

            init: function() {
                this.bindEvents();
                this.validateForm();
                this.initializePreselectedType();
            },
            
            initializePreselectedType: function() {
                // Check if dropdown has a preselected value
                const preselectedValue = $('#account_type_select').val();
                if (preselectedValue) {
                    // Show sections immediately for preselected type
                    $('#personal-info-section').show();
                    $('#credentials-section').show();
                    
                    // Trigger the dropdown change to sync with radio buttons
                    $('#account_type_select').trigger('change');
                    
                    // Also check the corresponding radio button
                    $(`input[name="account_type"][value="${preselectedValue}"]`).prop('checked', true).trigger('change');
                }
            },

            bindEvents: function() {
                // Dropdown change - sync with radio buttons
                $('#account_type_select').on('change', this.handleDropdownChange.bind(this));
                
                // Account type selection (radio buttons)
                $('input[name="account_type"]').on('change', this.handleAccountTypeChange.bind(this));

                // Account type quick links
                $('.account-type-link').on('click', this.handleAccountTypeLink.bind(this));

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
                    // Uncheck all radio buttons first
                    $('input[name="account_type"]').prop('checked', false);
                    $('.account-type-card').removeClass('selected');
                    
                    // Check the corresponding radio button
                    const $radio = $(`input[name="account_type"][value="${selectedType}"]`);
                    $radio.prop('checked', true);
                    $radio.closest('.account-type-card').addClass('selected');
                    
                    // Trigger the radio change event to show/hide agent fields
                    $radio.trigger('change');
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

            handleAccountTypeLink: function(e) {
                e.preventDefault();
                const targetType = $(e.currentTarget).data('type');
                
                // Uncheck all account types
                $('input[name="account_type"]').prop('checked', false);
                $('.account-type-card').removeClass('selected');
                
                // Select the target type
                $(`input[name="account_type"][value="${targetType}"]`).prop('checked', true).trigger('change');
                
                // Scroll to account type section
                $('html, body').animate({
                    scrollTop: $('.account-type-grid').offset().top - 100
                }, 500);
            },

            handleAccountTypeChange: function(e) {
                const accountType = $(e.target).val();
                const roleMapping = {
                    'client': 'malisafi_client',
                    'agent': 'malisafi_agent_basic',
                    'owner': 'malisafi_owner',
                    'developer': 'malisafi_developer',
                    'hunter': 'malisafi_client'
                };

                this.selectedRole = roleMapping[accountType] || 'malisafi_client';
                $('#user_role').val(this.selectedRole);

                // Add visual feedback
                $('.account-type-card').removeClass('selected');
                $(e.target).closest('.account-type-card').addClass('selected');
                
                // Sync dropdown with radio selection
                $('#account_type_select').val(accountType);

                // Show personal info and credentials sections after type selection
                $('#personal-info-section').slideDown(400);
                $('#credentials-section').slideDown(400);

                // Show/hide agent registration notice
                if (accountType === 'agent') {
                    $('.agent-registration-notice').slideDown(400);
                    $('.agent-fields').slideDown(300);
                    
                    // Make agent fields required
                    $('.agent-required').prop('required', true);
                    
                    // At least one specialization must be checked
                    $('.specialization-checkbox').prop('required', true);
                } else {
                    $('.agent-registration-notice').slideUp(400);
                    $('.agent-fields').slideUp(300);
                    
                    // Remove required from agent fields
                    $('.agent-required').prop('required', false).val('');
                    $('.specialization-checkbox').prop('required', false).prop('checked', false);
                }

                // Validate form
                this.validateForm();
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
