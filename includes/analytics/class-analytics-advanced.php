<?php
/**
 * Analytics Advanced Features
 *
 * Fraud detection, revenue tracking, system health monitoring
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.0
 */

namespace MalisafiMLS\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

class Analytics_Advanced {

    /**
     * Detect duplicate listings
     */
    public static function detect_duplicate_listings() {
        global $wpdb;
        
        $sql = "
            SELECT 
                p1.property_id as property_1,
                p2.property_id as property_2,
                p1.author_id,
                p1.full_address,
                p1.price,
                p1.bedrooms,
                p1.bathrooms,
                'duplicate_listing' as fraud_type,
                CASE
                    WHEN p1.latitude = p2.latitude AND p1.longitude = p2.longitude THEN 90
                    WHEN SOUNDEX(p1.full_address) = SOUNDEX(p2.full_address) THEN 75
                    ELSE 60
                END as confidence_score
            FROM {$wpdb->prefix}mf_properties p1
            INNER JOIN {$wpdb->prefix}mf_properties p2 
                ON p1.property_id < p2.property_id
                AND (
                    (p1.latitude = p2.latitude AND p1.longitude = p2.longitude)
                    OR SOUNDEX(p1.full_address) = SOUNDEX(p2.full_address)
                )
                AND p1.bedrooms = p2.bedrooms
                AND p1.bathrooms = p2.bathrooms
                AND ABS(p1.price - p2.price) < (p1.price * 0.10)
            WHERE p1.status != 'rejected'
            AND p2.status != 'rejected'
        ";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Detect rapid edits (suspicious activity)
     */
    public static function detect_rapid_edits($threshold = 5, $minutes = 10) {
        global $wpdb;
        
        $sql = "
            SELECT 
                user_id,
                COUNT(DISTINCT JSON_EXTRACT(activity_data, '$.post_id')) as properties_edited,
                MIN(created_at) as first_edit,
                MAX(created_at) as last_edit,
                TIMESTAMPDIFF(MINUTE, MIN(created_at), MAX(created_at)) as time_span_minutes
            FROM {$wpdb->prefix}mf_user_activity
            WHERE activity_type = 'property_edit'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY user_id
            HAVING properties_edited >= %d
            AND time_span_minutes <= %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $threshold, $minutes));
    }

    /**
     * Detect suspicious IP activity
     */
    public static function detect_suspicious_ips($days = 7) {
        global $wpdb;
        
        $sql = "
            SELECT 
                ip_address,
                COUNT(DISTINCT user_id) as user_count,
                COUNT(DISTINCT session_id) as session_count,
                COUNT(*) as total_actions
            FROM {$wpdb->prefix}mf_user_activity
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND ip_address IS NOT NULL
            GROUP BY ip_address
            HAVING user_count > 5 OR session_count > 10
            ORDER BY user_count DESC, session_count DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }

    /**
     * Log fraud detection
     */
    public static function log_fraud_detection($user_id, $property_id, $fraud_type, $confidence_score, $data = []) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'mf_fraud_detection',
            [
                'user_id' => $user_id,
                'property_id' => $property_id,
                'fraud_type' => $fraud_type,
                'confidence_score' => $confidence_score,
                'detection_data' => json_encode($data),
                'status' => 'pending'
            ],
            ['%d', '%d', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Get revenue metrics
     */
    public static function get_revenue_metrics($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                DATE(created_at) as date,
                transaction_type,
                plan_type,
                COUNT(*) as transactions,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue,
                SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) as refunds,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failures
            FROM {$wpdb->prefix}mf_revenue_tracking
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(created_at), transaction_type, plan_type
            ORDER BY date DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }

    /**
     * Get revenue summary
     */
    public static function get_revenue_summary($days = 30) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) as total_refunds,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_transactions,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
                ROUND(AVG(CASE WHEN status = 'completed' THEN amount END), 2) as avg_transaction_value
            FROM {$wpdb->prefix}mf_revenue_tracking
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
    }

    /**
     * Get subscription analytics
     */
    public static function get_subscription_analytics() {
        global $wpdb;
        
        $sql = "
            SELECT 
                plan_type,
                COUNT(*) as subscriptions,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue
            FROM {$wpdb->prefix}mf_revenue_tracking
            WHERE transaction_type = 'subscription'
            GROUP BY plan_type
            ORDER BY revenue DESC
        ";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Get system health metrics
     */
    public static function get_system_health($hours = 24) {
        global $wpdb;
        
        $sql = "
            SELECT 
                metric_type,
                AVG(metric_value) as avg_value,
                MAX(metric_value) as max_value,
                MIN(metric_value) as min_value,
                metric_unit,
                SUM(CASE WHEN status = 'critical' THEN 1 ELSE 0 END) as critical_count,
                SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning_count
            FROM {$wpdb->prefix}mf_system_health
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
            GROUP BY metric_type, metric_unit
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $hours));
    }

    /**
     * Log system metric
     */
    public static function log_system_metric($metric_type, $value, $unit, $endpoint = null) {
        global $wpdb;
        
        // Determine status
        $status = 'ok';
        $thresholds = [
            'api_response_time' => ['warning' => 1000, 'critical' => 3000], // ms
            'page_load' => ['warning' => 2000, 'critical' => 5000], // ms
            'memory_usage' => ['warning' => 80, 'critical' => 95], // %
            'disk_space' => ['warning' => 80, 'critical' => 95], // %
            'error_rate' => ['warning' => 5, 'critical' => 10] // %
        ];
        
        if (isset($thresholds[$metric_type])) {
            if ($value >= $thresholds[$metric_type]['critical']) {
                $status = 'critical';
            } elseif ($value >= $thresholds[$metric_type]['warning']) {
                $status = 'warning';
            }
        }
        
        return $wpdb->insert(
            $wpdb->prefix . 'mf_system_health',
            [
                'metric_type' => $metric_type,
                'metric_value' => $value,
                'metric_unit' => $unit,
                'endpoint' => $endpoint,
                'status' => $status
            ],
            ['%s', '%f', '%s', '%s', '%s']
        );
    }

    /**
     * Run automated fraud detection
     */
    public static function run_fraud_detection_scan() {
        // Detect duplicates
        $duplicates = self::detect_duplicate_listings();
        foreach ($duplicates as $duplicate) {
            self::log_fraud_detection(
                $duplicate->author_id,
                $duplicate->property_1,
                'duplicate_listing',
                $duplicate->confidence_score,
                [
                    'duplicate_property_id' => $duplicate->property_2,
                    'address' => $duplicate->full_address
                ]
            );
        }
        
        // Detect rapid edits
        $rapid_edits = self::detect_rapid_edits();
        foreach ($rapid_edits as $edit) {
            self::log_fraud_detection(
                $edit->user_id,
                null,
                'rapid_edits',
                70,
                [
                    'properties_edited' => $edit->properties_edited,
                    'time_span' => $edit->time_span_minutes
                ]
            );
        }
        
        // Detect suspicious IPs
        $suspicious_ips = self::detect_suspicious_ips();
        foreach ($suspicious_ips as $ip) {
            self::log_fraud_detection(
                null,
                null,
                'suspicious_ip',
                65,
                [
                    'ip' => $ip->ip_address,
                    'user_count' => $ip->user_count,
                    'session_count' => $ip->session_count
                ]
            );
        }
        
        // Check user reports and flag agents/properties with multiple reports
        $reported_agents = self::get_highly_reported_agents();
        foreach ($reported_agents as $agent) {
            self::log_fraud_detection(
                $agent->agent_id,
                null,
                'multiple_accounts',
                85,
                [
                    'report_count' => $agent->report_count,
                    'reason' => 'Multiple user reports'
                ]
            );
        }
        
        $reported_properties = self::get_highly_reported_properties();
        foreach ($reported_properties as $property) {
            self::log_fraud_detection(
                $property->author_id,
                $property->property_id,
                'spam_content',
                80,
                [
                    'report_count' => $property->report_count,
                    'reason' => 'Multiple property reports'
                ]
            );
        }
        
        // Check low-rated agents
        $low_rated_agents = self::get_low_rated_agents();
        foreach ($low_rated_agents as $agent) {
            self::log_fraud_detection(
                $agent->agent_id,
                null,
                'fake_images',
                70,
                [
                    'avg_rating' => $agent->avg_rating,
                    'review_count' => $agent->review_count,
                    'reason' => 'Consistently low ratings'
                ]
            );
        }
        
        return [
            'duplicates' => count($duplicates),
            'rapid_edits' => count($rapid_edits),
            'suspicious_ips' => count($suspicious_ips),
            'reported_agents' => count($reported_agents),
            'reported_properties' => count($reported_properties),
            'low_rated_agents' => count($low_rated_agents)
        ];
    }
    
    /**
     * Create a fraud report (user-submitted)
     */
    public static function create_fraud_report($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mf_fraud_reports';
        
        $insert_data = [
            'reporter_email' => sanitize_email($data['reporter_email']),
            'reporter_user_id' => get_current_user_id() ?: null,
            'report_type' => sanitize_text_field($data['report_type']),
            'agent_id' => isset($data['agent_id']) ? intval($data['agent_id']) : null,
            'property_id' => isset($data['property_id']) ? intval($data['property_id']) : null,
            'reason' => sanitize_text_field($data['reason']),
            'details' => sanitize_textarea_field($data['details']),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
        ];
        
        $inserted = $wpdb->insert($table, $insert_data);
        
        if ($inserted) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get fraud reports by agent
     */
    public static function get_reports_by_agent($agent_id, $limit = 50) {
        global $wpdb;
        
        $sql = "SELECT * FROM {$wpdb->prefix}mf_fraud_reports
                WHERE agent_id = %d
                ORDER BY created_at DESC
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $agent_id, $limit));
    }
    
    /**
     * Get fraud reports by property
     */
    public static function get_reports_by_property($property_id, $limit = 50) {
        global $wpdb;
        
        $sql = "SELECT * FROM {$wpdb->prefix}mf_fraud_reports
                WHERE property_id = %d
                ORDER BY created_at DESC
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $property_id, $limit));
    }
    
    /**
     * Get agents with multiple reports (>=3)
     */
    private static function get_highly_reported_agents() {
        global $wpdb;
        
        $sql = "SELECT agent_id, COUNT(*) as report_count
                FROM {$wpdb->prefix}mf_fraud_reports
                WHERE agent_id IS NOT NULL
                AND status IN ('new', 'under_review')
                GROUP BY agent_id
                HAVING report_count >= 3";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get properties with multiple reports (>=2)
     */
    private static function get_highly_reported_properties() {
        global $wpdb;
        
        $sql = "SELECT 
                    fr.property_id, 
                    p.post_author as author_id,
                    COUNT(*) as report_count
                FROM {$wpdb->prefix}mf_fraud_reports fr
                JOIN {$wpdb->posts} p ON fr.property_id = p.ID
                WHERE fr.property_id IS NOT NULL
                AND fr.status IN ('new', 'under_review')
                GROUP BY fr.property_id, p.post_author
                HAVING report_count >= 2";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get agents with low ratings (<2 stars, minimum 3 reviews)
     */
    private static function get_low_rated_agents() {
        global $wpdb;
        
        $sql = "SELECT 
                    post_id as agent_id,
                    AVG(CAST(meta_value AS DECIMAL(3,2))) as avg_rating,
                    COUNT(*) as review_count
                FROM {$wpdb->prefix}postmeta
                WHERE meta_key = '_malisafi_agent_rating'
                GROUP BY post_id
                HAVING avg_rating < 2.0 AND review_count >= 3";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Calculate comprehensive fraud score for user/property
     */
    public static function calculate_fraud_score($user_id = null, $property_id = null) {
        global $wpdb;
        
        $score = 0;
        $factors = [];
        
        if ($user_id) {
            // Check fraud detection alerts
            $alerts = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_detection 
                 WHERE user_id = %d AND status IN ('pending', 'confirmed')",
                $user_id
            ));
            if ($alerts > 0) {
                $score += min($alerts * 15, 50);
                $factors[] = "Fraud alerts: {$alerts}";
            }
            
            // Check user reports against this user
            $reports = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_reports 
                 WHERE agent_id = %d AND status IN ('new', 'under_review')",
                $user_id
            ));
            if ($reports > 0) {
                $score += min($reports * 20, 60);
                $factors[] = "User reports: {$reports}";
            }
            
            // Check average rating
            $avg_rating = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(CAST(meta_value AS DECIMAL(3,2))) FROM {$wpdb->prefix}postmeta 
                 WHERE post_id = %d AND meta_key = '_malisafi_agent_rating'",
                $user_id
            ));
            if ($avg_rating && $avg_rating < 2.5) {
                $score += (2.5 - $avg_rating) * 20;
                $factors[] = "Low rating: {$avg_rating}/5";
            }
        }
        
        if ($property_id) {
            // Check property-specific fraud detection
            $property_alerts = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_detection 
                 WHERE property_id = %d AND status IN ('pending', 'confirmed')",
                $property_id
            ));
            if ($property_alerts > 0) {
                $score += min($property_alerts * 15, 40);
                $factors[] = "Property alerts: {$property_alerts}";
            }
            
            // Check property reports
            $property_reports = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_reports 
                 WHERE property_id = %d AND status IN ('new', 'under_review')",
                $property_id
            ));
            if ($property_reports > 0) {
                $score += min($property_reports * 25, 50);
                $factors[] = "Property reports: {$property_reports}";
            }
        }
        
        return [
            'score' => min($score, 100),
            'risk_level' => $score >= 75 ? 'high' : ($score >= 40 ? 'medium' : 'low'),
            'factors' => $factors
        ];
    }
}
