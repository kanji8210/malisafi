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
            
            <?php
            // Get preselected account type from URL parameter
            $preselected_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
            ?>
            
            <!-- Account Type Selection -->
            <div class="form-section">
                <h3><?php _e('Choose Account Type', 'malisafi-mls'); ?></h3>
                
                <!-- Dropdown for Account Type -->
                <div class="form-group">
                    <label for="account_type_select">
                        <?php _e('I want to register as', 'malisafi-mls'); ?> <span class="required">*</span>
                    </label>
                    <select id="account_type_select" name="account_type_select" required>
                        <option value=""><?php _e('-- Select Account Type --', 'malisafi-mls'); ?></option>
                        <option value="client" <?php selected($preselected_type, 'client'); ?>>
                            <?php _e('Client - Find properties to buy or rent', 'malisafi-mls'); ?>
                        </option>
                        <option value="agent" <?php selected($preselected_type, 'agent'); ?>>
                            <?php _e('Agent - Real estate professional', 'malisafi-mls'); ?>
                        </option>
                        <option value="hunter" <?php selected($preselected_type, 'hunter'); ?>>
                            <?php _e('Hunter - Property searcher/investor', 'malisafi-mls'); ?>
                        </option>
                        <option value="owner" <?php selected($preselected_type, 'owner'); ?>>
                            <?php _e('Owner - List my property', 'malisafi-mls'); ?>
                        </option>
                        <option value="developer" <?php selected($preselected_type, 'developer'); ?>>
                            <?php _e('Developer - Property development projects', 'malisafi-mls'); ?>
                        </option>
                    </select>
                </div>
                
                <div class="account-type-grid">
                    <label class="account-type-card" data-role="malisafi_client">
                        <input type="radio" name="account_type" value="client" <?php checked($preselected_type, 'client'); ?>>
                        <div class="card-content">
                            <div class="icon">🏠</div>
                            <h4><?php _e('Client', 'malisafi-mls'); ?></h4>
                            <p><?php _e('Find property like apartments, houses to buy or rent', 'malisafi-mls'); ?></p>
                        </div>
                    </label>

                    <label class="account-type-card" data-role="malisafi_agent_basic">
                        <input type="radio" name="account_type" value="agent" <?php checked($preselected_type, 'agent'); ?>>
                        <div class="card-content">
                            <div class="icon">💼</div>
                            <h4><?php _e('Agent', 'malisafi-mls'); ?></h4>
                            <p><?php _e('Real estate professional helping clients find properties', 'malisafi-mls'); ?></p>
                        </div>
                    </label>

                    <label class="account-type-card" data-role="malisafi_client">
                        <input type="radio" name="account_type" value="hunter" <?php checked($preselected_type, 'hunter'); ?>>
                        <div class="card-content">
                            <div class="icon">🔍</div>
                            <h4><?php _e('Hunter', 'malisafi-mls'); ?></h4>
                            <p><?php _e('Property searcher actively looking for deals', 'malisafi-mls'); ?></p>
                        </div>
                    </label>

                    <label class="account-type-card" data-role="malisafi_owner">
                        <input type="radio" name="account_type" value="owner" <?php checked($preselected_type, 'owner'); ?>>
                        <div class="card-content">
                            <div class="icon">🔑</div>
                            <h4><?php _e('Owner', 'malisafi-mls'); ?></h4>
                            <p><?php _e('List my property to sell or rent out', 'malisafi-mls'); ?></p>
                        </div>
                    </label>

                    <label class="account-type-card" data-role="malisafi_developer">
                        <input type="radio" name="account_type" value="developer" <?php checked($preselected_type, 'developer'); ?>>
                        <div class="card-content">
                            <div class="icon">🏗️</div>
                            <h4><?php _e('Developer', 'malisafi-mls'); ?></h4>
                            <p><?php _e('Develop and market new property projects', 'malisafi-mls'); ?></p>
                        </div>
                    </label>
                </div>

                <!-- Agent Registration Notice -->
                <div class="agent-registration-notice" style="display: none;">
                    <div class="notice-box">
                        <div class="notice-icon">💼</div>
                        <div class="notice-content">
                            <h4><?php _e('Registering as an Agent', 'malisafi-mls'); ?></h4>
                            <p><?php _e('You are about to create an agent account. This will give you access to:', 'malisafi-mls'); ?></p>
                            <ul>
                                <li><?php _e('List properties for sale or rent', 'malisafi-mls'); ?></li>
                                <li><?php _e('Manage client inquiries', 'malisafi-mls'); ?></li>
                                <li><?php _e('Track property performance', 'malisafi-mls'); ?></li>
                                <li><?php _e('Access agent dashboard', 'malisafi-mls'); ?></li>
                            </ul>
                            <p class="other-account-types">
                                <?php _e('Looking for a different account type?', 'malisafi-mls'); ?>
                                <br>
                                <a href="#" class="account-type-link" data-type="owner">
                                    <?php _e('Register as Property Owner', 'malisafi-mls'); ?>
                                </a> | 
                                <a href="#" class="account-type-link" data-type="developer">
                                    <?php _e('Register as Developer', 'malisafi-mls'); ?>
                                </a> | 
                                <a href="#" class="account-type-link" data-type="client">
                                    <?php _e('Register as Client', 'malisafi-mls'); ?>
                                </a> | 
                                <a href="#" class="account-type-link" data-type="hunter">
                                    <?php _e('Register as Hunter', 'malisafi-mls'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="form-section" id="personal-info-section" style="display: none;">
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
                    <h4 class="subsection-title"><?php _e('Professional Information', 'malisafi-mls'); ?></h4>
                    
                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="agency_name">
                                <?php _e('Agency Name', 'malisafi-mls'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" id="agency_name" name="agency_name" class="agent-required"
                                   placeholder="<?php esc_attr_e('Your real estate agency', 'malisafi-mls'); ?>">
                        </div>

                        <div class="form-group half-width">
                            <label for="license_number">
                                <?php _e('License Number', 'malisafi-mls'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" id="license_number" name="license_number" class="agent-required"
                                   placeholder="<?php esc_attr_e('Professional license number', 'malisafi-mls'); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="years_experience">
                                <?php _e('Years of Experience', 'malisafi-mls'); ?> <span class="required">*</span>
                            </label>
                            <select id="years_experience" name="years_experience" class="agent-required">
                                <option value=""><?php _e('Select experience', 'malisafi-mls'); ?></option>
                                <option value="0-1"><?php _e('Less than 1 year', 'malisafi-mls'); ?></option>
                                <option value="1-3"><?php _e('1-3 years', 'malisafi-mls'); ?></option>
                                <option value="3-5"><?php _e('3-5 years', 'malisafi-mls'); ?></option>
                                <option value="5-10"><?php _e('5-10 years', 'malisafi-mls'); ?></option>
                                <option value="10+"><?php _e('10+ years', 'malisafi-mls'); ?></option>
                            </select>
                        </div>

                        <div class="form-group half-width">
                            <label for="agent_county">
                                <?php _e('Operating County', 'malisafi-mls'); ?> <span class="required">*</span>
                            </label>
                            <select id="agent_county" name="agent_county" class="agent-required">
                                <option value=""><?php _e('Select county', 'malisafi-mls'); ?></option>
                                <?php
                                if (function_exists('malisafi_get_kenya_counties')) {
                                    $counties = malisafi_get_kenya_counties();
                                    foreach ($counties as $county) {
                                        echo '<option value="' . esc_attr($county) . '">' . esc_html($county) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="business_address">
                            <?php _e('Business Address', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="business_address" name="business_address" class="agent-required"
                               placeholder="<?php esc_attr_e('Street address, building name, floor', 'malisafi-mls'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="city">
                            <?php _e('City/Town', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="city" name="city" class="agent-required"
                               placeholder="<?php esc_attr_e('Enter city or town', 'malisafi-mls'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="specializations">
                            <?php _e('Specializations', 'malisafi-mls'); ?> <span class="required">*</span>
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
                            <label class="checkbox-label-inline">
                                <input type="checkbox" name="specializations[]" value="rental" class="specialization-checkbox">
                                <span><?php _e('Rental', 'malisafi-mls'); ?></span>
                            </label>
                            <label class="checkbox-label-inline">
                                <input type="checkbox" name="specializations[]" value="luxury" class="specialization-checkbox">
                                <span><?php _e('Luxury', 'malisafi-mls'); ?></span>
                            </label>
                        </div>
                        <small class="form-hint"><?php _e('Select at least one specialization', 'malisafi-mls'); ?></small>
                    </div>

                    <div class="form-group">
                        <label for="agent_bio">
                            <?php _e('Professional Bio', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <textarea id="agent_bio" name="agent_bio" rows="4" class="agent-required"
                                  placeholder="<?php esc_attr_e('Tell clients about your experience, achievements, and what makes you a great agent...', 'malisafi-mls'); ?>"
                                  minlength="100" maxlength="500"></textarea>
                        <small class="form-hint char-count"><?php _e('100-500 characters (0/500)', 'malisafi-mls'); ?></small>
                    </div>

                    <div class="form-group">
                        <label for="national_id">
                            <?php _e('National ID Number', 'malisafi-mls'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" id="national_id" name="national_id" class="agent-required"
                               placeholder="<?php esc_attr_e('Enter your National ID number', 'malisafi-mls'); ?>"
                               pattern="[0-9]{7,8}" maxlength="8">
                        <small class="form-hint"><?php _e('Required for verification purposes', 'malisafi-mls'); ?></small>
                    </div>

                    <h4 class="subsection-title"><?php _e('Contact & Service Details', 'malisafi-mls'); ?></h4>

                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="office_phone">
                                <?php _e('Office Phone (Optional)', 'malisafi-mls'); ?>
                            </label>
                            <div class="phone-input-wrapper">
                                <span class="phone-prefix">+254</span>
                                <input type="tel" id="office_phone" name="office_phone"
                                       placeholder="712345678"
                                       pattern="[0-9]{9,10}"
                                       maxlength="10">
                            </div>
                        </div>

                        <div class="form-group half-width">
                            <label for="whatsapp">
                                <?php _e('WhatsApp Number (Optional)', 'malisafi-mls'); ?>
                            </label>
                            <div class="phone-input-wrapper">
                                <span class="phone-prefix">+254</span>
                                <input type="tel" id="whatsapp" name="whatsapp"
                                       placeholder="712345678"
                                       pattern="[0-9]{9,10}"
                                       maxlength="10">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="languages">
                            <?php _e('Languages Spoken (Optional)', 'malisafi-mls'); ?>
                        </label>
                        <input type="text" id="languages" name="languages"
                               placeholder="<?php esc_attr_e('e.g. English, Swahili, French', 'malisafi-mls'); ?>">
                        <small class="form-hint"><?php _e('Separate multiple languages with commas', 'malisafi-mls'); ?></small>
                    </div>

                    <div class="form-group">
                        <label for="service_areas">
                            <?php _e('Service Areas (Optional)', 'malisafi-mls'); ?>
                        </label>
                        <textarea id="service_areas" name="service_areas" rows="2"
                                  placeholder="<?php esc_attr_e('e.g. Nairobi, Westlands, Karen, Kiambu', 'malisafi-mls'); ?>"></textarea>
                        <small class="form-hint"><?php _e('Specific neighborhoods, towns or areas you serve', 'malisafi-mls'); ?></small>
                    </div>

                    <div class="form-group">
                        <label for="commission_rate">
                            <?php _e('Commission Rate % (Optional)', 'malisafi-mls'); ?>
                        </label>
                        <input type="number" id="commission_rate" name="commission_rate"
                               min="0" max="100" step="0.1"
                               placeholder="<?php esc_attr_e('e.g. 2.5', 'malisafi-mls'); ?>">
                        <small class="form-hint"><?php _e('Your typical commission percentage', 'malisafi-mls'); ?></small>
                    </div>

                    <div class="form-group">
                        <label for="website">
                            <?php _e('Website (Optional)', 'malisafi-mls'); ?>
                        </label>
                        <input type="url" id="website" name="website"
                               placeholder="<?php esc_attr_e('https://yourwebsite.com', 'malisafi-mls'); ?>">
                    </div>

                    <h4 class="subsection-title"><?php _e('Social Media Profiles (Optional)', 'malisafi-mls'); ?></h4>
                    
                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="facebook">
                                <?php _e('Facebook', 'malisafi-mls'); ?>
                            </label>
                            <input type="url" id="facebook" name="facebook"
                                   placeholder="<?php esc_attr_e('https://facebook.com/yourprofile', 'malisafi-mls'); ?>">
                        </div>

                        <div class="form-group half-width">
                            <label for="twitter">
                                <?php _e('Twitter/X', 'malisafi-mls'); ?>
                            </label>
                            <input type="url" id="twitter" name="twitter"
                                   placeholder="<?php esc_attr_e('https://twitter.com/yourhandle', 'malisafi-mls'); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="linkedin">
                                <?php _e('LinkedIn', 'malisafi-mls'); ?>
                            </label>
                            <input type="url" id="linkedin" name="linkedin"
                                   placeholder="<?php esc_attr_e('https://linkedin.com/in/yourprofile', 'malisafi-mls'); ?>">
                        </div>

                        <div class="form-group half-width">
                            <label for="instagram">
                                <?php _e('Instagram', 'malisafi-mls'); ?>
                            </label>
                            <input type="url" id="instagram" name="instagram"
                                   placeholder="<?php esc_attr_e('https://instagram.com/yourhandle', 'malisafi-mls'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="youtube">
                            <?php _e('YouTube Channel', 'malisafi-mls'); ?>
                        </label>
                        <input type="url" id="youtube" name="youtube"
                               placeholder="<?php esc_attr_e('https://youtube.com/@yourchannel', 'malisafi-mls'); ?>">
                    </div>
                </div>
            </div>

            <!-- Account Credentials -->
            <div class="form-section" id="credentials-section" style="display: none;">
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
