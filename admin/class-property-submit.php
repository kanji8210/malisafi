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
        $validator->in_array($_POST['listing_type'] ?? '', array('sale', 'rent', 'lease'), 'listing_type', true);
        
        // Validate location
        $validator->text($_POST['county'] ?? '', 'county', 2, 50, true);
        $validator->text($_POST['city'] ?? '', 'city', 2, 50, true);
        
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
        
        // Premium agents can publish directly
        if (in_array('malisafi_agent_premium', $user->roles)) {
            return 'publish';
        }
        
        // Others go to pending review
        return 'pending';
    }
    
    /**
     * Sanitize property data - Updated for new admin form
     */
    private static function sanitize_property_data($data) {
        return array(
            'title' => sanitize_text_field($data['property_title'] ?? ''),
            'description' => wp_kses_post($data['property_description'] ?? ''),
            
            // Pricing
            'price' => floatval($data['property_price'] ?? 0),
            'currency' => sanitize_text_field($data['property_currency'] ?? 'KES'),
            'listing_type' => sanitize_text_field($data['listing_type'] ?? ''),
            
            // Details
            'bedrooms' => intval($data['bedrooms'] ?? 0),
            'bathrooms' => intval($data['bathrooms'] ?? 0),
            'size' => floatval($data['size'] ?? 0),
            'size_unit' => sanitize_text_field($data['size_unit'] ?? 'sqm'),
            'year_built' => intval($data['year_built'] ?? 0),
            'condition' => sanitize_text_field($data['condition'] ?? ''),
            'parking' => intval($data['parking'] ?? 0),
            'floors' => intval($data['floors'] ?? 1),
            
            // Location
            'address' => sanitize_text_field($data['address'] ?? ''),
            'county' => sanitize_text_field($data['county'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'area' => sanitize_text_field($data['area'] ?? ''),
            'gps' => sanitize_text_field($data['property_gps'] ?? ''),
            'postal_code' => sanitize_text_field($data['postal_code'] ?? ''),
            
            // Features & Amenities
            'features' => isset($data['features']) ? array_map('sanitize_text_field', (array) $data['features']) : array(),
            'amenities' => isset($data['amenities']) ? array_map('sanitize_text_field', (array) $data['amenities']) : array(),
            
            // Taxonomies
            'property_type' => isset($data['property_type']) ? intval($data['property_type']) : 0,
            
            // Agent info
            'agent_name' => sanitize_text_field($data['agent_name'] ?? ''),
            'agent_email' => sanitize_email($data['agent_email'] ?? ''),
            'agent_phone' => sanitize_text_field($data['agent_phone'] ?? ''),
            
            // Media
            'video_url' => esc_url_raw($data['video_url'] ?? ''),
            'virtual_tour' => esc_url_raw($data['virtual_tour'] ?? ''),
            
            // Additional
            'reference_id' => sanitize_text_field($data['reference_id'] ?? ''),
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
        
        if (empty($data['city'])) {
            $errors[] = __('City is required.', 'malisafi-mls');
        }
        
        if (empty($data['property_type']) || $data['property_type'] === 0) {
            $errors[] = __('Property type is required.', 'malisafi-mls');
        }
        
        if (empty($data['listing_type'])) {
            $errors[] = __('Listing type (sale/rent/lease) is required.', 'malisafi-mls');
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
     * Save property meta data
     */
    private static function save_property_meta($property_id, $data) {
        $meta_fields = array(
            '_malisafi_price' => $data['price'],
            '_malisafi_price_suffix' => $data['price_suffix'],
            '_malisafi_bedrooms' => $data['bedrooms'],
            '_malisafi_bathrooms' => $data['bathrooms'],
            '_malisafi_area' => $data['area'],
            '_malisafi_lot_size' => $data['lot_size'],
            '_malisafi_year_built' => $data['year_built'],
            '_malisafi_garage' => $data['garage'],
            '_malisafi_address' => $data['address'],
            '_malisafi_city' => $data['city'],
            '_malisafi_county' => $data['county'],
            '_malisafi_neighbourhood' => $data['neighbourhood'],
            '_malisafi_setting' => $data['setting'],
            '_malisafi_state' => $data['state'],
            '_malisafi_zip' => $data['zip'],
            '_malisafi_country' => $data['country'],
            '_malisafi_latitude' => $data['latitude'],
            '_malisafi_longitude' => $data['longitude'],
            '_malisafi_agent_name' => $data['agent_name'],
            '_malisafi_agent_email' => $data['agent_email'],
            '_malisafi_agent_phone' => $data['agent_phone'],
            '_malisafi_video_url' => $data['video_url'],
            '_malisafi_virtual_tour' => $data['virtual_tour'],
        );
        
        foreach ($meta_fields as $key => $value) {
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
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $file = $_FILES['file'];
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }
        
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }
        
        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);
        
        wp_send_json_success(array(
            'id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'thumb' => wp_get_attachment_image_url($attachment_id, 'thumbnail')
        ));
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
        
        if (wp_delete_attachment($image_id, true)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Failed to delete image.', 'malisafi-mls')));
        }
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
     * Redirect with success
     */
    private static function redirect_with_success($property_id, $status) {
        $message = ($status === 'publish') ? 'property_published' : 'property_pending';
        
        $redirect_url = add_query_arg(array(
            'page' => 'malisafi-properties',
            'message' => $message,
            'property_id' => $property_id
        ), admin_url('admin.php'));
        
        wp_redirect($redirect_url);
    }
}
