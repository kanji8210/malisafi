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

        <form id="malisafi-registration-form" class="single-page-form" method="post">
            <?php wp_nonce_field('malisafi_registration', 'malisafi_registration_nonce'); ?>
            
            <!-- Account Type Selection -->
            <div class="form-section">
                <h3><?php _e('Choose Account Type', 'malisafi-mls'); ?></h3>
                
                <div class="account-type-grid">
                    <label class="account-type-card" data-role="malisafi_client">
                        <input type="radio" name="account_type" value="client" required>
                        <div class="card-content">
                            <div class="icon">🏠</div>
                            <h4><?php _e('Client', 'malisafi-mls'); ?></h4>
                            <p><?php _e('Find property like apartments, houses to buy or rent', 'malisafi-mls'); ?></p>
                        </div>
                    </label>

                    <label class="account-type-card" data-role="malisafi_agent_basic">
                        <input type="radio" name="account_type" value="agent" required>
                        <div class="card-content">
                            <div class="icon">💼</div>
                            <h4><?php _e('Agent', 'malisafi-mls'); ?></h4>
                            <p><?php _e('Real estate professional helping clients find properties', 'malisafi-mls'); ?></p>
                        </div>
                    </label>

                    <label class="account-type-card" data-role="malisafi_owner">
                        <input type="radio" name="account_type" value="owner" required>
                        <div class="card-content">
                            <div class="icon">🔑</div>
                            <h4><?php _e('Owner', 'malisafi-mls'); ?></h4>
                            <p><?php _e('List my property to sell or rent out', 'malisafi-mls'); ?></p>
                        </div>
                    </label>

                    <label class="account-type-card" data-role="malisafi_developer">
                        <input type="radio" name="account_type" value="developer" required>
                        <div class="card-content">
                            <div class="icon">🏗️</div>
                            <h4><?php _e('Developer', 'malisafi-mls'); ?></h4>
                            <p><?php _e('Develop and market new property projects', 'malisafi-mls'); ?></p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="form-section">
                <h3><?php _e('Your Information', 'malisafi-mls'); ?></h3>
                
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="first_name">
                            <?php _e('First Name', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="first_name" name="first_name" required 
                               placeholder="<?php esc_attr_e('Enter your first name', 'malisafi-mls'); ?>">
                    </div>
                    
                    <div class="form-group half-width">
                        <label for="last_name">
                            <?php _e('Last Name', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="last_name" name="last_name" required 
                               placeholder="<?php esc_attr_e('Enter your last name', 'malisafi-mls'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">
                        <?php _e('Email Address', 'malisafi-mls'); ?> <span class="required">*</span>
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="<?php esc_attr_e('your.email@example.com', 'malisafi-mls'); ?>">
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
                    <small class="form-hint"><?php _e('Enter your mobile number without the country code', 'malisafi-mls'); ?></small>
                </div>

                <!-- Additional fields for Agents -->
                <div class="agent-fields" style="display: none;">
                    <div class="form-group">
                        <label for="agency_name">
                            <?php _e('Agency Name', 'malisafi-mls'); ?>
                        </label>
                        <input type="text" id="agency_name" name="agency_name" 
                               placeholder="<?php esc_attr_e('Your real estate agency (optional)', 'malisafi-mls'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="license_number">
                            <?php _e('License Number', 'malisafi-mls'); ?>
                        </label>
                        <input type="text" id="license_number" name="license_number" 
                               placeholder="<?php esc_attr_e('Your professional license number (optional)', 'malisafi-mls'); ?>">
                    </div>
                </div>
            </div>

            <!-- Account Credentials -->
            <div class="form-section">
                <h3><?php _e('Account Credentials', 'malisafi-mls'); ?></h3>
                
                <div class="form-group">
                    <label for="username">
                        <?php _e('Username', 'malisafi-mls'); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="username" name="username" required 
                           placeholder="<?php esc_attr_e('Choose a unique username', 'malisafi-mls'); ?>"
                           pattern="[a-zA-Z0-9_-]{4,}"
                           minlength="4">
                    <small class="form-hint"><?php _e('At least 4 characters, letters, numbers, dashes and underscores only', 'malisafi-mls'); ?></small>
                </div>

                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="password">
                            <?php _e('Password', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" id="password" name="password" required 
                                   placeholder="<?php esc_attr_e('Create a strong password', 'malisafi-mls'); ?>"
                                   minlength="8">
                            <button type="button" class="toggle-password" aria-label="<?php esc_attr_e('Show password', 'malisafi-mls'); ?>">
                                <span class="eye-icon">👁️</span>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar"></div>
                            <span class="strength-text"></span>
                        </div>
                        <small class="form-hint"><?php _e('At least 8 characters', 'malisafi-mls'); ?></small>
                    </div>

                    <div class="form-group half-width">
                        <label for="password_confirm">
                            <?php _e('Confirm Password', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <input type="password" id="password_confirm" name="password_confirm" required 
                               placeholder="<?php esc_attr_e('Re-enter your password', 'malisafi-mls'); ?>">
                    </div>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="terms" name="terms" required>
                        <span>
                            <?php _e('I agree to the', 'malisafi-mls'); ?> 
                            <a href="#" target="_blank"><?php _e('Terms & Conditions', 'malisafi-mls'); ?></a> 
                            <?php _e('and', 'malisafi-mls'); ?> 
                            <a href="#" target="_blank"><?php _e('Privacy Policy', 'malisafi-mls'); ?></a>
                        </span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-submit">
                    <?php _e('Create My Account', 'malisafi-mls'); ?> ✨
                </button>
            </div>

            <!-- Hidden field for user role -->
            <input type="hidden" id="user_role" name="user_role" value="">
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
