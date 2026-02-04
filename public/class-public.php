<?php
/**
 * Public-facing functionality
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS\PublicArea;

use MalisafiMLS\Property_Manager;

/**
 * PublicArea class
 */
class PublicArea {
    
    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        // Enqueue dashicons globally for property cards
        wp_enqueue_style('dashicons');
        
        // Enqueue global variables first
        wp_enqueue_style(
            'malisafi-mls-variables',
            MALISAFI_MLS_URL . 'assets/css/variables.css',
            array(),
            MALISAFI_MLS_VERSION,
            'all'
        );
        
        wp_enqueue_style(
            'malisafi-mls-public',
            MALISAFI_MLS_URL . 'assets/css/public.css',
            array('malisafi-mls-variables', 'dashicons'),
            MALISAFI_MLS_VERSION,
            'all'
        );
        
        // Enqueue dashboard styles
        wp_enqueue_style(
            'malisafi-mls-dashboards',
            MALISAFI_MLS_URL . 'assets/css/dashboards.css',
            array('malisafi-mls-variables'),
            MALISAFI_MLS_VERSION,
            'all'
        );
        
        // Enqueue featured properties styles
        wp_enqueue_style(
            'malisafi-mls-featured',
            MALISAFI_MLS_URL . 'assets/css/featured-properties.css',
            array('malisafi-mls-variables'),
            MALISAFI_MLS_VERSION,
            'all'
        );
        
        // Enqueue unified property grids styles (must load early for all grids)
        wp_enqueue_style(
            'malisafi-mls-property-grids-unified',
            MALISAFI_MLS_URL . 'assets/css/property-grids-unified.css',
            array('malisafi-mls-variables'),
            MALISAFI_MLS_VERSION,
            'all'
        );
        
        // Enqueue property card modern styles
        wp_enqueue_style(
            'malisafi-mls-property-card-modern',
            MALISAFI_MLS_URL . 'assets/css/property-card-modern.css',
            array('malisafi-mls-variables', 'malisafi-mls-property-grids-unified'),
            MALISAFI_MLS_VERSION,
            'all'
        );
        
        // Enqueue property filters modern styles
        wp_enqueue_style(
            'malisafi-mls-property-filters-modern',
            MALISAFI_MLS_URL . 'assets/css/property-filters-modern.css',
            array('malisafi-mls-variables', 'malisafi-mls-property-grids-unified'),
            MALISAFI_MLS_VERSION,
            'all'
        );
        
        // Enqueue moderation styles on single property pages
        if (is_singular('malisafi_property')) {
            wp_enqueue_style('dashicons');
            wp_enqueue_style(
                'malisafi-mls-moderation',
                MALISAFI_MLS_URL . 'public/css/property-moderation.css',
                array(),
                MALISAFI_MLS_VERSION,
                'all'
            );
        }
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'malisafi-mls-public',
            MALISAFI_MLS_URL . 'assets/js/public.js',
            array('jquery'),
            MALISAFI_MLS_VERSION,
            false
        );
        
        wp_localize_script('malisafi-mls-public', 'malisafiMLS', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_mls_public_nonce'),
        ));
        
        // Also create alias for backward compatibility
        wp_localize_script('malisafi-mls-public', 'malisafiAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_mls_public_nonce'),
        ));
        
        // Enqueue moderation scripts on single property pages
        if (is_singular('malisafi_property') && class_exists('Malisafi_Property_Moderation')) {
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
        
        // Enqueue Google Maps if API key is set
        $maps_key = get_option('malisafi_mls_google_maps_api_key');
        if (!empty($maps_key)) {
            wp_enqueue_script(
                'google-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . $maps_key,
                array(),
                null,
                false
            );
        }
    }
    
    /**
     * Properties listing shortcode
     */
    public function properties_shortcode($atts) {
        if (class_exists('Malisafi_Shortcodes') && method_exists('Malisafi_Shortcodes', 'properties_with_filters')) {
            return Malisafi_Shortcodes::properties_with_filters($atts);
        }

        $atts = shortcode_atts(array(
            'type' => '',
            'status' => '',
            'location' => '',
            'featured' => '',
            'count' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
        ), $atts);

        $args = array(
            'posts_per_page' => intval($atts['count']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        );

        if (!empty($atts['type'])) {
            $args['property_type'] = $atts['type'];
        }

        if (!empty($atts['status'])) {
            $args['property_status'] = $atts['status'];
        }

        if (!empty($atts['location'])) {
            $args['location'] = $atts['location'];
        }

        if (!empty($atts['featured'])) {
            $args['featured'] = true;
        }

        $properties = Property_Manager::get_properties($args);

        ob_start();
        include MALISAFI_MLS_PATH . 'templates/properties-grid.php';
        return ob_get_clean();
    }
    
    /**
     * Property search form shortcode
     */
    public function search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'minimalist',
        ), $atts);

        wp_enqueue_style(
            'malisafi-search-form-minimalist',
            MALISAFI_MLS_URL . 'assets/css/search-form-minimalist.css',
            array('malisafi-mls-variables'),
            MALISAFI_MLS_VERSION
        );
        
        ob_start();
        include MALISAFI_MLS_PATH . 'templates/search-form.php';
        return ob_get_clean();
    }
    
    /**
     * Featured properties shortcode
     * 
     * Usage: [malisafi_featured_properties count="6" columns="3" orderby="date" order="DESC"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function featured_properties_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 6,           // Number of properties to display
            'columns' => 3,          // Number of columns (1-4)
            'rows' => 0,             // Number of rows (0 = auto based on count)
            'orderby' => 'date',     // date, title, rand, price
            'order' => 'DESC',       // DESC (descending/newest first) or ASC (ascending/oldest first)
            'show_excerpt' => 'yes', // Show property excerpt
            'show_features' => 'yes' // Show property features
        ), $atts);
        
        // Sanitize attributes
        $count = intval($atts['count']);
        $columns = max(1, min(4, intval($atts['columns'])));
        $rows = intval($atts['rows']);
        $orderby = sanitize_text_field($atts['orderby']);
        $order = strtoupper(sanitize_text_field($atts['order'])) === 'ASC' ? 'ASC' : 'DESC';
        
        // Build query args
        $query_args = array(
            'posts_per_page' => $count,
            'order' => $order
        );
        
        // Handle orderby
        switch ($orderby) {
            case 'price':
                $query_args['meta_key'] = '_malisafi_price';
                $query_args['orderby'] = 'meta_value_num';
                break;
            case 'title':
                $query_args['orderby'] = 'title';
                break;
            case 'rand':
            case 'random':
                $query_args['orderby'] = 'rand';
                break;
            default:
                $query_args['orderby'] = 'date';
                break;
        }
        
        $properties = Property_Manager::get_featured_properties($count, $query_args);
        
        // Pass shortcode attributes to template
        $shortcode_atts = $atts;
        
        ob_start();
        include MALISAFI_MLS_PATH . 'templates/featured-properties.php';
        return ob_get_clean();
    }
}
