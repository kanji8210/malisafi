<?php
/**
 * Complete Admin Property Form Template
 * All fields included with validation
 * @package MalisafiMLS
 */
if (!defined('ABSPATH')) exit;

// Get property data if editing
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$property = null;
$property_meta = array();

if ($property_id) {
    $property = get_post($property_id);
    // Get all meta data
    $meta_keys = array(
        'price', 'currency', 'listing_type', 'bedrooms', 'bathrooms', 'size', 'size_unit',
        'year_built', 'condition', 'parking', 'floors', 'address', 'county', 'city', 
        'area', 'gps', 'postal_code', 'features', 'amenities', 'video_url', 'virtual_tour',
        'agent_name', 'agent_email', 'agent_phone', 'reference_id', 'featured'
    );
    
    foreach ($meta_keys as $key) {
        $property_meta[$key] = get_post_meta($property_id, '_malisafi_' . $key, true);
    }
}

// Get Kenya counties
$kenya_counties = array(
    'Nairobi', 'Mombasa', 'Kwale', 'Kilifi', 'Tana River', 'Lamu', 'Taita-Taveta',
    'Garissa', 'Wajir', 'Mandera', 'Marsabit', 'Isiolo', 'Meru', 'Tharaka-Nithi',
    'Embu', 'Kitui', 'Machakos', 'Makueni', 'Nyandarua', 'Nyeri', 'Kirinyaga',
    'Murang\'a', 'Kiambu', 'Turkana', 'West Pokot', 'Samburu', 'Trans-Nzoia',
    'Uasin Gishu', 'Elgeyo-Marakwet', 'Nandi', 'Baringo', 'Laikipia', 'Nakuru',
    'Narok', 'Kajiado', 'Kericho', 'Bomet', 'Kakamega', 'Vihiga', 'Bungoma',
    'Busia', 'Siaya', 'Kisumu', 'Homa Bay', 'Migori', 'Kisii', 'Nyamira'
);
?>

