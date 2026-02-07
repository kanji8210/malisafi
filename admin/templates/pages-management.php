<?php
/**
 * Pages Management Template
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

use MalisafiMLS\Page_Manager;

// Handle actions
if (isset($_POST['malisafi_create_all_pages']) && check_admin_referer('malisafi_create_pages', 'malisafi_pages_nonce')) {
    $created = Page_Manager::create_all_pages();
    $message = sprintf(__('%d pages created successfully!', 'malisafi-mls'), count($created));
    echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
}

if (isset($_POST['malisafi_recreate_page']) && isset($_POST['page_key']) && check_admin_referer('malisafi_recreate_page', 'malisafi_recreate_nonce')) {
    $page_key = sanitize_text_field($_POST['page_key']);
    $page_id = Page_Manager::recreate_page($page_key);
    if ($page_id) {
        echo '<div class="notice notice-success"><p>' . __('Page recreated successfully!', 'malisafi-mls') . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . __('Failed to recreate page.', 'malisafi-mls') . '</p></div>';
    }
}

if (isset($_POST['malisafi_delete_all_pages']) && check_admin_referer('malisafi_delete_pages', 'malisafi_delete_nonce')) {
    Page_Manager::delete_all_pages();
    echo '<div class="notice notice-success"><p>' . __('All plugin pages deleted.', 'malisafi-mls') . '</p></div>';
}

$pages_status = Page_Manager::get_pages_status();
$required_pages = Page_Manager::get_required_pages();
$missing_pages = Page_Manager::get_missing_pages();

// Group pages by category
$page_categories = array(
    'public' => array(
        'title' => __('Public Pages', 'malisafi-mls'),
        'pages' => array('properties', 'property_search', 'featured_properties', 'agents', 'pricing')
    ),
    'client' => array(
        'title' => __('Client Dashboard', 'malisafi-mls'),
        'pages' => array('client_dashboard', 'client_favorites', 'client_searches', 'client_inquiries')
    ),
    'agent' => array(
        'title' => __('Agent Dashboard', 'malisafi-mls'),
        'pages' => array('agent_dashboard', 'agent_properties', 'agent_add_property', 'agent_leads', 'agent_profile')
    ),
    'owner' => array(
        'title' => __('Owner Dashboard', 'malisafi-mls'),
        'pages' => array('owner_dashboard', 'owner_properties', 'owner_add_property', 'owner_inquiries')
    ),
    'agency' => array(
        'title' => __('Agency Dashboard', 'malisafi-mls'),
        'pages' => array('agency_dashboard', 'agency_agents', 'agency_inquiries')
    ),
    'developer' => array(
        'title' => __('Developer Dashboard', 'malisafi-mls'),
        'pages' => array('developer_dashboard', 'developer_projects', 'developer_add_project', 'developer_analytics')
    ),
    'account' => array(
        'title' => __('Account Pages', 'malisafi-mls'),
        'pages' => array('login', 'register', 'account')
    )
);

$total_pages = count($required_pages);
$existing_pages = count(array_filter($pages_status, function($status) { return $status['exists']; }));
$missing_count = $total_pages - $existing_pages;
?>

<div class="wrap malisafi-pages-management">
    <div class="page-header">
        <h1><?php _e('Pages Management', 'malisafi-mls'); ?></h1>
        <a href="<?php echo MALISAFI_MLS_URL; ?>PAGES-SETUP-GUIDE.md" target="_blank" class="button button-secondary">
            <span class="dashicons dashicons-book"></span>
            <?php _e('Setup Guide', 'malisafi-mls'); ?>
        </a>
    </div>
    
    <!-- Info Banner -->
    <div class="malisafi-info-banner">
        <div class="banner-icon">
            <span class="dashicons dashicons-info"></span>
        </div>
        <div class="banner-content">
            <h3><?php _e('What are these pages?', 'malisafi-mls'); ?></h3>
            <p><?php _e('These pages are required for your Malisafi MLS platform to function. Each page uses a shortcode to display specific features like property listings, dashboards, and user accounts.', 'malisafi-mls'); ?></p>
            <p><strong><?php _e('Quick Start:', 'malisafi-mls'); ?></strong> <?php _e('If you see missing pages below, click the "Create All Missing Pages" button to set up everything automatically.', 'malisafi-mls'); ?></p>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="pages-summary">
        <div class="summary-card card-total">
            <div class="card-icon">
                <span class="dashicons dashicons-admin-page"></span>
            </div>
            <div class="card-content">
                <h3><?php echo $total_pages; ?></h3>
                <p><?php _e('Total Pages', 'malisafi-mls'); ?></p>
            </div>
        </div>
        
        <div class="summary-card card-existing">
            <div class="card-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="card-content">
                <h3><?php echo $existing_pages; ?></h3>
                <p><?php _e('Existing', 'malisafi-mls'); ?></p>
            </div>
        </div>
        
        <div class="summary-card card-missing">
            <div class="card-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="card-content">
                <h3><?php echo $missing_count; ?></h3>
                <p><?php _e('Missing', 'malisafi-mls'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <?php if ($missing_count > 0): ?>
        <div class="malisafi-card quick-actions-card">
            <h2><?php _e('Quick Actions', 'malisafi-mls'); ?></h2>
            <p><?php printf(__('You have %d missing pages. Create them now to enable all plugin features.', 'malisafi-mls'), $missing_count); ?></p>
            
            <form method="post" style="display: inline-block;">
                <?php wp_nonce_field('malisafi_create_pages', 'malisafi_pages_nonce'); ?>
                <button type="submit" name="malisafi_create_all_pages" class="button button-primary button-hero">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create All Missing Pages', 'malisafi-mls'); ?>
                </button>
            </form>
            
            <p class="description">
                <?php _e('This will automatically create all missing pages with the appropriate shortcodes.', 'malisafi-mls'); ?>
            </p>
        </div>
    <?php else: ?>
        <div class="notice notice-success">
            <p><strong><?php _e('All pages are set up correctly!', 'malisafi-mls'); ?></strong></p>
        </div>
    <?php endif; ?>
    
    <!-- Pages by Category -->
    <?php foreach ($page_categories as $category_key => $category): ?>
        <div class="malisafi-card pages-category">
            <h2><?php echo esc_html($category['title']); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('Status', 'malisafi-mls'); ?></th>
                        <th><?php _e('Page Title', 'malisafi-mls'); ?></th>
                        <th><?php _e('Shortcode', 'malisafi-mls'); ?></th>
                        <th><?php _e('URL', 'malisafi-mls'); ?></th>
                        <th style="width: 150px;"><?php _e('Actions', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($category['pages'] as $page_key): ?>
                        <?php
                        if (!isset($required_pages[$page_key])) continue;
                        
                        $page = $required_pages[$page_key];
                        $status = $pages_status[$page_key];
                        $exists = $status['exists'];
                        ?>
                        <tr class="<?php echo $exists ? 'page-exists' : 'page-missing'; ?>">
                            <td class="status-cell">
                                <?php if ($exists): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450; font-size: 24px;"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning" style="color: #f0b849; font-size: 24px;"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($page['title']); ?></strong>
                                <?php if ($page['parent'] !== 0 && isset($required_pages[$page['parent']])): ?>
                                    <br/><small style="color: #666;">
                                        <?php _e('Child of:', 'malisafi-mls'); ?> 
                                        <?php echo esc_html($required_pages[$page['parent']]['title']); ?>
                                    </small>
                                <?php endif; ?>
                                <br/><small style="color: #999;"><?php echo esc_html($page['description']); ?></small>
                            </td>
                            <td>
                                <code><?php echo esc_html($page['shortcode']); ?></code>
                            </td>
                            <td>
                                <?php if ($exists): ?>
                                    <a href="<?php echo get_permalink($status['page_id']); ?>" target="_blank">
                                        <?php _e('View', 'malisafi-mls'); ?> <span class="dashicons dashicons-external"></span>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($exists): ?>
                                    <a href="<?php echo get_edit_post_link($status['page_id']); ?>" class="button button-small">
                                        <?php _e('Edit', 'malisafi-mls'); ?>
                                    </a>
                                    <form method="post" style="display: inline-block;">
                                        <?php wp_nonce_field('malisafi_recreate_page', 'malisafi_recreate_nonce'); ?>
                                        <input type="hidden" name="page_key" value="<?php echo esc_attr($page_key); ?>" />
                                        <button type="submit" name="malisafi_recreate_page" class="button button-small" 
                                                onclick="return confirm('<?php _e('This will delete and recreate the page. Continue?', 'malisafi-mls'); ?>');">
                                            <?php _e('Recreate', 'malisafi-mls'); ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" style="display: inline-block;">
                                        <?php wp_nonce_field('malisafi_recreate_page', 'malisafi_recreate_nonce'); ?>
                                        <input type="hidden" name="page_key" value="<?php echo esc_attr($page_key); ?>" />
                                        <button type="submit" name="malisafi_recreate_page" class="button button-primary button-small">
                                            <?php _e('Create', 'malisafi-mls'); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
    
    <!-- Danger Zone -->
    <div class="malisafi-card danger-zone">
        <h2 style="color: #dc3545;"><?php _e('Danger Zone', 'malisafi-mls'); ?></h2>
        <p><?php _e('These actions are irreversible. Use with caution.', 'malisafi-mls'); ?></p>
        
        <form method="post" onsubmit="return confirm('<?php _e('Are you sure you want to delete ALL plugin pages? This cannot be undone!', 'malisafi-mls'); ?>');">
            <?php wp_nonce_field('malisafi_delete_pages', 'malisafi_delete_nonce'); ?>
            <button type="submit" name="malisafi_delete_all_pages" class="button button-link-delete">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Delete All Plugin Pages', 'malisafi-mls'); ?>
            </button>
        </form>
    </div>
    
    <!-- Help Section -->
    <div class="malisafi-card help-section">
        <h2><?php _e('Need Help?', 'malisafi-mls'); ?></h2>
        <ul>
            <li><strong><?php _e('What are these pages?', 'malisafi-mls'); ?></strong><br/>
                <?php _e('These pages are required for the plugin to function properly. Each page displays specific content using shortcodes.', 'malisafi-mls'); ?>
            </li>
            <li><strong><?php _e('Can I customize the pages?', 'malisafi-mls'); ?></strong><br/>
                <?php _e('Yes! You can edit any page content, but keep the shortcode intact for the page to work correctly.', 'malisafi-mls'); ?>
            </li>
            <li><strong><?php _e('What if I delete a page?', 'malisafi-mls'); ?></strong><br/>
                <?php _e('You can recreate it anytime using the "Create" or "Recreate" button.', 'malisafi-mls'); ?>
            </li>
            <li><strong><?php _e('Page hierarchies', 'malisafi-mls'); ?></strong><br/>
                <?php _e('Child pages are organized under their parent pages for better navigation structure.', 'malisafi-mls'); ?>
            </li>
        </ul>
    </div>
</div>

<style>
.malisafi-pages-management {
    max-width: 1400px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.page-header h1 {
    margin: 0;
}

.page-header .button .dashicons {
    margin-right: 5px;
    vertical-align: middle;
}

.malisafi-info-banner {
    background: linear-gradient(135deg, #e7f3ff 0%, #f0f6ff 100%);
    border: 2px solid #0073aa;
    border-radius: 12px;
    padding: 24px;
    margin: 20px 0 30px;
    display: flex;
    gap: 20px;
    align-items: flex-start;
    box-shadow: 0 4px 12px rgba(0, 115, 170, 0.1);
}

.banner-icon {
    font-size: 48px;
    color: #0073aa;
    flex-shrink: 0;
}

.banner-icon .dashicons {
    width: 48px;
    height: 48px;
    font-size: 48px;
}

.banner-content h3 {
    margin: 0 0 10px 0;
    color: #0073aa;
    font-size: 18px;
}

.banner-content p {
    margin: 8px 0;
    color: #1a1a1a;
    line-height: 1.6;
}

.banner-content strong {
    color: #0073aa;
}

.pages-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0 30px;
}

.summary-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card-icon {
    font-size: 48px;
    margin-right: 15px;
}

.card-total .card-icon { color: #0073aa; }
.card-existing .card-icon { color: #46b450; }
.card-missing .card-icon { color: #f0b849; }

.card-content h3 {
    margin: 0;
    font-size: 32px;
    font-weight: bold;
}

.card-content p {
    margin: 5px 0 0;
    color: #666;
}

.malisafi-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.malisafi-card h2 {
    margin-top: 0;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.pages-category {
    margin-bottom: 30px;
}

.page-exists {
    background: #f0f9ff;
}

.page-missing {
    background: #fff9e6;
}

.status-cell {
    text-align: center;
}

.quick-actions-card {
    background: #e7f3ff;
    border-color: #0073aa;
}

.quick-actions-card h2 {
    color: #0073aa;
}

.button-hero .dashicons {
    margin-right: 5px;
    vertical-align: middle;
}

.danger-zone {
    background: #fff0f0;
    border-color: #dc3545;
}

.danger-zone h2 {
    border-bottom-color: #dc3545;
}

.help-section {
    background: #f8f9fa;
}

.help-section ul {
    list-style: none;
    padding: 0;
}

.help-section li {
    padding: 10px 0;
    border-bottom: 1px solid #e2e3e5;
}

.help-section li:last-child {
    border-bottom: none;
}

@media screen and (max-width: 782px) {
    .pages-summary {
        grid-template-columns: 1fr;
    }
}
</style>
