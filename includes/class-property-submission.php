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
        error_log('Property_Submission::init() called');
        
        // AJAX handlers
        add_action('wp_ajax_malisafi_save_property_step', array(__CLASS__, 'ajax_save_property_step'));
        add_action('wp_ajax_malisafi_submit_property', array(__CLASS__, 'ajax_submit_property'));
        add_action('wp_ajax_malisafi_upload_property_images', array(__CLASS__, 'ajax_upload_images'));
        add_action('wp_ajax_malisafi_upload_featured_image', array(__CLASS__, 'ajax_upload_featured_image'));
        add_action('wp_ajax_malisafi_delete_property_image', array(__CLASS__, 'ajax_delete_image'));
        add_action('wp_ajax_malisafi_clear_featured_image', array(__CLASS__, 'ajax_clear_featured_image'));
        add_action('wp_ajax_malisafi_reorder_property_images', array(__CLASS__, 'ajax_reorder_images'));
        add_action('wp_ajax_malisafi_get_property_draft', array(__CLASS__, 'ajax_get_draft'));
        add_action('wp_ajax_malisafi_get_subcounties', array(__CLASS__, 'ajax_get_subcounties'));
        add_action('wp_ajax_nopriv_malisafi_get_subcounties', array(__CLASS__, 'ajax_get_subcounties'));
        
        // Handle direct property submission requests
        add_action('wp', array(__CLASS__, 'handle_direct_requests'));
        
        // Shortcode for property submission form
        add_shortcode('malisafi_submit_property', array(__CLASS__, 'render_submission_form'));
        
        // Shortcode for success page
        add_shortcode('malisafi_property_success', array(__CLASS__, 'render_success_page'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        
        error_log('Property_Submission::init() completed');
    }
    
    /**
     * Handle direct property submission requests
     */
    public static function handle_direct_requests() {
        if (isset($_GET['malisafi_action']) && $_GET['malisafi_action'] === 'submit_property') {
            // Output the submission form directly
            echo '<!DOCTYPE html><html><head>';
            wp_head();
            echo '</head><body>';
            echo self::render_submission_form(array());
            wp_footer();
            echo '</body></html>';
            exit;
        }
    }
    
    /**
     * Enqueue assets
     */
    public static function enqueue_assets() {
        if (is_page() && (has_shortcode(get_post()->post_content, 'malisafi_submit_property') ||
                          has_shortcode(get_post()->post_content, 'malisafi_property_submit') ||
                          has_shortcode(get_post()->post_content, 'malisafi_agent_add_property')) ||
            (isset($_GET['malisafi_action']) && $_GET['malisafi_action'] === 'submit_property')) {
            error_log('Enqueuing property submission assets');
            
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');

            wp_enqueue_style(
                'malisafi-property-submission',
                MALISAFI_MLS_URL . 'assets/css/property-submission.css',
                array('malisafi-mls-variables'),
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
        $atts = is_array($atts) ? $atts : array();
        // Check if we should show success page instead
        $submission = isset($_GET['submission']) ? sanitize_text_field(wp_unslash($_GET['submission'])) : '';
        $property_id = isset($_GET['property_id']) ? absint($_GET['property_id']) : 0;
        if ($submission === 'success' && $property_id) {
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

        // Enqueue editor assets for frontend usage of admin-style form
        if (function_exists('wp_enqueue_editor')) {
            wp_enqueue_editor();
        }

        $current_user = wp_get_current_user();
        $can_assign_agent = current_user_can('manage_options') || current_user_can('edit_others_properties') || current_user_can('publish_properties');

        $frontend_cancel_url = '';
        if (class_exists('MalisafiMLS\\Page_Manager') && method_exists('MalisafiMLS\\Page_Manager', 'get_page_url')) {
            if (in_array('malisafi_agent_basic', (array) $current_user->roles, true) || in_array('malisafi_agent_premium', (array) $current_user->roles, true)) {
                $frontend_cancel_url = Page_Manager::get_page_url('agent_dashboard');
            } elseif (in_array('malisafi_owner', (array) $current_user->roles, true)) {
                $frontend_cancel_url = Page_Manager::get_page_url('owner_dashboard');
            } elseif (in_array('malisafi_developer', (array) $current_user->roles, true)) {
                $frontend_cancel_url = Page_Manager::get_page_url('developer_dashboard');
            }
        }

        // Load wizard template for frontend usage
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
            $max_properties = 0;
            if (isset($limits->max_listings)) {
                $max_properties = intval($limits->max_listings);
            }
            
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
        
        // Handle JSON data
        if (is_string($data)) {
            $data = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $data = array();
            }
        }
        
        // Debug logging
        error_log('Malisafi Property Save Debug:');
        error_log('Property ID: ' . $property_id);
        error_log('Step: ' . $step);
        error_log('Data: ' . print_r($data, true));
        
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
            // Try to reuse a recently-created auto-draft to avoid duplicates (race conditions)
            $current_user_id = get_current_user_id();

            // Check transient lock first
            $lock_key = 'malisafi_autodraft_lock_' . $current_user_id;
            $locked_id = get_transient($lock_key);
            if ($locked_id) {
                $existing_post = get_post($locked_id);
                if ($existing_post && $existing_post->post_type === 'malisafi_property' && $existing_post->post_status === 'draft' && (int) $existing_post->post_author === $current_user_id) {
                    $property_id = $existing_post->ID;
                } else {
                    delete_transient($lock_key);
                    $locked_id = false;
                }
            }

            if (empty($property_id)) {
                // Look for the most recent draft created by this user within the last 10 minutes
                $recent_posts = get_posts(array(
                    'post_type' => 'malisafi_property',
                    'author' => $current_user_id,
                    'post_status' => 'draft',
                    'posts_per_page' => 1,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'date_query' => array(
                        array('after' => '10 minutes ago')
                    )
                ));

                if (!empty($recent_posts) && isset($recent_posts[0]->ID)) {
                    $property_id = $recent_posts[0]->ID;
                }
            }

            if (empty($property_id)) {
                // Create new draft and set transient lock
                $property_id = wp_insert_post(array(
                    'post_type' => 'malisafi_property',
                    'post_status' => 'draft',
                    'post_author' => $current_user_id,
                    'post_title' => __('Draft Property', 'malisafi-mls') . ' ' . date('Y-m-d H:i:s')
                ));

                if (is_wp_error($property_id)) {
                    wp_send_json_error(array('message' => $property_id->get_error_message()));
                }

                // Lock for 5 minutes to avoid duplicate inserts from concurrent requests
                set_transient($lock_key, $property_id, 5 * MINUTE_IN_SECONDS);
                // Mark as auto-draft for easier lookup/debug
                update_post_meta($property_id, '_malisafi_auto_draft', 1);
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
        $validator->in_array($data['listing_type'] ?? '', array('sale', 'rent', 'lease', 'short_term'), 'listing_type', true);
        
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
        
        // Set property status based on listing type
        $status_term = self::get_status_term_from_listing_type($validated['listing_type']);
        if ($status_term) {
            wp_set_object_terms($property_id, $status_term, 'malisafi_property_status');
        }
        
        return true;
    }
    
    /**
     * Get status term from listing type
     */
    private static function get_status_term_from_listing_type($listing_type) {
        $mapping = array(
            'sale' => 'For Sale',
            'rent' => 'For Rent',
            'short_term' => 'Short Term Rent',
            'lease' => 'For Sale' // Map lease to For Sale since no specific term exists
        );
        
        return isset($mapping[$listing_type]) ? $mapping[$listing_type] : '';
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

        $listing_type = get_post_meta($property_id, '_malisafi_listing_type', true);
        $is_sale_or_lease = in_array($listing_type, array('sale', 'lease'), true);

        if ($is_sale_or_lease) {
            $validator->text($data['floor_plan_urls'] ?? '', 'floor_plan_urls', 0, 2000, false);
            $validator->number($data['expected_roi'] ?? 0, 'expected_roi', 0, 100, false);
            $validator->number($data['rental_yield'] ?? 0, 'rental_yield', 0, 100, false);
            $validator->number($data['annual_rent_income'] ?? 0, 'annual_rent_income', 0, null, false);
            $validator->in_array($data['ownership_type'] ?? '', array('freehold', 'leasehold', 'company_shares', 'sectional_title'), 'ownership_type', false);
            $validator->in_array($data['title_deed_status'] ?? '', array('ready', 'processing', 'not_available'), 'title_deed_status', false);
            $validator->number($data['financing_min_deposit'] ?? 0, 'financing_min_deposit', 0, 100, false);
            $validator->integer($data['financing_tenor_months'] ?? 0, 'financing_tenor_months', 0, 600, false);
            $validator->number($data['financing_interest_rate'] ?? 0, 'financing_interest_rate', 0, 100, false);
            $validator->text($data['diaspora_financing_details'] ?? '', 'diaspora_financing_details', 0, 255, false);
            $validator->text($data['developer_guarantee'] ?? '', 'developer_guarantee', 0, 1000, false);
            $validator->text($data['green_certification'] ?? '', 'green_certification', 0, 255, false);
        }
        
        if ($validator->fails()) {
            return new \WP_Error('validation_failed', __('Validation failed', 'malisafi-mls'), $validator->get_errors());
        }
        
        $validated = $validator->validated();
        
        // Save meta
        foreach ($validated as $key => $value) {
            update_post_meta($property_id, '_malisafi_' . $key, $value);
        }

        // Save array-based fields (sale/lease only)
        if ($is_sale_or_lease) {
            $financing_options = array();
            if (isset($data['financing_options']) && is_array($data['financing_options'])) {
                $financing_options = array_map('sanitize_text_field', $data['financing_options']);
            }
            update_post_meta($property_id, '_malisafi_financing_options', $financing_options);

            $sustainability = array();
            if (isset($data['sustainability']) && is_array($data['sustainability'])) {
                $sustainability = array_map('sanitize_text_field', $data['sustainability']);
            }
            update_post_meta($property_id, '_malisafi_sustainability', $sustainability);
        } else {
            update_post_meta($property_id, '_malisafi_floor_plan_urls', '');
            update_post_meta($property_id, '_malisafi_expected_roi', 0);
            update_post_meta($property_id, '_malisafi_rental_yield', 0);
            update_post_meta($property_id, '_malisafi_annual_rent_income', 0);
            update_post_meta($property_id, '_malisafi_ownership_type', '');
            update_post_meta($property_id, '_malisafi_title_deed_status', '');
            update_post_meta($property_id, '_malisafi_financing_options', array());
            update_post_meta($property_id, '_malisafi_financing_min_deposit', 0);
            update_post_meta($property_id, '_malisafi_financing_tenor_months', 0);
            update_post_meta($property_id, '_malisafi_financing_interest_rate', 0);
            update_post_meta($property_id, '_malisafi_diaspora_financing_details', '');
            update_post_meta($property_id, '_malisafi_developer_guarantee', '');
            update_post_meta($property_id, '_malisafi_sustainability', array());
            update_post_meta($property_id, '_malisafi_green_certification', '');
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
        $validator->text($data['subcounty'] ?? '', 'subcounty', 2, 80, true);
        $validator->text($data['city'] ?? '', 'city', 2, 50, false);
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
        $location_terms = array();
        if (!empty($validated['county'])) {
            $location_terms[] = $validated['county'];
        }
        if (!empty($validated['subcounty'])) {
            $location_terms[] = $validated['subcounty'];
        }

        if (!empty($location_terms)) {
            wp_set_object_terms($property_id, $location_terms, 'malisafi_property_location');
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
        if (isset($data['featured_image_id'])) {
            $featured_id = (int) $data['featured_image_id'];
            if ($featured_id > 0) {
                set_post_thumbnail($property_id, $featured_id);
            }
        }

        if (isset($data['gallery_ids'])) {
            $gallery_ids = array();
            if (is_array($data['gallery_ids'])) {
                $gallery_ids = array_map('intval', $data['gallery_ids']);
            } elseif (is_string($data['gallery_ids']) && $data['gallery_ids'] !== '') {
                $gallery_ids = array_map('intval', explode(',', $data['gallery_ids']));
            }

            $gallery_ids = array_filter($gallery_ids);
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

        $current_user = wp_get_current_user();
        $add_new_url = '';
        if (class_exists('MalisafiMLS\\Page_Manager') && method_exists('MalisafiMLS\\Page_Manager', 'get_page_url')) {
            if (in_array('malisafi_agent_basic', (array) $current_user->roles, true) || in_array('malisafi_agent_premium', (array) $current_user->roles, true)) {
                $add_new_url = Page_Manager::get_page_url('agent_add_property');
            } elseif (in_array('malisafi_owner', (array) $current_user->roles, true)) {
                $add_new_url = Page_Manager::get_page_url('owner_add_property');
            } else {
                $add_new_url = Page_Manager::get_page_url('submit_property');
            }
        }

        if (!$add_new_url) {
            $add_new_url = home_url('/add-property/');
        }

        $view_url = get_permalink($property_id);
        
        wp_send_json_success(array(
            'message' => __('Property submitted successfully!', 'malisafi-mls'),
            'redirect' => $success_url,
            'add_new_url' => $add_new_url,
            'view_url' => $view_url
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

        $subcounty = get_post_meta($property_id, '_malisafi_subcounty', true);
        if (empty($subcounty)) {
            $errors['subcounty'] = __('Subcounty is required', 'malisafi-mls');
        }
        
        // Require at least one image via front-end gallery upload
        $featured_image_id = get_post_thumbnail_id($property_id);
        if (empty($featured_image_id)) {
            $errors['images'] = __('Featured image is required to submit this listing.', 'malisafi-mls');
        }
        
        if (!empty($errors)) {
            return new \WP_Error('validation_failed', __('Validation failed', 'malisafi-mls'), $errors);
        }
        
        return true;
    }
    
    /**
     * AJAX: Upload featured image
     */
    public static function ajax_upload_featured_image() {
        check_ajax_referer('malisafi_upload_images', 'nonce');

        if (!self::user_can_submit()) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }

        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;

        // Check if property exists and user owns it
        if ($property_id) {
            $property = get_post($property_id);
            if (!$property || $property->post_author != get_current_user_id()) {
                wp_send_json_error(array('message' => __('Invalid property', 'malisafi-mls')));
            }
        }

        // Handle file upload
        if (empty($_FILES['image'])) {
            wp_send_json_error(array('message' => __('No image uploaded', 'malisafi-mls')));
        }

        $file = $_FILES['image'];

        // Upload using Image_Handler
        $upload_result = Image_Handler::upload_single($file, array(
            'validate_dimensions' => false, // Made optional for flexibility
            'size_map' => array(
                'url' => 'large',
                'thumb' => 'medium'
            ),
            'min_width' => 800,  // Reduced from 1600
            'min_height' => 600  // Reduced from 900
        ));

        if (is_wp_error($upload_result)) {
            wp_send_json_error(array('message' => $upload_result->get_error_message()));
        }

        // If we have a property ID, set as featured image
        if ($property_id) {
            set_post_thumbnail($property_id, $upload_result['id']);
        }

        wp_send_json_success(array(
            'message' => __('Featured image uploaded successfully', 'malisafi-mls'),
            'image' => array(
                'id' => $upload_result['id'],
                'url' => $upload_result['sizes']['url']
            )
        ));
    }

    /**
     * AJAX: Upload gallery images
     */
    public static function ajax_upload_images() {
        check_ajax_referer('malisafi_upload_images', 'nonce');

        if (!self::user_can_submit()) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }

        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;

        // Check if property exists and user owns it
        if ($property_id) {
            $property = get_post($property_id);
            if (!$property || $property->post_author != get_current_user_id()) {
                wp_send_json_error(array('message' => __('Invalid property', 'malisafi-mls')));
            }
        }

        // Handle multiple file uploads
        if (empty($_FILES['images'])) {
            wp_send_json_error(array('message' => __('No images uploaded', 'malisafi-mls')));
        }

        $uploaded_images = array();
        $files = $_FILES['images'];

        // Handle both single file and multiple files
        if (is_array($files['name'])) {
            // Multiple files
            $file_count = count($files['name']);
            for ($i = 0; $i < $file_count; $i++) {
                $file = array(
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                );

                $upload_result = Image_Handler::upload_single($file, array(
                    'validate_dimensions' => false,
                    'size_map' => array(
                        'url' => 'medium',
                        'thumb' => 'thumbnail'
                    ),
                    'min_width' => 800,
                    'min_height' => 600
                ));

                if (!is_wp_error($upload_result)) {
                    $uploaded_images[] = array(
                        'id' => $upload_result['id'],
                        'url' => $upload_result['sizes']['url']
                    );
                }
            }
        } else {
            // Single file
            $upload_result = Image_Handler::upload_single($files, array(
                'validate_dimensions' => false,
                'size_map' => array(
                    'url' => 'medium',
                    'thumb' => 'thumbnail'
                ),
                'min_width' => 800,
                'min_height' => 600
            ));

            if (!is_wp_error($upload_result)) {
                $uploaded_images[] = array(
                    'id' => $upload_result['id'],
                    'url' => $upload_result['sizes']['url']
                );
            }
        }

        if (empty($uploaded_images)) {
            wp_send_json_error(array('message' => __('No valid images were uploaded', 'malisafi-mls')));
        }

        $replace_id = isset($_POST['replace_id']) ? intval($_POST['replace_id']) : 0;

        // If we have a property ID, update gallery
        if ($property_id) {
            $existing_gallery = get_post_meta($property_id, '_malisafi_gallery_ids', true);
            $existing_ids = $existing_gallery ? array_filter(array_map('intval', explode(',', $existing_gallery))) : array();

            $new_ids = array_map(function($img) { return $img['id']; }, $uploaded_images);
            
            if ($replace_id && in_array($replace_id, $existing_ids)) {
                // Replace the specific image
                $key = array_search($replace_id, $existing_ids);
                $existing_ids[$key] = $new_ids[0]; // Replace with first new image
                $updated_gallery = $existing_ids;
            } else {
                // Add new images
                $updated_gallery = array_merge($existing_ids, $new_ids);
            }

            update_post_meta($property_id, '_malisafi_gallery_ids', implode(',', $updated_gallery));

            // Set first image as featured if none exists
            if (empty($existing_ids) && !has_post_thumbnail($property_id)) {
                set_post_thumbnail($property_id, $new_ids[0]);
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Successfully uploaded %d image(s)', 'malisafi-mls'), count($uploaded_images)),
            'images' => $uploaded_images
        ));
    }

    /**
     * AJAX: Delete property image
     */
    public static function ajax_delete_image() {
        check_ajax_referer('malisafi_upload_images', 'nonce');

        if (!self::user_can_submit()) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }

        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

        if (!$property_id || !$image_id) {
            wp_send_json_error(array('message' => __('Invalid data', 'malisafi-mls')));
        }

        // Verify ownership
        $property = get_post($property_id);
        if (!$property || $property->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }

        // Check if image belongs to property
        $gallery_ids_raw = get_post_meta($property_id, '_malisafi_gallery_ids', true);
        $gallery_ids = $gallery_ids_raw ? array_filter(array_map('intval', explode(',', $gallery_ids_raw))) : array();

        if (!in_array($image_id, $gallery_ids)) {
            wp_send_json_error(array('message' => __('Image not found in property gallery', 'malisafi-mls')));
        }

        // Delete the image
        $deleted = Image_Handler::delete_image($image_id, array(
            'property_id' => $property_id,
            'user_id' => get_current_user_id(),
        ));

        if (is_wp_error($deleted)) {
            wp_send_json_error(array('message' => $deleted->get_error_message()));
        }

        // Remove from gallery
        $updated_gallery = array_diff($gallery_ids, array($image_id));
        update_post_meta($property_id, '_malisafi_gallery_ids', implode(',', $updated_gallery));

        wp_send_json_success(array('message' => __('Image deleted', 'malisafi-mls')));
    }

    /**
     * AJAX: Clear featured image
     */
    public static function ajax_clear_featured_image() {
        check_ajax_referer('malisafi_upload_images', 'nonce');

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
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }

        delete_post_thumbnail($property_id);

        wp_send_json_success(array('message' => __('Featured image cleared', 'malisafi-mls')));
    }

    /**
     * AJAX: Reorder images
     */
    public static function ajax_reorder_images() {
        check_ajax_referer('malisafi_upload_images', 'nonce');

        if (!self::user_can_submit()) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }

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
        $featured_image_id = get_post_thumbnail_id($property_id);
        $featured_image = null;
        if ($featured_image_id) {
            $featured_url = wp_get_attachment_image_url($featured_image_id, 'large');
            if ($featured_url) {
                $featured_image = array(
                    'id' => $featured_image_id,
                    'url' => $featured_url
                );
            }
        }

        $gallery_images = array();
        $gallery_ids_raw = get_post_meta($property_id, '_malisafi_gallery_ids', true);
        if (!empty($gallery_ids_raw)) {
            $gallery_ids = array_filter(array_map('intval', explode(',', $gallery_ids_raw)));
            foreach ($gallery_ids as $image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'medium');
                if ($image_url) {
                    $gallery_images[] = array(
                        'id' => $image_id,
                        'url' => $image_url
                    );
                }
            }
        }

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
            'subcounty' => get_post_meta($property_id, '_malisafi_subcounty', true),
            'city' => get_post_meta($property_id, '_malisafi_city', true),
            'floor_plan_urls' => get_post_meta($property_id, '_malisafi_floor_plan_urls', true),
            'expected_roi' => get_post_meta($property_id, '_malisafi_expected_roi', true),
            'rental_yield' => get_post_meta($property_id, '_malisafi_rental_yield', true),
            'annual_rent_income' => get_post_meta($property_id, '_malisafi_annual_rent_income', true),
            'ownership_type' => get_post_meta($property_id, '_malisafi_ownership_type', true),
            'title_deed_status' => get_post_meta($property_id, '_malisafi_title_deed_status', true),
            'financing_options' => get_post_meta($property_id, '_malisafi_financing_options', true),
            'financing_min_deposit' => get_post_meta($property_id, '_malisafi_financing_min_deposit', true),
            'financing_tenor_months' => get_post_meta($property_id, '_malisafi_financing_tenor_months', true),
            'financing_interest_rate' => get_post_meta($property_id, '_malisafi_financing_interest_rate', true),
            'diaspora_financing_details' => get_post_meta($property_id, '_malisafi_diaspora_financing_details', true),
            'developer_guarantee' => get_post_meta($property_id, '_malisafi_developer_guarantee', true),
            'sustainability' => get_post_meta($property_id, '_malisafi_sustainability', true),
            'green_certification' => get_post_meta($property_id, '_malisafi_green_certification', true),
            'features' => get_post_meta($property_id, '_malisafi_features', true),
            'amenities' => get_post_meta($property_id, '_malisafi_amenities', true),
            'gallery_ids' => $gallery_ids_raw,
            'featured_image' => $featured_image,
            'gallery_images' => $gallery_images
        );
        
        wp_send_json_success(array('data' => $data));
    }

    /**
     * AJAX: Get subcounties for a county
     */
    public static function ajax_get_subcounties() {
        check_ajax_referer('malisafi_property_submission', 'nonce');

        $county = isset($_POST['county']) ? sanitize_text_field(wp_unslash($_POST['county'])) : '';
        if ($county === '') {
            wp_send_json_success(array('subcounties' => array()));
        }

        $subcounties = function_exists('malisafi_get_subcounties_by_county')
            ? malisafi_get_subcounties_by_county($county)
            : array();

        wp_send_json_success(array('subcounties' => $subcounties));
    }
}
