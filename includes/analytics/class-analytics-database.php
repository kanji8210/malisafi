<?php
/**
 * Analytics Database Management
 *
 * Handles creation and management of analytics tables
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.0
 */

namespace MalisafiMLS\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

class Analytics_Database {

    /**
     * Create all analytics tables
     */
    public static function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        self::create_user_activity_table($charset_collate);
        self::create_property_views_table($charset_collate);
        self::create_property_interactions_table($charset_collate);
        self::create_search_analytics_table($charset_collate);
        self::create_submission_funnel_table($charset_collate);
        self::create_fraud_detection_table($charset_collate);
        self::create_fraud_reports_table($charset_collate);
        self::create_revenue_tracking_table($charset_collate);
        self::create_system_health_table($charset_collate);
        
        // Update version
        update_option('malisafi_analytics_db_version', '1.0.1');
    }

    /**
     * Create user activity table
     */
    private static function create_user_activity_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_user_activity';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            activity_type ENUM(
                'login', 'logout', 'dashboard_visit', 
                'property_add_start', 'property_add_complete', 'property_edit',
                'property_delete', 'profile_edit', 'search', 'filter_use'
            ) NOT NULL,
            activity_data JSON,
            page_url VARCHAR(500),
            referrer VARCHAR(500),
            time_spent INT UNSIGNED DEFAULT 0,
            session_id VARCHAR(255),
            ip_address VARCHAR(45),
            user_agent TEXT,
            device_type ENUM('mobile', 'tablet', 'desktop') DEFAULT 'desktop',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            KEY idx_user (user_id),
            KEY idx_activity (activity_type),
            KEY idx_session (session_id),
            KEY idx_date (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Create property views table
     */
    private static function create_property_views_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_property_views';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            property_id BIGINT UNSIGNED NOT NULL,
            post_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED,
            session_id VARCHAR(255) NOT NULL,
            view_type ENUM('list', 'grid', 'single', 'featured', 'search_result') NOT NULL,
            view_duration INT UNSIGNED DEFAULT 0,
            scroll_depth TINYINT UNSIGNED DEFAULT 0,
            gallery_viewed BOOLEAN DEFAULT FALSE,
            map_viewed BOOLEAN DEFAULT FALSE,
            contact_clicked BOOLEAN DEFAULT FALSE,
            source VARCHAR(100),
            referrer VARCHAR(500),
            device_type ENUM('mobile', 'tablet', 'desktop') DEFAULT 'desktop',
            ip_address VARCHAR(45),
            geo_location JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            KEY idx_property (property_id),
            KEY idx_post (post_id),
            KEY idx_user (user_id),
            KEY idx_session (session_id),
            KEY idx_date (created_at),
            KEY idx_source (source)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Create property interactions table
     */
    private static function create_property_interactions_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_property_interactions';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            property_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED,
            interaction_type ENUM(
                'favorite', 'unfavorite', 'share_email', 'share_social',
                'inquiry', 'phone_click', 'email_click', 'whatsapp_click',
                'virtual_tour', 'download_brochure', 'schedule_visit'
            ) NOT NULL,
            interaction_data JSON,
            session_id VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            KEY idx_property (property_id),
            KEY idx_user (user_id),
            KEY idx_type (interaction_type),
            KEY idx_date (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Create search analytics table
     */
    private static function create_search_analytics_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_search_analytics';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED,
            session_id VARCHAR(255) NOT NULL,
            search_type ENUM('keyword', 'filter', 'advanced', 'saved') DEFAULT 'keyword',
            search_query TEXT,
            filters_used JSON,
            results_count INT UNSIGNED DEFAULT 0,
            first_result_clicked INT,
            time_to_click INT UNSIGNED,
            results_viewed INT UNSIGNED DEFAULT 0,
            zero_results BOOLEAN DEFAULT FALSE,
            device_type ENUM('mobile', 'tablet', 'desktop') DEFAULT 'desktop',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            KEY idx_user (user_id),
            KEY idx_session (session_id),
            KEY idx_zero_results (zero_results),
            KEY idx_date (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Create submission funnel table
     */
    private static function create_submission_funnel_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_submission_funnel';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            step_name ENUM(
                'form_loaded', 'basic_info', 'pricing', 'details',
                'location', 'amenities', 'images', 'submit_attempt',
                'submit_success', 'submit_error'
            ) NOT NULL,
            step_data JSON,
            time_spent INT UNSIGNED DEFAULT 0,
            property_id BIGINT UNSIGNED,
            error_message TEXT,
            completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            KEY idx_user (user_id),
            KEY idx_session (session_id),
            KEY idx_step (step_name),
            KEY idx_completed (completed),
            KEY idx_date (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Create fraud detection table
     */
    private static function create_fraud_detection_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_fraud_detection';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED,
            property_id BIGINT UNSIGNED,
            fraud_type ENUM(
                'duplicate_listing', 'rapid_edits', 'suspicious_ip',
                'fake_images', 'price_manipulation', 'spam_content',
                'multiple_accounts', 'stolen_content'
            ) NOT NULL,
            confidence_score TINYINT UNSIGNED DEFAULT 0,
            detection_data JSON,
            status ENUM('pending', 'reviewed', 'confirmed', 'false_positive') DEFAULT 'pending',
            reviewed_by BIGINT UNSIGNED,
            reviewed_at TIMESTAMP NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            KEY idx_user (user_id),
            KEY idx_property (property_id),
            KEY idx_type (fraud_type),
            KEY idx_status (status),
            KEY idx_score (confidence_score)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Create fraud reports table (user-submitted reports)
     */
    private static function create_fraud_reports_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_fraud_reports';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            reporter_email VARCHAR(255),
            reporter_user_id BIGINT UNSIGNED,
            report_type ENUM(
                'fake_listing', 'duplicate_property', 'misleading_info',
                'fake_agent', 'price_scam', 'fake_photos', 'contact_fraud',
                'identity_theft', 'spam', 'other'
            ) NOT NULL,
            agent_id BIGINT UNSIGNED,
            property_id BIGINT UNSIGNED,
            reason VARCHAR(500) NOT NULL,
            details TEXT,
            status ENUM('new', 'under_review', 'resolved', 'dismissed') DEFAULT 'new',
            reviewed_by BIGINT UNSIGNED,
            reviewed_at TIMESTAMP NULL,
            admin_notes TEXT,
            created_suspicion_id BIGINT UNSIGNED,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            KEY idx_reporter_email (reporter_email),
            KEY idx_reporter_user (reporter_user_id),
            KEY idx_type (report_type),
            KEY idx_agent (agent_id),
            KEY idx_property (property_id),
            KEY idx_status (status),
            KEY idx_date (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Create revenue tracking table
     */
    private static function create_revenue_tracking_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_revenue_tracking';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            transaction_type ENUM(
                'subscription', 'featured_listing', 'boost',
                'premium_upgrade', 'additional_listings', 'refund'
            ) NOT NULL,
            plan_type VARCHAR(50),
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'KES',
            stripe_payment_id VARCHAR(255),
            stripe_invoice_id VARCHAR(255),
            status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            
            KEY idx_user (user_id),
            KEY idx_type (transaction_type),
            KEY idx_status (status),
            KEY idx_amount (amount),
            KEY idx_date (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Create system health table
     */
    private static function create_system_health_table($charset_collate) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_system_health';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            metric_type ENUM(
                'api_response_time', 'image_upload', 'cdn_delivery',
                'database_query', 'page_load', 'error_rate',
                'memory_usage', 'disk_space'
            ) NOT NULL,
            metric_value DECIMAL(10,2) DEFAULT 0,
            metric_unit VARCHAR(20),
            endpoint VARCHAR(255),
            status ENUM('ok', 'warning', 'critical') DEFAULT 'ok',
            error_details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            KEY idx_type (metric_type),
            KEY idx_status (status),
            KEY idx_date (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Drop all analytics tables (for development/testing)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = [
            'mf_user_activity',
            'mf_property_views',
            'mf_property_interactions',
            'mf_search_analytics',
            'mf_submission_funnel',
            'mf_fraud_detection',
            'mf_revenue_tracking',
            'mf_system_health'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
        
        delete_option('malisafi_analytics_db_version');
    }
}
