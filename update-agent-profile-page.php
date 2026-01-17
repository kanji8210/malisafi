<?php
/**
 * Update Agent Profile Page with correct shortcode
 * Navigate to: /wp-content/plugins/malisafi/update-agent-profile-page.php in browser
 */

// Load WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die('Cannot find wp-load.php');
}

// Security check
if (!current_user_can('manage_options')) {
    die('❌ You must be an administrator to run this script.');
}

echo '<!DOCTYPE html><html><head><title>Update Agent Profile Page</title><style>
body { font-family: Arial, sans-serif; padding: 40px; background: #f0f0f0; }
.container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
.success { color: #00a32a; font-weight: bold; }
.error { color: #d63638; font-weight: bold; }
.info { background: #f0f6fc; padding: 15px; border-left: 4px solid #0073aa; margin: 20px 0; }
pre { background: #f6f7f7; padding: 15px; border-radius: 4px; overflow-x: auto; }
</style></head><body><div class="container">';

echo '<h1>🔧 Update Agent Profile Page</h1>';

// Find the agent-profile page
$page = get_page_by_path('agent-profile');

if (!$page) {
    echo '<p class="error">❌ Agent profile page not found!</p>';
    echo '<p>The page may need to be created first. Check Page Manager.</p>';
    echo '</div></body></html>';
    exit;
}

echo '<p class="success">✅ Found agent-profile page (ID: ' . $page->ID . ')</p>';
echo '<div class="info"><strong>Current state:</strong><br>';
echo '<strong>Title:</strong> ' . esc_html($page->post_title) . '<br>';
echo '<strong>Shortcode:</strong> <code>' . esc_html($page->post_content) . '</code><br>';
echo '<strong>Status:</strong> ' . $page->post_status . '<br>';
echo '<strong>URL:</strong> <a href="' . get_permalink($page->ID) . '" target="_blank">' . get_permalink($page->ID) . '</a>';
echo '</div>';

// Update the page content with correct shortcode
echo '<h2>Updating page...</h2>';

$updated = wp_update_post(array(
    'ID' => $page->ID,
    'post_content' => '[malisafi_agent_profile_view]',
    'post_title' => 'Agent Profile',
    'post_status' => 'publish'
));

if (is_wp_error($updated)) {
    echo '<p class="error">❌ Error updating page: ' . $updated->get_error_message() . '</p>';
} else {
    echo '<p class="success">✅ Successfully updated agent-profile page!</p>';
    
    // Verify the change
    $page = get_post($page->ID);
    echo '<div class="info"><strong>New state:</strong><br>';
    echo '<strong>Title:</strong> ' . esc_html($page->post_title) . '<br>';
    echo '<strong>Shortcode:</strong> <code>' . esc_html($page->post_content) . '</code><br>';
    echo '<strong>Status:</strong> ' . $page->post_status . '<br>';
    echo '<strong>URL:</strong> <a href="' . get_permalink($page->ID) . '" target="_blank">' . get_permalink($page->ID) . '</a>';
    echo '</div>';
    
    echo '<h3>✅ Changes Applied:</h3>';
    echo '<ul>';
    echo '<li>Shortcode changed from <code>[malisafi_agent_profile]</code> to <code>[malisafi_agent_profile_view]</code></li>';
    echo '<li>Title changed from "My Profile" to "Agent Profile"</li>';
    echo '<li>Page is now public (not part of agent dashboard)</li>';
    echo '</ul>';
    
    echo '<h3>🧪 Testing Instructions:</h3>';
    echo '<ol>';
    echo '<li>Visit any property page on your site</li>';
    echo '<li>Click on the agent\'s name in the property details</li>';
    echo '<li>You should see the full agent profile with:</li>';
    echo '<ul>';
    echo '<li>Agent photo and info</li>';
    echo '<li>Contact buttons (phone, email, WhatsApp)</li>';
    echo '<li>Rate Agent button (if you\'re a client)</li>';
    echo '<li>Agent statistics and bio</li>';
    echo '<li>Current listings</li>';
    echo '<li>Reviews</li>';
    echo '</ul>';
    echo '</ol>';
    
    echo '<p style="margin-top: 30px;"><a href="' . get_permalink($page->ID) . '" target="_blank" style="background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">🔍 Test Agent Profile Page</a></p>';
}

echo '</div></body></html>';
