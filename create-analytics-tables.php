<?php
/**
 * Quick Script to Create Analytics Tables
 * 
 * Run this once to create all analytics tables.
 * Access via: http://yoursite.com/wp-content/plugins/malisafi/create-analytics-tables.php?key=YOUR_ADMIN_KEY
 */

// Load WordPress
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

// Security check - only allow from WP-Admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Admin privileges required.');
}

require_once MALISAFI_MLS_PATH . 'includes/analytics/class-analytics-migration.php';

use MalisafiMLS\Analytics\Analytics_Migration;

// Check if tables already exist
if (Analytics_Migration::tables_exist()) {
    echo '<div style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">';
    echo '<strong>✓ Success!</strong> All analytics tables already exist.';
    echo '</div>';
} else {
    try {
        Analytics_Migration::create_all_tables();
        echo '<div style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">';
        echo '<strong>✓ Success!</strong> All analytics tables have been created successfully.';
        echo '</div>';
    } catch (Exception $e) {
        echo '<div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">';
        echo '<strong>✗ Error:</strong> ' . esc_html($e->getMessage());
        echo '</div>';
    }
}

// Show table status
echo '<h2 style="margin-top: 30px;">Table Status:</h2>';
echo '<table style="border-collapse: collapse; width: 100%; margin-top: 15px;">';
echo '<tr style="background: #f8f9fa;"><th style="border: 1px solid #dee2e6; padding: 10px; text-align: left;">Table</th><th style="border: 1px solid #dee2e6; padding: 10px;">Exists</th></tr>';

global $wpdb;
$tables = [
    'wp_mf_user_activity',
    'wp_mf_property_views',
    'wp_mf_property_interactions',
    'wp_mf_search_analytics',
    'wp_mf_submission_funnel',
    'wp_mf_fraud_detection',
    'wp_mf_revenue_tracking',
    'wp_mf_system_health'
];

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    $status = $exists ? '<span style="color: green;">✓ Yes</span>' : '<span style="color: red;">✗ No</span>';
    echo '<tr><td style="border: 1px solid #dee2e6; padding: 10px;">' . $table . '</td><td style="border: 1px solid #dee2e6; padding: 10px; text-align: center;">' . $status . '</td></tr>';
}
echo '</table>';

echo '<p style="margin-top: 20px;"><a href="' . admin_url('admin.php?page=malisafi-analytics') . '">Go to Analytics Dashboard →</a></p>';
