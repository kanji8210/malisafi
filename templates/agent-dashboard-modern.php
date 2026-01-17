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
?>

<div class="malisafi-agent-dashboard-modern">
    <!-- Collapsible Sidebar -->
    <aside class="agent-sidebar" id="agentSidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <span class="brand-icon">🏠</span>
                <span class="brand-text">Malisafi</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                <span class="dashicons dashicons-menu"></span>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="<?php echo esc_url(add_query_arg('section', 'dashboard')); ?>" 
               class="nav-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>"
               data-tooltip="Dashboard">
                <span class="nav-icon dashicons dashicons-dashboard"></span>
                <span class="nav-text"><?php _e('Dashboard', 'malisafi-mls'); ?></span>
            </a>

            <a href="<?php echo esc_url(add_query_arg('section', 'properties')); ?>" 
               class="nav-item <?php echo $current_page === 'properties' ? 'active' : ''; ?>"
               data-tooltip="My Properties">
                <span class="nav-icon dashicons dashicons-admin-home"></span>
                <span class="nav-text"><?php _e('My Properties', 'malisafi-mls'); ?></span>
                <?php if ($total_properties > 0): ?>
                    <span class="nav-badge"><?php echo $total_properties; ?></span>
                <?php endif; ?>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=malisafi-property-edit')); ?>" 
               class="nav-item"
               data-tooltip="Add Property">
                <span class="nav-icon dashicons dashicons-plus-alt"></span>
                <span class="nav-text"><?php _e('Add Property', 'malisafi-mls'); ?></span>
            </a>

            <a href="<?php echo esc_url(add_query_arg('section', 'leads')); ?>" 
               class="nav-item <?php echo $current_page === 'leads' ? 'active' : ''; ?>"
               data-tooltip="Leads">
                <span class="nav-icon dashicons dashicons-email"></span>
                <span class="nav-text"><?php _e('Leads', 'malisafi-mls'); ?></span>
            </a>

            <a href="<?php echo esc_url(add_query_arg('section', 'profile')); ?>" 
               class="nav-item <?php echo $current_page === 'profile' ? 'active' : ''; ?>"
               data-tooltip="My Profile">
                <span class="nav-icon dashicons dashicons-businessman"></span>
                <span class="nav-text"><?php _e('My Profile', 'malisafi-mls'); ?></span>
            </a>

            <div class="sidebar-divider"></div>

            <a href="<?php echo esc_url(add_query_arg('section', 'settings')); ?>" 
               class="nav-item <?php echo $current_page === 'settings' ? 'active' : ''; ?>"
               data-tooltip="Settings">
                <span class="nav-icon dashicons dashicons-admin-settings"></span>
                <span class="nav-text"><?php _e('Settings', 'malisafi-mls'); ?></span>
            </a>

            <a href="<?php echo wp_logout_url(home_url()); ?>" 
               class="nav-item"
               data-tooltip="Logout">
                <span class="nav-icon dashicons dashicons-exit"></span>
                <span class="nav-text"><?php _e('Logout', 'malisafi-mls'); ?></span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo get_avatar($current_user->ID, 40); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo esc_html($current_user->display_name); ?></div>
                    <div class="user-role"><?php _e('Agent', 'malisafi-mls'); ?></div>
                </div>
            </div>
        </div>
    </aside>

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