<div class="wrap malisafi-admin-property-form">
    <h1><?php echo $property_id ? __('Edit Property', 'malisafi-mls') : __('Add New Property', 'malisafi-mls'); ?></h1>
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Property saved successfully!', 'malisafi-mls'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['error'])); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" class="malisafi-property-form">
        <?php wp_nonce_field('malisafi_submit_property', 'malisafi_property_nonce'); ?>
        <input type="hidden" name="action" value="malisafi_submit_property" />
        <input type="hidden" name="property_id" value="<?php echo esc_attr($property_id); ?>" />
        
        <!-- Basic Information Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Basic Information', 'malisafi-mls'); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="property_title"><?php _e('Property Title', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="property_title" id="property_title" 
                                   value="<?php echo $property ? esc_attr($property->post_title) : ''; ?>" 
                                   class="large-text" required 
                                   placeholder="<?php esc_attr_e('e.g., Modern 3-Bedroom Apartment in Westlands', 'malisafi-mls'); ?>" />
                            <p class="description"><?php _e('Enter a descriptive title for the property (minimum 5 characters)', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="property_description"><?php _e('Description', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <?php 
                            wp_editor(
                                $property ? $property->post_content : '', 
                                'property_description',
                                array(
                                    'textarea_name' => 'property_description',
                                    'textarea_rows' => 10,
                                    'media_buttons' => false,
                                    'teeny' => true
                                )
                            ); ?>
                            <p class="description"><?php _e('Detailed description of the property', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="property_price"><?php _e('Price', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="number" name="property_price" id="property_price" 
                                   value="<?php echo esc_attr($property_meta['price'] ?? ''); ?>" 
                                   class="regular-text" min="0" step="0.01" required 
                                   placeholder="5000000" />
                            <select name="property_currency" id="property_currency" required>
                                <option value="KES" <?php selected($property_meta['currency'] ?? 'KES', 'KES'); ?>>KES (Kenyan Shilling)</option>
                                <option value="USD" <?php selected($property_meta['currency'] ?? '', 'USD'); ?>>USD (US Dollar)</option>
                                <option value="EUR" <?php selected($property_meta['currency'] ?? '', 'EUR'); ?>>EUR (Euro)</option>
                                <option value="GBP" <?php selected($property_meta['currency'] ?? '', 'GBP'); ?>>GBP (British Pound)</option>
                            </select>
                            <p class="description"><?php _e('Enter the property price and select currency', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="property_type"><?php _e('Property Type', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <?php
                            $current_type = $property_id ? wp_get_object_terms($property_id, 'malisafi_property_type', array('fields' => 'ids')) : array();
                            $current_type = !empty($current_type) && !is_wp_error($current_type) ? $current_type[0] : '';
                            
                            wp_dropdown_categories(array(
                                'taxonomy' => 'malisafi_property_type',
                                'name' => 'property_type',
                                'id' => 'property_type',
                                'show_option_none' => __('Select Property Type', 'malisafi-mls'),
                                'option_none_value' => '',
                                'selected' => $current_type,
                                'hide_empty' => false,
                                'required' => true
                            ));
                            ?>
                            <p class="description"><?php _e('Select the type of property (house, apartment, land, etc.)', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="listing_type"><?php _e('Listing Type', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select name="listing_type" id="listing_type" required>
                                <option value=""><?php _e('Select...', 'malisafi-mls'); ?></option>
                                <option value="sale" <?php selected($property_meta['listing_type'] ?? '', 'sale'); ?>><?php _e('For Sale', 'malisafi-mls'); ?></option>
                                <option value="rent" <?php selected($property_meta['listing_type'] ?? '', 'rent'); ?>><?php _e('For Rent', 'malisafi-mls'); ?></option>
                                <option value="lease" <?php selected($property_meta['listing_type'] ?? '', 'lease'); ?>><?php _e('For Lease', 'malisafi-mls'); ?></option>
                            </select>
                            <p class="description"><?php _e('Is this property for sale, rent, or lease?', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="reference_id"><?php _e('Reference ID', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="reference_id" id="reference_id" 
                                   value="<?php echo esc_attr($property_meta['reference_id'] ?? ''); ?>" 
                                   class="regular-text" 
                                   placeholder="<?php esc_attr_e('e.g., PROP-2026-001', 'malisafi-mls'); ?>" />
                            <p class="description"><?php _e('Internal reference number (optional)', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Property Details Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Property Details', 'malisafi-mls'); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Bedrooms & Bathrooms', 'malisafi-mls'); ?></th>
                        <td>
                            <label for="bedrooms"><?php _e('Bedrooms:', 'malisafi-mls'); ?></label>
                            <input type="number" name="bedrooms" id="bedrooms" 
                                   value="<?php echo esc_attr($property_meta['bedrooms'] ?? '0'); ?>" 
                                   min="0" max="50" style="width: 80px;" />
                            
                            <label for="bathrooms" style="margin-left: 20px;"><?php _e('Bathrooms:', 'malisafi-mls'); ?></label>
                            <input type="number" name="bathrooms" id="bathrooms" 
                                   value="<?php echo esc_attr($property_meta['bathrooms'] ?? '0'); ?>" 
                                   min="0" max="50" style="width: 80px;" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Property Size', 'malisafi-mls'); ?></th>
                        <td>
                            <input type="number" name="size" id="size" 
                                   value="<?php echo esc_attr($property_meta['size'] ?? ''); ?>" 
                                   min="0" step="0.01" style="width: 150px;" 
                                   placeholder="120" />
                            
                            <select name="size_unit" id="size_unit" style="width: 150px;">
                                <option value="sqm" <?php selected($property_meta['size_unit'] ?? 'sqm', 'sqm'); ?>><?php _e('Square Meters', 'malisafi-mls'); ?></option>
                                <option value="sqft" <?php selected($property_meta['size_unit'] ?? '', 'sqft'); ?>><?php _e('Square Feet', 'malisafi-mls'); ?></option>
                                <option value="acres" <?php selected($property_meta['size_unit'] ?? '', 'acres'); ?>><?php _e('Acres', 'malisafi-mls'); ?></option>
                                <option value="hectares" <?php selected($property_meta['size_unit'] ?? '', 'hectares'); ?>><?php _e('Hectares', 'malisafi-mls'); ?></option>
                            </select>
                            <p class="description"><?php _e('Enter the size and select unit', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Additional Details', 'malisafi-mls'); ?></th>
                        <td>
                            <label for="year_built"><?php _e('Year Built:', 'malisafi-mls'); ?></label>
                            <input type="number" name="year_built" id="year_built" 
                                   value="<?php echo esc_attr($property_meta['year_built'] ?? ''); ?>" 
                                   min="1800" max="<?php echo date('Y') + 5; ?>" style="width: 100px;" 
                                   placeholder="<?php echo date('Y'); ?>" />
                            
                            <label for="parking" style="margin-left: 20px;"><?php _e('Parking Spaces:', 'malisafi-mls'); ?></label>
                            <input type="number" name="parking" id="parking" 
                                   value="<?php echo esc_attr($property_meta['parking'] ?? '0'); ?>" 
                                   min="0" max="20" style="width: 80px;" />
                            
                            <label for="floors" style="margin-left: 20px;"><?php _e('Floors:', 'malisafi-mls'); ?></label>
                            <input type="number" name="floors" id="floors" 
                                   value="<?php echo esc_attr($property_meta['floors'] ?? '1'); ?>" 
                                   min="1" max="100" style="width: 80px;" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="condition"><?php _e('Condition', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <select name="condition" id="condition">
                                <option value=""><?php _e('Select...', 'malisafi-mls'); ?></option>
                                <option value="new" <?php selected($property_meta['condition'] ?? '', 'new'); ?>><?php _e('New', 'malisafi-mls'); ?></option>
                                <option value="excellent" <?php selected($property_meta['condition'] ?? '', 'excellent'); ?>><?php _e('Excellent', 'malisafi-mls'); ?></option>
                                <option value="good" <?php selected($property_meta['condition'] ?? '', 'good'); ?>><?php _e('Good', 'malisafi-mls'); ?></option>
                                <option value="fair" <?php selected($property_meta['condition'] ?? '', 'fair'); ?>><?php _e('Fair', 'malisafi-mls'); ?></option>
                                <option value="renovation" <?php selected($property_meta['condition'] ?? '', 'renovation'); ?>><?php _e('Needs Renovation', 'malisafi-mls'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        
        <!-- Location Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Location', 'malisafi-mls'); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="address"><?php _e('Street Address', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="address" id="address" 
                                   value="<?php echo esc_attr($property_meta['address'] ?? ''); ?>" 
                                   class="large-text" 
                                   placeholder="<?php esc_attr_e('e.g., Waiyaki Way, ABC Building', 'malisafi-mls'); ?>" />
                            <p class="description"><?php _e('Full street address (optional)', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="county"><?php _e('County', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <select name="county" id="county" class="regular-text" required>
                                <option value=""><?php _e('Select County...', 'malisafi-mls'); ?></option>
                                <?php foreach ($kenya_counties as $county): ?>
                                    <option value="<?php echo esc_attr($county); ?>" <?php selected($property_meta['county'] ?? '', $county); ?>>
                                        <?php echo esc_html($county); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Select the county where the property is located', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="city"><?php _e('City/Town', 'malisafi-mls'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" name="city" id="city" 
                                   value="<?php echo esc_attr($property_meta['city'] ?? ''); ?>" 
                                   class="regular-text" required 
                                   placeholder="<?php esc_attr_e('e.g., Westlands', 'malisafi-mls'); ?>" />
                            <p class="description"><?php _e('City or town name', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="area"><?php _e('Area/Neighborhood', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="area" id="area" 
                                   value="<?php echo esc_attr($property_meta['area'] ?? ''); ?>" 
                                   class="regular-text" 
                                   placeholder="<?php esc_attr_e('e.g., Parklands, Lavington', 'malisafi-mls'); ?>" />
                            <p class="description"><?php _e('Specific area or neighborhood (optional)', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="property_gps"><?php _e('GPS Coordinates', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="property_gps" id="property_gps" 
                                   value="<?php echo esc_attr($property_meta['gps'] ?? ''); ?>" 
                                   class="regular-text" 
                                   placeholder="-1.2921, 36.8219" />
                            <button type="button" class="button" onclick="malisafiGetLocation()">
                                📍 <?php _e('Use My Location', 'malisafi-mls'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Using your current location helps enable map search and shows your property on the map. You can slightly adjust the coordinates for privacy before saving.', 'malisafi-mls'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="postal_code"><?php _e('Postal Code', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="postal_code" id="postal_code" 
                                   value="<?php echo esc_attr($property_meta['postal_code'] ?? ''); ?>" 
                                   class="regular-text" 
                                   placeholder="00100" />
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Features & Amenities Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Features & Amenities', 'malisafi-mls'); ?></h2>
            </div>
            <div class="inside">
                <?php
                $current_features = is_array($property_meta['features'] ?? null) ? $property_meta['features'] : array();
                $current_amenities = is_array($property_meta['amenities'] ?? null) ? $property_meta['amenities'] : array();
                
                $available_features = array(
                    'parking' => __('Parking', 'malisafi-mls'),
                    'garden' => __('Garden', 'malisafi-mls'),
                    'balcony' => __('Balcony', 'malisafi-mls'),
                    'terrace' => __('Terrace', 'malisafi-mls'),
                    'pool' => __('Swimming Pool', 'malisafi-mls'),
                    'gym' => __('Gym', 'malisafi-mls'),
                    'security' => __('24/7 Security', 'malisafi-mls'),
                    'furnished' => __('Furnished', 'malisafi-mls'),
                    'pet_friendly' => __('Pet Friendly', 'malisafi-mls'),
                    'fireplace' => __('Fireplace', 'malisafi-mls'),
                    'storage' => __('Storage Space', 'malisafi-mls'),
                    'laundry' => __('Laundry Room', 'malisafi-mls')
                );
                
                $available_amenities = array(
                    'wifi' => __('WiFi', 'malisafi-mls'),
                    'ac' => __('Air Conditioning', 'malisafi-mls'),
                    'heating' => __('Heating', 'malisafi-mls'),
                    'elevator' => __('Elevator', 'malisafi-mls'),
                    'backup_generator' => __('Backup Generator', 'malisafi-mls'),
                    'water_backup' => __('Water Backup', 'malisafi-mls'),
                    'playground' => __('Playground', 'malisafi-mls'),
                    'clubhouse' => __('Clubhouse', 'malisafi-mls'),
                    'cctv' => __('CCTV', 'malisafi-mls'),
                    'intercom' => __('Intercom', 'malisafi-mls'),
                    'borehole' => __('Borehole', 'malisafi-mls'),
                    'solar' => __('Solar Power', 'malisafi-mls')
                );
                ?>
                
                <h3><?php _e('Property Features', 'malisafi-mls'); ?></h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px;">
                    <?php foreach ($available_features as $key => $label): ?>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="features[]" value="<?php echo esc_attr($key); ?>" 
                                   <?php checked(in_array($key, $current_features)); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <h3><?php _e('Amenities', 'malisafi-mls'); ?></h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                    <?php foreach ($available_amenities as $key => $label): ?>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="amenities[]" value="<?php echo esc_attr($key); ?>" 
                                   <?php checked(in_array($key, $current_amenities)); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Images Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Property Images', 'malisafi-mls'); ?></h2>
            </div>
            <div class="inside">
                <div class="media-controls" style="margin-bottom: 15px; display:flex; align-items:center; gap:10px;">
                    <button type="button" class="button button-secondary" id="select-property-images">
                        <?php _e('Select Images', 'malisafi-mls'); ?>
                    </button>
                    <span class="description"><?php _e('Recommended size 1200x800 (landscape). Max 15 images. Drag to reorder. First image will be Featured.', 'malisafi-mls'); ?></span>
                </div>

                <div id="property-images-container" class="property-images-grid" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:10px;">
                    <?php
                    if ($property_id) {
                        $attachments = get_posts(array(
                            'post_type' => 'attachment',
                            'posts_per_page' => -1,
                            'post_parent' => $property_id,
                            'post_mime_type' => 'image',
                            'orderby' => 'menu_order ID',
                            'order' => 'ASC'
                        ));
                        foreach ($attachments as $attachment) {
                            $image_url = wp_get_attachment_image_url($attachment->ID, 'thumbnail');
                            echo '<div class="property-image-item" data-id="' . $attachment->ID . '" style="position:relative; width:120px; height:120px; border:1px solid #ddd; border-radius:4px; overflow:hidden;">';
                            echo '<img src="' . esc_url($image_url) . '" style="width:100%; height:100%; object-fit:cover;" />';
                            echo '<button type="button" class="remove-image button-link" style="position:absolute; top:4px; right:4px; color:#dc2626;">&times;</button>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
                <input type="hidden" name="property_images" id="property-images-input" value="">

                <script>
                jQuery(function($){
                    var imageIds = [];

                    // Prefill IDs from existing DOM
                    $('#property-images-container .property-image-item').each(function(){
                        var id = $(this).data('id');
                        if (id) imageIds.push(id);
                    });
                    updateImageInput();

                    // Open media frame
                    $('#select-property-images').on('click', function(e){
                        e.preventDefault();
                        if (typeof wp === 'undefined' || !wp.media) { alert('Media library not available.'); return; }
                        var frame = wp.media({
                            title: '<?php echo esc_js(__('Select Property Images', 'malisafi-mls')); ?>',
                            button: { text: '<?php echo esc_js(__('Use Images', 'malisafi-mls')); ?>' },
                            multiple: true
                        });
                        frame.on('select', function(){
                            var selection = frame.state().get('selection');
                            selection.each(function(attachment){
                                attachment = attachment.toJSON();
                                if (imageIds.indexOf(attachment.id) === -1) {
                                    if (imageIds.length >= 15) { return; }
                                    imageIds.push(attachment.id);
                                    var thumbUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                                    var $item = $('<div class="property-image-item" data-id="'+attachment.id+'" style="position:relative; width:120px; height:120px; border:1px solid #ddd; border-radius:4px; overflow:hidden;">'
                                        + '<img src="'+thumbUrl+'" style="width:100%; height:100%; object-fit:cover;" />'
                                        + '<button type="button" class="remove-image button-link" style="position:absolute; top:4px; right:4px; color:#dc2626;">&times;</button>'
                                        + '</div>');
                                    $('#property-images-container').append($item);\n                                    // ensure single Featured badge\n                                    $('.main-badge').remove();\n                                    $('#property-images-container .property-image-item').first().append('<span class=\"main-badge\" style=\"position:absolute; left:4px; top:4px; background:#2563eb; color:#fff; font-size:11px; padding:2px 6px; border-radius:3px;\">Featured</span>');
                                }
                            });
                            updateImageInput();
                        });
                        frame.open();
                    });

                    // Remove
                    $(document).on('click', '.remove-image', function(){
                        var $wrap = $(this).closest('.property-image-item');
                        var id = $wrap.data('id');
                        imageIds = imageIds.filter(function(x){ return x !== id; });
                        $wrap.remove();
                        updateImageInput();
                    });

                    // Sortable if available
                    if ($.fn.sortable) {
                        $('#property-images-container').sortable({
                            items: '.property-image-item',
                            update: function(){
                                imageIds = [];
                                $('#property-images-container .property-image-item').each(function(){
                                    imageIds.push($(this).data('id'));
                                });
                                updateImageInput();
                            }
                        });
                    }

                    function updateImageInput(){
                        $('#property-images-input').val(imageIds.join(','));
                    }
                });
                </script>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="video_url"><?php _e('Video URL', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="url" name="video_url" id="video_url" 
                                   value="<?php echo esc_attr($property_meta['video_url'] ?? ''); ?>" 
                                   class="large-text" 
                                   placeholder="https://youtube.com/watch?v=..." />
                            <p class="description"><?php _e('YouTube or Vimeo video URL (optional)', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="virtual_tour"><?php _e('Virtual Tour URL', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="url" name="virtual_tour" id="virtual_tour" 
                                   value="<?php echo esc_attr($property_meta['virtual_tour'] ?? ''); ?>" 
                                   class="large-text" 
                                   placeholder="https://..." />
                            <p class="description"><?php _e('360° virtual tour link (optional)', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Agent Information Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Agent/Contact Information', 'malisafi-mls'); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="agent_name"><?php _e('Agent/Contact Name', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="agent_name" id="agent_name" 
                                   value="<?php echo esc_attr($property_meta['agent_name'] ?? get_the_author_meta('display_name', get_current_user_id())); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="agent_email"><?php _e('Agent Email', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="email" name="agent_email" id="agent_email" 
                                   value="<?php echo esc_attr($property_meta['agent_email'] ?? get_the_author_meta('user_email', get_current_user_id())); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="agent_phone"><?php _e('Agent Phone', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="tel" name="agent_phone" id="agent_phone" 
                                   value="<?php echo esc_attr($property_meta['agent_phone'] ?? ''); ?>" 
                                   class="regular-text" 
                                   placeholder="+254712345678" />
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Additional Options Section -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php _e('Additional Options', 'malisafi-mls'); ?></h2>
            </div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Featured Property', 'malisafi-mls'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="featured" value="1" 
                                       <?php checked($property_meta['featured'] ?? '0', '1'); ?>>
                                <?php _e('Mark this property as featured', 'malisafi-mls'); ?>
                            </label>
                            <p class="description"><?php _e('Featured properties appear prominently on the site', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Submit Button -->
        <p class="submit">
            <button type="submit" name="submit_property" class="button button-primary button-large">
                <?php echo $property_id ? __('Update Property', 'malisafi-mls') : __('Create Property', 'malisafi-mls'); ?>
            </button>
            <a href="<?php echo admin_url('edit.php?post_type=malisafi_property'); ?>" class="button button-secondary button-large">
                <?php _e('Cancel', 'malisafi-mls'); ?>
            </a>
        </p>
    </form>
</div>

<style>
.malisafi-admin-property-form .postbox {
    margin-bottom: 20px;
}
.malisafi-admin-property-form .postbox-header h2 {
    padding: 12px;
    margin: 0;
    font-size: 14px;
}
.malisafi-admin-property-form .required {
    color: #d63638;
}
.malisafi-admin-property-form .form-table th {
    width: 200px;
    font-weight: 600;
}
</style>

<script>
function malisafiGetLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude.toFixed(6);
            var lng = position.coords.longitude.toFixed(6);
            document.getElementById('property_gps').value = lat + ', ' + lng;
        }, function(error) {
            alert('<?php _e('Unable to retrieve your location.', 'malisafi-mls'); ?>');
        });
    } else {
        alert('<?php _e('Geolocation is not supported by your browser.', 'malisafi-mls'); ?>');
    }
}
</script>
