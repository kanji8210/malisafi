<?php
/**
 * Quick test to check if all classes can be loaded
 * Run this from wp-content/plugins/malisafi_mls/
 */

// Set WordPress path
define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

// Load WordPress
require_once ABSPATH . 'wp-load.php';

echo "Testing MalisafiMLS Plugin Classes...\n\n";

// Test class loading
$classes = array(
    'Malisafi_Property_Moderation' => MALISAFI_MLS_PATH . 'admin/class-property-moderation.php',
    'Malisafi_Property_Submit' => MALISAFI_MLS_PATH . 'admin/class-property-submit.php',
    'Malisafi_User_Manager' => MALISAFI_MLS_PATH . 'admin/class-user-manager.php',
    'Malisafi_Admin_Dashboard' => MALISAFI_MLS_PATH . 'admin/class-admin-dashboard.php',
);

foreach ($classes as $class => $file) {
    if (file_exists($file)) {
        require_once $file;
        if (class_exists($class)) {
            echo "✓ $class loaded successfully\n";
        } else {
            echo "✗ $class failed to load\n";
        }
    } else {
        echo "✗ File not found: $file\n";
    }
}

echo "\n";

// Test database table
global $wpdb;
$table = $wpdb->prefix . 'mf_property_reports';
$exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;

if ($exists) {
    echo "✓ Table $table exists\n";
} else {
    echo "✗ Table $table does NOT exist\n";
    echo "\nTo create the table, go to: Malisafi > Database Tools\n";
}

echo "\nTest completed!\n";
