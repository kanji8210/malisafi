<?php
/**
 * Property Submission Wizard Template
 *
 * @package MalisafiMLS
 */
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
?>

<div class="malisafi-submission-wizard">
    
    <!-- Progress Steps -->
    <div class="wizard-progress">
        <div class="progress-step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-label"><?php _e('Basic Info', 'malisafi-mls'); ?></div>
        </div>
        <div class="progress-step" data-step="2">
            <div class="step-number">2</div>
            <div class="step-label"><?php _e('Details', 'malisafi-mls'); ?></div>
        </div>
        <div class="progress-step" data-step="3">
            <div class="step-number">3</div>
            <div class="step-label"><?php _e('Location', 'malisafi-mls'); ?></div>
        </div>
        <div class="progress-step" data-step="4">
            <div class="step-number">4</div>
            <div class="step-label"><?php _e('Features', 'malisafi-mls'); ?></div>
        </div>
        <div class="progress-step" data-step="5">
            <div class="step-number">5</div>
            <div class="step-label"><?php _e('Images', 'malisafi-mls'); ?></div>
        </div>
        <div class="progress-step" data-step="6">
            <div class="step-number">6</div>
            <div class="step-label"><?php _e('Review', 'malisafi-mls'); ?></div>
        </div>
    </div>

    <!-- Auto-save indicator -->
    <div class="autosave-indicator">
        <span class="spinner"></span>
        <span class="status-text"></span>
    </div>

    <!-- Wizard Form -->
    <form id="property-submission-form" class="wizard-form">
        <input type="hidden" name="property_id" id="property_id" value="<?php echo esc_attr($property_id); ?>">
        
        <!-- Step 1: Basic Information -->
        <div class="wizard-step active" id="step-1">
            <h2><?php _e('Basic Information', 'malisafi-mls'); ?></h2>
            <p class="step-description"><?php _e('Tell us about your property', 'malisafi-mls'); ?></p>
            
            <div class="form-row">
                <label for="property_title" class="required">
                    <?php _e('Property Title', 'malisafi-mls'); ?>
                </label>
                <input type="text" 
                       id="property_title" 
                       name="title" 
                       class="form-control" 
                       placeholder="<?php esc_attr_e('e.g., Modern 3-Bedroom Apartment in Westlands', 'malisafi-mls'); ?>"
                       required>
                <span class="field-hint"><?php _e('Minimum 5 characters', 'malisafi-mls'); ?></span>
            </div>

            <div class="form-row">
                <label for="property_description">
                    <?php _e('Description', 'malisafi-mls'); ?>
                </label>
                <textarea id="property_description" 
                          name="description" 
                          class="form-control" 
                          rows="6"
                          placeholder="<?php esc_attr_e('Describe your property...', 'malisafi-mls'); ?>"></textarea>
                <span class="field-hint"><?php _e('Minimum 20 characters recommended', 'malisafi-mls'); ?></span>
            </div>

            <div class="form-row">
                <label for="reference_id">
                    <?php _e('Property ID (MLS #)', 'malisafi-mls'); ?>
                </label>
                <div class="gps-input-group">
                    <input type="text" id="reference_id" name="reference_id" class="form-control" placeholder="<?php esc_attr_e('Not generated yet', 'malisafi-mls'); ?>" readonly>
                    <button type="button" class="btn btn-secondary btn-generate-ref" title="<?php esc_attr_e('Generate ID', 'malisafi-mls'); ?>">
                        <?php _e('Generate ID', 'malisafi-mls'); ?>
                    </button>
                </div>
                <span class="field-hint"><?php _e('Click "Generate ID" to create a unique property identifier, or it will auto-generate on save.', 'malisafi-mls'); ?></span>
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="property_price" class="required">
                        <?php _e('Price', 'malisafi-mls'); ?>
                    </label>
                    <input type="number" 
                           id="property_price" 
                           name="price" 
                           class="form-control" 
                           min="0" 
                           step="0.01"
                           placeholder="5000000"
                           required>
                </div>

                <div class="form-row">
                    <label for="property_currency" class="required">
                        <?php _e('Currency', 'malisafi-mls'); ?>
                    </label>
                    <select id="property_currency" name="currency" class="form-control" required>
                        <option value="KES">KES (Kenyan Shilling)</option>
                        <option value="USD">USD (US Dollar)</option>
                        <option value="EUR">EUR (Euro)</option>
                        <option value="GBP">GBP (British Pound)</option>
                    </select>
                </div>
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="property_type" class="required">
                        <?php _e('Property Type', 'malisafi-mls'); ?>
                    </label>
                    <select id="property_type" name="property_type" class="form-control" required>
                        <option value=""><?php _e('Select type...', 'malisafi-mls'); ?></option>
                        <option value="house"><?php _e('House', 'malisafi-mls'); ?></option>
                        <option value="apartment"><?php _e('Apartment', 'malisafi-mls'); ?></option>
                        <option value="land"><?php _e('Land', 'malisafi-mls'); ?></option>
                        <option value="commercial"><?php _e('Commercial', 'malisafi-mls'); ?></option>
                        <option value="industrial"><?php _e('Industrial', 'malisafi-mls'); ?></option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="listing_type" class="required">
                        <?php _e('Listing Type', 'malisafi-mls'); ?>
                    </label>
                    <select id="listing_type" name="listing_type" class="form-control" required>
                        <option value=""><?php _e('Select...', 'malisafi-mls'); ?></option>
                        <option value="sale"><?php _e('For Sale', 'malisafi-mls'); ?></option>
                        <option value="rent"><?php _e('For Rent', 'malisafi-mls'); ?></option>
                        <option value="lease"><?php _e('For Lease', 'malisafi-mls'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Step 2: Details -->
        <div class="wizard-step" id="step-2">
            <h2><?php _e('Property Details', 'malisafi-mls'); ?></h2>
            <p class="step-description"><?php _e('Add specific details about your property', 'malisafi-mls'); ?></p>
            
            <div class="form-row-group">
                <div class="form-row">
                    <label for="bedrooms">
                        <?php _e('Bedrooms', 'malisafi-mls'); ?>
                    </label>
                    <input type="number" id="bedrooms" name="bedrooms" class="form-control" min="0" max="50" value="0">
                </div>

                <div class="form-row">
                    <label for="bathrooms">
                        <?php _e('Bathrooms', 'malisafi-mls'); ?>
                    </label>
                    <input type="number" id="bathrooms" name="bathrooms" class="form-control" min="0" max="50" value="0">
                </div>
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="property_size">
                        <?php _e('Size', 'malisafi-mls'); ?>
                    </label>
                    <input type="number" id="property_size" name="size" class="form-control" min="0" step="0.01" placeholder="120">
                </div>

                <div class="form-row">
                    <label for="size_unit">
                        <?php _e('Unit', 'malisafi-mls'); ?>
                    </label>
                    <select id="size_unit" name="size_unit" class="form-control">
                        <option value="sqm"><?php _e('Square Meters', 'malisafi-mls'); ?></option>
                        <option value="sqft"><?php _e('Square Feet', 'malisafi-mls'); ?></option>
                        <option value="acres"><?php _e('Acres', 'malisafi-mls'); ?></option>
                        <option value="hectares"><?php _e('Hectares', 'malisafi-mls'); ?></option>
                    </select>
                </div>
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="year_built">
                        <?php _e('Year Built', 'malisafi-mls'); ?>
                    </label>
                    <input type="number" 
                           id="year_built" 
                           name="year_built" 
                           class="form-control" 
                           min="1800" 
                           max="<?php echo date('Y') + 5; ?>" 
                           placeholder="<?php echo date('Y'); ?>">
                </div>

                <div class="form-row">
                    <label for="condition">
                        <?php _e('Condition', 'malisafi-mls'); ?>
                    </label>
                    <select id="condition" name="condition" class="form-control">
                        <option value=""><?php _e('Select...', 'malisafi-mls'); ?></option>
                        <option value="new"><?php _e('New', 'malisafi-mls'); ?></option>
                        <option value="excellent"><?php _e('Excellent', 'malisafi-mls'); ?></option>
                        <option value="good"><?php _e('Good', 'malisafi-mls'); ?></option>
                        <option value="fair"><?php _e('Fair', 'malisafi-mls'); ?></option>
                        <option value="renovation"><?php _e('Needs Renovation', 'malisafi-mls'); ?></option>
                    </select>
                </div>
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="agent_name"><?php _e('Agent/Contact Name', 'malisafi-mls'); ?></label>
                    <input type="text" id="agent_name" name="agent_name" class="form-control" placeholder="<?php esc_attr_e('e.g., John Doe', 'malisafi-mls'); ?>">
                </div>
                <div class="form-row">
                    <label for="agent_email"><?php _e('Agent Email', 'malisafi-mls'); ?></label>
                    <input type="email" id="agent_email" name="agent_email" class="form-control" placeholder="agent@example.com">
                </div>
            </div>
            <div class="form-row">
                <label for="agent_phone"><?php _e('Agent Phone', 'malisafi-mls'); ?></label>
                <input type="text" id="agent_phone" name="agent_phone" class="form-control" placeholder="<?php esc_attr_e('+254700000000', 'malisafi-mls'); ?>">
            </div>
        </div>

        <!-- Step 3: Location -->
        <div class="wizard-step" id="step-3">
            <h2><?php _e('Location', 'malisafi-mls'); ?></h2>
            <p class="step-description"><?php _e('Where is your property located?', 'malisafi-mls'); ?></p>
            
            <div class="form-row">
                <label for="property_address">
                    <?php _e('Street Address', 'malisafi-mls'); ?>
                </label>
                <input type="text" 
                       id="property_address" 
                       name="address" 
                       class="form-control" 
                       placeholder="<?php esc_attr_e('e.g., Waiyaki Way, Building Name', 'malisafi-mls'); ?>">
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="property_county" class="required">
                        <?php _e('County', 'malisafi-mls'); ?>
                    </label>
                    <select id="property_county" name="county" class="form-control" required>
                        <option value=""><?php _e('Select county...', 'malisafi-mls'); ?></option>
                        <?php if (function_exists('malisafi_get_kenya_counties')): 
                            $counties = malisafi_get_kenya_counties();
                            foreach ($counties as $county): ?>
                                <option value="<?php echo esc_attr($county); ?>"><?php echo esc_html($county); ?></option>
                            <?php endforeach; endif; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="property_city" class="required">
                        <?php _e('City/Town', 'malisafi-mls'); ?>
                    </label>
                    <input type="text" 
                           id="property_city" 
                           name="city" 
                           class="form-control" 
                           placeholder="<?php esc_attr_e('e.g., Westlands', 'malisafi-mls'); ?>"
                           required>
                </div>
            </div>

            <div class="form-row">
                <label for="property_area">
                    <?php _e('Area/Neighborhood', 'malisafi-mls'); ?>
                </label>
                <input type="text" 
                       id="property_area" 
                       name="area" 
                       class="form-control" 
                       placeholder="<?php esc_attr_e('e.g., Parklands, Lavington', 'malisafi-mls'); ?>">
            </div>

            <div class="form-row">
                <label for="property_gps">
                    <?php _e('GPS Coordinates (Optional)', 'malisafi-mls'); ?>
                </label>
                <div class="gps-input-group">
                    <input type="text" 
                           id="property_gps" 
                           name="gps" 
                           class="form-control" 
                           placeholder="-1.2921, 36.8219">
                    <button type="button" class="btn btn-secondary btn-get-location">
                        <span class="icon">📍</span>
                        <?php _e('Get My Location', 'malisafi-mls'); ?>
                    </button>
                </div>
                <div class="field-hint privacy-notice" style="background: #e8f4f8; padding: 10px; border-radius: 4px; margin-top: 8px; border-left: 3px solid #0073aa;">
                    <p style="margin: 0 0 5px 0; font-weight: 600; color: #0073aa; font-size: 13px;">
                        <span style="font-size: 16px;">🛡️</span> <?php _e('Privacy Protection', 'malisafi-mls'); ?>
                    </p>
                    <p style="margin: 0; font-size: 12px; line-height: 1.4;">
                        <?php _e('Your exact GPS location will be automatically offset by 200-400 meters on public maps for security. Admins see the precise location. Please enter accurate coordinates.', 'malisafi-mls'); ?>
                    </p>
                </div>
            </div>

            <div id="property-map" class="property-map"></div>
        </div>

        <!-- Step 4: Features & Amenities -->
        <div class="wizard-step" id="step-4">
            <h2><?php _e('Features & Amenities', 'malisafi-mls'); ?></h2>
            <p class="step-description"><?php _e('Select all that apply to your property', 'malisafi-mls'); ?></p>
            
            <div class="features-section">
                <h3><?php _e('Key Features', 'malisafi-mls'); ?></h3>
                <div class="checkbox-grid">
                    <label class="checkbox-item">
                        <input type="checkbox" name="features[]" value="parking">
                        <span class="icon">🚗</span>
                        <span class="label"><?php _e('Parking', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="features[]" value="garden">
                        <span class="icon">🌳</span>
                        <span class="label"><?php _e('Garden', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="features[]" value="balcony">
                        <span class="icon">🏠</span>
                        <span class="label"><?php _e('Balcony', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="features[]" value="terrace">
                        <span class="icon">☀️</span>
                        <span class="label"><?php _e('Terrace', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="features[]" value="pool">
                        <span class="icon">🏊</span>
                        <span class="label"><?php _e('Swimming Pool', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="features[]" value="gym">
                        <span class="icon">💪</span>
                        <span class="label"><?php _e('Gym', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="features[]" value="security">
                        <span class="icon">🔒</span>
                        <span class="label"><?php _e('24/7 Security', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="features[]" value="furnished">
                        <span class="icon">🛋️</span>
                        <span class="label"><?php _e('Furnished', 'malisafi-mls'); ?></span>
                    </label>
                </div>
            </div>

            <div class="features-section">
                <h3><?php _e('Amenities', 'malisafi-mls'); ?></h3>
                <div class="checkbox-grid">
                    <label class="checkbox-item">
                        <input type="checkbox" name="amenities[]" value="wifi">
                        <span class="icon">📶</span>
                        <span class="label"><?php _e('WiFi', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="amenities[]" value="ac">
                        <span class="icon">❄️</span>
                        <span class="label"><?php _e('Air Conditioning', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="amenities[]" value="heating">
                        <span class="icon">🔥</span>
                        <span class="label"><?php _e('Heating', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="amenities[]" value="elevator">
                        <span class="icon">🛗</span>
                        <span class="label"><?php _e('Elevator', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="amenities[]" value="backup_generator">
                        <span class="icon">⚡</span>
                        <span class="label"><?php _e('Backup Generator', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="amenities[]" value="water_backup">
                        <span class="icon">💧</span>
                        <span class="label"><?php _e('Water Backup', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="amenities[]" value="playground">
                        <span class="icon">🎮</span>
                        <span class="label"><?php _e('Playground', 'malisafi-mls'); ?></span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="amenities[]" value="clubhouse">
                        <span class="icon">🏛️</span>
                        <span class="label"><?php _e('Clubhouse', 'malisafi-mls'); ?></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Step 5: Images -->
        <div class="wizard-step" id="step-5">
            <h2><?php _e('Property Images', 'malisafi-mls'); ?></h2>
            <p class="step-description"><?php _e('Upload high-quality images of your property (minimum 1 image required). Landscape images at least 1200x800 are recommended.', 'malisafi-mls'); ?></p>
            
            <!-- Drag & Drop Upload Area -->
            <div class="image-upload-area" id="dropzone">
                <div class="upload-placeholder">
                    <span class="upload-icon">📸</span>
                    <h3><?php _e('Drag & Drop Images Here', 'malisafi-mls'); ?></h3>
                    <p><?php _e('or', 'malisafi-mls'); ?></p>
                    <button type="button" class="btn btn-primary btn-browse-images">
                        <?php _e('Browse Images', 'malisafi-mls'); ?>
                    </button>
                    <input type="file" 
                           id="image-file-input" 
                           name="images[]" 
                           accept="image/jpeg,image/png,image/webp" 
                           multiple 
                           style="display:none;">
                    <p class="upload-hint">
                        <?php _e('Supported formats: JPG, PNG, WEBP (Max 10MB each)', 'malisafi-mls'); ?>
                    </p>
                </div>
            </div>

            <!-- Upload Progress -->
            <div class="upload-progress" style="display:none;">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <span class="progress-text">0%</span>
            </div>

            <!-- Image Gallery (Sortable) -->
            <div class="image-gallery" id="image-gallery"></div>

            <div class="gallery-hint">
                <p><?php _e('💡 Tip: Drag images to reorder. The first image will be the main photo.', 'malisafi-mls'); ?></p>
            </div>
        </div>

        <!-- Step 6: Review & Submit -->
        <div class="wizard-step" id="step-6">
            <h2><?php _e('Review Your Property', 'malisafi-mls'); ?></h2>
            <p class="step-description"><?php _e('Please review all information before submitting', 'malisafi-mls'); ?></p>
            
            <div class="property-preview">
                <div class="preview-section">
                    <h3><?php _e('Basic Information', 'malisafi-mls'); ?></h3>
                    <div class="preview-grid">
                        <div class="preview-item">
                            <span class="label"><?php _e('Title:', 'malisafi-mls'); ?></span>
                            <span class="value" id="preview-title">-</span>
                        </div>
                        <div class="preview-item">
                            <span class="label"><?php _e('Price:', 'malisafi-mls'); ?></span>
                            <span class="value" id="preview-price">-</span>
                        </div>
                        <div class="preview-item">
                            <span class="label"><?php _e('Type:', 'malisafi-mls'); ?></span>
                            <span class="value" id="preview-type">-</span>
                        </div>
                        <div class="preview-item">
                            <span class="label"><?php _e('Listing:', 'malisafi-mls'); ?></span>
                            <span class="value" id="preview-listing">-</span>
                        </div>
                    </div>
                </div>

                <div class="preview-section">
                    <h3><?php _e('Details', 'malisafi-mls'); ?></h3>
                    <div class="preview-grid">
                        <div class="preview-item">
                            <span class="label"><?php _e('Bedrooms:', 'malisafi-mls'); ?></span>
                            <span class="value" id="preview-bedrooms">-</span>
                        </div>
                        <div class="preview-item">
                            <span class="label"><?php _e('Bathrooms:', 'malisafi-mls'); ?></span>
                            <span class="value" id="preview-bathrooms">-</span>
                        </div>
                        <div class="preview-item">
                            <span class="label"><?php _e('Size:', 'malisafi-mls'); ?></span>
                            <span class="value" id="preview-size">-</span>
                        </div>
                        <div class="preview-item">
                            <span class="label"><?php _e('Condition:', 'malisafi-mls'); ?></span>
                            <span class="value" id="preview-condition">-</span>
                        </div>
                    </div>
                </div>

                <div class="preview-section">
                    <h3><?php _e('Location', 'malisafi-mls'); ?></h3>
                    <div class="preview-grid">
                        <div class="preview-item">
                            <span class="label"><?php _e('County:', 'malisafi-mls'); ?></span>
                            <span class="value" id="preview-county">-</span>
                        </div>
                        <div class="preview-item">
                            <span class="label"><?php _e('City:', 'malisafi-mls'); ?></span>
                            <span class="value" id="preview-city">-</span>
                        </div>
                    </div>
                </div>

                <div class="preview-section">
                    <h3><?php _e('Images', 'malisafi-mls'); ?></h3>
                    <div class="preview-images" id="preview-images">
                        <p class="no-images"><?php _e('No images uploaded', 'malisafi-mls'); ?></p>
                    </div>
                </div>
            </div>

            <div class="submit-notice">
                <p>
                    <strong><?php _e('Note:', 'malisafi-mls'); ?></strong>
                    <?php _e('Your property will be submitted for review. You will be notified once it is approved.', 'malisafi-mls'); ?>
                </p>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="wizard-navigation">
            <button type="button" class="btn btn-secondary btn-prev">
                <span class="icon">←</span>
                <?php _e('Previous', 'malisafi-mls'); ?>
            </button>
            <button type="button" class="btn btn-primary btn-next">
                <?php _e('Next', 'malisafi-mls'); ?>
                <span class="icon">→</span>
            </button>
            <button type="button" class="btn btn-success btn-submit" style="display:none;">
                <?php _e('Submit Property', 'malisafi-mls'); ?>
            </button>
        </div>
    </form>
</div>
