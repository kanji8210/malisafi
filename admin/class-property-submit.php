<?php
/**
 * Property Submit Manager
 *
 * @package MalisafiMLS
 */

/**
 * Malisafi_Property_Submit class
 */
class Malisafi_Property_Submit {
    
    /**
     * Initialize property submission
     */
    public static function init() {
        add_action('admin_post_malisafi_submit_property', array(__CLASS__, 'handle_property_submission'));
        add_action('admin_post_nopriv_malisafi_submit_property', array(__CLASS__, 'handle_property_submission'));
        add_action('wp_ajax_malisafi_upload_property_image', array(__CLASS__, 'ajax_upload_image'));
        add_action('wp_ajax_malisafi_delete_property_image', array(__CLASS__, 'ajax_delete_image'));
    }
    
    /**
     * Handle property submission from admin form
     */
    public static function handle_property_submission() {
        // Security check
        check_admin_referer('malisafi_submit_property', 'malisafi_property_nonce');
        
        // Check user capabilities
        $current_user = wp_get_current_user();
        if (!self::can_submit_property($current_user)) {
            wp_die(__('You do not have permission to submit properties.', 'malisafi-mls'));
        }
        
        // Validate required fields
        require_once MALISAFI_MLS_PATH . 'includes/class-validator.php';
        $validator = new \MalisafiMLS\Validator();
        
        // Validate basic info
        $validator->text($_POST['property_title'] ?? '', 'title', 5, 200, true);
        $validator->price($_POST['property_price'] ?? '', 'price', true);
        $validator->in_array($_POST['property_currency'] ?? 'KES', array('KES', 'USD', 'EUR', 'GBP'), 'currency', true);
        $validator->in_array($_POST['listing_type'] ?? '', array('sale', 'rent', 'lease', 'short_term'), 'listing_type', true);
        
        // Validate location
        $validator->text($_POST['county'] ?? '', 'county', 2, 50, true);
        $validator->text($_POST['subcounty'] ?? '', 'subcounty', 2, 80, true);
        $validator->text($_POST['city'] ?? '', 'city', 2, 50, false);
        
        if ($validator->fails()) {
            $error_message = $validator->first_error();
            $redirect_url = add_query_arg(array(
                'page' => 'malisafi-properties',
                'action' => 'add',
                'error' => urlencode($error_message)
            ), admin_url('admin.php'));
            wp_redirect($redirect_url);
            exit;
        }
        
        $validated = $validator->validated();
        
        // Sanitize and validate input
        $property_data = self::sanitize_property_data($_POST);
        
        // Validate required fields
        $errors = self::validate_property_data($property_data);
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
            self::redirect_with_error($error_message);
            exit;
        }
        
        // Determine post status based on user role
        $post_status = self::get_post_status_for_user($current_user);
        
        // Create or update property
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        
        if ($property_id && get_post($property_id)) {
            // Update existing property
            $property_id = self::update_property($property_id, $property_data, $post_status);
        } else {
            // Create new property
            $property_id = self::create_property($property_data, $post_status);
        }
        
        if (is_wp_error($property_id)) {
            self::redirect_with_error($property_id->get_error_message());
            exit;
        }
        
        // Save meta data
        self::save_property_meta($property_id, $property_data);
        
        // Save taxonomies
        self::save_property_taxonomies($property_id, $property_data);
        
        // Handle media uploads
        if (!empty($_POST['property_images'])) {
            self::attach_images($property_id, $_POST['property_images']);
        }
        
