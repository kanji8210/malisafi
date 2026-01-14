<?php
/**
 * Agent Test Setup Script
 * Creates a test agent user for testing the custom navigation system
 * 
 * Run this from WordPress admin or via command line
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Only allow admins
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

// Create test agent user
$username = 'test_agent_demo';
$email = 'testagent@malisafi-demo.local';
$password = 'TestAgent2026!';

// Check if user already exists
$existing_user = get_user_by('login', $username);

if ($existing_user) {
    echo "<h2>✓ Test Agent Already Exists</h2>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>User ID:</strong> " . $existing_user->ID . "</p>";
    echo "<p><strong>Role:</strong> " . implode(', ', $existing_user->roles) . "</p>";
    
    // Make sure role is agent
    if (!in_array('malisafi_agent_basic', $existing_user->roles)) {
        $existing_user->set_role('malisafi_agent_basic');
        echo "<p style='color: orange;'>⚠ Role updated to malisafi_agent_basic</p>";
    }
} else {
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        echo "<h2 style='color: red;'>✗ Error Creating User</h2>";
        echo "<p>" . $user_id->get_error_message() . "</p>";
        exit;
    }
    
    $user = new WP_User($user_id);
    $user->set_role('malisafi_agent_basic');
    
    // Set display name
    wp_update_user(array(
        'ID' => $user_id,
        'display_name' => 'Demo Agent',
        'first_name' => 'Demo',
        'last_name' => 'Agent',
    ));
    
    echo "<h2 style='color: green;'>✓ Test Agent Created Successfully</h2>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>User ID:</strong> $user_id</p>";
    echo "<p><strong>Role:</strong> malisafi_agent_basic</p>";
}

echo "<hr>";
echo "<h2>📋 Testing Instructions</h2>";
echo "<ol>";
echo "<li><strong>Open a new private/incognito browser window</strong></li>";
echo "<li><strong>Go to:</strong> <a href='" . wp_login_url() . "' target='_blank'>" . wp_login_url() . "</a></li>";
echo "<li><strong>Login with:</strong>";
echo "<ul>";
echo "<li>Username: <code>$username</code></li>";
echo "<li>Password: <code>$password</code></li>";
echo "</ul></li>";
echo "<li><strong>Expected Results:</strong>";
echo "<ul>";
echo "<li>✓ Custom purple navigation bar at top</li>";
echo "<li>✗ NO WordPress admin bar</li>";
echo "<li>✓ Only see: My Dashboard, My Properties, Add Property, My Profile, Leads</li>";
echo "<li>✗ NO default WordPress menus (Posts, Pages, etc.)</li>";
echo "<li>✓ Redirected to agent dashboard (not WP dashboard)</li>";
echo "</ul></li>";
echo "<li><strong>Test Creating Property:</strong>";
echo "<ul>";
echo "<li>Click 'Add Property'</li>";
echo "<li>Fill in property details</li>";
echo "<li>Upload images (should work now!)</li>";
echo "<li>Submit property</li>";
echo "<li>✓ Property should be 'Pending Approval'</li>";
echo "</ul></li>";
echo "<li><strong>Test as Admin:</strong>";
echo "<ul>";
echo "<li>Login as admin in another window</li>";
echo "<li>Go to Properties → Pending</li>";
echo "<li>Approve the property</li>";
echo "<li>✓ Property becomes 'Published'</li>";
echo "</ul></li>";
echo "<li><strong>Test Editing:</strong>";
echo "<ul>";
echo "<li>As agent, edit the published property</li>";
echo "<li>Change title or description</li>";
echo "<li>Save changes</li>";
echo "<li>✓ Property should return to 'Pending Approval'</li>";
echo "</ul></li>";
echo "</ol>";

echo "<hr>";
echo "<h2>🔗 Quick Links</h2>";
echo "<ul>";
echo "<li><a href='" . wp_login_url() . "' target='_blank'>Login Page</a></li>";
echo "<li><a href='" . admin_url('users.php') . "' target='_blank'>Manage Users</a></li>";
echo "<li><a href='" . admin_url('edit.php?post_type=malisafi_property') . "' target='_blank'>All Properties</a></li>";
echo "<li><a href='" . admin_url('edit.php?post_status=pending&post_type=malisafi_property') . "' target='_blank'>Pending Properties</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p style='color: #666; font-size: 12px;'>To delete this test user later, go to Users → All Users → Delete 'test_agent_demo'</p>";

// Add some inline styling
echo "<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; background: #f0f0f1; }
    h2 { color: #1e40af; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
    code { background: #1f2937; color: #10b981; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
    ul { line-height: 1.8; }
    a { color: #2563eb; text-decoration: none; }
    a:hover { text-decoration: underline; }
    hr { margin: 30px 0; border: none; border-top: 1px solid #d1d5db; }
</style>";
