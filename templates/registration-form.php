<?php
/**
 * Conversational Registration Form Template
 *
 * @package MalisafiMLS
 */

defined('ABSPATH') || exit;
?>

<div class="malisafi-registration-wrapper">
    <div class="registration-container">
        <div class="registration-header">
            <h2><?php _e('Create Your Malisafi Account', 'malisafi-mls'); ?></h2>
            <p class="subtitle"><?php _e('What do you need an account for?', 'malisafi-mls'); ?></p>
        </div>

        <form id="malisafi-registration-form" class="multi-step-form" method="post">
            <?php wp_nonce_field('malisafi_registration', 'malisafi_registration_nonce'); ?>
            
            <?php
            // Get preselected account type from URL parameter
            $preselected_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
            ?>
            
            <!-- Step Progress Indicator with Bar -->
            <div class="step-progress-wrapper">
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: 33.33%;"></div>
                </div>
                <div class="step-counter"><?php _e('Step 1 of 3', 'malisafi-mls'); ?></div>
            </div>
            
            <div class="step-progress">
                <div class="step-item active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label"><?php _e('Account Type', 'malisafi-mls'); ?></div>
                </div>
                <div class="step-item" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label"><?php _e('Your Info', 'malisafi-mls'); ?></div>
                </div>
                <div class="step-item" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label"><?php _e('Login Details', 'malisafi-mls'); ?></div>
                </div>
            </div>
            
            <!-- Step 1: Account Type Selection -->
            <div class="form-step" data-step="1">
                <h3><?php _e('What brings you to Malisafi?', 'malisafi-mls'); ?></h3>
                <p class="step-description"><?php _e('Choose the option that best describes you', 'malisafi-mls'); ?></p>
                
                <div class="account-type-cards">
                    <label class="account-card" data-type="client">
                        <input type="radio" name="account_type" value="client" required>
                        <div class="card-content">
                            <div class="card-icon">🏠</div>
                            <h4><?php _e('Looking for Property', 'malisafi-mls'); ?></h4>
                            <p><?php _e('I want to buy, rent, or browse properties', 'malisafi-mls'); ?></p>
                        </div>
                        <div class="card-checkmark">✓</div>
                    </label>
                    
                    <label class="account-card" data-type="agent">
                        <input type="radio" name="account_type" value="agent" required>
                        <div class="card-content">
                            <div class="card-icon">💼</div>
                            <h4><?php _e('Real Estate Agent', 'malisafi-mls'); ?></h4>
                            <p><?php _e('Licensed agent or agency professional', 'malisafi-mls'); ?></p>
                        </div>
                        <div class="card-checkmark">✓</div>
                    </label>
                    
                    <label class="account-card" data-type="owner">
                        <input type="radio" name="account_type" value="owner" required>
                        <div class="card-content">
                            <div class="card-icon">🔑</div>
                            <h4><?php _e('Property Owner', 'malisafi-mls'); ?></h4>
                            <p><?php _e('I want to list my property for sale or rent', 'malisafi-mls'); ?></p>
                        </div>
                        <div class="card-checkmark">✓</div>
                    </label>
                    
                    <label class="account-card" data-type="developer">
                        <input type="radio" name="account_type" value="developer" required>
                        <div class="card-content">
                            <div class="card-icon">🏗️</div>
                            <h4><?php _e('Developer', 'malisafi-mls'); ?></h4>
                            <p><?php _e('Property developer or construction company', 'malisafi-mls'); ?></p>
                        </div>
                        <div class="card-checkmark">✓</div>
                    </label>
                </div>
                
                <input type="hidden" id="user_role" name="user_role" value="">
            </div>

            <!-- Step 2: Personal Information -->
            <div class="form-step" data-step="2" style="display: none;">
                <h3><?php _e('Tell us about yourself', 'malisafi-mls'); ?></h3>
                <p class="step-description"><?php _e('Basic information to get started', 'malisafi-mls'); ?></p>
                
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="first_name">
                            <?php _e('First Name', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="first_name" name="first_name" required 
                               placeholder="<?php esc_attr_e('John', 'malisafi-mls'); ?>">
                    </div>
                    
                    <div class="form-group half-width">
                        <label for="last_name">
                            <?php _e('Last Name', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="last_name" name="last_name" required 
                               placeholder="<?php esc_attr_e('Doe', 'malisafi-mls'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">
                        <?php _e('Phone Number', 'malisafi-mls'); ?> <span class="required">*</span>
                    </label>
                    <div class="phone-input-wrapper">
                        <span class="phone-prefix">+254</span>
                        <input type="tel" id="phone" name="phone" required 
                               placeholder="712345678"
                               pattern="[0-9]{9,10}"
                               maxlength="10">
                    </div>
                    <small class="form-hint"><?php _e('Your mobile number', 'malisafi-mls'); ?></small>
                </div>
                
                <!-- Agent-specific fields (conditionally shown) -->
                <div class="agent-fields" style="display: none;">
                    <h4 class="subsection-title"><?php _e('Professional Details', 'malisafi-mls'); ?></h4>
                    
                    <div class="form-group">
                        <label for="agency_name">
                            <?php _e('Agency/Company Name', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="agency_name" name="agency_name" class="agent-required"
                               placeholder="<?php esc_attr_e('e.g. Malisafi Real Estate', 'malisafi-mls'); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="license_number">
                                <?php _e('License Number', 'malisafi-mls'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" id="license_number" name="license_number" class="agent-required"
                                   placeholder="<?php esc_attr_e('Your professional license', 'malisafi-mls'); ?>">
                        </div>
                        
                        <div class="form-group half-width">
                            <label for="years_experience">
                                <?php _e('Experience', 'malisafi-mls'); ?> <span class="required">*</span>
                            </label>
                            <select id="years_experience" name="years_experience" class="agent-required">
                                <option value=""><?php _e('Select...', 'malisafi-mls'); ?></option>
                                <option value="0-1"><?php _e('<1 year', 'malisafi-mls'); ?></option>
                                <option value="1-3"><?php _e('1-3 years', 'malisafi-mls'); ?></option>
                                <option value="3-5"><?php _e('3-5 years', 'malisafi-mls'); ?></option>
                                <option value="5-10"><?php _e('5-10 years', 'malisafi-mls'); ?></option>
                                <option value="10+"><?php _e('10+ years', 'malisafi-mls'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="specializations">
                            <?php _e('Specialization', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <div class="checkbox-group-inline">
                            <label class="checkbox-label-inline">
                                <input type="checkbox" name="specializations[]" value="residential" class="specialization-checkbox">
                                <span><?php _e('Residential', 'malisafi-mls'); ?></span>
                            </label>
                            <label class="checkbox-label-inline">
                                <input type="checkbox" name="specializations[]" value="commercial" class="specialization-checkbox">
                                <span><?php _e('Commercial', 'malisafi-mls'); ?></span>
                            </label>
                            <label class="checkbox-label-inline">
                                <input type="checkbox" name="specializations[]" value="land" class="specialization-checkbox">
                                <span><?php _e('Land', 'malisafi-mls'); ?></span>
                            </label>
                        </div>
                        <small class="form-hint"><?php _e('Select at least one', 'malisafi-mls'); ?></small>
                    </div>
                </div>
                <!-- End of agent-specific fields -->
            </div>

            <!-- Step 3: Account Credentials -->
            <div class="form-step" data-step="3" style="display: none;">
                <h3><?php _e('Create Your Account', 'malisafi-mls'); ?></h3>
                <p class="step-description"><?php _e('Almost there! Set up your login details', 'malisafi-mls'); ?></p>
                
                <div class="form-group">
                    <label for="email">
                        <?php _e('Email Address', 'malisafi-mls'); ?> <span class="required">*</span>
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="<?php esc_attr_e('your.email@example.com', 'malisafi-mls'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="username">
                        <?php _e('Username', 'malisafi-mls'); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="username" name="username" required 
                           placeholder="<?php esc_attr_e('Choose a username', 'malisafi-mls'); ?>"
                           pattern="[a-zA-Z0-9_-]{4,}"
                           minlength="4">
                    <small class="form-hint"><?php _e('At least 4 characters', 'malisafi-mls'); ?></small>
                </div>

                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="password">
                            <?php _e('Password', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" id="password" name="password" required 
                                   placeholder="<?php esc_attr_e('••••••••', 'malisafi-mls'); ?>"
                                   minlength="8">
                            <button type="button" class="toggle-password" aria-label="<?php esc_attr_e('Show password', 'malisafi-mls'); ?>">
                                <span class="eye-icon">👁️</span>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar"></div>
                            <span class="strength-text"></span>
                        </div>
                        <small class="form-hint"><?php _e('Min 8 characters', 'malisafi-mls'); ?></small>
                    </div>

                    <div class="form-group half-width">
                        <label for="password_confirm">
                            <?php _e('Confirm Password', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" id="password_confirm" name="password_confirm" required 
                                   placeholder="<?php esc_attr_e('••••••••', 'malisafi-mls'); ?>">
                            <button type="button" class="toggle-password" aria-label="<?php esc_attr_e('Show password', 'malisafi-mls'); ?>">
                                <span class="eye-icon">👁️</span>
                            </button>
                        </div>
                        <div class="password-match-status"></div>
                    </div>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="terms_checkbox" name="terms" required>
                        <span>
                            <?php _e('I agree to the', 'malisafi-mls'); ?> 
                            <a href="#" target="_blank"><?php _e('Terms & Conditions', 'malisafi-mls'); ?></a> 
                            <?php _e('and', 'malisafi-mls'); ?> 
                            <a href="#" target="_blank"><?php _e('Privacy Policy', 'malisafi-mls'); ?></a>
                        </span>
                    </label>
                </div>
            </div>
            
            <!-- Single Navigation Section (outside steps) -->
            <div class="step-navigation">
                <button type="button" class="btn btn-secondary btn-prev" style="display: none;">
                    ← <?php _e('Back', 'malisafi-mls'); ?>
                </button>
                <button type="button" class="btn btn-primary btn-next">
                    <?php _e('Continue', 'malisafi-mls'); ?> →
                </button>
                <button type="submit" class="btn btn-primary btn-submit" style="display: none;">
                    <?php _e('Create My Account', 'malisafi-mls'); ?> ✨
                </button>
            </div>
        </form>

        <div class="registration-footer">
            <p><?php _e('Already have an account?', 'malisafi-mls'); ?> 
                <a href="<?php echo wp_login_url(); ?>"><?php _e('Sign in here', 'malisafi-mls'); ?></a>
            </p>
        </div>

        <!-- Success Message (hidden by default) -->
        <div class="registration-success" style="display: none;">
            <div class="success-icon">✅</div>
            <h3><?php _e('Welcome aboard!', 'malisafi-mls'); ?></h3>
            <p><?php _e('Your account has been created successfully. Redirecting you to your dashboard...', 'malisafi-mls'); ?></p>
        </div>

        <!-- Error Messages -->
        <div class="registration-errors" style="display: none;"></div>
    </div>
</div>
