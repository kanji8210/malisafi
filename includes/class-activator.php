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
        
        // Initialize default subscription plans
        self::initialize_default_plans();
        
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
     * Initialize default subscription plans based on roles
     */
    public static function initialize_default_plans() {
        // Check if plans already exist
        $existing_plans = get_option('malisafi_mls_plans', false);
        if ($existing_plans && is_array($existing_plans) && !empty($existing_plans)) {
            // Plans already exist, don't overwrite
            return;
        }
        
        // Get default currency
        $default_currency = get_option('malisafi_mls_currency', 'USD');
        
        // Define role-based plans with adjustable settings
        $default_plans = array(
            'client' => array(
                'name' => __('Client (Free)', 'malisafi-mls'),
                'price' => 0.00,
                'currency' => $default_currency,
                'interval' => 'month',
                'max_listings' => 0,
                'featured_listings' => 0,
                'can_boost' => 0,
                'analytics_access' => 0,
                'features' => array(
                    __('Browse properties', 'malisafi-mls'),
                    __('Save favorites', 'malisafi-mls'),
                    __('Contact agents', 'malisafi-mls'),
                ),
                'stripe_price_id' => ''
            ),
            'agent_basic' => array(
                'name' => __('Agent Basic', 'malisafi-mls'),
                'price' => 29.99,
                'currency' => $default_currency,
                'interval' => 'month',
                'max_listings' => 10,
                'featured_listings' => 0,
                'can_boost' => 0,
                'analytics_access' => 1,
                'features' => array(
                    __('Up to 10 property listings', 'malisafi-mls'),
                    __('Basic analytics', 'malisafi-mls'),
                    __('Email support', 'malisafi-mls'),
                    __('Agent profile page', 'malisafi-mls'),
                ),
                'stripe_price_id' => ''
            ),
            'agent_premium' => array(
                'name' => __('Agent Premium', 'malisafi-mls'),
                'price' => 99.99,
                'currency' => $default_currency,
                'interval' => 'month',
                'max_listings' => -1, // -1 = unlimited
                'featured_listings' => 5,
                'can_boost' => 1,
                'analytics_access' => 1,
                'features' => array(
                    __('Unlimited property listings', 'malisafi-mls'),
                    __('5 featured listings per month', 'malisafi-mls'),
                    __('Boost properties', 'malisafi-mls'),
                    __('Advanced analytics', 'malisafi-mls'),
                    __('Priority support', 'malisafi-mls'),
                    __('Agent profile page', 'malisafi-mls'),
                ),
                'stripe_price_id' => ''
            ),
            'owner_basic' => array(
                'name' => __('Property Owner', 'malisafi-mls'),
                'price' => 19.99,
                'currency' => $default_currency,
                'interval' => 'month',
                'max_listings' => 3,
                'featured_listings' => 0,
                'can_boost' => 0,
                'analytics_access' => 1,
                'features' => array(
                    __('Up to 3 property listings', 'malisafi-mls'),
                    __('Basic analytics', 'malisafi-mls'),
                    __('Email support', 'malisafi-mls'),
                    __('Direct property management', 'malisafi-mls'),
                ),
                'stripe_price_id' => ''
            ),
            'developer' => array(
                'name' => __('Developer', 'malisafi-mls'),
                'price' => 199.99,
                'currency' => $default_currency,
                'interval' => 'month',
                'max_listings' => -1, // -1 = unlimited
                'featured_listings' => 10,
                'can_boost' => 1,
                'analytics_access' => 1,
                'features' => array(
                    __('Unlimited projects and properties', 'malisafi-mls'),
                    __('10 featured listings per month', 'malisafi-mls'),
                    __('Bulk import/export', 'malisafi-mls'),
                    __('Advanced analytics and reporting', 'malisafi-mls'),
                    __('Dedicated support', 'malisafi-mls'),
                    __('API access', 'malisafi-mls'),
                    __('Custom branding options', 'malisafi-mls'),
                ),
                'stripe_price_id' => ''
            ),
            'agency' => array(
                'name' => __('Real Estate Agency', 'malisafi-mls'),
                'price' => 299.99,
                'currency' => $default_currency,
                'interval' => 'month',
                'max_listings' => -1, // -1 = unlimited
                'featured_listings' => 20,
                'can_boost' => 1,
                'analytics_access' => 1,
                'features' => array(
                    __('Unlimited properties', 'malisafi-mls'),
                    __('20 featured listings per month', 'malisafi-mls'),
                    __('Manage multiple agents', 'malisafi-mls'),
                    __('Agency profile page', 'malisafi-mls'),
                    __('Bulk operations', 'malisafi-mls'),
                    __('Advanced analytics', 'malisafi-mls'),
                    __('Priority support', 'malisafi-mls'),
                    __('Custom branding', 'malisafi-mls'),
                ),
                'stripe_price_id' => ''
            ),
        );
        
        // Save the default plans
        update_option('malisafi_mls_plans', $default_plans);
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
