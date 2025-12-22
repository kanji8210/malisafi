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
                this.initializeAccountTypeCards();
                this.updateStepProgress();
                this.updateNavigationButtons();
            },
            
            initializeAccountTypeCards: function() {
                // Check if any radio is pre-selected
                const preselectedRadio = $('input[name="account_type"]:checked');
                if (preselectedRadio.length > 0) {
                    const selectedType = preselectedRadio.val();
                    preselectedRadio.closest('.account-card').addClass('selected');
                    this.handleAccountTypeSelect(selectedType);
                    $('.btn-next').prop('disabled', false);
                }
            },
            
            goToStep: function(stepNumber) {
                // Validate step number
                if (stepNumber < 1 || stepNumber > 3) {
                    return;
                }
                
                // Hide current step with fade out
                const $currentStep = $(`.form-step[data-step="${this.currentStep}"]`);
                $currentStep.fadeOut(200, () => {
                    // After fade out, hide all steps
                    $('.form-step').hide().removeClass('active-step');
                    
                    // Show only target step with fade in
                    const $targetStep = $(`.form-step[data-step="${stepNumber}"]`);
                    $targetStep.addClass('active-step').fadeIn(300);
                    
                    // Update current step
                    this.currentStep = stepNumber;
                    
                    // Update progress indicator and bar
                    this.updateStepProgress();
                    this.updateProgressBar();
                    this.updateNavigationButtons();
                    
                    // Focus on first input of new step (no scrolling)
                    setTimeout(() => {
                        $targetStep.find('input:visible, select:visible').first().focus();
                    }, 350);
                });
            },
            
            updateNavigationButtons: function() {
                const $prevBtn = $('.btn-prev');
                const $nextBtn = $('.btn-next');
                const $submitBtn = $('.btn-submit');
                
                // Show/hide buttons based on current step
                if (this.currentStep === 1) {
                    $prevBtn.hide();
                    $nextBtn.show();
                    $submitBtn.hide();
                } else if (this.currentStep === 2) {
                    $prevBtn.show();
                    $nextBtn.show();
                    $submitBtn.hide();
                } else if (this.currentStep === 3) {
                    $prevBtn.show();
                    $nextBtn.hide();
                    $submitBtn.show();
                }
            },
            
            updateProgressBar: function() {
                const progressPercent = (this.currentStep / 3) * 100;
                $('.progress-bar').css('width', progressPercent + '%');
                $('.step-counter').text(`Step ${this.currentStep} of 3`);
            },
            
            updateStepProgress: function() {
                $('.step-item').removeClass('active completed future');
                
                $('.step-item').each((index, item) => {
                    const stepNum = parseInt($(item).data('step'));
                    if (stepNum < this.currentStep) {
                        $(item).addClass('completed');
                    } else if (stepNum === this.currentStep) {
                        $(item).addClass('active');
                    } else {
                        $(item).addClass('future');
                    }
                });
                
                // Update navigation button visibility
                this.updateNavigationButtons();
            },
            
            updateNavigationButtons: function() {
                const $prevBtn = $('.btn-prev');
                const $nextBtn = $('.btn-next');
                const $submitBtn = $('.btn-submit');
                
                // Show/hide previous button
                if (this.currentStep === 1) {
                    $prevBtn.hide();
                } else {
                    $prevBtn.show();
                }
                
                // Show/hide next vs submit button
                if (this.currentStep === 3) {
                    $nextBtn.hide();
                    $submitBtn.show();
                } else {
                    $nextBtn.show();
                    $submitBtn.hide();
                }
            },
            
            validateCurrentStep: function() {
                const currentStepElement = $(`.form-step[data-step="${this.currentStep}"]`);
                const requiredFields = currentStepElement.find('input[required]:visible, select[required]:visible, textarea[required]:visible');
                let isValid = true;
                let errorMessages = [];
                
                // Clear previous errors
                requiredFields.removeClass('error');
                $('.error-message').remove();
                
                requiredFields.each(function() {
                    const $field = $(this);
                    const fieldName = $field.attr('name');
                    const fieldLabel = $field.closest('.form-group').find('label').text().replace('*', '').trim();
                    
                    if (!$field.val() || !$field[0].checkValidity()) {
                        $field.addClass('error');
                        errorMessages.push(fieldLabel || fieldName);
                        isValid = false;
                    }
                });
                
                // Step-specific validation
                if (this.currentStep === 1) {
                    // Account type must be selected
                    const accountType = $('input[name="account_type"]:checked').val();
                    if (!accountType) {
                        isValid = false;
                        errorMessages.push('Account Type');
                    }
                } else if (this.currentStep === 2) {
                    // Agent-specific validation
                    const accountType = $('input[name="account_type"]:checked').val();
                    if (accountType === 'agent') {
                        // Check specializations
                        if ($('.specialization-checkbox:checked').length === 0) {
                            isValid = false;
                            errorMessages.push('At least one specialization');
                        }
                        
                        // Check required agent fields
                        const agencyName = $('#agency_name').val();
                        const licenseNumber = $('#license_number').val();
                        const experience = $('#years_experience').val();
                        
                        if (!agencyName) {
                            isValid = false;
                            $('#agency_name').addClass('error');
                            errorMessages.push('Agency/Company Name');
                        }
                        if (!licenseNumber) {
                            isValid = false;
                            $('#license_number').addClass('error');
                            errorMessages.push('License Number');
                        }
                        if (!experience) {
                            isValid = false;
                            $('#years_experience').addClass('error');
                            errorMessages.push('Experience Level');
                        }
                    }
                } else if (this.currentStep === 3) {
                    // Password validation
                    const password = $('#password').val();
                    const passwordConfirm = $('#password_confirm').val();
                    
                    // Simple password validation - just check length
                    if (password.length < 8) {
                        isValid = false;
                        $('#password').addClass('error');
                        errorMessages.push('Password must be at least 8 characters');
                    }
                    
                    if (password !== passwordConfirm) {
                        isValid = false;
                        $('#password_confirm').addClass('error');
                        errorMessages.push('Passwords must match');
                    }
                    
                    // Check terms agreement
                    if (!$('#terms_checkbox').is(':checked')) {
                        isValid = false;
                        errorMessages.push('Terms and Conditions agreement');
                    }
                }
                
                if (!isValid) {
                    // Display error message
                    const errorHtml = `
                        <div class="error-message" style="background:#fee; border-left:4px solid #dc2626; padding:15px; margin:20px 0; border-radius:8px;">
                            <strong>⚠️ Please complete the following:</strong>
                            <ul style="margin:10px 0 0 20px;">
                                ${errorMessages.map(msg => `<li>${msg}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                    currentStepElement.prepend(errorHtml);
                    
                    // Scroll to error message
                    $('html, body').animate({
                        scrollTop: $('.error-message').offset().top - 100
                    }, 300);
                }
                
                return isValid;
            },

            bindEvents: function() {
                // Step navigation
                $('.btn-next').on('click', (e) => {
                    e.preventDefault();
                    if (this.validateCurrentStep()) {
                        this.goToStep(this.currentStep + 1);
                    }
                });
                
                $('.btn-prev').on('click', (e) => {
                    e.preventDefault();
                    this.goToStep(this.currentStep - 1);
                });
                
                // Account type card selection
                $('.account-card').on('click', this.handleAccountCardClick.bind(this));
                $('input[name="account_type"]').on('change', this.handleAccountTypeChange.bind(this));

                // Form submission
                $('#malisafi-registration-form').on('submit', this.handleSubmit.bind(this));

                // Password toggle
                $('.toggle-password').on('click', this.togglePassword);

                // Password strength and requirements
                $('#password').on('input', this.checkPasswordStrength.bind(this));
                $('#password').on('input', this.checkPasswordRequirements.bind(this));

                // Password confirmation
                $('#password_confirm').on('input', this.checkPasswordMatch);

                // Real-time validation
                $('#email').on('blur', this.validateEmail);
                $('#username').on('blur', this.validateUsername);
                $('#phone').on('input', this.formatPhone);

                // Enable/disable submit button based on validation
                $('input[required], select[required], textarea[required]').on('input change', this.validateForm.bind(this));
            },

            handleAccountCardClick: function(e) {
                const $card = $(e.currentTarget);
                const $radio = $card.find('input[type="radio"]');
                
                // Remove selected class from all cards
                $('.account-card').removeClass('selected');
                
                // Add selected class to clicked card
                $card.addClass('selected');
                
                // Check the radio button
                $radio.prop('checked', true).trigger('change');
            },
            
            handleAccountTypeChange: function(e) {
                const selectedType = $(e.target).val();
                this.handleAccountTypeSelect(selectedType);
                
                // Enable Next button
                $('.btn-next').prop('disabled', false);
            },
            
            handleAccountTypeSelect: function(type) {
                // Map account type to role
                const roleMapping = {
                    'client': 'malisafi_client',
                    'agent': 'malisafi_agent_basic',
                    'owner': 'malisafi_owner',
                    'developer': 'malisafi_developer'
                };
                
                this.selectedRole = roleMapping[type] || 'malisafi_client';
                $('#user_role').val(this.selectedRole);
                
                // Show/hide agent-specific fields
                if (type === 'agent') {
                    $('.agent-fields').slideDown(300);
                    $('.agent-required').prop('required', true);
                    $('.specialization-checkbox').prop('required', true);
                } else {
                    $('.agent-fields').slideUp(300);
                    $('.agent-required').prop('required', false).val('');
                    $('.specialization-checkbox').prop('required', false).prop('checked', false);
                }
            },
            
            checkPasswordRequirements: function() {
                const password = $('#password').val();
                
                // Simple password strength indicator
                let strength = 0;
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                // Update strength bar
                const $strengthBar = $('.password-strength-bar');
                $strengthBar.removeClass('weak medium strong');
                if (strength <= 2) {
                    $strengthBar.addClass('weak');
                } else if (strength === 3 || strength === 4) {
                    $strengthBar.addClass('medium');
                } else {
                    $strengthBar.addClass('strong');
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
