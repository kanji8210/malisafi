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

// Count properties
$total_properties = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_author = %d",
    $linked_user_id
));

$published = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_author = %d AND post_status = 'publish'",
    $linked_user_id
));

$pending = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_author = %d AND post_status = 'pending'",
    $linked_user_id
));

// Get recent properties
$recent_properties = get_posts([
    'post_type' => 'malisafi_property',
    'author' => $linked_user_id,
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
]);

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
