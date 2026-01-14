<?php
namespace MalisafiMLS;

/**
 * Modern Property Submission System
 * 
 * Handles property creation with wizard interface, validation, and auto-save
 *
 * @package MalisafiMLS
 */
class Property_Submission {
    
    /**
     * Initialize
     */
    public static function init() {
        // AJAX handlersproperty su
        add_action('wp_ajax_malisafi_save_property_step', array(__CLASS__, 'ajax_save_property_step'));
        add_action('wp_ajax_malisafi_submit_property', array(__CLASS__, 'ajax_submit_property'));
        add_action('wp_ajax_malisafi_upload_property_images', array(__CLASS__, 'ajax_upload_images'));
        add_action('wp_ajax_malisafi_delete_property_image', array(__CLASS__, 'ajax_delete_image'));
        add_action('wp_ajax_malisafi_reorder_property_images', array(__CLASS__, 'ajax_reorder_images'));
        add_action('wp_ajax_malisafi_get_property_draft', array(__CLASS__, 'ajax_get_draft'));
        
        // Shortcode for property submission form
        add_shortcode('malisafi_submit_property', array(__CLASS__, 'render_submission_form'));
        
        // Shortcode for success page
        add_shortcode('malisafi_property_success', array(__CLASS__, 'render_success_page'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }
    
    /**
     * Enqueue assets
     */
    public static function enqueue_assets() {
        if (is_page() && has_shortcode(get_post()->post_content, 'malisafi_submit_property')) {
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
            
            wp_enqueue_style(
                'malisafi-property-submission',
                MALISAFI_MLS_URL . 'assets/css/property-submission.css',
                array(),
                MALISAFI_MLS_VERSION
            );
            
            wp_enqueue_script(
                'malisafi-property-submission',
                MALISAFI_MLS_URL . 'assets/js/property-submission.js',
                array('jquery', 'jquery-ui-sortable'),
                MALISAFI_MLS_VERSION,
                true
            );
            
            wp_localize_script('malisafi-property-submission', 'malisafiSubmission', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('malisafi_property_submission'),
                'uploadNonce' => wp_create_nonce('malisafi_upload_images'),
                'refNonce' => wp_create_nonce('malisafi_generate_ref_id'),
                'uploadsEnabled' => true,
                'strings' => array(
                    'saving' => __('Saving...', 'malisafi-mls'),
                    'saved' => __('Saved', 'malisafi-mls'),
                    'error' => __('Error saving', 'malisafi-mls'),
                    'uploading' => __('Uploading...', 'malisafi-mls'),
                    'uploadError' => __('Upload failed', 'malisafi-mls'),
                    'confirmDelete' => __('Are you sure you want to delete this image?', 'malisafi-mls'),
                    'submitProperty' => __('Submit Property', 'malisafi-mls'),
                    'submitting' => __('Submitting...', 'malisafi-mls'),
                    'success' => __('Property submitted successfully!', 'malisafi-mls'),
                )
            ));
        }
    }
    
    /**
     * Render submission form shortcode
     */
    public static function render_submission_form($atts) {
        // Check if we should show success page instead
        if (isset($_GET['submission']) && $_GET['submission'] === 'success' && isset($_GET['property_id'])) {
            return self::render_success_page();
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="malisafi-notice error"><p>' . 
                   sprintf(__('Please <a href="%s">log in</a> to submit a property.', 'malisafi-mls'), wp_login_url(get_permalink())) . 
                   '</p></div>';
        }
        
        // Check if user can submit properties
        if (!self::user_can_submit()) {
            return '<div class="malisafi-notice error"><p>' . 
                   __('You do not have permission to submit properties. Please upgrade your account.', 'malisafi-mls') . 
                   '</p></div>';
        }
        
        // Check property limits
        $limit_check = self::check_property_limits();
        if (is_wp_error($limit_check)) {
            return '<div class="malisafi-notice error"><p>' . $limit_check->get_error_message() . '</p></div>';
        }
        
        // Load template
        ob_start();
        include MALISAFI_MLS_PATH . 'templates/property-submission-wizard.php';
        return ob_get_clean();
    }
    
    /**
     * Check if user can submit properties
     */
    public static function user_can_submit() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        $allowed_roles = array(
            'administrator', 
            'malisafi_moderator',
            'malisafi_agent_basic', 
            'malisafi_agent_premium', 
            'malisafi_owner', 
            'malisafi_developer'
        );
        
        return array_intersect($allowed_roles, $user->roles) !== array();
    }
    
