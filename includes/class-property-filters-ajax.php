<?php
/**
 * Property Filters AJAX Handler
 * Handles AJAX requests for filtering properties
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Property_Filters_Ajax {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_malisafi_filter_properties', array($this, 'filter_properties'));
        add_action('wp_ajax_nopriv_malisafi_filter_properties', array($this, 'filter_properties'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Always enqueue on frontend (will be used by shortcode)
        if (!is_admin()) {
            // Modern filters
            wp_enqueue_style(
                'malisafi-property-filters',
                MALISAFI_MLS_URL . 'assets/css/property-filters.css',
                array(),
                MALISAFI_MLS_VERSION
            );
            
            wp_enqueue_script(
                'malisafi-property-filters',
                MALISAFI_MLS_URL . 'assets/js/property-filters.js',
                array('jquery'),
                MALISAFI_MLS_VERSION,
                true
            );
            
            wp_localize_script('malisafi-property-filters', 'malisafiFilters', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('malisafi_filter_nonce'),
                'isLoggedIn' => is_user_logged_in(),
                'homeUrl' => home_url()
            ));
            
            // Enqueue property moderation scripts (for report functionality)
            if (class_exists('Malisafi_Property_Moderation')) {
                wp_enqueue_style(
                    'malisafi-mls-moderation',
                    MALISAFI_MLS_URL . 'public/css/property-moderation.css',
                    array(),
                    MALISAFI_MLS_VERSION
                );
                
                wp_enqueue_script(
                    'malisafi-mls-moderation',
                    MALISAFI_MLS_URL . 'public/js/property-moderation.js',
                    array('jquery'),
                    MALISAFI_MLS_VERSION,
                    true
                );
                
                $report_reasons = \Malisafi_Property_Moderation::get_report_reasons();
                
                wp_localize_script('malisafi-mls-moderation', 'malisafiModeration', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('malisafi_report_property'),
                    'isLoggedIn' => is_user_logged_in(),
                    'reportReasons' => $report_reasons,
                    'i18n' => array(
                        'reportProperty' => __('Report Property', 'malisafi-mls'),
                        'reason' => __('Reason', 'malisafi-mls'),
                        'selectReason' => __('Select a reason...', 'malisafi-mls'),
                        'additionalDetails' => __('Additional Details (optional)', 'malisafi-mls'),
                        'detailsPlaceholder' => __('Please provide more information...', 'malisafi-mls'),
                        'submitReport' => __('Submit Report', 'malisafi-mls'),
                        'cancel' => __('Cancel', 'malisafi-mls'),
                        'loginRequired' => __('You must be logged in to report a property.', 'malisafi-mls'),
                        'selectReasonError' => __('Please select a reason for reporting.', 'malisafi-mls'),
                        'submitting' => __('Submitting...', 'malisafi-mls'),
                        'errorOccurred' => __('An error occurred. Please try again.', 'malisafi-mls')
                    )
                ));
            }
            
            // Minimalist filters
            wp_enqueue_style(
                'malisafi-property-filters-minimalist',
                MALISAFI_MLS_URL . 'assets/css/property-filters-minimalist.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'malisafi-property-filters-minimalist',
                MALISAFI_MLS_URL . 'assets/js/property-filters-minimalist.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            // Same localized data for minimalist version
            wp_localize_script('malisafi-property-filters-minimalist', 'malisafiFilters', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('malisafi_filter_nonce'),
                'isLoggedIn' => is_user_logged_in()
            ));
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'malisafi') !== false) {
            wp_enqueue_style(
                'malisafi-property-filters',
                MALISAFI_MLS_URL . 'assets/css/property-filters.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'malisafi-property-filters',
                MALISAFI_MLS_URL . 'assets/js/property-filters.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_localize_script('malisafi-property-filters', 'malisafiFilters', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('malisafi_filter_nonce'),
                'isLoggedIn' => is_user_logged_in()
            ));
        }
    }
    
    /**
     * Handle AJAX filter request
     */
    public function filter_properties() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_filter_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
        }
        
        // Get filters
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 12;
        
        // Enforce maximum limit to prevent performance issues
        $per_page = min($per_page, 100);
        
        // Build query args
        $args = array(
            'post_type' => 'malisafi_property',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
        );
        
        // Search
        if (!empty($filters['search'])) {
            $args['s'] = sanitize_text_field($filters['search']);
        }
        
        // Tax queries
        $tax_query = array('relation' => 'AND');
        $has_tax_filters = false;
        
        // Property type
        if (!empty($filters['property_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'malisafi_property_type',
                'field' => 'slug',
                'terms' => sanitize_text_field($filters['property_type']),
            );
            $has_tax_filters = true;
        }

        // Status (taxonomy)
        if (!empty($filters['status'])) {
            $tax_query[] = array(
                'taxonomy' => 'malisafi_property_status',
                'field' => 'slug',
                'terms' => sanitize_text_field($filters['status']),
            );
            $has_tax_filters = true;
        }
        
        // Location
        if (!empty($filters['location'])) {
            $tax_query[] = array(
                'taxonomy' => 'malisafi_property_location',
                'field' => 'slug',
                'terms' => sanitize_text_field($filters['location']),
            );
            $has_tax_filters = true;
        }
        
        if ($has_tax_filters) {
            $args['tax_query'] = $tax_query;
        }
        
        // Meta queries
        $meta_query = array('relation' => 'AND');
        $has_meta_filters = false;
        
        // County
        if (!empty($filters['county'])) {
            $meta_query[] = array(
                'key' => '_malisafi_county',
                'value' => sanitize_text_field($filters['county']),
                'compare' => '=',
            );
            $has_meta_filters = true;
        }

        // Subcounty
        if (!empty($filters['subcounty'])) {
            $meta_query[] = array(
                'key' => '_malisafi_subcounty',
                'value' => sanitize_text_field($filters['subcounty']),
                'compare' => '=',
            );
            $has_meta_filters = true;
        }
        
        // Bedrooms
        if (!empty($filters['bedrooms'])) {
            $meta_query[] = array(
                'key' => '_malisafi_bedrooms',
                'value' => intval($filters['bedrooms']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            );
            $has_meta_filters = true;
        }
        
        // Bathrooms
        if (!empty($filters['bathrooms'])) {
            $meta_query[] = array(
                'key' => '_malisafi_bathrooms',
                'value' => intval($filters['bathrooms']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            );
            $has_meta_filters = true;
        }
        
        // Price range
        if (!empty($filters['price_min'])) {
            $meta_query[] = array(
                'key' => '_malisafi_price',
                'value' => floatval($filters['price_min']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            );
            $has_meta_filters = true;
        }
        
        if (!empty($filters['price_max'])) {
            $meta_query[] = array(
                'key' => '_malisafi_price',
                'value' => floatval($filters['price_max']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            );
            $has_meta_filters = true;
        }
        
        // Area range
        if (!empty($filters['area_min'])) {
            $meta_query[] = array(
                'key' => '_malisafi_area',
                'value' => floatval($filters['area_min']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            );
            $has_meta_filters = true;
        }
        
        if (!empty($filters['area_max'])) {
            $meta_query[] = array(
                'key' => '_malisafi_area',
                'value' => floatval($filters['area_max']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            );
            $has_meta_filters = true;
        }
        
        // Features
        if (!empty($filters['features']) && is_array($filters['features'])) {
            foreach ($filters['features'] as $feature) {
                $meta_query[] = array(
                    'key' => '_malisafi_features',
                    'value' => sanitize_text_field($feature),
                    'compare' => 'LIKE',
                );
                $has_meta_filters = true;
            }
        }
        
        if ($has_meta_filters) {
            $args['meta_query'] = $meta_query;
        }
        
        // Sorting
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = '_malisafi_price';
                    $args['order'] = 'ASC';
                    break;
                    
                case 'price_desc':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = '_malisafi_price';
                    $args['order'] = 'DESC';
                    break;
                    
                case 'area_asc':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = '_malisafi_area';
                    $args['order'] = 'ASC';
                    break;
                    
                case 'area_desc':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = '_malisafi_area';
                    $args['order'] = 'DESC';
                    break;
                    
                case 'date_asc':
                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                    break;
                    
                case 'title_asc':
                    $args['orderby'] = 'title';
                    $args['order'] = 'ASC';
                    break;
                    
                case 'title_desc':
                    $args['orderby'] = 'title';
                    $args['order'] = 'DESC';
                    break;
                    
                case 'date_desc':
                default:
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                    break;
            }
        }
        
        // Execute query
        $query = new \WP_Query($args);
        
        // Generate HTML
        ob_start();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                include(plugin_dir_path(__FILE__) . '../templates/property-card-modern.php');
            }
        }
        
        $html = ob_get_clean();
        wp_reset_postdata();
        
        // Generate pagination HTML
        $pagination_html = '';
        if ($query->max_num_pages > 1) {
            ob_start();
            
            $total_pages = $query->max_num_pages;
            
            // Previous button
            if ($page > 1) {
                echo '<button class="pagination-button" data-page="' . ($page - 1) . '">Previous</button>';
            } else {
                echo '<button class="pagination-button" data-page="1" disabled>Previous</button>';
            }
            
            // Page numbers
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $page) {
                    echo '<button class="pagination-button active" data-page="' . $i . '">' . $i . '</button>';
                } elseif ($i <= 2 || $i > $total_pages - 2 || abs($i - $page) <= 2) {
                    echo '<button class="pagination-button" data-page="' . $i . '">' . $i . '</button>';
                } elseif ($i == 3 && $page > 5) {
                    echo '<span class="pagination-dots">...</span>';
                } elseif ($i == $total_pages - 2 && $page < $total_pages - 4) {
                    echo '<span class="pagination-dots">...</span>';
                }
            }
            
            // Next button
            if ($page < $total_pages) {
                echo '<button class="pagination-button" data-page="' . ($page + 1) . '">Next</button>';
            } else {
                echo '<button class="pagination-button" data-page="' . $total_pages . '" disabled>Next</button>';
            }
            
            $pagination_html = ob_get_clean();
        }
        
        // Send response
        wp_send_json_success(array(
            'html' => $html,
            'total' => $query->found_posts,
            'max_pages' => $query->max_num_pages,
            'current_page' => $page,
            'pagination' => $pagination_html,
        ));
    }
}

// Initialize
Property_Filters_Ajax::get_instance();