        // Redirect with success
        self::redirect_with_success($property_id, $post_status);
        exit;
    }
    
    /**
     * Check if user can submit properties
     */
    private static function can_submit_property($user) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $allowed_roles = array(
            'administrator',
            'malisafi_moderator',
            'malisafi_agent_basic',
            'malisafi_agent_premium',
            'malisafi_owner',
            'malisafi_developer'
        );
        
        $user_roles = (array) $user->roles;
        return !empty(array_intersect($allowed_roles, $user_roles));
    }
    
    /**
     * Get post status based on user role
     */
    private static function get_post_status_for_user($user) {
        // Admin and moderators can publish directly
        if (user_can($user, 'moderate_properties')) {
            return 'publish';
        }
        
        // All agents (including premium) must go through approval
        // Premium agents can be given direct publish rights via settings if needed
        $allow_premium_auto_publish = get_option('malisafi_allow_premium_auto_publish', false);
        if ($allow_premium_auto_publish && in_array('malisafi_agent_premium', $user->roles)) {
            return 'publish';
        }
        
        // Others go to pending review (including all agents by default)
        return 'pending';
    }
    
    /**
     * Sanitize property data - Updated for new admin form with PHP 8 compatibility
     */
    private static function sanitize_property_data($data) {
        // Ensure data is an array
        if (!is_array($data)) {
            $data = array();
        }
        
        return array(
            'title' => isset($data['property_title']) && $data['property_title'] !== null ? sanitize_text_field($data['property_title']) : '',
            'description' => isset($data['property_description']) && $data['property_description'] !== null ? wp_kses_post($data['property_description']) : '',
            
            // Pricing
            'price' => isset($data['property_price']) ? floatval($data['property_price']) : 0,
            'currency' => isset($data['property_currency']) && $data['property_currency'] !== null ? sanitize_text_field($data['property_currency']) : 'KES',
            'listing_type' => isset($data['listing_type']) && $data['listing_type'] !== null ? sanitize_text_field($data['listing_type']) : '',
            
            // Details
            'bedrooms' => isset($data['bedrooms']) ? intval($data['bedrooms']) : 0,
            'bathrooms' => isset($data['bathrooms']) ? intval($data['bathrooms']) : 0,
            'size' => isset($data['size']) ? floatval($data['size']) : 0,
            'size_unit' => isset($data['size_unit']) && $data['size_unit'] !== null ? sanitize_text_field($data['size_unit']) : 'sqm',
            'year_built' => isset($data['year_built']) ? intval($data['year_built']) : 0,
            'condition' => isset($data['condition']) && $data['condition'] !== null ? sanitize_text_field($data['condition']) : '',
            'parking' => isset($data['parking']) ? intval($data['parking']) : 0,
            'floors' => isset($data['floors']) ? intval($data['floors']) : 1,
            
            // Location
            'address' => isset($data['address']) && $data['address'] !== null ? sanitize_text_field($data['address']) : '',
            'county' => isset($data['county']) && $data['county'] !== null ? sanitize_text_field($data['county']) : '',
            'subcounty' => isset($data['subcounty']) && $data['subcounty'] !== null ? sanitize_text_field($data['subcounty']) : '',
            'city' => isset($data['city']) && $data['city'] !== null ? sanitize_text_field($data['city']) : '',
            'area' => isset($data['area']) && $data['area'] !== null ? sanitize_text_field($data['area']) : '',
            'gps' => isset($data['property_gps']) && $data['property_gps'] !== null ? sanitize_text_field($data['property_gps']) : '',
            'postal_code' => isset($data['postal_code']) && $data['postal_code'] !== null ? sanitize_text_field($data['postal_code']) : '',
            
            // Features & Amenities
            'features' => isset($data['features']) && is_array($data['features']) ? array_filter(array_map('sanitize_text_field', $data['features'])) : array(),
            'amenities' => isset($data['amenities']) && is_array($data['amenities']) ? array_filter(array_map('sanitize_text_field', $data['amenities'])) : array(),
            
            // Taxonomies
            'property_type' => isset($data['property_type']) ? intval($data['property_type']) : 0,
            
            // Agent info
            'agent_name' => isset($data['agent_name']) && $data['agent_name'] !== null ? sanitize_text_field($data['agent_name']) : '',
            'agent_email' => isset($data['agent_email']) && $data['agent_email'] !== null ? sanitize_email($data['agent_email']) : '',
            'agent_phone' => isset($data['agent_phone']) && $data['agent_phone'] !== null ? sanitize_text_field($data['agent_phone']) : '',
            
            // Media
            'video_url' => isset($data['video_url']) && $data['video_url'] !== null ? esc_url_raw($data['video_url']) : '',
            'virtual_tour' => isset($data['virtual_tour']) && $data['virtual_tour'] !== null ? esc_url_raw($data['virtual_tour']) : '',

            // Sale/Lease Details
            'floor_plan_urls' => isset($data['floor_plan_urls']) && $data['floor_plan_urls'] !== null ? sanitize_textarea_field($data['floor_plan_urls']) : '',
            'expected_roi' => isset($data['expected_roi']) ? floatval($data['expected_roi']) : 0,
            'rental_yield' => isset($data['rental_yield']) ? floatval($data['rental_yield']) : 0,
            'annual_rent_income' => isset($data['annual_rent_income']) ? floatval($data['annual_rent_income']) : 0,
            'ownership_type' => isset($data['ownership_type']) && $data['ownership_type'] !== null ? sanitize_text_field($data['ownership_type']) : '',
            'title_deed_status' => isset($data['title_deed_status']) && $data['title_deed_status'] !== null ? sanitize_text_field($data['title_deed_status']) : '',
            'financing_options' => isset($data['financing_options']) && is_array($data['financing_options']) ? array_filter(array_map('sanitize_text_field', $data['financing_options'])) : array(),
            'financing_min_deposit' => isset($data['financing_min_deposit']) ? floatval($data['financing_min_deposit']) : 0,
            'financing_tenor_months' => isset($data['financing_tenor_months']) ? intval($data['financing_tenor_months']) : 0,
            'financing_interest_rate' => isset($data['financing_interest_rate']) ? floatval($data['financing_interest_rate']) : 0,
            'diaspora_financing_details' => isset($data['diaspora_financing_details']) && $data['diaspora_financing_details'] !== null ? sanitize_text_field($data['diaspora_financing_details']) : '',
            'developer_guarantee' => isset($data['developer_guarantee']) && $data['developer_guarantee'] !== null ? sanitize_textarea_field($data['developer_guarantee']) : '',
            'sustainability' => isset($data['sustainability']) && is_array($data['sustainability']) ? array_filter(array_map('sanitize_text_field', $data['sustainability'])) : array(),
            'green_certification' => isset($data['green_certification']) && $data['green_certification'] !== null ? sanitize_text_field($data['green_certification']) : '',
            
            // Additional
            'reference_id' => isset($data['reference_id']) && $data['reference_id'] !== null ? sanitize_text_field($data['reference_id']) : '',
            'featured' => isset($data['featured']) ? 1 : 0,
        );
    }
    
    /**
     * Validate property data - Updated for new fields
     */
    private static function validate_property_data($data) {
        $errors = array();
        
        // Already validated by Validator class, but double check critical fields
        if (empty($data['title']) || strlen($data['title']) < 5) {
            $errors[] = __('Property title is required (minimum 5 characters).', 'malisafi-mls');
        }
        
        if (empty($data['price']) || $data['price'] <= 0) {
            $errors[] = __('Valid property price is required.', 'malisafi-mls');
        }
        
        if (empty($data['county'])) {
            $errors[] = __('County is required.', 'malisafi-mls');
        }
        
        if (empty($data['subcounty'])) {
            $errors[] = __('Subcounty is required.', 'malisafi-mls');
        }
        
        if (empty($data['property_type']) || $data['property_type'] === 0) {
            $errors[] = __('Property type is required.', 'malisafi-mls');
        }
        
        if (empty($data['listing_type'])) {
            $errors[] = __('Listing type (sale/rent/lease/short term) is required.', 'malisafi-mls');
        }
        
        return $errors;
    }
    
    /**
     * Create new property
     */
    private static function create_property($data, $status) {
        $property_data = array(
            'post_title' => $data['title'],
            'post_content' => $data['description'],
            'post_excerpt' => $data['excerpt'],
            'post_status' => $status,
            'post_type' => 'malisafi_property',
            'post_author' => get_current_user_id(),
        );
        
        return wp_insert_post($property_data, true);
    }
    
    /**
     * Update existing property
     */
    private static function update_property($property_id, $data, $status) {
        // Get current property
        $current_property = get_post($property_id);
        if (!$current_property) {
            return new \WP_Error('invalid_property', __('Property not found.', 'malisafi-mls'));
        }
        
        // Check if user is editing their own property
        $current_user = wp_get_current_user();
        $is_own_property = ($current_property->post_author == $current_user->ID);
        
        // Force status to pending if agent/owner/developer is editing
        // Only admins and moderators can keep published status
        if ($is_own_property && !current_user_can('moderate_properties')) {
            // Property was edited by agent/owner/developer - force to pending
            $status = 'pending';
        }
        
        $property_data = array(
            'ID' => $property_id,
            'post_title' => $data['title'],
            'post_content' => $data['description'],
            'post_excerpt' => $data['excerpt'],
            'post_status' => $status,
        );
        
        return wp_update_post($property_data, true);
    }
    
    /**
     * Save property meta data - Updated for new admin form with PHP 8 compatibility
     */
    private static function save_property_meta($property_id, $data) {
        // Ensure data is an array
        if (!is_array($data)) {
            $data = array();
        }
        
        $meta_fields = array(
            // Pricing
            '_malisafi_price' => isset($data['price']) ? $data['price'] : 0,
            '_malisafi_currency' => isset($data['currency']) ? $data['currency'] : 'KES',
            '_malisafi_listing_type' => isset($data['listing_type']) ? $data['listing_type'] : '',
            
            // Property Details
            '_malisafi_bedrooms' => isset($data['bedrooms']) ? $data['bedrooms'] : 0,
            '_malisafi_bathrooms' => isset($data['bathrooms']) ? $data['bathrooms'] : 0,
            '_malisafi_size' => isset($data['size']) ? $data['size'] : 0,
            '_malisafi_size_unit' => isset($data['size_unit']) ? $data['size_unit'] : 'sqm',
            '_malisafi_year_built' => isset($data['year_built']) ? $data['year_built'] : 0,
            '_malisafi_condition' => isset($data['condition']) ? $data['condition'] : '',
            '_malisafi_parking' => isset($data['parking']) ? $data['parking'] : 0,
            '_malisafi_floors' => isset($data['floors']) ? $data['floors'] : 1,
            
            // Location
            '_malisafi_address' => isset($data['address']) ? $data['address'] : '',
            '_malisafi_county' => isset($data['county']) ? $data['county'] : '',
            '_malisafi_subcounty' => isset($data['subcounty']) ? $data['subcounty'] : '',
            '_malisafi_city' => isset($data['city']) ? $data['city'] : '',
            '_malisafi_area' => isset($data['area']) ? $data['area'] : '',
            '_malisafi_gps' => isset($data['gps']) ? $data['gps'] : '',
            '_malisafi_postal_code' => isset($data['postal_code']) ? $data['postal_code'] : '',
            
            // Features & Amenities (as arrays)
            '_malisafi_features' => isset($data['features']) && is_array($data['features']) ? $data['features'] : array(),
            '_malisafi_amenities' => isset($data['amenities']) && is_array($data['amenities']) ? $data['amenities'] : array(),
            
            // Agent/Contact Info
            '_malisafi_agent_name' => isset($data['agent_name']) ? $data['agent_name'] : '',
            '_malisafi_agent_email' => isset($data['agent_email']) ? $data['agent_email'] : '',
            '_malisafi_agent_phone' => isset($data['agent_phone']) ? $data['agent_phone'] : '',
            
            // Media
            '_malisafi_video_url' => isset($data['video_url']) ? $data['video_url'] : '',
            '_malisafi_virtual_tour' => isset($data['virtual_tour']) ? $data['virtual_tour'] : '',

            // Sale/Lease Details
            '_malisafi_floor_plan_urls' => isset($data['floor_plan_urls']) ? $data['floor_plan_urls'] : '',
            '_malisafi_expected_roi' => isset($data['expected_roi']) ? $data['expected_roi'] : 0,
            '_malisafi_rental_yield' => isset($data['rental_yield']) ? $data['rental_yield'] : 0,
            '_malisafi_annual_rent_income' => isset($data['annual_rent_income']) ? $data['annual_rent_income'] : 0,
            '_malisafi_ownership_type' => isset($data['ownership_type']) ? $data['ownership_type'] : '',
            '_malisafi_title_deed_status' => isset($data['title_deed_status']) ? $data['title_deed_status'] : '',
            '_malisafi_financing_options' => isset($data['financing_options']) && is_array($data['financing_options']) ? $data['financing_options'] : array(),
            '_malisafi_financing_min_deposit' => isset($data['financing_min_deposit']) ? $data['financing_min_deposit'] : 0,
            '_malisafi_financing_tenor_months' => isset($data['financing_tenor_months']) ? $data['financing_tenor_months'] : 0,
            '_malisafi_financing_interest_rate' => isset($data['financing_interest_rate']) ? $data['financing_interest_rate'] : 0,
            '_malisafi_diaspora_financing_details' => isset($data['diaspora_financing_details']) ? $data['diaspora_financing_details'] : '',
            '_malisafi_developer_guarantee' => isset($data['developer_guarantee']) ? $data['developer_guarantee'] : '',
            '_malisafi_sustainability' => isset($data['sustainability']) && is_array($data['sustainability']) ? $data['sustainability'] : array(),
            '_malisafi_green_certification' => isset($data['green_certification']) ? $data['green_certification'] : '',
            
            // Additional
            '_malisafi_reference_id' => isset($data['reference_id']) ? $data['reference_id'] : '',
            '_malisafi_featured' => isset($data['featured']) ? $data['featured'] : 0,
        );

        $listing_type = isset($data['listing_type']) ? $data['listing_type'] : '';
        if (!in_array($listing_type, array('sale', 'lease'), true)) {
            $meta_fields['_malisafi_floor_plan_urls'] = '';
            $meta_fields['_malisafi_expected_roi'] = 0;
            $meta_fields['_malisafi_rental_yield'] = 0;
            $meta_fields['_malisafi_annual_rent_income'] = 0;
            $meta_fields['_malisafi_ownership_type'] = '';
            $meta_fields['_malisafi_title_deed_status'] = '';
            $meta_fields['_malisafi_financing_options'] = array();
            $meta_fields['_malisafi_financing_min_deposit'] = 0;
            $meta_fields['_malisafi_financing_tenor_months'] = 0;
            $meta_fields['_malisafi_financing_interest_rate'] = 0;
            $meta_fields['_malisafi_diaspora_financing_details'] = '';
            $meta_fields['_malisafi_developer_guarantee'] = '';
            $meta_fields['_malisafi_sustainability'] = array();
            $meta_fields['_malisafi_green_certification'] = '';
        }
        
        foreach ($meta_fields as $key => $value) {
            // Ensure we're not passing null values to update_post_meta
            if ($value === null) {
                $value = '';
            }
            update_post_meta($property_id, $key, $value);
        }
    }
    
    /**
     * Save property taxonomies
     */
    private static function save_property_taxonomies($property_id, $data) {
        if (!empty($data['property_type'])) {
            wp_set_object_terms($property_id, $data['property_type'], 'malisafi_property_type');
        }
        
        if (!empty($data['property_status'])) {
            wp_set_object_terms($property_id, $data['property_status'], 'malisafi_property_status');
        }
        
        if (!empty($data['property_location'])) {
            wp_set_object_terms($property_id, $data['property_location'], 'malisafi_property_location');
        }
        
        if (!empty($data['property_features'])) {
            wp_set_object_terms($property_id, $data['property_features'], 'malisafi_property_features');
        }
    }
    
    /**
     * Attach images to property
     */
    private static function attach_images($property_id, $image_ids) {
        $image_ids = array_map('intval', explode(',', $image_ids));
        
        foreach ($image_ids as $index => $image_id) {
            if ($image_id) {
                wp_update_post(array(
                    'ID' => $image_id,
                    'post_parent' => $property_id
                ));
                
                // Set first image as featured image
                if ($index === 0) {
                    set_post_thumbnail($property_id, $image_id);
                }
            }
        }
    }
    
    /**
     * AJAX upload image
     */
    public static function ajax_upload_image() {
        check_ajax_referer('malisafi_upload_image', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'malisafi-mls')));
        }
        $result = \MalisafiMLS\Image_Handler::upload_single($_FILES['file'], array(
            'validate_dimensions' => false,
            'size_map' => array(
                'url' => 'full',
                'thumb' => 'thumbnail',
            ),
        ));

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }
    
    /**
     * AJAX delete image
     */
    public static function ajax_delete_image() {
        check_ajax_referer('malisafi_delete_image', 'nonce');
        
        $image_id = intval($_POST['image_id']);
        
        if (!current_user_can('delete_post', $image_id)) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }

        $deleted = \MalisafiMLS\Image_Handler::delete_image($image_id);

        if (is_wp_error($deleted)) {
            wp_send_json_error(array('message' => $deleted->get_error_message()));
        }

        wp_send_json_success();
    }
    
    /**
     * Redirect with error
     */
    private static function redirect_with_error($message) {
        $redirect_url = add_query_arg(array(
            'page' => 'malisafi-properties',
            'action' => 'add',
            'error' => urlencode($message)
        ), admin_url('admin.php'));
        
        wp_redirect($redirect_url);
    }
    
    /**
     * Redirect with success to success page
     */
    private static function redirect_with_success($property_id, $status) {
        // Determine if this was a create or update action
        $action = isset($_POST['property_id']) && intval($_POST['property_id']) > 0 ? 'updated' : 'created';
        
        $redirect_url = add_query_arg(array(
            'page' => 'malisafi-property-success',
            'property_id' => $property_id,
            'action' => $action
        ), admin_url('admin.php'));
        
        wp_redirect($redirect_url);
    }
}
