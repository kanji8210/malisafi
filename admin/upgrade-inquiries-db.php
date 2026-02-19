<?php
/**
 * Database Upgrade Script for Inquiries Table
 * 
 * Adds email tracking fields to existing inquiries table
 * Run this from WordPress admin or via WP-CLI
 * 
 * Usage: Visit yoursite.com/wp-admin/admin.php?page=malisafi-upgrade-inquiries-db
 * 
 * @package MalisafiMLS
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once('../../../../wp-load.php');
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator privileges required.');
}

// Get table name
global $wpdb;
$table_name = $wpdb->prefix . 'mf_inquiries';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

if (!$table_exists) {
    wp_die('Inquiries table does not exist. Please activate the plugin first.');
}

$results = [];

// 1. Add email_sent column
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'email_sent'");
if (empty($column_exists)) {
    $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN email_sent BOOLEAN DEFAULT TRUE COMMENT 'Whether notification email was sent successfully' AFTER status");
    $results[] = [
        'action' => 'Add email_sent column',
        'success' => $result !== false,
        'message' => $result !== false ? 'Column added successfully' : 'Failed: ' . $wpdb->last_error
    ];
} else {
    $results[] = ['action' => 'Add email_sent column', 'success' => true, 'message' => 'Column already exists'];
}

// 2. Add email_recipient column
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'email_recipient'");
if (empty($column_exists)) {
    $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN email_recipient VARCHAR(255) COMMENT 'Agent/agency email that received notification' AFTER email_sent");
    $results[] = [
        'action' => 'Add email_recipient column',
        'success' => $result !== false,
        'message' => $result !== false ? 'Column added successfully' : 'Failed: ' . $wpdb->last_error
    ];
} else {
    $results[] = ['action' => 'Add email_recipient column', 'success' => true, 'message' => 'Column already exists'];
}

// 3. Add agency_id column
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'agency_id'");
if (empty($column_exists)) {
    $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN agency_id BIGINT UNSIGNED DEFAULT 0 AFTER agent_id");
    $results[] = [
        'action' => 'Add agency_id column',
        'success' => $result !== false,
        'message' => $result !== false ? 'Column added successfully' : 'Failed: ' . $wpdb->last_error
    ];
} else {
    $results[] = ['action' => 'Add agency_id column', 'success' => true, 'message' => 'Column already exists'];
}

// 4. Add client_name column
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'client_name'");
if (empty($column_exists)) {
    $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN client_name VARCHAR(255) AFTER client_email");
    $results[] = [
        'action' => 'Add client_name column',
        'success' => $result !== false,
        'message' => $result !== false ? 'Column added successfully' : 'Failed: ' . $wpdb->last_error
    ];
} else {
    $results[] = ['action' => 'Add client_name column', 'success' => true, 'message' => 'Column already exists'];
}

// 5. Update status ENUM to include 'email_failed'
$result = $wpdb->query("ALTER TABLE {$table_name} MODIFY COLUMN status ENUM('new', 'read', 'replied', 'closed', 'email_failed') DEFAULT 'new'");
$results[] = [
    'action' => 'Update status ENUM',
    'success' => $result !== false,
    'message' => $result !== false ? 'ENUM updated successfully' : 'Failed: ' . $wpdb->last_error
];

// 6. Add index for email_sent
$index_exists = $wpdb->get_results("SHOW INDEX FROM {$table_name} WHERE Key_name = 'idx_email_status'");
if (empty($index_exists)) {
    $result = $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_email_status (email_sent)");
    $results[] = [
        'action' => 'Add email_status index',
        'success' => $result !== false,
        'message' => $result !== false ? 'Index added successfully' : 'Failed: ' . $wpdb->last_error
    ];
} else {
    $results[] = ['action' => 'Add email_status index', 'success' => true, 'message' => 'Index already exists'];
}

// 7. Update existing records where email failed
$updated = $wpdb->query("UPDATE {$table_name} SET email_sent = 0 WHERE status = 'email_failed' AND email_sent = 1");
$results[] = [
    'action' => 'Update existing email_failed records',
    'success' => $updated !== false,
    'message' => $updated !== false ? "Updated {$updated} records" : 'Failed: ' . $wpdb->last_error
];

// Display results
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inquiries Database Upgrade - Malisafi MLS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
            background: #f0f0f1;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .subtitle {
            color: #646970;
            margin: 0 0 30px 0;
            font-size: 14px;
        }
        .result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .result.success {
            background: #f6fef9;
            border-color: #00a32a;
        }
        .result.error {
            background: #fcf0f1;
            border-color: #d63638;
        }
        .result h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 600;
        }
        .result p {
            margin: 0;
            font-size: 13px;
            color: #646970;
        }
        .summary {
            background: #f0f6fc;
            border: 1px solid #0d5bd0;
            padding: 20px;
            border-radius: 4px;
            margin-top: 30px;
        }
        .summary h2 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #0d5bd0;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #2271b1;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background: #135e96;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Inquiries Database Upgrade</h1>
        <p class="subtitle">Adding email notification tracking to inquiries table</p>
        
        <?php foreach ($results as $result): ?>
            <div class="result <?php echo $result['success'] ? 'success' : 'error'; ?>">
                <h3><?php echo esc_html($result['action']); ?></h3>
                <p><?php echo esc_html($result['message']); ?></p>
            </div>
        <?php endforeach; ?>
        
        <div class="summary">
            <h2>✅ Upgrade Complete</h2>
            <p><strong>New Features Available:</strong></p>
            <ul style="margin: 10px 0;">
                <li>Email notification status tracking (sent/failed)</li>
                <li>Filter inquiries by email delivery status</li>
                <li>View recipient email address for each inquiry</li>
                <li>Enhanced status tracking with 'email_failed' state</li>
            </ul>
            <p style="margin-top: 15px;">
                <strong>Next Steps:</strong><br>
                Visit the <a href="<?php echo admin_url('admin.php?page=malisafi-inquiries'); ?>">Inquiries page</a> to see the new email status column and filters.
            </p>
        </div>
        
        <a href="<?php echo admin_url('admin.php?page=malisafi-inquiries'); ?>" class="back-link">
            → Go to Inquiries Page
        </a>
    </div>
</body>
</html>
