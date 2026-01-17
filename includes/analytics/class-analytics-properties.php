<?php
/**
 * Analytics Properties Metrics
 *
 * Property performance analytics: views, engagement, conversions
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.0
 */

namespace MalisafiMLS\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

class Analytics_Properties {

    /**
     * Get property engagement metrics
     */
    public static function get_engagement_metrics($property_id = null) {
        global $wpdb;
        
        $where = $property_id ? $wpdb->prepare("WHERE pv.property_id = %d", $property_id) : "";
        
        $sql = "
            SELECT 
                pv.property_id,
                p.post_id,
                COUNT(DISTINCT pv.id) as total_views,
                COUNT(DISTINCT pv.session_id) as unique_visitors,
                ROUND(AVG(pv.view_duration), 2) as avg_time_on_page,
                ROUND(AVG(pv.scroll_depth), 2) as avg_scroll_depth,
                SUM(CASE WHEN pv.gallery_viewed = 1 THEN 1 ELSE 0 END) as gallery_views,
                SUM(CASE WHEN pv.map_viewed = 1 THEN 1 ELSE 0 END) as map_views,
                SUM(CASE WHEN pv.contact_clicked = 1 THEN 1 ELSE 0 END) as contact_clicks,
                (
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}mf_property_interactions pi2 
                    WHERE pi2.property_id = pv.property_id 
                    AND pi2.interaction_type = 'inquiry'
                ) as inquiries,
                (
                    SELECT COUNT(*) 
                    FROM {$wpdb->prefix}mf_property_interactions pi3 
                    WHERE pi3.property_id = pv.property_id 
                    AND pi3.interaction_type = 'favorite'
                ) as favorites
            FROM {$wpdb->prefix}mf_property_views pv
            LEFT JOIN {$wpdb->prefix}mf_properties p ON pv.property_id = p.property_id
            {$where}
            GROUP BY pv.property_id
            ORDER BY total_views DESC
        ";
        
        return $property_id ? $wpdb->get_row($sql) : $wpdb->get_results($sql);
    }

    /**
     * Get geographic insights
     */
    public static function get_geographic_insights() {
        global $wpdb;
        
        $sql = "
            SELECT 
                pt.name as location,
                COUNT(DISTINCT p.property_id) as properties_count,
                SUM(p.views_count) as total_views,
                SUM(p.inquiries_count) as total_inquiries,
                ROUND(AVG(p.price), 2) as avg_price
            FROM {$wpdb->prefix}mf_properties p
            INNER JOIN {$wpdb->posts} posts ON p.post_id = posts.ID
            INNER JOIN {$wpdb->term_relationships} tr ON posts.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} pt ON tt.term_id = pt.term_id
            WHERE tt.taxonomy = 'malisafi_property_location'
            GROUP BY pt.term_id
            ORDER BY properties_count DESC
            LIMIT 20
        ";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Get conversion metrics
     */
    public static function get_conversion_metrics($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                DATE(pv.created_at) as date,
                COUNT(DISTINCT pv.id) as total_views,
                COUNT(DISTINCT CASE WHEN pi.interaction_type = 'inquiry' THEN pi.id END) as inquiries,
                COUNT(DISTINCT CASE WHEN pi.interaction_type = 'favorite' THEN pi.id END) as favorites,
                COUNT(DISTINCT CASE WHEN pi.interaction_type LIKE 'share_%%' THEN pi.id END) as shares,
                ROUND(
                    COUNT(DISTINCT CASE WHEN pi.interaction_type = 'inquiry' THEN pi.id END) * 100.0 / 
                    NULLIF(COUNT(DISTINCT pv.id), 0), 
                    2
                ) as inquiry_conversion_rate
            FROM {$wpdb->prefix}mf_property_views pv
            LEFT JOIN {$wpdb->prefix}mf_property_interactions pi 
                ON pv.property_id = pi.property_id 
                AND DATE(pv.created_at) = DATE(pi.created_at)
            WHERE pv.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(pv.created_at)
            ORDER BY date DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }

    /**
     * Get top performing properties
     */
    public static function get_top_properties($metric = 'views', $limit = 10) {
        global $wpdb;
        
        $order_by = 'p.views_count DESC';
        
        switch ($metric) {
            case 'inquiries':
                $order_by = 'p.inquiries_count DESC';
                break;
            case 'engagement':
                $order_by = '(p.views_count + p.inquiries_count * 5) DESC';
                break;
            case 'conversion':
                $order_by = '(p.inquiries_count / NULLIF(p.views_count, 0)) DESC';
                break;
        }
        
        $sql = "
            SELECT 
                p.property_id,
                p.post_id,
                posts.post_title,
                p.price,
                p.views_count,
                p.inquiries_count,
                p.status,
                u.display_name as author_name,
                ROUND(p.inquiries_count * 100.0 / NULLIF(p.views_count, 0), 2) as conversion_rate
            FROM {$wpdb->prefix}mf_properties p
            INNER JOIN {$wpdb->posts} posts ON p.post_id = posts.ID
            LEFT JOIN {$wpdb->users} u ON p.author_id = u.ID
            WHERE p.status = 'published'
            ORDER BY {$order_by}
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }

    /**
     * Get property sources breakdown
     */
    public static function get_traffic_sources($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                source,
                COUNT(*) as views,
                COUNT(DISTINCT session_id) as unique_visitors,
                ROUND(AVG(view_duration), 2) as avg_duration
            FROM {$wpdb->prefix}mf_property_views
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND source IS NOT NULL
            GROUP BY source
            ORDER BY views DESC
            LIMIT 10
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }

    /**
     * Get device type breakdown
     */
    public static function get_device_breakdown($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                device_type,
                COUNT(*) as views,
                COUNT(DISTINCT session_id) as unique_visitors,
                ROUND(AVG(view_duration), 2) as avg_duration,
                ROUND(AVG(scroll_depth), 2) as avg_scroll_depth
            FROM {$wpdb->prefix}mf_property_views
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY device_type
            ORDER BY views DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }

    /**
     * Get search analytics
     */
    public static function get_search_analytics($days = 30) {
        global $wpdb;
        
        $sql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_searches,
                COUNT(DISTINCT session_id) as unique_searchers,
                SUM(CASE WHEN zero_results = 1 THEN 1 ELSE 0 END) as zero_result_searches,
                ROUND(AVG(results_count), 2) as avg_results,
                ROUND(AVG(time_to_click), 2) as avg_time_to_click
            FROM {$wpdb->prefix}mf_search_analytics
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $days));
    }

    /**
     * Get popular search filters
     */
    public static function get_popular_filters($days = 30) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT filters_used
            FROM {$wpdb->prefix}mf_search_analytics
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND filters_used IS NOT NULL
        ", $days));
        
        $filter_counts = [];
        
        foreach ($results as $row) {
            $filters = json_decode($row->filters_used, true);
            if (is_array($filters)) {
                foreach ($filters as $key => $value) {
                    if (!isset($filter_counts[$key])) {
                        $filter_counts[$key] = 0;
                    }
                    $filter_counts[$key]++;
                }
            }
        }
        
        arsort($filter_counts);
        
        return array_slice($filter_counts, 0, 10, true);
    }
}
