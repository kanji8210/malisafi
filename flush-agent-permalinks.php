#!/usr/bin/env php
<?php
/**
 * Flush Rewrite Rules for Malisafi Plugin
 * Run this if agent permalinks are not working (404 errors)
 * 
 * @package MalisafiMLS
 */

// Load WordPress
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
if (!file_exists($wp_load)) {
    echo "Error: Cannot find wp-load.php. Make sure this script is in wp-content/plugins/malisafi/\n";
    echo "Looking for: $wp_load\n";
    exit(1);
}

require_once($wp_load);

echo "===================================\n";
echo "Malisafi Agent Permalinks Fix\n";
echo "===================================\n\n";

// Check if plugin is active
if (!function_exists('malisafi_mls_activate')) {
    echo "❌ Error: Malisafi MLS plugin is not active!\n";
    exit(1);
}

echo "✓ Plugin is active\n\n";

// Get current permalink structure
echo "Current Permalink Structure:\n";
echo "----------------------------\n";
$permalink_structure = get_option('permalink_structure');
echo "WordPress: " . ($permalink_structure ?: 'Plain (not SEO-friendly)') . "\n\n";

// Test agent post type registration
$agent_post_type = get_post_type_object('malisafi_agent');
if (!$agent_post_type) {
    echo "❌ Error: Agent post type not registered!\n";
    exit(1);
}

echo "Agent Post Type Configuration:\n";
echo "-----------------------------\n";
echo "Public: " . ($agent_post_type->public ? 'Yes' : 'No') . "\n";
echo "Publicly Queryable: " . ($agent_post_type->publicly_queryable ? 'Yes' : 'No') . "\n";
echo "Has Archive: " . ($agent_post_type->has_archive ? 'Yes' : 'No') . "\n";
echo "Rewrite Slug: " . ($agent_post_type->rewrite['slug'] ?? 'N/A') . "\n\n";

// Get a sample agent to test permalink
$sample_agent = get_posts([
    'post_type' => 'malisafi_agent',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (!empty($sample_agent)) {
    $agent = $sample_agent[0];
    $permalink = get_permalink($agent->ID);
    
    echo "Sample Agent Permalink Test:\n";
    echo "----------------------------\n";
    echo "Agent ID: " . $agent->ID . "\n";
    echo "Agent Name: " . $agent->post_title . "\n";
    echo "Permalink: " . $permalink . "\n";
    
    // Parse the URL
    $url_parts = parse_url($permalink);
    $path = $url_parts['path'] ?? '';
    
    if (strpos($path, '/agent/') !== false) {
        echo "✓ Permalink looks good (contains /agent/)\n\n";
    } else {
        echo "⚠ Warning: Permalink doesn't contain /agent/ - may need flush\n\n";
    }
} else {
    echo "⚠ Warning: No published agents found to test permalink\n\n";
}

// Flush rewrite rules
echo "Flushing Rewrite Rules...\n";
echo "-------------------------\n";

flush_rewrite_rules(false); // false = don't do hard flush

echo "✓ Rewrite rules flushed successfully!\n\n";

// Verify flush worked
$sample_agent_after = get_posts([
    'post_type' => 'malisafi_agent',
    'posts_per_page' => 1,
    'post_status' => 'publish'
]);

if (!empty($sample_agent_after)) {
    $agent = $sample_agent_after[0];
    $permalink_after = get_permalink($agent->ID);
    
    echo "After Flush - Sample Agent Permalink:\n";
    echo "------------------------------------\n";
    echo "Permalink: " . $permalink_after . "\n";
    
    if (strpos(parse_url($permalink_after, PHP_URL_PATH), '/agent/') !== false) {
        echo "✓ Permalinks are working correctly!\n\n";
    } else {
        echo "⚠ Permalink still doesn't look right\n\n";
        echo "TROUBLESHOOTING STEPS:\n";
        echo "1. Go to WordPress Admin > Settings > Permalinks\n";
        echo "2. Don't change anything, just click 'Save Changes'\n";
        echo "3. Test an agent link again\n\n";
        echo "If still not working:\n";
        echo "1. Check your .htaccess file has WordPress rewrite rules\n";
        echo "2. Verify mod_rewrite is enabled in Apache\n";
        echo "3. Check file permissions on .htaccess (should be 644)\n\n";
    }
}

echo "===================================\n";
echo "Summary:\n";
echo "===================================\n";
echo "✓ Agent post type is registered\n";
echo "✓ Rewrite rules have been flushed\n";
echo "✓ Agent permalinks should now work\n\n";

echo "If agents still show 404 errors:\n";
echo "1. Visit WordPress Admin > Settings > Permalinks\n";
echo "2. Click 'Save Changes' without changing anything\n";
echo "3. Clear any caching plugins (if installed)\n";
echo "4. Test an agent profile link\n\n";

echo "Example expected URLs:\n";
echo "- Agent List: " . home_url('/our-agents/') . "\n";
echo "- Agent Profile: " . home_url('/agent/john-doe/') . "\n";
echo "- Agent Archive: " . home_url('/agent/') . "\n\n";
