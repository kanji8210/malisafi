<?php
/**
 * Modern Agent Dashboard Template with Collapsible Sidebar
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$is_agent = in_array('malisafi_agent_basic', $current_user->roles) || in_array('malisafi_agent_premium', $current_user->roles);

if (!$is_agent) {
    echo '<div class="malisafi-access-denied"><p>' . __('Access restricted to agents only.', 'malisafi-mls') . '</p></div>';
    return;
}

// Get agent statistics
global $wpdb;
$linked_user_id = $current_user->ID;

// Build property list for this agent (author or linked meta)
$author_ids = get_posts([
    'post_type' => 'malisafi_property',
    'author' => $linked_user_id,
    'post_status' => array('publish', 'pending', 'draft'),
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => true
]);

$meta_ids = get_posts([
    'post_type' => 'malisafi_property',
    'post_status' => array('publish', 'pending', 'draft'),
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => true,
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => '_malisafi_agent_id',
            'value' => $linked_user_id,
            'compare' => '='
        ),
        array(
            'key' => '_property_agent_id',
            'value' => $linked_user_id,
            'compare' => '='
        )
    )
]);

$agent_property_ids = array_values(array_unique(array_merge($author_ids, $meta_ids)));

// Count properties
$total_properties = count($agent_property_ids);

$published = $agent_property_ids ? count(get_posts([
    'post_type' => 'malisafi_property',
    'post_status' => array('publish'),
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => true,
    'post__in' => $agent_property_ids
])) : 0;

$pending = $agent_property_ids ? count(get_posts([
    'post_type' => 'malisafi_property',
    'post_status' => array('pending'),
    'fields' => 'ids',
    'posts_per_page' => -1,
    'no_found_rows' => true,
    'post__in' => $agent_property_ids
])) : 0;

// Get recent properties
$recent_properties = $agent_property_ids ? get_posts([
    'post_type' => 'malisafi_property',
    'post__in' => $agent_property_ids,
    'post_status' => array('publish', 'pending', 'draft'),
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
]) : array();

// Current page
$current_page = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'dashboard';

$logout_url = wp_logout_url(home_url());
?>

<div class="malisafi-agent-dashboard-modern">
    <div class="dashboard-header">
        <a class="button button-secondary" href="<?php echo esc_url($logout_url); ?>">
            <?php _e('Logout', 'malisafi-mls'); ?>
        </a>
    </div>
    <!-- Main Content -->
    <main class="agent-main-content">
        <?php
        switch ($current_page) {
            case 'dashboard':
                include __DIR__ . '/agent-dashboard-home.php';
                break;
            case 'properties':
                include __DIR__ . '/agent-dashboard-properties.php';
                break;
            case 'leads':
                include __DIR__ . '/agent-dashboard-leads.php';
                break;
            case 'profile':
                include __DIR__ . '/agent-dashboard-profile.php';
                break;
            case 'settings':
                include __DIR__ . '/agent-dashboard-settings.php';
                break;
            default:
                include __DIR__ . '/agent-dashboard-home.php';
        }
        ?>
    </main>
</div>
