<?php
/**
 * Malisafi MLS - Inquiry DB debug helper
 *
 * Run this file in a browser while logged in as an administrator to test
 * inserting a sample inquiry and view verbose DB debug info.
 *
 * IMPORTANT: Delete this file after debugging.
 */

if (!file_exists(__DIR__ . '/../../../wp-load.php')) {
    echo 'Unable to find wp-load.php';
    exit;
}

require_once __DIR__ . '/../../../wp-load.php';

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied. Administrator only.');
}

global $wpdb;

// Sample payload (use realistic values matching your app)
$table = $wpdb->prefix . 'mf_inquiries';
$payload = array(
    'property_id' => 1,
    'client_id' => 0,
    'agent_id' => 1,
    'agency_id' => null,
    'inquiry_type' => 'general',
    'message' => 'Test inquiry from debug-inquiry-test.php',
    'status' => 'new',
    'client_phone' => '0700000000',
    'client_email' => 'debug@example.test',
    'created_at' => current_time('mysql'),
    'updated_at' => current_time('mysql'),
    'client_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
);

echo '<h2>Malisafi Inquiry Insert Debug</h2>';
echo '<p>Table: <strong>' . esc_html($table) . '</strong></p>';
echo '<pre>Payload: ' . esc_html(print_r($payload, true)) . '</pre>';

$inserted = $wpdb->insert($table, $payload);

if ($inserted === false) {
    echo '<h3 style="color: red;">Insert failed</h3>';
    echo '<p><strong>last_error:</strong> ' . esc_html($wpdb->last_error) . '</p>';
    echo '<p><strong>last_query:</strong> ' . esc_html($wpdb->last_query) . '</p>';
    echo '<pre>' . esc_html(print_r($wpdb, true)) . '</pre>';
} else {
    echo '<h3 style="color: green;">Insert succeeded</h3>';
    echo '<p>Insert ID: ' . intval($wpdb->insert_id) . '</p>';
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    echo '<p>Total rows in table: ' . intval($count) . '</p>';
}

echo '<p style="color: #a00;">Remember to remove <code>debug-inquiry-test.php</code> after debugging.</p>';

?>
