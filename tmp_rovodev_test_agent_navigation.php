<?php
/**
 * Test Script for Agent Navigation and Approval Workflow
 * 
 * This script verifies the custom agent navigation and property approval system
 * Run from WordPress admin or via WP-CLI
 * 
 * Usage: Place in plugin root and access via browser (admin only)
 * Or run: wp eval-file tmp_rovodev_test_agent_navigation.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted');
}

// Only allow admins to run this
if (!current_user_can('manage_options')) {
    die('Administrator access required');
}

echo "<!DOCTYPE html><html><head><title>Agent Navigation Test</title>";
echo "<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; background: #f0f0f1; }
    .test-container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    h1 { color: #1d4ed8; border-bottom: 3px solid #1d4ed8; padding-bottom: 10px; }
    h2 { color: #374151; margin-top: 30px; border-left: 4px solid #60a5fa; padding-left: 15px; }
    .test-section { margin: 20px 0; padding: 20px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb; }
    .success { color: #059669; font-weight: 600; }
    .success:before { content: '✓ '; }
    .error { color: #dc2626; font-weight: 600; }
    .error:before { content: '✗ '; }
    .warning { color: #d97706; font-weight: 600; }
    .warning:before { content: '⚠ '; }
    .info { color: #2563eb; }
    .info:before { content: 'ℹ '; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    th { background: #f3f4f6; font-weight: 600; color: #374151; }
    tr:hover { background: #f9fafb; }
    .code { background: #1f2937; color: #10b981; padding: 15px; border-radius: 6px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-error { background: #fee2e2; color: #991b1b; }
    .badge-warning { background: #fef3c7; color: #92400e; }
</style></head><body>";

echo "<div class='test-container'>";
echo "<h1>🧪 Agent Navigation & Approval Workflow Test Suite</h1>";

// Test 1: Check if files exist
echo "<div class='test-section'>";
echo "<h2>Test 1: File Existence</h2>";
$files_to_check = array(
    'includes/class-agent-navigation.php' => 'Agent Navigation Class',
    'includes/class-property-approval-workflow.php' => 'Property Approval Workflow Class',
    'assets/css/agent-navigation.css' => 'Agent Navigation Styles',
);

foreach ($files_to_check as $file => $name) {
    $path = MALISAFI_MLS_PATH . $file;
    if (file_exists($path)) {
        echo "<p class='success'>$name found</p>";
    } else {
        echo "<p class='error'>$name NOT found at: $path</p>";
    }
}
echo "</div>";

// Test 2: Check if classes are loaded
echo "<div class='test-section'>";
echo "<h2>Test 2: Class Loading</h2>";
$classes_to_check = array(
    'Malisafi_Agent_Navigation' => 'Agent Navigation',
    'Malisafi_Property_Approval_Workflow' => 'Property Approval Workflow',
);

foreach ($classes_to_check as $class => $name) {
    if (class_exists($class)) {
        echo "<p class='success'>$name class loaded</p>";
    } else {
        echo "<p class='error'>$name class NOT loaded</p>";
    }
}
echo "</div>";

// Test 3: Check user roles
echo "<div class='test-section'>";
echo "<h2>Test 3: User Roles Configuration</h2>";
$agent_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer');
echo "<table>";
echo "<tr><th>Role</th><th>Status</th><th>User Count</th></tr>";

foreach ($agent_roles as $role) {
    $role_obj = get_role($role);
    if ($role_obj) {
        $users = get_users(array('role' => $role, 'number' => 1000));
        $count = count($users);
        echo "<tr><td>$role</td><td><span class='badge badge-success'>Exists</span></td><td>$count users</td></tr>";
    } else {
        echo "<tr><td>$role</td><td><span class='badge badge-error'>Missing</span></td><td>0 users</td></tr>";
    }
}
echo "</table>";
echo "</div>";

// Test 4: Check hooks
echo "<div class='test-section'>";
echo "<h2>Test 4: WordPress Hooks Registration</h2>";
global $wp_filter;

$hooks_to_check = array(
    'after_setup_theme' => 'Admin bar hiding',
    'show_admin_bar' => 'Admin bar filter',
    'admin_menu' => 'Admin menu removal',
    'admin_head' => 'Custom navigation rendering',
    'admin_init' => 'Dashboard redirect',
    'save_post_malisafi_property' => 'Property status workflow',
);

foreach ($hooks_to_check as $hook => $description) {
    if (isset($wp_filter[$hook]) && !empty($wp_filter[$hook])) {
        echo "<p class='success'>$hook ($description)</p>";
    } else {
        echo "<p class='warning'>$hook ($description) - No callbacks registered</p>";
    }
}
echo "</div>";

// Test 5: Create test agent user
echo "<div class='test-section'>";
echo "<h2>Test 5: Test Agent User Creation</h2>";

$test_username = 'test_agent_' . time();
$test_email = $test_username . '@malisafi-test.local';

$user_id = wp_create_user($test_username, wp_generate_password(), $test_email);

if (!is_wp_error($user_id)) {
    $user = new WP_User($user_id);
    $user->set_role('malisafi_agent_basic');
    
    echo "<p class='success'>Test agent created successfully</p>";
    echo "<div class='code'>";
    echo "Username: $test_username<br>";
    echo "Email: $test_email<br>";
    echo "User ID: $user_id<br>";
    echo "Role: malisafi_agent_basic";
    echo "</div>";
    
    echo "<p class='info'>You can now test the agent experience:</p>";
    echo "<ol>";
    echo "<li>Open an incognito/private browser window</li>";
    echo "<li>Go to: " . wp_login_url() . "</li>";
    echo "<li>Login with username: <strong>$test_username</strong></li>";
    echo "<li>Use the generated password (check your email or reset it)</li>";
    echo "<li>Verify custom navigation bar appears</li>";
    echo "<li>Verify WordPress admin bar is hidden</li>";
    echo "</ol>";
    
    echo "<p class='warning'>Remember to delete this test user after testing!</p>";
    echo "<p><a href='" . admin_url('users.php') . "' target='_blank'>Go to Users →</a></p>";
} else {
    echo "<p class='error'>Failed to create test user: " . $user_id->get_error_message() . "</p>";
}
echo "</div>";

// Test 6: Property status workflow check
echo "<div class='test-section'>";
echo "<h2>Test 6: Property Status Configuration</h2>";

$allow_premium_auto = get_option('malisafi_allow_premium_auto_publish', false);
echo "<p><strong>Premium Auto-Publish Setting:</strong> ";
if ($allow_premium_auto) {
    echo "<span class='badge badge-warning'>ENABLED</span> (Premium agents can publish directly)";
} else {
    echo "<span class='badge badge-success'>DISABLED</span> (All agents require approval)";
}
echo "</p>";

echo "<p class='info'>To change this setting:</p>";
echo "<div class='code'>";
echo "// Enable premium auto-publish:<br>";
echo "update_option('malisafi_allow_premium_auto_publish', true);<br><br>";
echo "// Disable premium auto-publish (default):<br>";
echo "update_option('malisafi_allow_premium_auto_publish', false);";
echo "</div>";
echo "</div>";

// Test 7: Check recent properties
echo "<div class='test-section'>";
echo "<h2>Test 7: Recent Properties Status</h2>";

$properties = get_posts(array(
    'post_type' => 'malisafi_property',
    'posts_per_page' => 10,
    'post_status' => array('publish', 'pending', 'draft'),
    'orderby' => 'date',
    'order' => 'DESC',
));

if ($properties) {
    echo "<table>";
    echo "<tr><th>Property</th><th>Author</th><th>Status</th><th>Date</th></tr>";
    foreach ($properties as $property) {
        $author = get_userdata($property->post_author);
        $author_name = $author ? $author->display_name : 'Unknown';
        $author_role = $author && !empty($author->roles) ? $author->roles[0] : 'No role';
        
        $status_badge = '';
        if ($property->post_status === 'publish') {
            $status_badge = "<span class='badge badge-success'>Published</span>";
        } elseif ($property->post_status === 'pending') {
            $status_badge = "<span class='badge badge-warning'>Pending</span>";
        } else {
            $status_badge = "<span class='badge'>$property->post_status</span>";
        }
        
        echo "<tr>";
        echo "<td>" . esc_html($property->post_title) . "</td>";
        echo "<td>" . esc_html($author_name) . " <small>($author_role)</small></td>";
        echo "<td>$status_badge</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($property->post_date)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='info'>No properties found. Create a test property to verify the approval workflow.</p>";
}
echo "</div>";

// Test 8: Summary and Next Steps
echo "<div class='test-section'>";
echo "<h2>✅ Test Summary & Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Login as the test agent</strong> created above to verify the custom navigation bar</li>";
echo "<li><strong>Create a property</strong> as the agent and verify it's set to 'Pending'</li>";
echo "<li><strong>Login as admin</strong> and approve the property</li>";
echo "<li><strong>Edit the property</strong> as agent and verify it returns to 'Pending'</li>";
echo "<li><strong>Test on different devices</strong> to verify responsive design</li>";
echo "</ol>";

echo "<h3>Key Features to Verify:</h3>";
echo "<ul>";
echo "<li>✓ Custom purple gradient navigation bar for agents</li>";
echo "<li>✓ No WordPress admin bar for agents</li>";
echo "<li>✓ Only agent-relevant menus visible</li>";
echo "<li>✓ Auto-redirect from wp-admin to agent dashboard</li>";
echo "<li>✓ New properties start as 'Pending'</li>";
echo "<li>✓ Edited properties return to 'Pending'</li>";
echo "<li>✓ Admins/moderators see normal WP admin</li>";
echo "</ul>";

echo "<p class='success'>All tests completed! Review the results above.</p>";
echo "</div>";

echo "</div>"; // Close test-container
echo "</body></html>";
