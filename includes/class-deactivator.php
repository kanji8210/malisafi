<?php
/**
 * Plugin deactivation handler
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * Deactivator class
 */
class Deactivator {
    
    /**
     * Plugin deactivation logic
     */
    public static function deactivate() {
        // Remove custom roles (optional - commented out to preserve user roles)
        // require_once MALISAFI_MLS_PATH . 'includes/class-role-manager.php';
        // self::remove_custom_roles();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set deactivation timestamp
        update_option('malisafi_mls_deactivated', time());
        
        // Clean up transients
        self::clean_transients();
    }
    
    /**
     * Remove custom roles
     */
    private static function remove_custom_roles() {
        $custom_roles = array(
            'malisafi_client',
            'malisafi_agent_basic',
            'malisafi_agent_premium',
            'malisafi_owner',
            'malisafi_developer',
            'malisafi_moderator'
        );
        
        foreach ($custom_roles as $role) {
            remove_role($role);
        }
    }
    
    /**
     * Clean up plugin transients
     */
    private static function clean_transients() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_malisafi_mls_%' 
            OR option_name LIKE '_transient_timeout_malisafi_mls_%'"
        );
    }
}
