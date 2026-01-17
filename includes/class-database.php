<?php
/**
 * Database handler for Malisafi MLS
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * Database class
 */
class Database {
    
    /**
     * Create all custom database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Core User Roles and Subscriptions
        self::create_subscriptions_table($charset_collate);
        self::create_user_limits_table($charset_collate);
        
        // Properties Master Table
        self::create_properties_table($charset_collate);
        
        // Property Related Tables
        self::create_property_amenities_table($charset_collate);
        self::create_property_media_table($charset_collate);
        
        // Inquiries and Client Interactions
        self::create_inquiries_table($charset_collate);
        
        // Saved Searches and Favorites
        self::create_saved_searches_table($charset_collate);
        self::create_favorites_table($charset_collate);
        
        // Moderation Queue
        self::create_moderation_queue_table($charset_collate);
        
        // Property Reports
        self::create_property_reports_table($charset_collate);
        
        // Agent Ratings and Reports
        self::create_agent_ratings_table($charset_collate);
        
        // Analytics Tables
        self::create_analytics_tables($charset_collate);
        self::create_agent_reports_table($charset_collate);
        
        // Analytics and Tracking
        self::create_analytics_table($charset_collate);
    }
    
    /**
     * Create subscriptions table
     */
    private static function create_subscriptions_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_subscriptions';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            plan_type ENUM('agent_basic', 'agent_premium', 'owner_basic', 'developer') NOT NULL,
            status ENUM('active', 'canceled', 'expired', 'pending') DEFAULT 'pending',
            stripe_subscription_id VARCHAR(255),
            current_period_start DATETIME,
            current_period_end DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create user limits table
     */
    private static function create_user_limits_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_user_limits';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            max_listings INT DEFAULT 0,
            used_listings INT DEFAULT 0,
            featured_listings INT DEFAULT 0,
            can_boost BOOLEAN DEFAULT FALSE,
            analytics_access BOOLEAN DEFAULT FALSE,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create properties master table
     */
    private static function create_properties_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_properties';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            property_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            author_id BIGINT UNSIGNED NOT NULL,
            property_type ENUM('residential', 'commercial', 'mixed-use', 'development') NOT NULL,
            transaction_type ENUM('sale', 'rent', 'lease') NOT NULL,
            price DECIMAL(15,2),
            price_currency VARCHAR(3) DEFAULT 'USD',
            status ENUM('draft', 'pending_review', 'published', 'rejected', 'sold', 'rented') DEFAULT 'draft',
            
            full_address TEXT,
            display_address TEXT,
            latitude DECIMAL(10,8),
            longitude DECIMAL(11,8),
            privacy_level ENUM('full', 'approximate', 'area_only') DEFAULT 'full',
            
            bedrooms INT,
            bathrooms DECIMAL(3,1),
            area_sqft INT,
            area_sqm INT,
            
