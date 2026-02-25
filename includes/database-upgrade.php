<?php
/**
 * Database upgrade handler
 *
 * @package MalisafiMLS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Hook to check and update database schema
add_action('admin_init', 'malisafi_check_database_update');

/**
 * Check if database needs updating
 */
function malisafi_check_database_update() {
    // Only run if Database class is available
    if (!class_exists('MalisafiMLS\Database')) {
        require_once MALISAFI_MLS_PATH . 'includes/class-database.php';
    }
    
    $current_version = get_option('malisafi_mls_db_version', '0.0.0');
    $plugin_version = MALISAFI_MLS_VERSION;
    
    // Check if update is needed
    if (version_compare($current_version, $plugin_version, '<')) {
        try {
            MalisafiMLS\Database::update_schema();
            
            // Update version
            update_option('malisafi_mls_db_version', $plugin_version);
            
            // Add admin notice
            add_action('admin_notices', 'malisafi_database_updated_notice');
        } catch (Exception $e) {
            // Log error but don't break the plugin
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MalisafiMLS Database Update Error: ' . $e->getMessage());
            }
        }
    }
}

/**
 * Show database update notice
 */
function malisafi_database_updated_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('MalisafiMLS database has been updated successfully.', 'malisafi-mls'); ?></p>
    </div>
    <?php
}
