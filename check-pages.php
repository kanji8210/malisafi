<?php
/**
 * Check if required pages exist
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
    require_once ABSPATH . 'wp-load.php';
}

echo "Checking Malisafi Pages Status\n";
echo "==============================\n\n";

$pages = array(
    'agent_dashboard',
    'agent_properties', 
    'agent_add_property',
    'agent_leads',
    'submit_property'
);

foreach ($pages as $page_key) {
    $page_id = get_option('malisafi_page_' . $page_key);
    if ($page_id) {
        $page = get_post($page_id);
        if ($page) {
            $url = get_permalink($page_id);
            echo "✅ {$page_key}: {$url}\n";
        } else {
            echo "❌ {$page_key}: Page ID {$page_id} exists but post not found\n";
        }
    } else {
        echo "❌ {$page_key}: Page not created (option missing)\n";
    }
}

echo "\nPage Manager Status:\n";
$status = get_option('malisafi_pages_status');
if ($status) {
    echo "Pages status: " . print_r($status, true) . "\n";
} else {
    echo "No pages status found\n";
}