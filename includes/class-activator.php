<?php
/**
 * Plugin activation handler
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * Activator class
 */
class Activator {
    
    /**
     * Plugin activation logic
     */
    public static function activate() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            wp_die(__('This plugin requires WordPress version 5.0 or higher.', 'malisafi-mls'));
        }
        
        // Create custom database tables
        require_once MALISAFI_MLS_PATH . 'includes/class-database.php';
        Database::create_tables();
        
        // Create analytics tables
        require_once MALISAFI_MLS_PATH . 'includes/analytics/class-analytics-migration.php';
        Analytics\Analytics_Migration::create_all_tables();
        
        // Initialize custom roles and capabilities
        require_once MALISAFI_MLS_PATH . 'includes/class-role-manager.php';
        \Malisafi_Roles_Manager::create_roles();
        \Malisafi_Roles_Manager::init();
        
        // Register custom post types and taxonomies
        require_once MALISAFI_MLS_PATH . 'includes/class-post-types.php';
        $post_types = new Post_Types();
        $post_types->register_property_post_type();
        $post_types->register_taxonomies();
        
        // Create default taxonomy terms
        self::create_default_terms();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create required pages
        require_once MALISAFI_MLS_PATH . 'includes/class-page-manager.php';
        Page_Manager::create_all_pages();
        
        // Set default options
        self::set_default_options();
        
        // Set activation timestamp
        update_option('malisafi_mls_activated', time());
        
        // Set database version
        update_option('malisafi_mls_db_version', '1.0.0');
    }
    
    /**
     * Create default taxonomy terms
     */
    private static function create_default_terms() {
        // Default property types
        $property_types = array(
            'Apartment' => 'Modern apartments and flats',
            'House' => 'Single-family houses',
            'Villa' => 'Luxury villas',
            'Townhouse' => 'Townhouses and row houses',
            'Bungalow' => 'Single-story bungalows',
            'Mansion' => 'Large luxury mansions',
            'Land' => 'Vacant land and plots',
            'Commercial' => 'Commercial properties',
            'Office' => 'Office spaces',
            'Shop' => 'Retail shops',
            'Warehouse' => 'Warehouses and storage',
            'Farm' => 'Farms and agricultural land',
        );
        
        foreach ($property_types as $type => $description) {
            if (!term_exists($type, 'malisafi_property_type')) {
                wp_insert_term($type, 'malisafi_property_type', array(
                    'description' => $description,
                    'slug' => sanitize_title($type)
                ));
            }
        }
        
        // Default listing statuses
        $listing_statuses = array(
            'For Sale' => 'Property available for purchase',
            'For Rent' => 'Property available for long-term rental',
            'Short Term Rent' => 'Property available for short-term rental (Airbnb type)',
            'Sold' => 'Property has been sold',
            'Rented' => 'Property has been rented',
            'Off Market' => 'Property temporarily off market',
        );
        
        foreach ($listing_statuses as $status => $description) {
            if (!term_exists($status, 'malisafi_property_status')) {
                wp_insert_term($status, 'malisafi_property_status', array(
                    'description' => $description,
                    'slug' => sanitize_title($status)
                ));
            }
        }
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            'currency' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'price_decimals' => 0,
            'area_unit' => 'sqft',
            'properties_per_page' => 12,
            'enable_front_end_submission' => false,
            'google_maps_api_key' => '',
            'enable_favorite_properties' => true,
            'enable_property_comparison' => true,
            'enable_agent_profiles' => true,
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option('malisafi_mls_' . $key) === false) {
                update_option('malisafi_mls_' . $key, $value);
            }
        }
    }
}
