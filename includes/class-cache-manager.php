<?php
/**
 * Cache Manager for Malisafi MLS
 * 
 * Provides caching utilities for expensive database operations
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * Cache_Manager class
 */
class Cache_Manager {
    
    /**
     * Cache prefix to avoid conflicts with other plugins
     */
    const CACHE_PREFIX = 'malisafi_mls_';
    
    /**
     * Cache durations (in seconds)
     */
    const HOUR = 3600;
    const DAY = 86400;
    const WEEK = 604800;
    
    /**
     * Get cached data or execute callback and cache result
     *
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param int $expiration Cache duration in seconds (default: 1 hour)
     * @return mixed Cached data or callback result
     */
    public static function remember($key, $callback, $expiration = self::HOUR) {
        $cache_key = self::CACHE_PREFIX . $key;
        
        // Try to get from cache
        $cached = get_transient($cache_key);
        
        if (false !== $cached) {
            return $cached;
        }
        
        // Cache miss - execute callback
        $data = $callback();
        
        // Store in cache
        set_transient($cache_key, $data, $expiration);
        
        return $data;
    }
    
    /**
     * Get cached property statistics for a user
     *
     * @param int $user_id User ID
     * @return array Property statistics
     */
    public static function get_user_property_stats($user_id) {
        return self::remember(
            "user_stats_{$user_id}",
            function() use ($user_id) {
                global $wpdb;
                
                $stats = array(
                    'total' => 0,
                    'active' => 0,
                    'pending' => 0,
                    'total_views' => 0,
                    'total_inquiries' => 0
                );
                
                // Get property counts
                $counts = wp_count_posts('malisafi_property', $user_id);
                if ($counts) {
                    $stats['total'] = ($counts->publish ?? 0) + ($counts->pending ?? 0) + ($counts->draft ?? 0);
                    $stats['active'] = $counts->publish ?? 0;
                    $stats['pending'] = $counts->pending ?? 0;
                }
                
                // Get views and inquiries
                $properties = get_posts(array(
                    'post_type' => 'malisafi_property',
                    'author' => $user_id,
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'post_status' => array('publish', 'pending', 'draft')
                ));
                
                if (!empty($properties)) {
                    foreach ($properties as $property_id) {
                        $stats['total_views'] += intval(get_post_meta($property_id, '_malisafi_views', true));
                        $stats['total_inquiries'] += intval(get_post_meta($property_id, '_malisafi_inquiries', true));
                    }
                }
                
                return $stats;
            },
            self::HOUR
        );
    }
    
    /**
     * Get cached agent ratings
     *
     * @param int $agent_id Agent user ID or post ID
     * @return array Rating statistics
     */
    public static function get_agent_ratings($agent_id) {
        return self::remember(
            "agent_ratings_{$agent_id}",
            function() use ($agent_id) {
                global $wpdb;
                $table = $wpdb->prefix . 'mf_agent_ratings';
                
                $stats = $wpdb->get_row($wpdb->prepare(
                    "SELECT 
                        COUNT(*) as total_reviews,
                        AVG(rating) as average_rating,
                        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                    FROM {$table}
                    WHERE agent_id = %d AND status = %s",
                    $agent_id,
                    'approved'
                ), ARRAY_A);
                
                return $stats ?: array(
                    'total_reviews' => 0,
                    'average_rating' => 0,
                    'five_star' => 0,
                    'four_star' => 0,
                    'three_star' => 0,
                    'two_star' => 0,
                    'one_star' => 0
                );
            },
            self::DAY
        );
    }
    
    /**
     * Get cached featured properties
     *
     * @param int $limit Number of properties to retrieve
     * @return array Property IDs
     */
    public static function get_featured_properties($limit = 10) {
        return self::remember(
            "featured_properties_{$limit}",
            function() use ($limit) {
                $args = array(
                    'post_type' => 'malisafi_property',
                    'post_status' => 'publish',
                    'posts_per_page' => $limit,
                    'meta_query' => array(
                        array(
                            'key' => '_malisafi_featured',
                            'value' => '1',
                            'compare' => '='
                        )
                    ),
                    'fields' => 'ids'
                );
                
                return get_posts($args);
            },
            self::HOUR
        );
    }
    
    /**
     * Invalidate cache for a specific key
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public static function forget($key) {
        $cache_key = self::CACHE_PREFIX . $key;
        return delete_transient($cache_key);
    }
    
    /**
     * Invalidate all user-related caches
     *
     * @param int $user_id User ID
     */
    public static function invalidate_user_cache($user_id) {
        self::forget("user_stats_{$user_id}");
        self::forget("agent_ratings_{$user_id}");
    }
    
    /**
     * Invalidate property-related caches
     *
     * @param int $property_id Property ID
     */
    public static function invalidate_property_cache($property_id) {
        $author_id = get_post_field('post_author', $property_id);
        if ($author_id) {
            self::invalidate_user_cache($author_id);
        }
        
        // Also invalidate featured properties cache if this is featured
        $is_featured = get_post_meta($property_id, '_malisafi_featured', true);
        if ($is_featured) {
            self::forget('featured_properties_10');
            self::forget('featured_properties_20');
            self::forget('featured_properties_50');
        }
    }
    
    /**
     * Clear all plugin caches
     * 
     * Use sparingly - only when absolutely necessary
     */
    public static function clear_all() {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE %s 
            OR option_name LIKE %s",
            '_transient_' . self::CACHE_PREFIX . '%',
            '_transient_timeout_' . self::CACHE_PREFIX . '%'
        ));
        
        return true;
    }
    
    /**
     * Initialize cache invalidation hooks
     */
    public static function init() {
        // Invalidate cache when property is updated
        add_action('save_post_malisafi_property', array(__CLASS__, 'on_property_save'), 10, 1);
        
        // Invalidate cache when property meta is updated
        add_action('updated_post_meta', array(__CLASS__, 'on_property_meta_update'), 10, 4);
        
        // Invalidate cache when rating is added
        add_action('malisafi_rating_added', array(__CLASS__, 'on_rating_added'), 10, 1);
    }
    
    /**
     * Hook: Property saved
     */
    public static function on_property_save($property_id) {
        self::invalidate_property_cache($property_id);
    }
    
    /**
     * Hook: Property meta updated
     */
    public static function on_property_meta_update($meta_id, $property_id, $meta_key, $meta_value) {
        // Only invalidate for property post type
        if (get_post_type($property_id) !== 'malisafi_property') {
            return;
        }
        
        // Only invalidate for relevant meta keys
        $relevant_keys = array('_malisafi_featured', '_malisafi_views', '_malisafi_inquiries');
        if (in_array($meta_key, $relevant_keys)) {
            self::invalidate_property_cache($property_id);
        }
    }
    
    /**
     * Hook: Rating added
     */
    public static function on_rating_added($agent_id) {
        self::forget("agent_ratings_{$agent_id}");
    }
}
