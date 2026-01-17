<?php
/**
 * Analytics Core Metrics
 *
 * Core usage analytics: properties per role, login frequency, funnel analysis
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.0
 */

namespace MalisafiMLS\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

class Analytics_Core {

    /**
     * Get properties added per role
     */
    public static function get_properties_by_role($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                CASE 
                    WHEN um.meta_value LIKE '%malisafi_agent_premium%' THEN 'agent_premium'
                    WHEN um.meta_value LIKE '%malisafi_agent_basic%' THEN 'agent_basic'
                    WHEN um.meta_value LIKE '%malisafi_owner%' THEN 'owner'
                    WHEN um.meta_value LIKE '%malisafi_developer%' THEN 'developer'
                    ELSE 'other'
                END as role,
                COUNT(p.property_id) as total_properties,
                COUNT(CASE WHEN p.status = 'published' THEN 1 END) as published,
                COUNT(CASE WHEN p.status = 'pending_review' THEN 1 END) as pending
            FROM {$wpdb->prefix}mf_properties p
            INNER JOIN {$wpdb->users} u ON p.author_id = u.ID
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities'
            WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY role
            ORDER BY total_properties DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }

    /**
     * Get login frequency by role
     */
    public static function get_login_frequency($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                CASE 
                    WHEN um.meta_value LIKE '%malisafi_agent_premium%' THEN 'agent_premium'
                    WHEN um.meta_value LIKE '%malisafi_agent_basic%' THEN 'agent_basic'
                    WHEN um.meta_value LIKE '%malisafi_owner%' THEN 'owner'
                    WHEN um.meta_value LIKE '%malisafi_developer%' THEN 'developer'
                    ELSE 'other'
                END as role,
                COUNT(DISTINCT ua.user_id) as unique_users,
                COUNT(ua.id) as total_logins,
                ROUND(COUNT(ua.id) / COUNT(DISTINCT ua.user_id), 2) as avg_logins_per_user,
                ROUND(AVG(ua.time_spent), 2) as avg_session_duration
            FROM {$wpdb->prefix}mf_user_activity ua
            INNER JOIN {$wpdb->users} u ON ua.user_id = u.ID
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities'
            WHERE ua.activity_type = 'login'
            AND ua.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY role
            ORDER BY total_logins DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }

    /**
     * Get submission funnel metrics
     */
    public static function get_submission_funnel($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                step_name,
                COUNT(DISTINCT session_id) as sessions_reached,
                COUNT(DISTINCT CASE WHEN completed = 1 THEN session_id END) as sessions_completed,
                ROUND(
                    COUNT(DISTINCT CASE WHEN completed = 1 THEN session_id END) * 100.0 / 
                    NULLIF(COUNT(DISTINCT session_id), 0), 
                    2
                ) as completion_rate,
                ROUND(AVG(time_spent), 2) as avg_time_spent
            FROM {$wpdb->prefix}mf_submission_funnel
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY step_name
            ORDER BY FIELD(step_name, 
                'form_loaded', 'basic_info', 'pricing', 'details', 
                'location', 'amenities', 'images', 'submit_success'
            )
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }

    /**
     * Get drop-off analysis
     */
    public static function get_dropoff_points($days = 30) {
        global $wpdb;
        
        // Get all sessions that started
        $total_sessions = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT session_id)
            FROM {$wpdb->prefix}mf_submission_funnel
            WHERE step_name = 'form_loaded'
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        if (!$total_sessions) {
            return [];
        }
        
        $sql = "
            SELECT 
                step_name,
                COUNT(DISTINCT session_id) as reached,
                %d as total_started,
                ROUND(
                    (1 - (COUNT(DISTINCT session_id) / %d)) * 100, 
                    2
                ) as drop_off_rate
            FROM {$wpdb->prefix}mf_submission_funnel
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND step_name != 'form_loaded'
            GROUP BY step_name
            ORDER BY drop_off_rate DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $total_sessions, $total_sessions, $days));
    }

    /**
     * Get top contributors by role (with activity metrics)
     */
    public static function get_top_contributors($role = 'all', $limit = 10) {
        global $wpdb;
        
        $sql = "
            SELECT 
                u.ID as user_id,
                u.display_name,
                u.user_email,
                COUNT(DISTINCT p.ID) as properties,
                COALESCE(SUM(pv.view_count), 0) as views,
                COALESCE(SUM(pi.inquiry_count), 0) as inquiries,
                COALESCE(COUNT(DISTINCT ua.id), 0) as activities,
                MAX(p.post_date) as last_property_added
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author AND p.post_type = 'malisafi_property'
            LEFT JOIN (
                SELECT property_id, COUNT(*) as view_count
                FROM {$wpdb->prefix}mf_property_views
                GROUP BY property_id
            ) pv ON p.ID = pv.property_id
            LEFT JOIN (
                SELECT property_id, COUNT(*) as inquiry_count
                FROM {$wpdb->prefix}mf_property_interactions
                WHERE interaction_type = 'inquiry'
                GROUP BY property_id
            ) pi ON p.ID = pi.property_id
            LEFT JOIN {$wpdb->prefix}mf_user_activity ua ON u.ID = ua.user_id AND ua.activity_type IN ('login', 'property_add_complete', 'property_edit')
            GROUP BY u.ID
            HAVING properties > 0 OR activities > 0
            ORDER BY properties DESC, views DESC
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }

    /**
     * Get activity trends over time
     */
    public static function get_activity_trends($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(CASE WHEN activity_type = 'login' THEN 1 END) as logins,
                COUNT(CASE WHEN activity_type = 'property_add_complete' THEN 1 END) as properties_added,
                COUNT(CASE WHEN activity_type = 'property_edit' THEN 1 END) as properties_edited,
                COUNT(CASE WHEN activity_type = 'search' THEN 1 END) as searches,
                COUNT(DISTINCT user_id) as active_users
            FROM {$wpdb->prefix}mf_user_activity
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }

    /**
     * Get dashboard overview stats
     */
    public static function get_overview_stats($days = 30) {
        global $wpdb;
        
        $stats = [];
        
        // Total active users
        $stats['active_users'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id)
            FROM {$wpdb->prefix}mf_user_activity
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        // Total properties added
        $stats['properties_added'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}mf_properties
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        // Total property views
        $stats['total_views'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}mf_property_views
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        // Total inquiries
        $stats['total_inquiries'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}mf_property_interactions
            WHERE interaction_type = 'inquiry'
            AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        // Avg properties per user
        $stats['avg_properties_per_user'] = $wpdb->get_var($wpdb->prepare("
            SELECT ROUND(AVG(property_count), 2)
            FROM (
                SELECT COUNT(*) as property_count
                FROM {$wpdb->prefix}mf_properties
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY author_id
            ) as subquery
        ", $days));
        
        // Funnel completion rate
        $funnel_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT session_id) as total_sessions,
                COUNT(DISTINCT CASE WHEN completed = 1 THEN session_id END) as completed_sessions
            FROM {$wpdb->prefix}mf_submission_funnel
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        $stats['funnel_completion_rate'] = $funnel_stats && $funnel_stats->total_sessions > 0
            ? round(($funnel_stats->completed_sessions / $funnel_stats->total_sessions) * 100, 2)
            : 0;
        
        return $stats;
    }
}