            featured BOOLEAN DEFAULT FALSE,
            views_count INT DEFAULT 0,
            inquiries_count INT DEFAULT 0,
            last_viewed TIMESTAMP NULL,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            KEY author_id (author_id),
            KEY post_id (post_id),
            KEY idx_location (latitude, longitude),
            KEY idx_price (price),
            KEY idx_status (status),
            KEY idx_author (author_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create property amenities table
     */
    private static function create_property_amenities_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_property_amenities';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            property_id BIGINT UNSIGNED NOT NULL,
            amenity_type VARCHAR(50) NOT NULL,
            amenity_value VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY property_id (property_id),
            KEY idx_amenity_type (amenity_type)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create property media table
     */
    private static function create_property_media_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_property_media';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            media_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            property_id BIGINT UNSIGNED NOT NULL,
            media_type ENUM('image', 'virtual_tour', 'video', 'floor_plan', 'document') NOT NULL,
            media_url TEXT NOT NULL,
            thumbnail_url TEXT,
            display_order INT DEFAULT 0,
            metadata TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY property_id (property_id),
            KEY idx_property_media (property_id, media_type)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create inquiries table
     */
    private static function create_inquiries_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_inquiries';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            inquiry_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            property_id BIGINT UNSIGNED NOT NULL,
            client_id BIGINT UNSIGNED NOT NULL,
            agent_id BIGINT UNSIGNED NOT NULL,
            inquiry_type ENUM('general', 'tour_request', 'price_negotiation') DEFAULT 'general',
            message TEXT,
            status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
            client_phone VARCHAR(20),
            client_email VARCHAR(255),
            preferred_contact_time ENUM('morning', 'afternoon', 'evening', 'anytime'),
            tour_requested_date DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            KEY property_id (property_id),
            KEY client_id (client_id),
            KEY agent_id (agent_id),
            KEY idx_agent_inquiries (agent_id, status),
            KEY idx_property_inquiries (property_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create saved searches table
     */
    private static function create_saved_searches_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_saved_searches';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            search_name VARCHAR(255),
            search_parameters TEXT NOT NULL,
            notification_frequency ENUM('instant', 'daily', 'weekly', 'none') DEFAULT 'none',
            last_notified TIMESTAMP NULL,
            match_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create favorites table
     */
    private static function create_favorites_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_favorites';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            property_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY user_id (user_id),
            KEY property_id (property_id),
            UNIQUE KEY unique_favorite (user_id, property_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create moderation queue table
     */
    private static function create_moderation_queue_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_moderation_queue';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            property_id BIGINT UNSIGNED NOT NULL,
            moderator_id BIGINT UNSIGNED,
            action ENUM('publish', 'reject', 'request_changes') NOT NULL,
            notes TEXT,
            moderated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY property_id (property_id),
            KEY moderator_id (moderator_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create property reports table
     */
    private static function create_property_reports_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_property_reports';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            property_id BIGINT UNSIGNED NOT NULL,
            reporter_id BIGINT UNSIGNED NOT NULL,
            reason VARCHAR(50) NOT NULL,
            details TEXT,
            status ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY property_id (property_id),
            KEY reporter_id (reporter_id),
            KEY status (status),
            KEY idx_property_reports (property_id, status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create analytics table
     */
    private static function create_analytics_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_analytics';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            property_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED,
            action ENUM('view', 'inquiry', 'favorite', 'share') NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            session_id VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY property_id (property_id),
            KEY user_id (user_id),
            KEY idx_property_actions (property_id, action),
            KEY idx_analytics_date (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Drop all custom tables
     * 
     * WARNING: This is a destructive operation that will delete ALL plugin data.
     * This should ONLY be called during plugin uninstallation, never during deactivation.
     * 
     * @param bool $confirm Required confirmation flag to prevent accidental data loss
     * @return bool|WP_Error True on success, WP_Error on failure or missing confirmation
     */
    public static function drop_tables($confirm = false) {
        // CRITICAL: Require explicit confirmation to prevent accidental data loss
        if ($confirm !== true || !defined('WP_UNINSTALL_PLUGIN')) {
            return new \WP_Error(
                'drop_tables_forbidden',
                __('Table deletion requires explicit confirmation and can only be executed during plugin uninstall.', 'malisafi-mls')
            );
        }
        
        global $wpdb;
        
        // Log this critical operation
        error_log('MALISAFI MLS: Dropping all database tables - this action cannot be undone');
        
        $tables = array(
            'mf_agent_reports',
            'mf_agent_ratings',
            'mf_analytics',
            'mf_property_reports',
            'mf_moderation_queue',
            'mf_favorites',
            'mf_saved_searches',
            'mf_inquiries',
            'mf_property_media',
            'mf_property_amenities',
            'mf_properties',
            'mf_user_limits',
            'mf_subscriptions'
        );
        
        // Drop tables in reverse order to handle dependencies
        foreach ($tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $result = $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %i", $full_table_name));
            
            if ($result === false) {
                error_log("MALISAFI MLS: Failed to drop table {$full_table_name}");
            }
        }
        
        return true;
    }
    
    /**
     * Get table names
     */
    public static function get_table_names() {
        global $wpdb;
        
        return array(
            'subscriptions' => $wpdb->prefix . 'mf_subscriptions',
            'user_limits' => $wpdb->prefix . 'mf_user_limits',
            'properties' => $wpdb->prefix . 'mf_properties',
            'property_amenities' => $wpdb->prefix . 'mf_property_amenities',
            'property_media' => $wpdb->prefix . 'mf_property_media',
            'inquiries' => $wpdb->prefix . 'mf_inquiries',
            'saved_searches' => $wpdb->prefix . 'mf_saved_searches',
            'favorites' => $wpdb->prefix . 'mf_favorites',
            'moderation_queue' => $wpdb->prefix . 'mf_moderation_queue',
            'property_reports' => $wpdb->prefix . 'mf_property_reports',
            'agent_ratings' => $wpdb->prefix . 'mf_agent_ratings',
            'agent_reports' => $wpdb->prefix . 'mf_agent_reports',
            'analytics' => $wpdb->prefix . 'mf_analytics'
        );
    }
    
    /**
     * Create agent ratings table
     */
    private static function create_agent_ratings_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_agent_ratings';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            agent_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review_title VARCHAR(255),
            review_text TEXT,
            property_id BIGINT UNSIGNED NULL,
            verified_client BOOLEAN DEFAULT FALSE,
            helpful_count INT DEFAULT 0,
            not_helpful_count INT DEFAULT 0,
            agent_response TEXT NULL,
            agent_responded_at DATETIME NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY agent_id (agent_id),
            KEY user_id (user_id),
            KEY idx_agent_rating (agent_id, rating),
            KEY idx_status (status),
            UNIQUE KEY unique_user_agent (user_id, agent_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create agent reports table
     */
    private static function create_agent_reports_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mf_agent_reports';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            agent_id BIGINT UNSIGNED NOT NULL,
            reported_by BIGINT UNSIGNED NOT NULL,
            report_type ENUM('spam', 'inappropriate', 'fraud', 'harassment', 'fake_info', 'other') NOT NULL,
            report_reason TEXT NOT NULL,
            evidence_urls TEXT NULL,
            status ENUM('pending', 'under_review', 'resolved', 'dismissed') DEFAULT 'pending',
            admin_notes TEXT NULL,
            reviewed_by BIGINT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            KEY agent_id (agent_id),
            KEY reported_by (reported_by),
            KEY idx_status (status),
            KEY idx_agent_reports (agent_id, status)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Check if table exists
     */
    public static function table_exists($table_name) {
        global $wpdb;
        $table = $wpdb->prefix . $table_name;
        return $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }
    
    /**
     * Update database schema - creates missing tables
     */
    public static function update_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Check and create property_reports table if missing
        if (!self::table_exists('mf_property_reports')) {
            self::create_property_reports_table($charset_collate);
        }
        
        // Check and create other tables if needed
        $tables = array(
            'mf_subscriptions' => 'create_subscriptions_table',
            'mf_user_limits' => 'create_user_limits_table',
            'mf_properties' => 'create_properties_table',
            'mf_property_amenities' => 'create_property_amenities_table',
            'mf_property_media' => 'create_property_media_table',
            'mf_inquiries' => 'create_inquiries_table',
            'mf_saved_searches' => 'create_saved_searches_table',
            'mf_favorites' => 'create_favorites_table',
            'mf_moderation_queue' => 'create_moderation_queue_table',
            'mf_analytics' => 'create_analytics_table',
            // New analytics tables
            'mf_user_activity' => 'create_analytics_tables',
            'mf_property_views' => 'create_analytics_tables',
            'mf_property_interactions' => 'create_analytics_tables',
            'mf_search_analytics' => 'create_analytics_tables',
            'mf_submission_funnel' => 'create_analytics_tables',
            'mf_fraud_detection' => 'create_analytics_tables',
            'mf_revenue_tracking' => 'create_analytics_tables',
            'mf_system_health' => 'create_analytics_tables'
        );
        
        $analytics_created = false;
        foreach ($tables as $table => $method) {
            if (!self::table_exists($table)) {
                // For analytics tables, call once
                if ($method === 'create_analytics_tables' && !$analytics_created) {
                    self::$method($charset_collate);
                    $analytics_created = true;
                } elseif ($method !== 'create_analytics_tables') {
                    self::$method($charset_collate);
                }
            }
        }
    }
    
    /**
     * Create all analytics tables
     */
    private static function create_analytics_tables($charset_collate) {
        // Load Analytics_Database class
        require_once MALISAFI_MLS_PATH . 'includes/analytics/class-analytics-database.php';
        
        // Create all analytics tables
        \MalisafiMLS\Analytics\Analytics_Database::create_tables();
    }
}
