<?php
/**
 * Debug Agent Dashboard Statistics
 * Place this file in plugin root and access via: /wp-content/plugins/malisafi/debug-agent-stats.php
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!is_user_logged_in()) {
    die('Please log in as an agent first.');
}

$current_user = wp_get_current_user();
$is_agent = in_array('malisafi_agent_basic', $current_user->roles) || in_array('malisafi_agent_premium', $current_user->roles);

if (!$is_agent) {
    die('You must be logged in as an agent.');
}

global $wpdb;
$linked_user_id = $current_user->ID;

echo "<h1>Agent Dashboard Statistics Debug</h1>";
echo "<p><strong>Current User:</strong> " . $current_user->display_name . " (ID: " . $current_user->ID . ")</p>";
echo "<p><strong>User Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";
echo "<hr>";

// Test 1: Total properties
echo "<h2>Test 1: Total Properties</h2>";
$query1 = $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_author = %d",
    $linked_user_id
);
echo "<p><strong>Query:</strong> <code>" . $query1 . "</code></p>";
$total_properties = (int) $wpdb->get_var($query1);
echo "<p><strong>Result:</strong> " . $total_properties . " properties</p>";

// Test 2: Published properties
echo "<h2>Test 2: Published Properties</h2>";
$query2 = $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_author = %d AND post_status = 'publish'",
    $linked_user_id
);
echo "<p><strong>Query:</strong> <code>" . $query2 . "</code></p>";
$published = (int) $wpdb->get_var($query2);
echo "<p><strong>Result:</strong> " . $published . " published</p>";

// Test 3: Pending properties
echo "<h2>Test 3: Pending Properties</h2>";
$query3 = $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_author = %d AND post_status = 'pending'",
    $linked_user_id
);
echo "<p><strong>Query:</strong> <code>" . $query3 . "</code></p>";
$pending = (int) $wpdb->get_var($query3);
echo "<p><strong>Result:</strong> " . $pending . " pending</p>";

// Test 4: All properties by this author (any status)
echo "<h2>Test 4: All Post Statuses</h2>";
$all_statuses = $wpdb->get_results($wpdb->prepare(
    "SELECT post_status, COUNT(*) as count FROM {$wpdb->posts} 
     WHERE post_type = 'malisafi_property' AND post_author = %d 
     GROUP BY post_status",
    $linked_user_id
));
if ($all_statuses) {
    echo "<ul>";
    foreach ($all_statuses as $status) {
        echo "<li><strong>" . $status->post_status . ":</strong> " . $status->count . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No properties found for this author.</p>";
}

// Test 5: All properties in database
echo "<h2>Test 5: All Properties (Any Author)</h2>";
$all_properties = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property'");
echo "<p><strong>Total malisafi_property posts:</strong> " . $all_properties . "</p>";

// Test 6: Recent properties
echo "<h2>Test 6: Recent Properties</h2>";
$recent_properties = get_posts([
    'post_type' => 'malisafi_property',
    'author' => $linked_user_id,
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
    'post_status' => 'any'
]);
echo "<p><strong>Found:</strong> " . count($recent_properties) . " recent properties</p>";
if ($recent_properties) {
    echo "<ul>";
    foreach ($recent_properties as $prop) {
        $views = get_post_meta($prop->ID, '_property_views', true);
        echo "<li>" . $prop->post_title . " - Status: " . $prop->post_status . " - Views: " . ($views ?: '0') . "</li>";
    }
    echo "</ul>";
}

// Test 7: Check if user has agent post
echo "<h2>Test 7: Agent Post Linkage</h2>";
$agent_posts = get_posts([
    'post_type' => 'malisafi_agent',
    'meta_query' => [
        [
            'key' => '_agent_user_id',
            'value' => $current_user->ID
        ]
    ]
]);
if ($agent_posts) {
    echo "<p><strong>Agent Post Found:</strong> " . $agent_posts[0]->post_title . " (ID: " . $agent_posts[0]->ID . ")</p>";
} else {
    echo "<p><strong>WARNING:</strong> No agent post linked to this user!</p>";
}

// Test 8: Variables that would be available in dashboard
echo "<h2>Test 8: Dashboard Variables Summary</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Variable</th><th>Value</th></tr>";
echo "<tr><td>\$total_properties</td><td>" . $total_properties . "</td></tr>";
echo "<tr><td>\$published</td><td>" . $published . "</td></tr>";
echo "<tr><td>\$pending</td><td>" . $pending . "</td></tr>";
echo "<tr><td>\$recent_properties count</td><td>" . count($recent_properties) . "</td></tr>";

$total_views = 0;
foreach ($recent_properties as $prop) {
    $views = get_post_meta($prop->ID, '_property_views', true);
    $total_views += (int) $views;
}
echo "<tr><td>\$total_views</td><td>" . $total_views . "</td></tr>";
echo "</table>";

echo "<hr>";
echo "<p><strong>Conclusion:</strong> If all values above are 0 or empty, there might be an issue with:</p>";
echo "<ul>";
echo "<li>Property post type registration</li>";
echo "<li>User not being set as property author</li>";
echo "<li>Properties not saved in database</li>";
echo "</ul>";