    /**
     * Check property submission limits
     */
    public static function check_property_limits() {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Admin has no limits
        if (in_array('administrator', $user->roles)) {
            return true;
        }
        
        // Get user's property count
        $property_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = %s 
            AND post_author = %d 
            AND post_status IN ('publish', 'pending', 'draft')",
            'malisafi_property',
            $user_id
        ));
        
        // Check limits based on role
        $table = $wpdb->prefix . 'mf_user_limits';
        $limits = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d",
            $user_id
        ));
        
        if ($limits) {
            $max_properties = intval($limits->max_properties);
            
            if ($max_properties > 0 && $property_count >= $max_properties) {
                return new \WP_Error(
                    'limit_reached',
                    sprintf(
                        __('You have reached your property limit (%d/%d). Please upgrade your plan or delete existing properties.', 'malisafi-mls'),
                        $property_count,
                        $max_properties
                    )
                );
            }
        }
        
        return true;
    }
    
    /**
     * AJAX: Save property step (auto-save)
     */
    public static function ajax_save_property_step() {
        check_ajax_referer('malisafi_property_submission', 'nonce');
        
        if (!self::user_can_submit()) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }
        
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        $step = isset($_POST['step']) ? sanitize_text_field($_POST['step']) : '';
        $data = isset($_POST['data']) ? $_POST['data'] : array();
        
        // Validate data using Validator
        require_once MALISAFI_MLS_PATH . 'includes/class-validator.php';
        $validator = new Validator();
        
        // Create or update property
        if ($property_id) {
            // Verify ownership
            $property = get_post($property_id);
            if (!$property || $property->post_author != get_current_user_id()) {
                wp_send_json_error(array('message' => __('Invalid property', 'malisafi-mls')));
            }
        } else {
            // Create new draft
            $property_id = wp_insert_post(array(
                'post_type' => 'malisafi_property',
                'post_status' => 'draft',
                'post_author' => get_current_user_id(),
                'post_title' => __('Draft Property', 'malisafi-mls') . ' ' . date('Y-m-d H:i:s')
            ));
            
            if (is_wp_error($property_id)) {
                wp_send_json_error(array('message' => $property_id->get_error_message()));
            }
        }
        
        // Save step data
        $saved = self::save_step_data($property_id, $step, $data, $validator);
        
        if (is_wp_error($saved)) {
            wp_send_json_error(array(
                'message' => $saved->get_error_message(),
                'errors' => $saved->get_error_data()
            ));
        }
        
        wp_send_json_success(array(
            'property_id' => $property_id,
            'message' => __('Saved', 'malisafi-mls')
        ));
    }
    
    /**
     * Save step data
     */
    private static function save_step_data($property_id, $step, $data, $validator) {
        switch ($step) {
            case 'basic':
                return self::save_basic_info($property_id, $data, $validator);
            case 'details':
                return self::save_details($property_id, $data, $validator);
            case 'location':
                return self::save_location($property_id, $data, $validator);
            case 'features':
                return self::save_features($property_id, $data, $validator);
            case 'images':
                return self::save_images($property_id, $data);
            default:
                return new \WP_Error('invalid_step', __('Invalid step', 'malisafi-mls'));
        }
    }
    
    /**
     * Save basic information
     */
    private static function save_basic_info($property_id, $data, $validator) {
        // Validate
        $validator->text($data['title'] ?? '', 'title', 5, 200, true);
        $validator->text($data['description'] ?? '', 'description', 20, 5000, false);
        $validator->price($data['price'] ?? '', 'price', true);
        $validator->in_array($data['currency'] ?? 'KES', array('KES', 'USD', 'EUR', 'GBP'), 'currency', true);
        $validator->in_array($data['property_type'] ?? '', array('house', 'apartment', 'land', 'commercial', 'industrial'), 'property_type', true);
        $validator->in_array($data['listing_type'] ?? '', array('sale', 'rent', 'lease'), 'listing_type', true);
        
        if ($validator->fails()) {
            return new \WP_Error('validation_failed', __('Validation failed', 'malisafi-mls'), $validator->get_errors());
        }
        
        $validated = $validator->validated();
        
        // Update post
        wp_update_post(array(
            'ID' => $property_id,
            'post_title' => $validated['title'],
            'post_content' => $validated['description'] ?? ''
        ));
        
        // Update meta
        update_post_meta($property_id, '_malisafi_price', $validated['price']);
        update_post_meta($property_id, '_malisafi_currency', $validated['currency']);
        update_post_meta($property_id, '_malisafi_listing_type', $validated['listing_type']);
        
        // Set taxonomy
        wp_set_object_terms($property_id, $validated['property_type'], 'malisafi_property_type');
        
        return true;
    }
    
    /**
     * Save property details
     */
    private static function save_details($property_id, $data, $validator) {
        // Validate
        $validator->integer($data['bedrooms'] ?? 0, 'bedrooms', 0, 50, false);
        $validator->integer($data['bathrooms'] ?? 0, 'bathrooms', 0, 50, false);
        $validator->number($data['size'] ?? 0, 'size', 0, 100000, false);
        $validator->in_array($data['size_unit'] ?? 'sqm', array('sqm', 'sqft', 'acres', 'hectares'), 'size_unit', false);
        $validator->integer($data['year_built'] ?? 0, 'year_built', 1800, date('Y') + 5, false);
        $validator->in_array($data['condition'] ?? '', array('new', 'excellent', 'good', 'fair', 'renovation'), 'condition', false);
        
        if ($validator->fails()) {
            return new \WP_Error('validation_failed', __('Validation failed', 'malisafi-mls'), $validator->get_errors());
        }
        
        $validated = $validator->validated();
        
        // Save meta
        foreach ($validated as $key => $value) {
            update_post_meta($property_id, '_malisafi_' . $key, $value);
        }
        
        return true;
    }
    
    /**
     * Save location information
     */
    private static function save_location($property_id, $data, $validator) {
        // Validate
        $validator->text($data['address'] ?? '', 'address', 5, 200, false);
        $validator->text($data['county'] ?? '', 'county', 2, 50, true);
        $validator->text($data['city'] ?? '', 'city', 2, 50, true);
        $validator->text($data['area'] ?? '', 'area', 2, 100, false);
        $validator->text($data['gps'] ?? '', 'gps', 0, 100, false);
        
        if ($validator->fails()) {
            return new \WP_Error('validation_failed', __('Validation failed', 'malisafi-mls'), $validator->get_errors());
        }
        
        $validated = $validator->validated();
        
        // Save meta
        foreach ($validated as $key => $value) {
            update_post_meta($property_id, '_malisafi_' . $key, $value);
        }
        
        // Set location taxonomy
        if (!empty($validated['county'])) {
            wp_set_object_terms($property_id, $validated['county'], 'malisafi_property_location');
        }
        
        return true;
    }
    
    /**
     * Save features and amenities
     */
    private static function save_features($property_id, $data, $validator) {
        // Features are checkboxes, no validation needed
        $features = isset($data['features']) && is_array($data['features']) ? $data['features'] : array();
        $amenities = isset($data['amenities']) && is_array($data['amenities']) ? $data['amenities'] : array();
        
        // Sanitize
        $features = array_map('sanitize_text_field', $features);
        $amenities = array_map('sanitize_text_field', $amenities);
        
        // Save
        update_post_meta($property_id, '_malisafi_features', $features);
        update_post_meta($property_id, '_malisafi_amenities', $amenities);
        
        return true;
    }
    
    /**
     * Save images metadata
     */
    private static function save_images($property_id, $data) {
        if (isset($data['gallery_ids']) && is_array($data['gallery_ids'])) {
            $gallery_ids = array_map('intval', $data['gallery_ids']);
            update_post_meta($property_id, '_malisafi_gallery_ids', implode(',', $gallery_ids));
            
            // Set first image as featured
            if (!empty($gallery_ids) && !has_post_thumbnail($property_id)) {
                set_post_thumbnail($property_id, $gallery_ids[0]);
            }
        }
        
        return true;
    }
    
    /**
     * AJAX: Submit property for review
     */
    public static function ajax_submit_property() {
        check_ajax_referer('malisafi_property_submission', 'nonce');
        
        if (!self::user_can_submit()) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }
        
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        
        if (!$property_id) {
            wp_send_json_error(array('message' => __('Invalid property', 'malisafi-mls')));
        }
        
        // Verify ownership
        $property = get_post($property_id);
        if (!$property || $property->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('Invalid property', 'malisafi-mls')));
        }
        
        // Check if all required fields are filled
        $validation = self::validate_property($property_id);
        if (is_wp_error($validation)) {
            wp_send_json_error(array(
                'message' => __('Please complete all required fields', 'malisafi-mls'),
                'errors' => $validation->get_error_data()
            ));
        }
        
        // Update status to pending review
        wp_update_post(array(
            'ID' => $property_id,
            'post_status' => 'pending'
        ));
        
        // Clear cache
        if (class_exists('MalisafiMLS\Cache_Manager')) {
            Cache_Manager::invalidate_property_cache($property_id);
        }
        
        // Get success page URL with property info
        $success_url = add_query_arg(array(
            'property_id' => $property_id,
            'submission' => 'success'
        ), get_permalink());
        
        wp_send_json_success(array(
            'message' => __('Property submitted successfully!', 'malisafi-mls'),
            'redirect' => $success_url
        ));
    }
    
    /**
     * Validate complete property
     */
    private static function validate_property($property_id) {
        $errors = array();
        
        // Check title
        $property = get_post($property_id);
        if (empty($property->post_title) || strlen($property->post_title) < 5) {
            $errors['title'] = __('Title is required (minimum 5 characters)', 'malisafi-mls');
        }
        
        // Check price
        $price = get_post_meta($property_id, '_malisafi_price', true);
        if (empty($price) || $price <= 0) {
            $errors['price'] = __('Price is required', 'malisafi-mls');
        }
        
        // Check location
        $county = get_post_meta($property_id, '_malisafi_county', true);
        if (empty($county)) {
            $errors['county'] = __('County is required', 'malisafi-mls');
        }
        
        // Require at least one image via front-end gallery upload
        $gallery = get_post_meta($property_id, '_malisafi_gallery_ids', true);
        if (empty($gallery)) {
            $errors['images'] = __('At least one image is required', 'malisafi-mls');
        }
        
        if (!empty($errors)) {
            return new \WP_Error('validation_failed', __('Validation failed', 'malisafi-mls'), $errors);
        }
        
        return true;
    }
    
    /**
     * AJAX: Upload property images
     */
    public static function ajax_upload_images() {
        check_ajax_referer('malisafi_upload_images', 'nonce');
        
        if (!self::user_can_submit()) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }
        
        if (empty($_FILES['images'])) {
            wp_send_json_error(array('message' => __('No images uploaded', 'malisafi-mls')));
        }
        
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $uploaded_ids = array();
        $errors = array();
        
        // Handle multiple files
        $files = $_FILES['images'];
        $file_count = count($files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            $file = array(
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            );
            
            // Validate file
            $allowed_types = array('image/jpeg', 'image/png', 'image/webp');
            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = sprintf(__('Invalid file type for %s', 'malisafi-mls'), $file['name']);
                continue;
            }
            
            // Max 10MB
            if ($file['size'] > 10 * 1024 * 1024) {
                $errors[] = sprintf(__('File %s is too large (max 10MB)', 'malisafi-mls'), $file['name']);
                continue;
            }

            // Server-side dimension/orientation validation
            $img_info = @getimagesize($file['tmp_name']);
            if (!$img_info) {
                $errors[] = sprintf(__('Could not read image dimensions for %s', 'malisafi-mls'), $file['name']);
                continue;
            }
            $width = isset($img_info[0]) ? (int)$img_info[0] : 0;
            $height = isset($img_info[1]) ? (int)$img_info[1] : 0;
            $is_landscape = $width > $height;

            // Rules:
            // - Landscape images must be at least 1200x800
            // - Portrait images are allowed only if >= 1600x2000
            $min_land_w = 1200; $min_land_h = 800;
            $min_port_w = 1600; $min_port_h = 2000;
            $allow_portrait_large = (!$is_landscape) && ($width >= $min_port_w && $height >= $min_port_h);

            if ($is_landscape) {
                if (!($width >= $min_land_w && $height >= $min_land_h)) {
                    $errors[] = sprintf(__('Image %s is too small. Minimum %dx%d for landscape.', 'malisafi-mls'), $file['name'], $min_land_w, $min_land_h);
                    continue;
                }
            } else {
                if (!$allow_portrait_large) {
                    $errors[] = sprintf(__('Portrait image %s must be at least %dx%d.', 'malisafi-mls'), $file['name'], $min_port_w, $min_port_h);
                    continue;
                }
            }
            
            // Upload
            $upload = wp_handle_upload($file, array('test_form' => false));
            
            if (isset($upload['error'])) {
                $errors[] = $upload['error'];
                continue;
            }
            
            // Create attachment
            $attachment_id = wp_insert_attachment(array(
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name($file['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            ), $upload['file']);
            
            // Generate metadata
            $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $metadata);
            
            $uploaded_ids[] = array(
                'id' => $attachment_id,
                'url' => wp_get_attachment_image_url($attachment_id, 'malisafi_grid') ?: wp_get_attachment_image_url($attachment_id, 'medium'),
                'thumb' => wp_get_attachment_image_url($attachment_id, 'malisafi_thumb') ?: wp_get_attachment_image_url($attachment_id, 'thumbnail')
            );
        }
        
        if (!empty($uploaded_ids)) {
            wp_send_json_success(array(
                'images' => $uploaded_ids,
                'errors' => $errors
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Upload failed', 'malisafi-mls'),
                'errors' => $errors
            ));
        }
    }
    
    /**
     * AJAX: Delete property image
     */
    public static function ajax_delete_image() {
        check_ajax_referer('malisafi_property_submission', 'nonce');
        
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
        
        if (!$image_id) {
            wp_send_json_error(array('message' => __('Invalid image', 'malisafi-mls')));
        }
        
        // Verify ownership through property
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        if ($property_id) {
            $property = get_post($property_id);
            if (!$property || $property->post_author != get_current_user_id()) {
                wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
            }
        }
        
        if (wp_delete_attachment($image_id, true)) {
            wp_send_json_success(array('message' => __('Image deleted', 'malisafi-mls')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete image', 'malisafi-mls')));
        }
    }
    
    /**
     * AJAX: Reorder images
     */
    public static function ajax_reorder_images() {
        check_ajax_referer('malisafi_property_submission', 'nonce');
        
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        $order = isset($_POST['order']) && is_array($_POST['order']) ? array_map('intval', $_POST['order']) : array();
        
        if (!$property_id || empty($order)) {
            wp_send_json_error(array('message' => __('Invalid data', 'malisafi-mls')));
        }
        
        // Verify ownership
        $property = get_post($property_id);
        if (!$property || $property->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }
        
        // Save new order
        update_post_meta($property_id, '_malisafi_gallery_ids', implode(',', $order));
        
        // Update featured image if needed
        if (!empty($order) && !has_post_thumbnail($property_id)) {
            set_post_thumbnail($property_id, $order[0]);
        }
        
        wp_send_json_success(array('message' => __('Order updated', 'malisafi-mls')));
    }
    
    /**
     * Render success page for frontend
     */
    public static function render_success_page() {
        ob_start();
        include MALISAFI_MLS_PATH . 'templates/property-submission-success.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX: Get property draft
     */
    public static function ajax_get_draft() {
        check_ajax_referer('malisafi_property_submission', 'nonce');
        
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        
        if (!$property_id) {
            wp_send_json_error(array('message' => __('Invalid property', 'malisafi-mls')));
        }
        
        // Verify ownership
        $property = get_post($property_id);
        if (!$property || $property->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }
        
        // Get all property data
        $data = array(
            'title' => $property->post_title,
            'description' => $property->post_content,
            'price' => get_post_meta($property_id, '_malisafi_price', true),
            'currency' => get_post_meta($property_id, '_malisafi_currency', true),
            'property_type' => wp_get_object_terms($property_id, 'malisafi_property_type', array('fields' => 'slugs')),
            'listing_type' => get_post_meta($property_id, '_malisafi_listing_type', true),
            'bedrooms' => get_post_meta($property_id, '_malisafi_bedrooms', true),
            'bathrooms' => get_post_meta($property_id, '_malisafi_bathrooms', true),
            'size' => get_post_meta($property_id, '_malisafi_size', true),
            'county' => get_post_meta($property_id, '_malisafi_county', true),
            'city' => get_post_meta($property_id, '_malisafi_city', true),
            'features' => get_post_meta($property_id, '_malisafi_features', true),
            'gallery_ids' => get_post_meta($property_id, '_malisafi_gallery_ids', true)
        );
        
        wp_send_json_success(array('data' => $data));
    }
}
