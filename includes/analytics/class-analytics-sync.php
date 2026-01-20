<?php
/**
 * Analytics Data Sync Handler
 * 
 * Handles AJAX request for syncing WordPress data to analytics tables
 * 
 * @package MalisafiMLS
 * @since 1.0.0
 */

namespace MalisafiMLS\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

class Analytics_Sync {
    
    /**
     * Register AJAX handlers
     */
    public static function init() {
        add_action('wp_ajax_malisafi_sync_analytics_data', array(__CLASS__, 'sync_analytics_data'));
    }
    
    /**
     * Sync WordPress data to analytics tables
     */
    public static function sync_analytics_data() {
        // Security check
        check_ajax_referer('malisafi_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission refusée'));
        }
        
        global $wpdb;
        
        $log = "🔄 SYNCHRONISATION ANALYTICS - " . date('Y-m-d H:i:s') . "\n\n";
        
        // ==================================================
        // 1. SYNC PROPERTIES
        // ==================================================
        $log .= "=" . str_repeat("=", 50) . "\n";
        $log .= "1️⃣ SYNCHRONISATION DES PROPRIÉTÉS\n";
        $log .= "=" . str_repeat("=", 50) . "\n\n";
        
        $properties = get_posts(array(
            'post_type' => 'malisafi_property',
            'post_status' => array('publish', 'pending'),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        $properties_synced = 0;
        $properties_skipped = 0;
        
        foreach ($properties as $property_id) {
            // Check if already exists
            $exists = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}mf_properties 
                WHERE property_id = %d
            ", $property_id));
            
            if ($exists) {
                $properties_skipped++;
                continue;
            }
            
            // Get property data
            $post = get_post($property_id);
            $price = get_post_meta($property_id, '_malisafi_price', true);
            $county = get_post_meta($property_id, '_malisafi_county', true);
            $neighbourhood = get_post_meta($property_id, '_malisafi_neighbourhood', true);
            
            // Insert into analytics table
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'mf_properties',
                array(
                    'property_id' => $property_id,
                    'author_id' => $post->post_author,
                    'title' => $post->post_title,
                    'price' => floatval($price),
                    'location' => $county . ($neighbourhood ? ', ' . $neighbourhood : ''),
                    'status' => $post->post_status,
                    'created_at' => $post->post_date,
                    'updated_at' => $post->post_modified
                ),
                array('%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s')
            );
            
            if ($inserted) {
                $properties_synced++;
            }
        }
        
        $log .= "WordPress properties: " . count($properties) . "\n";
        $log .= "✅ Synchronisées: " . $properties_synced . "\n";
        $log .= "⏭️ Déjà existantes: " . $properties_skipped . "\n\n";
        
        // ==================================================
        // 2. SYNC USER ACTIVITY
        // ==================================================
        $log .= "=" . str_repeat("=", 50) . "\n";
        $log .= "2️⃣ SYNCHRONISATION ACTIVITÉ UTILISATEURS\n";
        $log .= "=" . str_repeat("=", 50) . "\n\n";
        
        $malisafi_roles = array('malisafi_client', 'malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_moderator');
        $user_query = new \WP_User_Query(array(
            'role__in' => $malisafi_roles,
            'fields' => 'all'
        ));
        $users = $user_query->get_results();
        
        $users_synced = 0;
        $users_skipped = 0;
        
        foreach ($users as $user) {
            // Check if user has any activity logged
            $has_activity = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}mf_user_activity 
                WHERE user_id = %d
            ", $user->ID));
            
            if ($has_activity > 0) {
                $users_skipped++;
                continue;
            }
            
            // Create a registration activity entry
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'mf_user_activity',
                array(
                    'user_id' => $user->ID,
                    'activity_type' => 'registration',
                    'activity_data' => json_encode(array(
                        'role' => $user->roles[0] ?? 'unknown',
                        'synced' => true
                    )),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Analytics Sync',
                    'created_at' => $user->user_registered
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($inserted) {
                $users_synced++;
            }
        }
        
        $log .= "Utilisateurs Malisafi: " . count($users) . "\n";
        $log .= "✅ Activité créée: " . $users_synced . "\n";
        $log .= "⏭️ Déjà actifs: " . $users_skipped . "\n\n";
        
        // ==================================================
        // 3. VERIFY RESULTS
        // ==================================================
        $log .= "=" . str_repeat("=", 50) . "\n";
        $log .= "3️⃣ VÉRIFICATION POST-SYNC\n";
        $log .= "=" . str_repeat("=", 50) . "\n\n";
        
        $final_properties = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mf_properties");
        $final_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}mf_user_activity");
        
        // Calculate avg properties per user
        $avg_props = $wpdb->get_var("
            SELECT ROUND(AVG(property_count), 2)
            FROM (
                SELECT COUNT(*) as property_count
                FROM {$wpdb->prefix}mf_properties
                GROUP BY author_id
            ) as subquery
        ");
        
        $log .= "📊 Tables Analytics (après sync):\n";
        $log .= "  - Total propriétés: " . $final_properties . "\n";
        $log .= "  - Total utilisateurs actifs: " . $final_users . "\n";
        $log .= "  - Moyenne props/utilisateur: " . ($avg_props ?: 0) . "\n\n";
        
        $log .= "=" . str_repeat("=", 50) . "\n";
        $log .= "✅ SYNCHRONISATION TERMINÉE!\n";
        $log .= "=" . str_repeat("=", 50) . "\n";
        
        // Log to error_log for debugging
        error_log($log);
        
        // Send success response
        wp_send_json_success(array(
            'message' => $log,
            'properties_synced' => $properties_synced,
            'users_synced' => $users_synced,
            'final_stats' => array(
                'properties' => $final_properties,
                'users' => $final_users,
                'avg_per_user' => $avg_props
            )
        ));
    }
}

// Initialize
Analytics_Sync::init();
