<?php
/**
 * Property Submission Wizard Template
 *
 * @package MalisafiMLS
 */
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$can_assign_agent = current_user_can('manage_options') || current_user_can('edit_others_properties') || current_user_can('malisafi_moderate_properties');

// Preset values passed from render_submission_form() — only used for new (non-edit) forms
$preset_listing_type  = !$property_id && isset($preset_listing_type)  ? $preset_listing_type  : '';
$preset_property_type = !$property_id && isset($preset_property_type) ? $preset_property_type : '';
$preset_county        = !$property_id && isset($preset_county)        ? $preset_county        : '';
?>

<div class="malisafi-submission-wizard">
    <!-- Progress Steps -->
    <div class="wizard-progress">
        <div class="progress-step" data-step="1">
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
    <form id="property-submission-form" class="wizard-form"
          data-preset-listing-type="<?php echo esc_attr($preset_listing_type); ?>"
          data-preset-property-type="<?php echo esc_attr($preset_property_type); ?>"
          data-preset-county="<?php echo esc_attr($preset_county); ?>">
        <input type="hidden" name="property_id" id="property_id" value="<?php echo esc_attr($property_id); ?>">

        <!-- Unified Error/Success Message Containers -->
        <div class="malisafi-form-error" style="display:none; margin-bottom: 20px;"></div>
        <div class="malisafi-form-success" style="display:none; margin-bottom: 20px;"></div>

        <!-- Step 1: Basic Information -->
        <div class="wizard-step" id="step-1">
            <h2><?php _e('Basic Information', 'malisafi-mls'); ?></h2>
            <p class="step-description"><?php _e('Tell us about your property', 'malisafi-mls'); ?></p>

            <div class="form-row">
                <label for="property_title" class="required"><?php _e('Property Title', 'malisafi-mls'); ?></label>
                <input type="text" id="property_title" name="title" class="form-control" placeholder="<?php echo esc_attr__('e.g., Modern 3-Bedroom Apartment in Westlands', 'malisafi-mls'); ?>" required>
                <span class="field-hint"><?php _e('Minimum 5 characters', 'malisafi-mls'); ?></span>
            </div>

            <div class="form-row">
                <label for="property_description"><?php _e('Description', 'malisafi-mls'); ?></label>
                <textarea id="property_description" name="description" class="form-control" rows="6" placeholder="<?php echo esc_attr__('Describe your property...', 'malisafi-mls'); ?>"></textarea>
                <span class="field-hint"><?php _e('Minimum 20 characters recommended', 'malisafi-mls'); ?></span>
            </div>

            <div class="form-row">
                <label for="reference_id"><?php _e('Property ID (MLS #)', 'malisafi-mls'); ?></label>
                <div class="gps-input-group">
                    <input type="text" id="reference_id" name="reference_id" class="form-control" placeholder="<?php echo esc_attr__('Not generated yet', 'malisafi-mls'); ?>" readonly>
                    <button type="button" class="btn btn-secondary btn-generate-ref" title="<?php echo esc_attr__('Generate ID', 'malisafi-mls'); ?>">
                        <?php _e('Generate ID', 'malisafi-mls'); ?>
                    </button>
                </div>
                <span class="field-hint"><?php _e('Click "Generate ID" or it will auto-generate on save.', 'malisafi-mls'); ?></span>
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="property_price" class="required"><?php _e('Price', 'malisafi-mls'); ?></label>
                    <input type="number" id="property_price" name="price" class="form-control" min="0" step="0.01" placeholder="5000000" required>
                </div>
                <div class="form-row">
                    <label for="property_currency" class="required"><?php _e('Currency', 'malisafi-mls'); ?></label>
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
                    <label for="property_type" class="required"><?php _e('Property Type', 'malisafi-mls'); ?></label>
                    <select id="property_type" name="property_type" class="form-control" required>
                        <option value=""><?php _e('Select type...', 'malisafi-mls'); ?></option>
                        <option value="house" <?php selected($preset_property_type, 'house'); ?>><?php _e('House', 'malisafi-mls'); ?></option>
                        <option value="apartment" <?php selected($preset_property_type, 'apartment'); ?>><?php _e('Apartment', 'malisafi-mls'); ?></option>
                        <option value="land" <?php selected($preset_property_type, 'land'); ?>><?php _e('Land', 'malisafi-mls'); ?></option>
                        <option value="commercial" <?php selected($preset_property_type, 'commercial'); ?>><?php _e('Commercial', 'malisafi-mls'); ?></option>
                        <option value="industrial" <?php selected($preset_property_type, 'industrial'); ?>><?php _e('Industrial', 'malisafi-mls'); ?></option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="listing_type" class="required"><?php _e('Listing Type', 'malisafi-mls'); ?></label>
                    <select id="listing_type" name="listing_type" class="form-control" required>
                        <option value=""><?php _e('Select...', 'malisafi-mls'); ?></option>
                        <option value="sale" <?php selected($preset_listing_type, 'sale'); ?>><?php _e('For Sale', 'malisafi-mls'); ?></option>
                        <option value="rent" <?php selected($preset_listing_type, 'rent'); ?>><?php _e('For Rent', 'malisafi-mls'); ?></option>
                        <option value="lease" <?php selected($preset_listing_type, 'lease'); ?>><?php _e('For Lease', 'malisafi-mls'); ?></option>
                        <option value="short_term" <?php selected($preset_listing_type, 'short_term'); ?>><?php _e('Short Term Rent (Airbnb)', 'malisafi-mls'); ?></option>
                    </select>
                </div>
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="ownership_type"><?php _e('Ownership Type', 'malisafi-mls'); ?></label>
                    <select id="ownership_type" name="ownership_type" class="form-control">
                        <option value=""><?php _e('Select...', 'malisafi-mls'); ?></option>
                        <option value="freehold"><?php _e('Freehold', 'malisafi-mls'); ?></option>
                        <option value="leasehold"><?php _e('Leasehold', 'malisafi-mls'); ?></option>
                        <option value="sectional"><?php _e('Sectional Title', 'malisafi-mls'); ?></option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="title_deed_status"><?php _e('Title Deed Status', 'malisafi-mls'); ?></label>
                    <select id="title_deed_status" name="title_deed_status" class="form-control">
                        <option value=""><?php _e('Select...', 'malisafi-mls'); ?></option>
                        <option value="available"><?php _e('Available / Ready', 'malisafi-mls'); ?></option>
                        <option value="processing"><?php _e('In Progress / Processing', 'malisafi-mls'); ?></option>
                        <option value="mother_title"><?php _e('Mother Title (Subdivision)', 'malisafi-mls'); ?></option>
                        <option value="none"><?php _e('Allocation Letter only', 'malisafi-mls'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Basic Details: Size (moved from Step 2) -->
            <div class="form-row-group">
                <div class="form-row">
                    <label for="property_size" class="required"><?php _e('Size', 'malisafi-mls'); ?></label>
                    <input type="number" id="property_size" name="size" class="form-control" min="0" step="0.01" placeholder="120" required>
                </div>
                <div class="form-row">
                    <label for="size_unit" class="required"><?php _e('Unit', 'malisafi-mls'); ?></label>
                    <select id="size_unit" name="size_unit" class="form-control" required>
                        <option value="sqm"><?php _e('Square Meters', 'malisafi-mls'); ?></option>
                        <option value="sqft"><?php _e('Square Feet', 'malisafi-mls'); ?></option>
                        <option value="acres"><?php _e('Acres', 'malisafi-mls'); ?></option>
                        <option value="hectares"><?php _e('Hectares', 'malisafi-mls'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Land Basics: Zoning / Road Access (moved from Step 2) -->
            <div class="form-row-group type-field" data-show-for="land">
                <div class="form-row">
                    <label for="land_use"><?php _e('Zoning / Land Use', 'malisafi-mls'); ?></label>
                    <select id="land_use" name="land_use" class="form-control">
                        <option value=""><?php _e('Select...', 'malisafi-mls'); ?></option>
                        <option value="residential"><?php _e('Residential', 'malisafi-mls'); ?></option>
                        <option value="commercial"><?php _e('Commercial', 'malisafi-mls'); ?></option>
                        <option value="agricultural"><?php _e('Agricultural', 'malisafi-mls'); ?></option>
                        <option value="industrial"><?php _e('Industrial', 'malisafi-mls'); ?></option>
                        <option value="mixed_use"><?php _e('Mixed Use', 'malisafi-mls'); ?></option>
                        <option value="conservation"><?php _e('Conservation', 'malisafi-mls'); ?></option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="road_access"><?php _e('Road Access', 'malisafi-mls'); ?></label>
                    <select id="road_access" name="road_access" class="form-control">
                        <option value=""><?php _e('Select...', 'malisafi-mls'); ?></option>
                        <option value="tarmac"><?php _e('Tarmac Road', 'malisafi-mls'); ?></option>
                        <option value="murram"><?php _e('Murram / Gravel Road', 'malisafi-mls'); ?></option>
                        <option value="footpath"><?php _e('Footpath Only', 'malisafi-mls'); ?></option>
                        <option value="none"><?php _e('No Road Access', 'malisafi-mls'); ?></option>
                    </select>
                </div>
            </div>

            <?php if ($can_assign_agent) : ?>
            <div class="form-row">
                <label for="agent_user_id"><?php _e('Assign to Agent (System User)', 'malisafi-mls'); ?></label>
                <select id="agent_user_id" name="agent_user_id" class="form-control">
                    <option value=""><?php _e('Select an Agent...', 'malisafi-mls'); ?></option>
                    <?php
                    $agents = get_users(array(
                        'role__in' => array('malisafi_agent_basic', 'malisafi_agent_premium', 'administrator'),
                        'orderby' => 'display_name'
                    ));
                    $current_author = 0;
                    if (isset($_GET['property_id'])) {
                        $prop = get_post(intval($_GET['property_id']));
                        if ($prop) $current_author = $prop->post_author;
                    } elseif (isset($property_id)) {
                        $prop = get_post($property_id);
                        if ($prop) $current_author = $prop->post_author;
                    }
                    
                    if (empty($current_author)) {
                        $current_author = get_current_user_id();
                    }

                    foreach ($agents as $agent) {
                        echo '<option value="' . esc_attr($agent->ID) . '" ' . selected($current_author, $agent->ID, false) . '>' . esc_html($agent->display_name) . ' (' . esc_html($agent->user_email) . ')</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="agent_name"><?php _e('Public Display Name', 'malisafi-mls'); ?></label>
                    <input type="text" id="agent_name" name="agent_name" class="form-control" placeholder="<?php echo esc_attr__('e.g., John Doe', 'malisafi-mls'); ?>">
                </div>
                <div class="form-row">
                    <label for="agent_email"><?php _e('Public Display Email', 'malisafi-mls'); ?></label>
                    <input type="email" id="agent_email" name="agent_email" class="form-control" placeholder="agent@example.com">
                </div>
            </div>
            <div class="form-row">
                <label for="agent_phone"><?php _e('Public Display Phone', 'malisafi-mls'); ?></label>
                <input type="text" id="agent_phone" name="agent_phone" class="form-control" placeholder="<?php echo esc_attr__('+254700000000', 'malisafi-mls'); ?>">
            </div>
            <?php endif; ?>
        </div>

        <!-- Step 2: Details -->
        <div class="wizard-step" id="step-2">
            <h2 id="step-2-title"><?php _e('Property Details', 'malisafi-mls'); ?></h2>
            <p class="step-description" id="step-2-desc"><?php _e('Add specific details about your property', 'malisafi-mls'); ?></p>

            <!-- Residential: Bedrooms / Bathrooms (house, apartment) -->
            <div class="form-row-group type-field" data-show-for="house,apartment">
                <div class="form-row">
                    <label for="bedrooms"><?php _e('Bedrooms', 'malisafi-mls'); ?></label>
                    <input type="number" id="bedrooms" name="bedrooms" class="form-control" min="0" max="50" value="0">
                </div>
                <div class="form-row">
                    <label for="bathrooms"><?php _e('Bathrooms', 'malisafi-mls'); ?></label>
                    <input type="number" id="bathrooms" name="bathrooms" class="form-control" min="0" max="50" value="0">
                </div>
            </div>

            <!-- Apartment: Floor number / Total floors -->
            <div class="form-row-group type-field" data-show-for="apartment">
                <div class="form-row">
                    <label for="floor_number"><?php _e('Floor Number', 'malisafi-mls'); ?></label>
                    <input type="number" id="floor_number" name="floor_number" class="form-control" min="0" max="200" placeholder="3">
                </div>
                <div class="form-row">
                    <label for="total_floors"><?php _e('Total Floors in Building', 'malisafi-mls'); ?></label>
                    <input type="number" id="total_floors" name="total_floors" class="form-control" min="1" max="200" placeholder="10">
                </div>
            </div>

            <!-- Commercial: Rooms/Offices count / Parking spaces -->
            <div class="form-row-group type-field" data-show-for="commercial">
                <div class="form-row">
                    <label for="office_spaces"><?php _e('Office / Shop Units', 'malisafi-mls'); ?></label>
                    <input type="number" id="office_spaces" name="office_spaces" class="form-control" min="0" placeholder="4">
                </div>
                <div class="form-row">
                    <label for="parking_spaces"><?php _e('Parking Spaces', 'malisafi-mls'); ?></label>
                    <input type="number" id="parking_spaces" name="parking_spaces" class="form-control" min="0" placeholder="10">
                </div>
            </div>
            <div class="form-row-group type-field" data-show-for="commercial">
                <div class="form-row">
                    <label for="floor_number_c"><?php _e('Floor Level', 'malisafi-mls'); ?></label>
                    <input type="number" id="floor_number_c" name="floor_number" class="form-control" min="0" max="200" placeholder="1">
                </div>
                <div class="form-row">
                    <label for="total_floors_c"><?php _e('Total Floors in Building', 'malisafi-mls'); ?></label>
                    <input type="number" id="total_floors_c" name="total_floors" class="form-control" min="1" max="200" placeholder="5">
                </div>
            </div>

            <!-- Industrial: Loading bays / Power capacity / Ceiling height -->
            <div class="form-row-group type-field" data-show-for="industrial">
                <div class="form-row">
                    <label for="loading_bays"><?php _e('Loading Bays', 'malisafi-mls'); ?></label>
                    <input type="number" id="loading_bays" name="loading_bays" class="form-control" min="0" placeholder="2">
                </div>
                <div class="form-row">
                    <label for="power_capacity_kva"><?php _e('Power Capacity (kVA)', 'malisafi-mls'); ?></label>
                    <input type="number" id="power_capacity_kva" name="power_capacity_kva" class="form-control" min="0" step="0.1" placeholder="100">
                </div>
            </div>
            <div class="form-row type-field" data-show-for="industrial">
                <label for="ceiling_height_m"><?php _e('Clear Ceiling Height (m)', 'malisafi-mls'); ?></label>
                <input type="number" id="ceiling_height_m" name="ceiling_height_m" class="form-control" min="0" step="0.1" placeholder="6">
            </div>



            <!-- Building types: Year built / Condition -->
            <div class="form-row-group type-field" data-show-for="house,apartment,commercial,industrial">
                <div class="form-row">
                    <label for="year_built"><?php _e('Year Built', 'malisafi-mls'); ?></label>
                    <input type="number" id="year_built" name="year_built" class="form-control" min="1800" max="<?php echo date('Y') + 5; ?>" placeholder="<?php echo date('Y'); ?>">
                </div>
                <div class="form-row">
                    <label for="condition"><?php _e('Condition', 'malisafi-mls'); ?></label>
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






        </div>

        <!-- Step 3: Location -->
        <div class="wizard-step" id="step-3">
            <h2><?php _e('Location', 'malisafi-mls'); ?></h2>
            <p class="step-description"><?php _e('Where is your property located?', 'malisafi-mls'); ?></p>

            <div class="form-row">
                <label for="property_address"><?php _e('Street Address', 'malisafi-mls'); ?></label>
                <input type="text" id="property_address" name="address" class="form-control" placeholder="<?php echo esc_attr__('e.g., Waiyaki Way, Building Name', 'malisafi-mls'); ?>">
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="property_county" class="required"><?php _e('County', 'malisafi-mls'); ?></label>
                    <select id="property_county" name="county" class="form-control" required>
                        <option value=""><?php _e('Select county...', 'malisafi-mls'); ?></option>
                        <?php if (function_exists('malisafi_get_kenya_counties')): ?>
                            <?php foreach (malisafi_get_kenya_counties() as $county): ?>
                                <option value="<?php echo esc_attr($county); ?>" <?php selected($preset_county, $county); ?>><?php echo esc_html($county); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="property_subcounty" class="required"><?php _e('Subcounty', 'malisafi-mls'); ?></label>
                    <select id="property_subcounty" name="subcounty" class="form-control" required>
                        <option value=""><?php _e('Select subcounty...', 'malisafi-mls'); ?></option>
                    </select>
                </div>
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="property_city"><?php _e('City/Town', 'malisafi-mls'); ?></label>
                    <input type="text" id="property_city" name="city" class="form-control" placeholder="<?php echo esc_attr__('e.g., Westlands', 'malisafi-mls'); ?>">
                </div>
                <div class="form-row">
                    <label for="property_area"><?php _e('Area/Neighborhood', 'malisafi-mls'); ?></label>
                    <input type="text" id="property_area" name="area" class="form-control" placeholder="<?php echo esc_attr__('e.g., Parklands, Lavington', 'malisafi-mls'); ?>">
                </div>
            </div>

            <div class="form-row">
                <label for="property_gps"><?php _e('GPS Coordinates (Optional)', 'malisafi-mls'); ?></label>
                <div class="gps-input-group">
                    <input type="text" id="property_gps" name="gps" class="form-control" placeholder="-1.2921, 36.8219">
                    <button type="button" class="btn btn-secondary btn-get-location">
                        <span class="icon">📍</span>
                        <?php _e('Get My Location', 'malisafi-mls'); ?>
                    </button>
                </div>
            </div>

            <div class="form-row">
                <label for="google_maps_url"><?php _e('Google Maps URL (Optional)', 'malisafi-mls'); ?></label>
                <div class="maps-input-group">
                    <input type="url" id="google_maps_url" name="google_maps_url" class="form-control" placeholder="https://maps.google.com/?q=-1.2921,36.8219">
                    <button type="button" class="btn btn-secondary btn-extract-coords">
                        <span class="icon">📌</span>
                        <?php _e('Extract Coordinates', 'malisafi-mls'); ?>
                    </button>
                </div>
                <p class="field-hint"><?php _e('Paste a Google Maps URL to automatically extract GPS coordinates.', 'malisafi-mls'); ?></p>
            </div>

            <div class="field-hint privacy-notice" style="background: #e8f4f8; padding: 10px; border-radius: 4px; margin-top: 8px; border-left: 3px solid #0073aa;">
                <p style="margin: 0 0 5px 0; font-weight: 600; color: #0073aa; font-size: 13px;">
                    <span style="font-size: 16px;">🛡️</span> <?php _e('Privacy Protection', 'malisafi-mls'); ?>
                </p>
                <p style="margin: 0; font-size: 13px; line-height: 1.5;">
                    <?php _e('For your security, exact GPS coordinates are offset slightly on public maps. Administrators can still see accurate coordinates.', 'malisafi-mls'); ?>
                </p>
            </div>
        </div>

        <!-- Step 4: Features & Amenities -->
        <div class="wizard-step" id="step-4">
            <h2><?php _e('Features & Amenities', 'malisafi-mls'); ?></h2>
            <p class="step-description"><?php _e('Select all that apply to your property', 'malisafi-mls'); ?></p>

            <!-- Residential Features (house, apartment) -->
            <div class="features-section type-field" data-show-for="house,apartment">
                <h3><?php _e('Residential Features', 'malisafi-mls'); ?></h3>
                <div class="checkbox-grid">
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="parking"> <span class="icon">🚗</span> <span class="label"><?php _e('Parking', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="garden"> <span class="icon">🌳</span> <span class="label"><?php _e('Garden', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="balcony"> <span class="icon">🏠</span> <span class="label"><?php _e('Balcony', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="terrace"> <span class="icon">☀️</span> <span class="label"><?php _e('Terrace', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="pool"> <span class="icon">🏊</span> <span class="label"><?php _e('Swimming Pool', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="gym"> <span class="icon">💪</span> <span class="label"><?php _e('Gym', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="security"> <span class="icon">🔒</span> <span class="label"><?php _e('24/7 Security', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="furnished"> <span class="icon">🛋️</span> <span class="label"><?php _e('Furnished', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="playground"> <span class="icon">🎮</span> <span class="label"><?php _e('Playground', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="clubhouse"> <span class="icon">🏛️</span> <span class="label"><?php _e('Clubhouse', 'malisafi-mls'); ?></span></label>
                </div>
            </div>

            <!-- Residential Amenities (house, apartment) -->
            <div class="features-section type-field" data-show-for="house,apartment">
                <h3><?php _e('Amenities', 'malisafi-mls'); ?></h3>
                <div class="checkbox-grid">
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="wifi"> <span class="icon">📶</span> <span class="label"><?php _e('WiFi', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="ac"> <span class="icon">❄️</span> <span class="label"><?php _e('Air Conditioning', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="heating"> <span class="icon">🔥</span> <span class="label"><?php _e('Heating', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="elevator"> <span class="icon">🛗</span> <span class="label"><?php _e('Elevator', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="backup_generator"> <span class="icon">⚡</span> <span class="label"><?php _e('Backup Generator', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="water_backup"> <span class="icon">💧</span> <span class="label"><?php _e('Water Backup', 'malisafi-mls'); ?></span></label>
                </div>
            </div>

            <!-- Commercial Features -->
            <div class="features-section type-field" data-show-for="commercial">
                <h3><?php _e('Commercial Features', 'malisafi-mls'); ?></h3>
                <div class="checkbox-grid">
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="parking"> <span class="icon">🚗</span> <span class="label"><?php _e('Parking', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="security"> <span class="icon">🔒</span> <span class="label"><?php _e('24/7 Security', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="terrace"> <span class="icon">☀️</span> <span class="label"><?php _e('Outdoor Terrace', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="loading_area"> <span class="icon">🏗️</span> <span class="label"><?php _e('Loading Area', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="reception"> <span class="icon">🏢</span> <span class="label"><?php _e('Reception Area', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="boardroom"> <span class="icon">📋</span> <span class="label"><?php _e('Boardroom', 'malisafi-mls'); ?></span></label>
                </div>
            </div>
            <div class="features-section type-field" data-show-for="commercial">
                <h3><?php _e('Commercial Amenities', 'malisafi-mls'); ?></h3>
                <div class="checkbox-grid">
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="elevator"> <span class="icon">🛗</span> <span class="label"><?php _e('Elevator', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="ac"> <span class="icon">❄️</span> <span class="label"><?php _e('Air Conditioning', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="backup_generator"> <span class="icon">⚡</span> <span class="label"><?php _e('Backup Generator', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="water_backup"> <span class="icon">💧</span> <span class="label"><?php _e('Water Backup', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="wifi"> <span class="icon">📶</span> <span class="label"><?php _e('WiFi Ready', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="cctv"> <span class="icon">📷</span> <span class="label"><?php _e('CCTV', 'malisafi-mls'); ?></span></label>
                </div>
            </div>

            <!-- Industrial Features -->
            <div class="features-section type-field" data-show-for="industrial">
                <h3><?php _e('Industrial Features', 'malisafi-mls'); ?></h3>
                <div class="checkbox-grid">
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="parking"> <span class="icon">🚗</span> <span class="label"><?php _e('Truck Parking', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="security"> <span class="icon">🔒</span> <span class="label"><?php _e('24/7 Security', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="loading_area"> <span class="icon">🏗️</span> <span class="label"><?php _e('Loading Bays', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="crane"> <span class="icon">🏗️</span> <span class="label"><?php _e('Overhead Crane', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="cold_storage"> <span class="icon">🧊</span> <span class="label"><?php _e('Cold Storage', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="sprinkler_system"> <span class="icon">💦</span> <span class="label"><?php _e('Sprinkler System', 'malisafi-mls'); ?></span></label>
                </div>
            </div>
            <div class="features-section type-field" data-show-for="industrial">
                <h3><?php _e('Industrial Utilities', 'malisafi-mls'); ?></h3>
                <div class="checkbox-grid">
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="backup_generator"> <span class="icon">⚡</span> <span class="label"><?php _e('Backup Generator', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="water_backup"> <span class="icon">💧</span> <span class="label"><?php _e('Water Backup', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="three_phase_power"> <span class="icon">⚡</span> <span class="label"><?php _e('3-Phase Power', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="amenities[]" value="wastewater_treatment"> <span class="icon">🌊</span> <span class="label"><?php _e('Wastewater Treatment', 'malisafi-mls'); ?></span></label>
                </div>
            </div>

            <!-- Land Features (Utilities moved from Step 2) -->
            <div class="features-section type-field" data-show-for="land">
                <h3><?php _e('Land Utilities', 'malisafi-mls'); ?></h3>
                <div class="checkbox-grid">
                    <label class="checkbox-item"><input type="checkbox" name="land_utilities[]" value="electricity"> <span class="icon">⚡</span> <span class="label"><?php _e('Electricity', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="land_utilities[]" value="water"> <span class="icon">💧</span> <span class="label"><?php _e('Water Supply', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="land_utilities[]" value="sewer"> <span class="icon">🌊</span> <span class="label"><?php _e('Sewer / Drainage', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="land_utilities[]" value="fibre"> <span class="icon">📶</span> <span class="label"><?php _e('Fibre / Internet', 'malisafi-mls'); ?></span></label>
                </div>
            </div>
            <div class="features-section type-field" data-show-for="land">
                <h3><?php _e('Land Features', 'malisafi-mls'); ?></h3>
                <div class="checkbox-grid">
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="security"> <span class="icon">🔒</span> <span class="label"><?php _e('Perimeter Wall / Fence', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="borehole"> <span class="icon">💧</span> <span class="label"><?php _e('Borehole', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="mature_trees"> <span class="icon">🌳</span> <span class="label"><?php _e('Mature Trees', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="flat_terrain"> <span class="icon">📐</span> <span class="label"><?php _e('Flat Terrain', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="river_frontage"> <span class="icon">🌊</span> <span class="label"><?php _e('River Frontage', 'malisafi-mls'); ?></span></label>
                    <label class="checkbox-item"><input type="checkbox" name="features[]" value="survey_done"> <span class="icon">📏</span> <span class="label"><?php _e('Survey Done', 'malisafi-mls'); ?></span></label>
                </div>
            </div>

            <div class="sale-lease-details" style="display: none; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
                <h3><?php _e('Buyer & Investor Details (Sale/Lease)', 'malisafi-mls'); ?></h3>
                <p class="step-description"><?php _e('Optional details for buyers and investors', 'malisafi-mls'); ?></p>

                <div class="form-row">
                    <label for="floor_plan_urls"><?php _e('Floor Plans (URLs)', 'malisafi-mls'); ?></label>
                    <textarea id="floor_plan_urls" name="floor_plan_urls" class="form-control" rows="3" placeholder="https://..."></textarea>
                </div>

                <div class="form-row-group">
                    <div class="form-row">
                        <label for="expected_roi"><?php _e('Expected ROI (%)', 'malisafi-mls'); ?></label>
                        <input type="number" id="expected_roi" name="expected_roi" class="form-control" min="0" max="100" step="0.01" placeholder="12.5">
                    </div>
                    <div class="form-row">
                        <label for="rental_yield"><?php _e('Rental Yield (%)', 'malisafi-mls'); ?></label>
                        <input type="number" id="rental_yield" name="rental_yield" class="form-control" min="0" max="100" step="0.01" placeholder="8.0">
                    </div>
                </div>

                <div class="form-row">
                    <label for="annual_rent_income"><?php _e('Expected Annual Income', 'malisafi-mls'); ?></label>
                    <input type="number" id="annual_rent_income" name="annual_rent_income" class="form-control" min="0" step="0.01" placeholder="1200000">
                </div>

                <div class="form-row">
                    <label for="developer_guarantee"><?php _e('Developer Guarantees', 'malisafi-mls'); ?></label>
                    <textarea id="developer_guarantee" name="developer_guarantee" class="form-control" rows="2"></textarea>
                </div>

                <div class="form-row">
                    <label><?php _e('Financing Options', 'malisafi-mls'); ?></label>
                    <div class="checkbox-grid">
                        <label><input type="checkbox" name="financing_options[]" value="bank_mortgage"> <?php _e('Bank Mortgage', 'malisafi-mls'); ?></label>
                        <label><input type="checkbox" name="financing_options[]" value="developer_finance"> <?php _e('Developer Financing', 'malisafi-mls'); ?></label>
                        <label><input type="checkbox" name="financing_options[]" value="installments"> <?php _e('Installments', 'malisafi-mls'); ?></label>
                        <label><input type="checkbox" name="financing_options[]" value="cash"> <?php _e('Cash', 'malisafi-mls'); ?></label>
                        <label><input type="checkbox" name="financing_options[]" value="diaspora"> <?php _e('Diaspora Financing', 'malisafi-mls'); ?></label>
                    </div>
                </div>

                <div class="form-row-group">
                    <div class="form-row">
                        <label for="financing_min_deposit"><?php _e('Min Deposit (%)', 'malisafi-mls'); ?></label>
                        <input type="number" id="financing_min_deposit" name="financing_min_deposit" class="form-control" min="0" max="100" step="0.01" placeholder="20">
                    </div>
                    <div class="form-row">
                        <label for="financing_tenor_months"><?php _e('Tenor (months)', 'malisafi-mls'); ?></label>
                        <input type="number" id="financing_tenor_months" name="financing_tenor_months" class="form-control" min="0" max="600" step="1" placeholder="120">
                    </div>
                </div>

                <div class="form-row-group">
                    <div class="form-row">
                        <label for="financing_interest_rate"><?php _e('Interest Rate (%)', 'malisafi-mls'); ?></label>
                        <input type="number" id="financing_interest_rate" name="financing_interest_rate" class="form-control" min="0" max="100" step="0.01" placeholder="12">
                    </div>
                    <div class="form-row">
                        <label for="diaspora_financing_details"><?php _e('Diaspora Financing Details', 'malisafi-mls'); ?></label>
                        <input type="text" id="diaspora_financing_details" name="diaspora_financing_details" class="form-control" placeholder="e.g., USD financing, KYC required">
                    </div>
                </div>

                <div class="form-row">
                    <label><?php _e('Sustainability', 'malisafi-mls'); ?></label>
                    <div class="checkbox-grid">
                        <label><input type="checkbox" name="sustainability[]" value="solar"> <?php _e('Solar', 'malisafi-mls'); ?></label>
                        <label><input type="checkbox" name="sustainability[]" value="water_harvesting"> <?php _e('Water Harvesting', 'malisafi-mls'); ?></label>
                    </div>
                </div>

                <div class="form-row">
                    <label for="green_certification"><?php _e('Green Certification', 'malisafi-mls'); ?></label>
                    <input type="text" id="green_certification" name="green_certification" class="form-control" placeholder="EDGE, LEED, etc.">
                </div>
            </div>
        </div>

        <!-- Step 5: Images -->
        <div class="wizard-step" id="step-5">
            <h2><span class="icon">🖼️</span> <?php _e('Property Media', 'malisafi-mls'); ?></h2>
            <p class="step-description"><?php _e('Upload a stunning high-resolution featured image followed by your gallery photos.', 'malisafi-mls'); ?></p>

            <div class="image-section featured-section">
                <h3><span class="icon">⭐</span> <?php _e('Featured Image (Mandatory)', 'malisafi-mls'); ?></h3>
                <p class="field-hint">
                    <?php _e('Recommended: landscape photo at least 1600x900 for the best cover quality.', 'malisafi-mls'); ?>
                </p>
                <p class="field-hint">
                    <?php _e('Featured image is required to submit your listing.', 'malisafi-mls'); ?>
                </p>

                <div class="image-upload-area" id="featured-dropzone">
                    <div class="upload-placeholder">
                        <span class="upload-icon">🖼️</span>
                        <h4><?php _e('Drag & Drop Featured Image', 'malisafi-mls'); ?></h4>
                        <p><?php _e('or', 'malisafi-mls'); ?></p>
                        <button type="button" class="btn btn-primary btn-browse-featured">
                            <?php _e('Choose Featured Image', 'malisafi-mls'); ?>
                        </button>
                        <input type="file" id="featured-file-input" name="featured_image" accept="image/jpeg,image/png,image/webp" style="display:none;">
                        <p class="upload-hint"><?php _e('Supported formats: JPG, PNG, WEBP (Max 10MB)', 'malisafi-mls'); ?></p>
                    </div>
                </div>

                <div class="featured-preview" id="featured-preview" style="display:none;">
                    <img src="" alt="">
                    <button type="button" class="btn btn-secondary btn-replace-featured">
                        <?php _e('Replace Featured Image', 'malisafi-mls'); ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-remove-featured">
                        <?php _e('Remove', 'malisafi-mls'); ?>
                    </button>
                </div>

                <input type="hidden" id="featured_image_id" name="featured_image_id" value="">
            </div>

            <div class="image-section gallery-section">
                <h3><span class="icon">🖼️</span> <?php _e('Property Gallery', 'malisafi-mls'); ?></h3>
                <p class="field-hint">
                    <?php _e('Add up to 15 additional images. Landscape images at least 1200x800 are recommended.', 'malisafi-mls'); ?>
                </p>

                <div class="image-upload-area" id="dropzone">
                    <div class="upload-placeholder">
                        <span class="upload-icon">📸</span>
                        <h4><?php _e('Drag & Drop Gallery Images', 'malisafi-mls'); ?></h4>
                        <p><?php _e('or', 'malisafi-mls'); ?></p>
                        <button type="button" class="btn btn-primary btn-browse-images"><?php _e('Browse Images', 'malisafi-mls'); ?></button>
                        <input type="file" id="image-file-input" name="images[]" accept="image/jpeg,image/png,image/webp" multiple style="display:none;">
                        <input type="file" id="replace-image-input" name="replace_image" accept="image/jpeg,image/png,image/webp" style="display:none;">
                        <p class="upload-hint"><?php _e('Supported formats: JPG, PNG, WEBP (Max 10MB each)', 'malisafi-mls'); ?></p>
                    </div>
                </div>

                <div class="upload-progress" style="display:none;">
                    <div class="progress-bar"><div class="progress-fill"></div></div>
                    <span class="progress-text">0%</span>
                </div>

                <div class="image-gallery" id="image-gallery"></div>
                <div class="gallery-hint">
                    <p><?php _e('💡 Tip: Drag images to reorder your gallery.', 'malisafi-mls'); ?></p>
                </div>
                <input type="hidden" id="gallery_ids" name="gallery_ids" value="">
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
                        <div class="preview-item"><span class="label"><?php _e('Title:', 'malisafi-mls'); ?></span> <span class="value" id="preview-title">-</span></div>
                        <div class="preview-item"><span class="label"><?php _e('Price:', 'malisafi-mls'); ?></span> <span class="value" id="preview-price">-</span></div>
                        <div class="preview-item"><span class="label"><?php _e('Type:', 'malisafi-mls'); ?></span> <span class="value" id="preview-type">-</span></div>
                        <div class="preview-item"><span class="label"><?php _e('Listing:', 'malisafi-mls'); ?></span> <span class="value" id="preview-listing">-</span></div>
                    </div>
                </div>

                <div class="preview-section">
                    <h3><?php _e('Details', 'malisafi-mls'); ?></h3>
                    <div class="preview-grid">
                        <div class="preview-item"><span class="label"><?php _e('Bedrooms:', 'malisafi-mls'); ?></span> <span class="value" id="preview-bedrooms">-</span></div>
                        <div class="preview-item"><span class="label"><?php _e('Bathrooms:', 'malisafi-mls'); ?></span> <span class="value" id="preview-bathrooms">-</span></div>
                        <div class="preview-item"><span class="label"><?php _e('Size:', 'malisafi-mls'); ?></span> <span class="value" id="preview-size">-</span></div>
                        <div class="preview-item"><span class="label"><?php _e('Condition:', 'malisafi-mls'); ?></span> <span class="value" id="preview-condition">-</span></div>
                    </div>
                </div>

                <div class="preview-section">
                    <h3><?php _e('Location', 'malisafi-mls'); ?></h3>
                    <div class="preview-grid">
                        <div class="preview-item"><span class="label"><?php _e('County:', 'malisafi-mls'); ?></span> <span class="value" id="preview-county">-</span></div>
                        <div class="preview-item"><span class="label"><?php _e('Subcounty:', 'malisafi-mls'); ?></span> <span class="value" id="preview-subcounty">-</span></div>
                        <div class="preview-item"><span class="label"><?php _e('Town:', 'malisafi-mls'); ?></span> <span class="value" id="preview-city">-</span></div>
                    </div>
                </div>

                <div class="preview-section">
                    <h3><?php _e('Featured Image', 'malisafi-mls'); ?></h3>
                    <div class="preview-images" id="preview-featured">
                        <p class="no-images"><?php _e('No featured image uploaded', 'malisafi-mls'); ?></p>
                    </div>
                </div>

                <div class="preview-section">
                    <h3><?php _e('Gallery Images', 'malisafi-mls'); ?></h3>
                    <div class="preview-images" id="preview-images">
                        <p class="no-images"><?php _e('No images uploaded', 'malisafi-mls'); ?></p>
                    </div>
                </div>
            </div>

            <div class="submit-notice">
                <p><strong><?php _e('Note:', 'malisafi-mls'); ?></strong> <?php _e('Your property will be submitted for review. You will be notified once it is approved.', 'malisafi-mls'); ?></p>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="wizard-navigation">
            <button type="button" class="btn btn-secondary btn-prev"><span class="icon">←</span> <?php _e('Previous', 'malisafi-mls'); ?></button>
            <button type="button" class="btn btn-secondary btn-save-draft"><?php _e('Save Draft', 'malisafi-mls'); ?></button>
            <button type="button" class="btn btn-primary btn-next"><?php _e('Next', 'malisafi-mls'); ?> <span class="icon">→</span></button>
            <button type="button" class="btn btn-success btn-submit" style="display:none;"><?php _e('Submit Property', 'malisafi-mls'); ?></button>
        </div>
        <p class="field-hint draft-hint"><?php _e('Save a draft to continue later. Your progress is also auto-saved as you go.', 'malisafi-mls'); ?></p>
    </form>

    <!-- Initialize Property Submission Wizard -->
    <script>
        // Initialize the property submission wizard
        if (typeof PropertySubmission !== 'undefined') {
            PropertySubmission.init();
        }
    </script>
</div>
