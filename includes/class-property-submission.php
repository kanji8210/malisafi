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
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Property_Submission::init() called');
        }
        
        // AJAX handlers
        add_action('wp_ajax_malisafi_save_property_step', array(__CLASS__, 'ajax_save_property_step'));
        add_action('wp_ajax_malisafi_get_subcounties', array(__CLASS__, 'ajax_get_subcounties'));
        add_action('wp_ajax_nopriv_malisafi_get_subcounties', array(__CLASS__, 'ajax_get_subcounties'));
        add_action('wp_ajax_malisafi_submit_property', array(__CLASS__, 'ajax_submit_property'));
        add_action('wp_ajax_malisafi_upload_property_images', array(__CLASS__, 'ajax_upload_images'));
        add_action('wp_ajax_malisafi_upload_featured_image', array(__CLASS__, 'ajax_upload_featured_image'));
        add_action('wp_ajax_malisafi_delete_property_image', array(__CLASS__, 'ajax_delete_image'));
        add_action('wp_ajax_malisafi_clear_featured_image', array(__CLASS__, 'ajax_clear_featured_image'));
        add_action('wp_ajax_malisafi_reorder_property_images', array(__CLASS__, 'ajax_reorder_images'));
        
        // Handle direct property submission requests
        add_action('wp', array(__CLASS__, 'handle_direct_requests'));
        
        // Shortcode for property submission form
        add_shortcode('malisafi_submit_property', array(__CLASS__, 'render_submission_form'));
        
        // Shortcode for success page
        add_shortcode('malisafi_property_success', array(__CLASS__, 'render_success_page'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Property_Submission::init() completed');
        }
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
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Enqueuing property submission assets');
            }
            
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
                'fieldRegistry' => self::get_field_registry(),
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
        $can_assign_agent = current_user_can('manage_options') || current_user_can('moderate_properties');

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
     * Get the centralized registry of all property fields
     * 
     * @return array
     */
    public static function get_field_registry() {
        return array(
            'basic' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'text',
                    'required' => true,
                    'min_length' => 5,
                    'is_core' => true
                ),
                'description' => array(
                    'name' => 'description',
                    'type' => 'textarea',
                    'required' => false,
                    'is_core' => true
                ),
                'reference_id' => array(
                    'name' => 'reference_id',
                    'type' => 'text',
                    'meta_key' => '_malisafi_reference_id',
                    'readonly' => true
                ),
                'price' => array(
                    'name' => 'price',
                    'type' => 'number',
                    'meta_key' => '_malisafi_price',
                    'required' => true
                ),
                'currency' => array(
                    'name' => 'currency',
                    'type' => 'select',
                    'meta_key' => '_malisafi_currency',
                    'required' => true
                ),
                'property_type' => array(
                    'name' => 'property_type',
                    'type' => 'select',
                    'taxonomy' => 'property_type',
                    'required' => true
                ),
                'listing_type' => array(
                    'name' => 'listing_type',
                    'type' => 'select',
                    'meta_key' => '_malisafi_listing_type',
                    'required' => true
                ),
                'agent_user_id' => array(
                    'name' => 'agent_user_id',
                    'type' => 'author',
                    'required' => false
                ),
                'size' => array(
                    'name' => 'size',
                    'type' => 'number',
                    'meta_key' => '_malisafi_size',
                    'required' => true
                ),
                'size_unit' => array(
                    'name' => 'size_unit',
                    'type' => 'select',
                    'meta_key' => '_malisafi_size_unit',
                    'required' => true
                ),
                'land_use' => array(
                    'name' => 'land_use',
                    'type' => 'select',
                    'meta_key' => '_malisafi_land_use',
                    'show_for' => array('land')
                ),
                'road_access' => array(
                    'name' => 'road_access',
                    'type' => 'select',
                    'meta_key' => '_malisafi_road_access',
                    'show_for' => array('land')
                ),
                'ownership_type' => array(
                    'name' => 'ownership_type',
                    'type' => 'select',
                    'meta_key' => '_malisafi_ownership_type'
                ),
                'title_deed_status' => array(
                    'name' => 'title_deed_status',
                    'type' => 'select',
                    'meta_key' => '_malisafi_title_deed_status'
                ),
                'agent_name' => array(
                    'name' => 'agent_name',
                    'type' => 'text',
                    'meta_key' => '_malisafi_agent_name'
                ),
                'agent_email' => array(
                    'name' => 'agent_email',
                    'type' => 'email',
                    'meta_key' => '_malisafi_agent_email'
                ),
                'agent_phone' => array(
                    'name' => 'agent_phone',
                    'type' => 'text',
                    'meta_key' => '_malisafi_agent_phone'
                )
            ),
            'details' => array(
                'bedrooms' => array(
                    'name' => 'bedrooms',
                    'type' => 'number',
                    'meta_key' => '_malisafi_bedrooms',
                    'show_for' => array('house', 'apartment')
                ),
                'bathrooms' => array(
                    'name' => 'bathrooms',
                    'type' => 'number',
                    'meta_key' => '_malisafi_bathrooms',
                    'show_for' => array('house', 'apartment')
                ),
                'floor_number' => array(
                    'name' => 'floor_number',
                    'type' => 'number',
                    'meta_key' => '_malisafi_floor_number',
                    'show_for' => array('apartment', 'commercial')
                ),
                'total_floors' => array(
                    'name' => 'total_floors',
                    'type' => 'number',
                    'meta_key' => '_malisafi_total_floors',
                    'show_for' => array('apartment', 'commercial')
                ),
                'office_spaces' => array(
                    'name' => 'office_spaces',
                    'type' => 'number',
                    'meta_key' => '_malisafi_office_spaces',
                    'show_for' => array('commercial')
                ),
                'parking_spaces' => array(
                    'name' => 'parking_spaces',
                    'type' => 'number',
                    'meta_key' => '_malisafi_parking_spaces',
                    'show_for' => array('commercial')
                ),
                'loading_bays' => array(
                    'name' => 'loading_bays',
                    'type' => 'number',
                    'meta_key' => '_malisafi_loading_bays',
                    'show_for' => array('industrial')
                ),
                'power_capacity_kva' => array(
                    'name' => 'power_capacity_kva',
                    'type' => 'number',
                    'meta_key' => '_malisafi_power_capacity_kva',
                    'show_for' => array('industrial')
                ),
                'ceiling_height_m' => array(
                    'name' => 'ceiling_height_m',
                    'type' => 'number',
                    'meta_key' => '_malisafi_ceiling_height_m',
                    'show_for' => array('industrial')
                ),
                'year_built' => array(
                    'name' => 'year_built',
                    'type' => 'number',
                    'meta_key' => '_malisafi_year_built',
                    'show_for' => array('house', 'apartment', 'commercial', 'industrial')
                ),
                'condition' => array(
                    'name' => 'condition',
                    'type' => 'select',
                    'meta_key' => '_malisafi_condition',
                    'show_for' => array('house', 'apartment', 'commercial', 'industrial')
                )
            ),
            'location' => array(
                'address' => array(
                    'name' => 'address',
                    'type' => 'text',
                    'meta_key' => '_malisafi_address'
                ),
                'county' => array(
                    'name' => 'county',
                    'type' => 'select',
                    'meta_key' => '_malisafi_county',
                    'required' => true
                ),
                'subcounty' => array(
                    'name' => 'subcounty',
                    'type' => 'select',
                    'meta_key' => '_malisafi_subcounty',
                    'required' => true
                ),
                'city' => array(
                    'name' => 'city',
                    'type' => 'text',
                    'meta_key' => '_malisafi_city'
                ),
                'area' => array(
                    'name' => 'area',
                    'type' => 'text',
                    'meta_key' => '_malisafi_area'
                ),
                'gps' => array(
                    'name' => 'gps',
                    'type' => 'text',
                    'meta_key' => '_malisafi_gps'
                ),
                'google_maps_url' => array(
                    'name' => 'google_maps_url',
                    'type' => 'url',
                    'meta_key' => '_malisafi_google_maps_url'
                )
            ),
            'features' => array(
                'features' => array(
                    'name' => 'features',
                    'type' => 'checkbox_group',
                    'meta_key' => '_malisafi_features'
                ),
                'amenities' => array(
                    'name' => 'amenities',
                    'type' => 'checkbox_group',
                    'meta_key' => '_malisafi_amenities'
                ),
                'land_utilities' => array(
                    'name' => 'land_utilities',
                    'type' => 'checkbox_group',
                    'meta_key' => '_malisafi_land_utilities',
                    'show_for' => array('land')
                ),
                'floor_plan_urls' => array(
                    'name' => 'floor_plan_urls',
                    'type' => 'textarea',
                    'meta_key' => '_malisafi_floor_plan_urls',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'expected_roi' => array(
                    'name' => 'expected_roi',
                    'type' => 'number',
                    'meta_key' => '_malisafi_expected_roi',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'rental_yield' => array(
                    'name' => 'rental_yield',
                    'type' => 'number',
                    'meta_key' => '_malisafi_rental_yield',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'annual_rent_income' => array(
                    'name' => 'annual_rent_income',
                    'type' => 'number',
                    'meta_key' => '_malisafi_annual_rent_income',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'developer_guarantee' => array(
                    'name' => 'developer_guarantee',
                    'type' => 'textarea',
                    'meta_key' => '_malisafi_developer_guarantee',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'financing_options' => array(
                    'name' => 'financing_options',
                    'type' => 'checkbox_group',
                    'meta_key' => '_malisafi_financing_options',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'financing_min_deposit' => array(
                    'name' => 'financing_min_deposit',
                    'type' => 'number',
                    'meta_key' => '_malisafi_financing_min_deposit',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'financing_tenor_months' => array(
                    'name' => 'financing_tenor_months',
                    'type' => 'number',
                    'meta_key' => '_malisafi_financing_tenor_months',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'financing_interest_rate' => array(
                    'name' => 'financing_interest_rate',
                    'type' => 'number',
                    'meta_key' => '_malisafi_financing_interest_rate',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'diaspora_financing_details' => array(
                    'name' => 'diaspora_financing_details',
                    'type' => 'text',
                    'meta_key' => '_malisafi_diaspora_financing_details',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'sustainability' => array(
                    'name' => 'sustainability',
                    'type' => 'checkbox_group',
                    'meta_key' => '_malisafi_sustainability',
                    'show_for_listing' => array('sale', 'lease')
                ),
                'green_certification' => array(
                    'name' => 'green_certification',
                    'type' => 'text',
                    'meta_key' => '_malisafi_green_certification',
                    'show_for_listing' => array('sale', 'lease')
                )
            ),
            'images' => array(
                'featured_image_id' => array(
                    'name' => 'featured_image_id',
                    'type' => 'hidden',
                    'meta_key' => '_thumbnail_id',
                    'required' => true
                ),
                'gallery_ids' => array(
                    'name' => 'gallery_ids',
                    'type' => 'hidden',
                    'meta_key' => '_malisafi_gallery_ids'
                )
            )
        );
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
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Malisafi Property Save Debug:');
            error_log('Property ID: ' . $property_id);
            error_log('Step: ' . $step);
            error_log('Data: ' . print_r($data, true));
        }
        
        // Validate data using Validator
        require_once MALISAFI_MLS_PATH . 'includes/class-validator.php';
        $validator = new Validator();
        
        // Create or update property
        if ($property_id) {
            $property = get_post($property_id);
            if (!$property) {
                wp_send_json_error(array('message' => __('Invalid property', 'malisafi-mls')));
            }
            
            // Verify ownership or admin status
            $can_edit = ($property->post_author == get_current_user_id() || current_user_can('moderate_properties'));
            if (!$can_edit) {
                wp_send_json_error(array('message' => __('Permission denied to edit this property', 'malisafi-mls')));
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
                // Remove hooks temporarily to prevent issues during draft creation
                remove_action('save_post_malisafi_property', array('Malisafi_Property_Approval_Workflow', 'handle_property_status'), 10);
                
                $property_id = wp_insert_post(array(
                    'post_type' => 'malisafi_property',
                    'post_status' => 'draft',
                    'post_author' => $current_user_id,
                    'post_title' => __('Draft Property', 'malisafi-mls') . ' ' . date('Y-m-d H:i:s')
                ));
                
                // Re-add hooks
                add_action('save_post_malisafi_property', array('Malisafi_Property_Approval_Workflow', 'handle_property_status'), 10, 3);

                if (is_wp_error($property_id)) {
                    wp_send_json_error(array('message' => $property_id->get_error_message()));
                }

                // Lock for 5 minutes to avoid duplicate inserts from concurrent requests
                set_transient($lock_key, $property_id, 5 * MINUTE_IN_SECONDS);
                // Mark as auto-draft for easier lookup/debug
                update_post_meta($property_id, '_malisafi_auto_draft', 1);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Malisafi: Created new draft property #' . $property_id . ' for user ' . $current_user_id);
                }
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
     * Persist fields based on registry configuration
     */
    private static function persist_fields($property_id, $step, $data, $validator) {
        $registry = self::get_field_registry();
        if (!isset($registry[$step])) {
            return new \WP_Error('invalid_step', __('Invalid step', 'malisafi-mls'));
        }

        $fields = $registry[$step];
        $post_data = array('ID' => $property_id);
        $update_post = false;

        // Get property type and listing type for conditional logic
        $property_types = wp_get_object_terms($property_id, 'malisafi_property_type', array('fields' => 'slugs'));
        $property_type = !empty($property_types) && !is_wp_error($property_types) ? $property_types[0] : '';
        $listing_type = get_post_meta($property_id, '_malisafi_listing_type', true);

        // If basic step is being saved, get immediate values from $data
        if ($step === 'basic') {
            if (isset($data['property_type'])) {
                $property_type = is_array($data['property_type']) ? $data['property_type'][0] : $data['property_type'];
            }
            if (isset($data['listing_type'])) {
                $listing_type = sanitize_text_field($data['listing_type']);
            }
        }

        foreach ($fields as $key => $config) {
            $value = isset($data[$key]) ? $data[$key] : null;

            if (isset($config['readonly']) && $config['readonly']) continue;

            // Check if field is applicable for this property type
            if (isset($config['show_for']) && !empty($property_type)) {
                $show_for = is_array($config['show_for']) ? $config['show_for'] : explode(',', $config['show_for']);
                if (!in_array($property_type, $show_for)) {
                    // Clear the field if it's not applicable
                    if (isset($config['meta_key'])) update_post_meta($property_id, $config['meta_key'], '');
                    continue;
                }
            }

            // Check if field is applicable for this listing type (sale/lease)
            if (isset($config['show_for_listing']) && !empty($listing_type)) {
                $show_for_listing = is_array($config['show_for_listing']) ? $config['show_for_listing'] : explode(',', $config['show_for_listing']);
                if (!in_array($listing_type, $show_for_listing)) {
                    // Clear the field if it's not applicable
                    if (isset($config['meta_key'])) update_post_meta($property_id, $config['meta_key'], '');
                    continue;
                }
            }

            // Handle validation based on config
            $required = isset($config['required']) ? $config['required'] : false;
            $type = isset($config['type']) ? $config['type'] : 'text';

            switch ($type) {
                case 'email':
                    $validator->email($value ?? '', $key, $required);
                    break;
                case 'author':
                    if (current_user_can('moderate_properties') && !empty($value)) {
                        $post_data['post_author'] = intval($value);
                        $update_post = true;
                    }
                    break;
                case 'url':
                    $validator->url($value ?? '', $key, $required);
                    break;
                case 'number':
                    $validator->number($value ?? 0, $key, 0, null, $required);
                    break;
                case 'integer':
                    $validator->integer($value ?? 0, $key, 0, null, $required);
                    break;
                case 'checkbox_group':
                    if ($required && empty($value)) {
                        $validator->add_error($key, sprintf(__('%s is required', 'malisafi-mls'), ucfirst($key)));
                    }
                    break;
                default:
                    $min = isset($config['min_length']) ? $config['min_length'] : ($required ? 1 : 0);
                    $validator->text($value ?? '', $key, $min, 5000, $required);
                    break;
            }

            if ($validator->fails() && $required) continue;

            $validated = $validator->validated();
            $sanitized_value = isset($validated[$key]) ? $validated[$key] : $value;

            // Persist based on config
            if (isset($config['is_core']) && $config['is_core']) {
                if ($key === 'title') {
                    $post_data['post_title'] = sanitize_text_field($sanitized_value);
                    $update_post = true;
                } elseif ($key === 'description') {
                    $post_data['post_content'] = wp_kses_post($sanitized_value);
                    $update_post = true;
                }
            } elseif (isset($config['taxonomy'])) {
                $terms = is_array($sanitized_value) ? array_map('sanitize_text_field', $sanitized_value) : sanitize_text_field($sanitized_value);
                wp_set_object_terms($property_id, 'malisafi_' . $config['taxonomy'], $terms);
            } elseif (isset($config['meta_key'])) {
                if (is_array($sanitized_value)) {
                    $sanitized_value = array_map('sanitize_text_field', $sanitized_value);
                } else {
                    $sanitized_value = sanitize_text_field($sanitized_value);
                }
                update_post_meta($property_id, $config['meta_key'], $sanitized_value);

                // Auto-set status term if listing type changes
                if ($key === 'listing_type') {
                    $status_term = self::get_status_term_from_listing_type($sanitized_value);
                    if ($status_term) {
                        wp_set_object_terms($property_id, $status_term, 'malisafi_property_status');
                    }
                }
            }
        }

        if ($validator->fails()) {
            return new \WP_Error('validation_failed', __('Validation failed', 'malisafi-mls'), $validator->get_errors());
        }

        if ($update_post) {
            wp_update_post($post_data);
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
            'lease' => 'For Sale'
        );
        
        return isset($mapping[$listing_type]) ? $mapping[$listing_type] : '';
    }

    /**
     * Save step data
     */
    private static function save_step_data($property_id, $step, $data, $validator) {
        if ($step === 'images') {
            return self::save_images($property_id, $data);
        }
        
        // Use consolidated persist_fields for all other steps
        return self::persist_fields($property_id, $step, $data, $validator);
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
            // Save images ids
            update_post_meta($property_id, '_malisafi_gallery_ids', implode(',', $gallery_ids));
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
        
        // Verify ownership or admin permissions
        $property = get_post($property_id);
        $can_submit = ($property && ($property->post_author == get_current_user_id() || current_user_can('moderate_properties')));
        if (!$can_submit) {
            wp_send_json_error(array('message' => __('Invalid property or permission denied', 'malisafi-mls')));
        }
        
        // DUPLICATE PREVENTION: Check submission lock
        $submission_lock_key = 'malisafi_submit_lock_' . $property_id;
        if (get_transient($submission_lock_key)) {
            wp_send_json_error(array('message' => __('This property is already being submitted. Please wait.', 'malisafi-mls')));
        }
        
        // Set submission lock (30 seconds)
        set_transient($submission_lock_key, time(), 30);
        
        // Check if all required fields are filled
        $validation = self::validate_property($property_id);
        if (is_wp_error($validation)) {
            wp_send_json_error(array(
                'message' => __('Please complete all required fields', 'malisafi-mls'),
                'errors' => $validation->get_error_data()
            ));
        }
        
        // Update status to pending review
        // Remove hooks temporarily to prevent duplication
        remove_action('save_post_malisafi_property', array('Malisafi_Property_Approval_Workflow', 'handle_property_status'), 10);
        
        wp_update_post(array(
            'ID' => $property_id,
            'post_status' => 'pending'
        ));
        
        // Re-add hooks
        add_action('save_post_malisafi_property', array('Malisafi_Property_Approval_Workflow', 'handle_property_status'), 10, 3);
        
        // Clear submission lock
        delete_transient($submission_lock_key);
        
        // Clear auto-draft marker
        delete_post_meta($property_id, '_malisafi_auto_draft');
        
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

        // Resolve property type once for all type-specific checks
        $property_type = '';
        $ptype_terms = wp_get_object_terms($property_id, 'malisafi_property_type', array('fields' => 'all'));
        if (!is_wp_error($ptype_terms) && !empty($ptype_terms)) {
            $property_type = strtolower($ptype_terms[0]->slug ?: $ptype_terms[0]->name);
        }
        $is_land = $property_type === 'land';

        // Subcounty waived for land (road access is used instead)
        if (empty($subcounty) && !$is_land) {
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

        // Check if property exists and user owns it or is admin
        if ($property_id) {
            $property = get_post($property_id);
            $can_edit = ($property && ($property->post_author == get_current_user_id() || current_user_can('moderate_properties')));
            if (!$can_edit) {
                wp_send_json_error(array('message' => __('Invalid property or permission denied', 'malisafi-mls')));
            }
        }

        // Handle file upload
        if (empty($_FILES['image'])) {
            wp_send_json_error(array('message' => __('No image uploaded', 'malisafi-mls')));
        }

        $file = $_FILES['image'];

        // Upload using Image_Handler
        $upload_result = Image_Handler::upload_single($file, array(
            'validate_dimensions' => false,
            'size_map' => array(
                'url' => 'large',
                'thumb' => 'medium'
            ),
            'min_width' => 800,
            'min_height' => 600
        ));

        if (is_wp_error($upload_result)) {
            wp_send_json_error(array('message' => $upload_result->get_error_message()));
        }

        // If we have a property ID, set as featured image
        if ($property_id) {
            set_post_thumbnail($property_id, $upload_result['id']);
        }

        wp_send_json_success(array(
            'data' => array(
                'message' => __('Featured image uploaded successfully', 'malisafi-mls'),
                'image' => array(
                    'id' => $upload_result['id'],
                    'url' => $upload_result['sizes']['url']
                )
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

        // Check if property exists and user owns it or is admin
        if ($property_id) {
            $property = get_post($property_id);
            $can_edit = ($property && ($property->post_author == get_current_user_id() || current_user_can('moderate_properties')));
            if (!$can_edit) {
                wp_send_json_error(array('message' => __('Invalid property or permission denied', 'malisafi-mls')));
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
                if ($key !== false) {
                    $existing_ids[$key] = $new_ids[0]; 
                }
                $updated_gallery = $existing_ids;
            } else {
                // Add new images
                $updated_gallery = array_merge($existing_ids, $new_ids);
            }

            update_post_meta($property_id, '_malisafi_gallery_ids', implode(',', $updated_gallery));
        }

        wp_send_json_success(array(
            'data' => array(
                'message' => sprintf(__('Successfully uploaded %d image(s)', 'malisafi-mls'), count($uploaded_images)),
                'images' => $uploaded_images
            )
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

        // Verify ownership and admin permissions
        $property = get_post($property_id);
        $can_edit = ($property && ($property->post_author == get_current_user_id() || current_user_can('moderate_properties')));
        if (!$can_edit) {
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

        // Verify ownership and admin permissions
        $property = get_post($property_id);
        $can_edit = ($property && ($property->post_author == get_current_user_id() || current_user_can('moderate_properties')));
        if (!$can_edit) {
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

        // Verify ownership and admin permissions
        $property = get_post($property_id);
        $can_edit = ($property && ($property->post_author == get_current_user_id() || current_user_can('moderate_properties')));
        if (!$can_edit) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }

        // Save new order
        update_post_meta($property_id, '_malisafi_gallery_ids', implode(',', $order));

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
        
        $registry = self::get_field_registry();
        $data = array();
        
        foreach ($registry as $step => $fields) {
            foreach ($fields as $key => $config) {
                if (isset($config['is_core']) && $config['is_core']) {
                    if ($key === 'title') $data[$key] = $property->post_title;
                    if ($key === 'description') $data[$key] = $property->post_content;
                } elseif (isset($config['taxonomy'])) {
                    $terms = wp_get_object_terms($property_id, 'malisafi_' . $config['taxonomy'], array('fields' => 'slugs'));
                    $data[$key] = !is_wp_error($terms) ? $terms : array();
                } elseif (isset($config['meta_key'])) {
                    $val = get_post_meta($property_id, $config['meta_key'], true);
                    $data[$key] = $val;
                }
            }
        }
        
        // Specialized handling for images
        $featured_image_id = get_post_thumbnail_id($property_id);
        $data['featured_image'] = $featured_image_id ? array(
            'id' => $featured_image_id,
            'url' => wp_get_attachment_image_url($featured_image_id, 'large')
        ) : null;

        $gallery_ids_raw = get_post_meta($property_id, '_malisafi_gallery_ids', true);
        $data['gallery_ids'] = $gallery_ids_raw;
        $data['gallery_images'] = array();
        if (!empty($gallery_ids_raw)) {
            $gallery_ids = array_filter(array_map('intval', explode(',', $gallery_ids_raw)));
            foreach ($gallery_ids as $image_id) {
                $url = wp_get_attachment_image_url($image_id, 'medium');
                if ($url) {
                    $data['gallery_images'][] = array('id' => $image_id, 'url' => $url);
                }
            }
        }
        
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

        if (!function_exists('malisafi_get_subcounties_by_county')) {
            require_once MALISAFI_MLS_PATH . 'includes/kenya-location-helpers.php';
        }

        $subcounties = malisafi_get_subcounties_by_county($county);

        wp_send_json_success(array('subcounties' => $subcounties));
    }
}
