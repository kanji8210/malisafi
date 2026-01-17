<?php
/**
 * Plugin Name: Malisafi MLS
 * Plugin URI: https://malisafi.com
 * Description: A robust Multiple Listing Service (MLS) plugin for real estate property management
 * Version: 1.0.0
 * Author: Malisafi
 * Author URI: https://malisafi.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: malisafi-mls
 * Domain Path: /languages
 *
 * @package MalisafiMLS
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Configure PHP settings for optimal plugin performance
 */
if (!ini_get('safe_mode')) {
    // Increase execution time for Stripe and heavy operations
    $current_limit = ini_get('max_execution_time');
    if ($current_limit < 180) {
        @ini_set('max_execution_time', '180');
    }
    
    // Increase memory limit if needed
    $current_memory = ini_get('memory_limit');
    $current_memory_bytes = wp_convert_hr_to_bytes($current_memory);
    $required_memory_bytes = 256 * 1024 * 1024; // 256MB
    
    if ($current_memory_bytes < $required_memory_bytes) {
        @ini_set('memory_limit', '256M');
    }
}

/**
 * Plugin version
 */
define('MALISAFI_MLS_VERSION', '1.0.0');

/**
 * Plugin paths
 */
define('MALISAFI_MLS_PATH', plugin_dir_path(__FILE__));
define('MALISAFI_MLS_URL', plugin_dir_url(__FILE__));
define('MALISAFI_MLS_BASENAME', plugin_basename(__FILE__));
define('MALISAFI_MLS_PLUGIN_DIR', plugin_dir_path(__FILE__)); // Alias for compatibility
define('MALISAFI_MLS_PLUGIN_URL', plugin_dir_url(__FILE__)); // Alias for compatibility

/**
 * Autoloader
 */
spl_autoload_register(function ($class) {
    $prefix = 'MalisafiMLS\\';
    $base_dir = MALISAFI_MLS_PATH . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . strtolower(str_replace('\\', '-', $relative_class)) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Activation hook
 */
function activate_malisafi_mls() {
    require_once MALISAFI_MLS_PATH . 'includes/class-activator.php';
    MalisafiMLS\Activator::activate();
}
register_activation_hook(__FILE__, 'activate_malisafi_mls');

/**
 * Deactivation hook
 */
function deactivate_malisafi_mls() {
    require_once MALISAFI_MLS_PATH . 'includes/class-deactivator.php';
    MalisafiMLS\Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_malisafi_mls');

/**
 * Database upgrade check
 */
require_once MALISAFI_MLS_PATH . 'includes/database-upgrade.php';

/**
 * Load Kenya location helpers
 */
require_once MALISAFI_MLS_PATH . 'includes/kenya-location-helpers.php';

/**
 * Load default terms helpers
 */
require_once MALISAFI_MLS_PATH . 'includes/default-terms.php';

/**
 * Load agent classes
 */
require_once MALISAFI_MLS_PATH . 'includes/class-agent-post-type.php';
require_once MALISAFI_MLS_PATH . 'admin/class-agent-dashboard.php';
require_once MALISAFI_MLS_PATH . 'admin/class-agent-ratings-admin.php';

/**
 * Load page manager
 */
require_once MALISAFI_MLS_PATH . 'includes/class-page-manager.php';

/**
 * Load dashboard shortcodes
 */
require_once MALISAFI_MLS_PATH . 'includes/class-dashboard-shortcodes.php';

/**
 * Initialize agent system
 */
add_action('init', function() {
    $agent_post_type = new MalisafiMLS\Agent_Post_Type();
    Malisafi_Agent_Dashboard::init();
});

/**
 * Initialize page manager
 */
add_action('init', function() {
    MalisafiMLS\Page_Manager::init();
});

/**
 * Initialize dashboard shortcodes
 */
add_action('init', function() {
    MalisafiMLS\Dashboard_Shortcodes::init();
});

/**
 * Initialize the plugin
 */
function run_malisafi_mls() {
    require_once MALISAFI_MLS_PATH . 'includes/class-core.php';
    $plugin = new MalisafiMLS\Core();
    $plugin->run();
}

// Run the plugin
run_malisafi_mls();
