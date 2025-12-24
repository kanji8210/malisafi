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
                this.initializeAccountTypeCards();
                this.updateStepProgress();
                this.updateNavigationButtons();
                // Initial validation only for current step
                this.validateForm();
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
                    $nextBtn.show().prop('disabled', true); // Disabled until account type selected
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
                
                // Trigger validation to update button states (but not on initial load)
                if (this.currentStep > 1 || $('input[name="account_type"]:checked').length > 0) {
                    this.validateForm();
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
                // Exclude specialization checkboxes from general required validation (handled separately)
                const requiredFields = currentStepElement.find('input[required]:visible:not(.specialization-checkbox), select[required]:visible, textarea[required]:visible');
                let isValid = true;
                let errorMessages = [];
                
                // Clear ALL previous errors (field-level AND error message box)
                requiredFields.removeClass('error');
                $('.checkbox-group-inline').removeClass('error');
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
                            $('.checkbox-group-inline').addClass('error');
                            errorMessages.push('Please select at least one specialization');
                        } else {
                            $('.checkbox-group-inline').removeClass('error');
                        }
                        
                        // Check required agent fields
                        const agencyName = $('#agency_name').val();
                        const licenseNumber = $('#license_number').val();
                        const experience = $('#years_experience').val();
                        const county = $('#agent_county').val();
                        const city = $('#city').val();
                        const address = $('#business_address').val();
                        const nationalId = $('#national_id').val();
                        const bio = $('#agent_bio').val();
                        
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
                            errorMessages.push('Years of Experience');
                        }
                        if (!county) {
                            isValid = false;
                            $('#agent_county').addClass('error');
                            errorMessages.push('Operating County');
                        }
                        if (!city) {
                            isValid = false;
                            $('#city').addClass('error');
                            errorMessages.push('City/Town');
                        }
                        if (!address) {
                            isValid = false;
                            $('#business_address').addClass('error');
                            errorMessages.push('Business Address');
                        }
                        if (!nationalId) {
                            isValid = false;
                            $('#national_id').addClass('error');
                            errorMessages.push('National ID Number');
                        }
                        if (!bio || bio.length < 100) {
                            isValid = false;
                            $('#agent_bio').addClass('error');
                            errorMessages.push('Professional Bio (minimum 100 characters)');
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
                    // Display error message with close button
                    const errorHtml = `
                        <div class="error-message" style="background:#fee; border-left:4px solid #dc2626; padding:15px; margin:20px 0; border-radius:8px; position:relative;">
                            <button type="button" class="close-error" style="position:absolute; top:10px; right:10px; background:transparent; border:none; font-size:20px; cursor:pointer; color:#dc2626; line-height:1; padding:0; width:24px; height:24px;">&times;</button>
                            <strong>⚠️ Please complete the following:</strong>
                            <ul style="margin:10px 0 0 20px;">
                                ${errorMessages.map(msg => `<li>${msg}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                    currentStepElement.prepend(errorHtml);
                    
                    // Add close button handler
                    $('.close-error').on('click', function() {
                        $(this).closest('.error-message').fadeOut(300, function() {
                            $(this).remove();
                        });
                    });
                    
                    // Don't auto-remove errors - user must close manually
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

                // Password strength and requirements (only if password field exists)
                if ($('#password').length > 0) {
                    $('#password').on('input', this.checkPasswordStrength.bind(this));
                    $('#password').on('input', this.checkPasswordRequirements.bind(this));
                }

                // Password confirmation
                $('#password_confirm').on('input', this.checkPasswordMatch);

                // Real-time validation
                $('#email').on('blur', this.validateEmail);
                $('#username').on('blur', this.validateUsername);
                $('#phone').on('input', this.formatPhone);
                
                // Specialization checkbox validation
                $('.specialization-checkbox').on('change', () => {
                    if ($('.specialization-checkbox:checked').length > 0) {
                        $('.checkbox-group-inline').removeClass('error');
                    }
                    // Re-validate form when specializations change
                    this.validateForm();
                });
                
                // Bio character counter
                const self = this; // Save reference to this
                $('#agent_bio').on('input', function() {
                    const text = $(this).val();
                    const length = text.trim().length; // Use trimmed length for validation
                    const counter = $('#bio-counter');
                    const $formGroup = $(this).closest('.form-group');
                    
                    if (length < 100) {
                        counter.text(`${length} / 100 characters minimum`).css('color', '#dc2626');
                        $(this).addClass('error');
                        $formGroup.addClass('has-error');
                    } else {
                        counter.text(`${length} characters`).css('color', '#00c853');
                        $(this).removeClass('error');
                        $formGroup.removeClass('has-error');
                    }
                    
                    // Trigger validation using saved reference
                    self.validateForm();
                });

                // Enable/disable submit button based on validation (with debounce)
                let validationTimeout;
                $('input[required], select[required], textarea[required]').on('input change', () => {
                    clearTimeout(validationTimeout);
                    validationTimeout = setTimeout(() => {
                        this.validateForm();
                    }, 300);
                });
                
                // Real-time field validation with visual feedback
                $('input[required], select[required], textarea[required]').on('blur', function() {
                    const $field = $(this);
                    
                    // Skip if field is hidden
                    if (!$field.is(':visible')) return;
                    
                    // Skip specialization checkboxes (handled separately)
                    if ($field.hasClass('specialization-checkbox')) return;
                    
                    // Special handling for select elements
                    if ($field.is('select')) {
                        if ($field.val() && $field.val() !== '') {
                            $field.removeClass('error');
                        } else {
                            $field.addClass('error');
                        }
                    } else {
                        // Standard validation for other fields
                        if ($field.val() && $field[0].validity.valid) {
                            $field.removeClass('error');
                        } else {
                            $field.addClass('error');
                        }
                    }
                });
                
                // Also trigger validation on select change
                $('select[required]').on('change', function() {
                    const $field = $(this);
                    if ($field.val() && $field.val() !== '') {
                        $field.removeClass('error');
                    }
                });
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
                    // Mark agent fields as required
                    $('.agent-required').each(function() {
                        $(this).prop('required', true);
                    });
                    // Note: Specialization checkboxes are NOT marked as required 
                    // because HTML5 doesn't support required on checkbox groups well
                    // We validate them manually in validateForm()
                    $('#agent_bio').prop('required', true);
                } else {
                    $('.agent-fields').slideUp(300);
                    // Remove required from agent fields and clear values
                    $('.agent-required').each(function() {
                        $(this).prop('required', false).val('').removeClass('error');
                    });
                    // Clear specializations
                    $('.specialization-checkbox').prop('checked', false).removeClass('error');
                    $('.checkbox-group-inline').removeClass('error');
                    // Clear bio
                    $('#agent_bio').prop('required', false).val('').removeClass('error');
                }
                
                // Re-validate form after showing/hiding fields
                setTimeout(() => {
                    this.validateForm();
                }, 350);
            },
            
            checkPasswordRequirements: function() {
                const password = $('#password').val();
                
                // Guard against undefined
                if (typeof password !== 'string') {
                    return;
                }
                
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
                // Clear previous error messages
                $('.validation-error-box').remove();
                $('.form-group').removeClass('has-error');
                
                // Special handling for Step 1 - only check if account type is selected
                if (this.currentStep === 1) {
                    const accountType = $('input[name="account_type"]:checked').val();
                    const isValid = !!accountType;
                    console.log('Step 1 validation:', { accountType, isValid });
                    $('.btn-next').prop('disabled', !isValid);
                    if (!isValid) {
                        $('.btn-next').attr('title', 'Please select an account type');
                    } else {
                        $('.btn-next').attr('title', 'Continue to next step');
                    }
                    return isValid;
                }
                
                // Get current step and account type
                const currentStepElement = $(`.form-step[data-step="${this.currentStep}"]`);
                const accountType = $('input[name="account_type"]:checked').val();
                const isAgent = accountType === 'agent';
                
                console.log('Validation check:', { 
                    step: this.currentStep, 
                    accountType, 
                    isAgent 
                });
                
                // Find all required fields, excluding specialization checkboxes (handled separately)
                const requiredInputs = currentStepElement.find('input[required]:visible:not(.specialization-checkbox), select[required]:visible, textarea[required]:visible');
                
                console.log('Found required inputs:', requiredInputs.length);
                
                let isValid = true;
                let invalidFields = [];

                requiredInputs.each(function() {
                    const $field = $(this);
                    const $formGroup = $field.closest('.form-group');
                    const fieldLabel = $formGroup.find('label').text().replace('*', '').trim();
                    const fieldName = $field.attr('name') || $field.attr('id');
                    
                    // Skip agent-required fields if not an agent
                    if (!isAgent && $field.hasClass('agent-required')) {
                        console.log('Skipping agent field for non-agent:', fieldLabel);
                        return true; // continue to next field
                    }
                    
                    console.log('Checking field:', { fieldLabel, fieldName, value: $field.val() });
                    
                    if ($field.attr('type') === 'radio') {
                        const radioName = $field.attr('name');
                        if (!$(`input[name="${radioName}"]:checked`).length) {
                            isValid = false;
                            invalidFields.push(fieldLabel || radioName);
                            return false;
                        }
                    } else if ($field.attr('type') === 'checkbox') {
                        if (!$field.is(':checked')) {
                            isValid = false;
                            invalidFields.push(fieldLabel || 'Terms checkbox');
                            return false;
                        }
                    } else {
                        // For select elements, check if value is empty string
                        if ($field.is('select')) {
                            if (!$field.val() || $field.val() === '') {
                                isValid = false;
                                invalidFields.push(fieldLabel || $field.attr('name'));
                                $formGroup.addClass('has-error');
                            }
                        } else {
                            // For other inputs and textareas, use standard validation
                            const value = $field.val();
                            
                            // Special handling for textarea bio field - check trimmed length
                            if ($field.is('textarea') && $field.attr('id') === 'agent_bio') {
                                const trimmedLength = value ? value.trim().length : 0;
                                console.log('Bio field check:', { value, trimmedLength, required: 100 });
                                
                                // Skip general validation here, we handle it separately below
                                if (trimmedLength >= 100) {
                                    $formGroup.removeClass('has-error');
                                }
                                // Don't mark as invalid here, will be checked in agent-specific validation
                                return true; // continue to next field
                            }
                            
                            if (!value || value.trim() === '') {
                                isValid = false;
                                invalidFields.push(fieldLabel || fieldName);
                                $formGroup.addClass('has-error');
                                console.log('Field is empty:', fieldLabel);
                            } else if ($field[0].validity && !$field[0].validity.valid) {
                                isValid = false;
                                invalidFields.push(fieldLabel || fieldName);
                                $formGroup.addClass('has-error');
                                console.log('Field is invalid:', fieldLabel);
                            }
                        }
                    }
                });
                
                console.log('Validation result:', { isValid, invalidFields });
                
                // For agent accounts in step 2, also check specializations and bio
                if (isAgent && this.currentStep === 2) {
                    if ($('.specialization-checkbox:checked').length === 0) {
                        isValid = false;
                        invalidFields.push('Specialization (select at least one)');
                    }
                    
                    // Check bio length (use trimmed text for validation)
                    const bio = $('#agent_bio').val();
                    const bioLength = bio ? bio.trim().length : 0;
                    console.log('Bio validation:', { bio, bioLength, required: 100 });
                    
                    if (bioLength < 100) {
                        isValid = false;
                        invalidFields.push(`Professional Bio (${bioLength}/100 characters)`);
                        $('#agent_bio').closest('.form-group').addClass('has-error');
                    } else {
                        $('#agent_bio').closest('.form-group').removeClass('has-error');
                    }
                }

                // Show visual error message to user if validation fails
                if (!isValid && invalidFields.length > 0) {
                    const errorHtml = `
                        <div class="validation-error-box" style="background:#fee; border-left:4px solid #dc2626; padding:15px; margin:15px 0; border-radius:8px;">
                            <h4 style="margin:0 0 10px; color:#dc2626; font-size:16px;">
                                <span style="margin-right:5px;">⚠️</span> Please complete the following fields:
                            </h4>
                            <ul style="margin:5px 0; padding-left:25px; color:#666;">
                                ${invalidFields.map(field => `<li>${field}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                    currentStepElement.prepend(errorHtml);
                    
                    // Scroll to error message
                    setTimeout(() => {
                        const errorBox = document.querySelector('.validation-error-box');
                        if (errorBox) {
                            errorBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 100);
                }
                
                // Enable/disable appropriate button based on step
                if (this.currentStep === 3) {
                    $('.btn-submit').prop('disabled', !isValid);
                    if (!isValid) {
                        $('.btn-submit').attr('title', 'Please complete all required fields: ' + invalidFields.join(', '));
                    } else {
                        $('.btn-submit').attr('title', 'Create your account');
                    }
                } else {
                    $('.btn-next').prop('disabled', !isValid);
                    if (!isValid) {
                        $('.btn-next').attr('title', 'Missing: ' + invalidFields.join(', '));
                    } else {
                        $('.btn-next').attr('title', 'Continue to next step');
                    }
                }

                console.log('Button state:', { 
                    disabled: $('.btn-next').prop('disabled'),
                    isValid 
                });

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
                // Safely get password value
                const password = $('#password').val();
                const strengthContainer = $('.password-strength');
                
                // Guard against undefined or null
                if (typeof password !== 'string' || password.length === 0) {
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

                // Get account type to check if agent validation is needed
                const accountType = $('input[name="account_type"]:checked').val();
                const isAgent = accountType === 'agent';

                // For agents, validate specializations before general validation
                if (isAgent) {
                    const checkedSpecializations = $('.specialization-checkbox:checked').length;
                    if (checkedSpecializations === 0) {
                        this.showError('Please select at least one specialization.');
                        // Scroll to specializations
                        $('.checkbox-group-inline').addClass('error');
                        $('html, body').animate({
                            scrollTop: $('.checkbox-group-inline').offset().top - 100
                        }, 500);
                        return;
                    }
                    
                    // Validate bio length (use trimmed text)
                    const bio = $('#agent_bio').val();
                    const bioLength = bio ? bio.trim().length : 0;
                    if (bioLength < 100) {
                        this.showError(`Professional Bio must be at least 100 characters. Current: ${bioLength}/100 characters.`);
                        $('#agent_bio').addClass('error').focus();
                        return;
                    }
                }

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
                    // Agent-specific fields
                    agency_name: $('#agency_name').val(),
                    license_number: $('#license_number').val(),
                    years_experience: $('#years_experience').val(),
                    agent_county: $('#agent_county').val(),
                    business_address: $('#business_address').val(),
                    city: $('#city').val(),
                    specializations: $('.specialization-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get(),
                    agent_bio: $('#agent_bio').val(),
                    national_id: $('#national_id').val(),
                    website: $('#website').val(),
                    whatsapp: $('#whatsapp').val(),
                    office_phone: $('#office_phone').val(),
                    languages: $('#languages').val(),
                    service_areas: $('#service_areas').val(),
                    commission_rate: $('#commission_rate').val(),
                    // Social media
                    facebook: $('#facebook').val(),
                    twitter: $('#twitter').val(),
                    linkedin: $('#linkedin').val(),
                    instagram: $('#instagram').val(),
                    youtube: $('#youtube').val()
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
