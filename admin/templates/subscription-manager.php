<?php
/**
 * Enhanced Subscription Management Interface
 * Advanced admin interface with search, filters, bulk actions, and analytics
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_malisafi_settings')) {
    wp_die(__('You do not have permission to access this page.', 'malisafi-mls'));
}

// Initialize subscription manager
require_once MALISAFI_MLS_PATH . 'includes/class-subscription-manager.php';
$manager = Malisafi_Subscription_Manager::get_instance();

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'manage';

// Get statistics
$stats = $manager->get_statistics();

// Handle search/filter parameters
$search_args = array(
    'status' => isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '',
    'plan_type' => isset($_GET['filter_plan']) ? sanitize_text_field($_GET['filter_plan']) : '',
    'user_email' => isset($_GET['search_user']) ? sanitize_text_field($_GET['search_user']) : '',
    'expiring_days' => isset($_GET['expiring_days']) ? intval($_GET['expiring_days']) : 0,
    'page' => isset($_GET['paged']) ? intval($_GET['paged']) : 1,
    'per_page' => 20,
);

$search_results = $manager->search_subscriptions($search_args);
$subscriptions = $search_results['subscriptions'];
$total_pages = $search_results['pages'];

// Get plans for dropdowns
$plans = get_option('malisafi_mls_plans', array());
?>

<div class="wrap malisafi-subscription-manager">
    <h1><?php _e('Advanced Subscription Management', 'malisafi-mls'); ?></h1>
    <p class="description"><?php _e('Complete subscription lifecycle management with advanced search, bulk operations, and analytics.', 'malisafi-mls'); ?></p>
    
    <!-- Statistics Dashboard -->
    <div class="malisafi-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; border-left: 4px solid #46b450; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #46b450; font-size: 14px; text-transform: uppercase;">
                <?php _e('Active Subscriptions', 'malisafi-mls'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #46b450;">
                <?php echo isset($stats['by_status']['active']) ? intval($stats['by_status']['active']->count) : 0; ?>
            </p>
        </div>
        
        <div class="stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #2271b1; font-size: 14px; text-transform: uppercase;">
                <?php _e('Monthly Revenue', 'malisafi-mls'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo Malisafi_Stripe::get_currency_symbol('KES') . number_format($stats['mrr'], 2); ?>
            </p>
        </div>
        
        <div class="stat-card" style="background: #fff; border-left: 4px solid #f39c12; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #f39c12; font-size: 14px; text-transform: uppercase;">
                <?php _e('Expiring (30 days)', 'malisafi-mls'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #f39c12;">
                <?php echo intval($stats['expiring_30_days']); ?>
            </p>
        </div>
        
        <div class="stat-card" style="background: #fff; border-left: 4px solid #737d5d; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #737d5d; font-size: 14px; text-transform: uppercase;">
                <?php _e('New This Month', 'malisafi-mls'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #737d5d;">
                <?php echo intval($stats['new_this_month']); ?>
            </p>
        </div>
        
        <div class="stat-card" style="background: #fff; border-left: 4px solid #d63638; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #d63638; font-size: 14px; text-transform: uppercase;">
                <?php _e('Churn This Month', 'malisafi-mls'); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #d63638;">
                <?php echo intval($stats['churn_this_month']); ?>
            </p>
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <nav class="nav-tab-wrapper wp-clearfix" style="margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=malisafi-subscription-manager&tab=manage'); ?>" 
           class="nav-tab <?php echo $current_tab === 'manage' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-users"></span>
            <?php _e('Manage Subscriptions', 'malisafi-mls'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-subscription-manager&tab=create'); ?>" 
           class="nav-tab <?php echo $current_tab === 'create' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Create New', 'malisafi-mls'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-subscription-manager&tab=bulk'); ?>" 
           class="nav-tab <?php echo $current_tab === 'bulk' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-edit"></span>
            <?php _e('Bulk Operations', 'malisafi-mls'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-subscription-manager&tab=analytics'); ?>" 
           class="nav-tab <?php echo $current_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php _e('Analytics', 'malisafi-mls'); ?>
        </a>
    </nav>
    
    <?php if ($current_tab === 'manage'): ?>
        <!-- MANAGE SUBSCRIPTIONS TAB -->
        <div class="malisafi-section">
            <!-- Advanced Search & Filters -->
            <div class="malisafi-filters" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 4px;">
                <form method="get" action="">
                    <input type="hidden" name="page" value="malisafi-subscription-manager" />
                    <input type="hidden" name="tab" value="manage" />
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <label><?php _e('Status', 'malisafi-mls'); ?></label>
                            <select name="filter_status" class="regular-text">
                                <option value=""><?php _e('All Statuses', 'malisafi-mls'); ?></option>
                                <option value="active" <?php selected($search_args['status'], 'active'); ?>><?php _e('Active', 'malisafi-mls'); ?></option>
                                <option value="canceled" <?php selected($search_args['status'], 'canceled'); ?>><?php _e('Canceled', 'malisafi-mls'); ?></option>
                                <option value="expired" <?php selected($search_args['status'], 'expired'); ?>><?php _e('Expired', 'malisafi-mls'); ?></option>
                                <option value="pending" <?php selected($search_args['status'], 'pending'); ?>><?php _e('Pending', 'malisafi-mls'); ?></option>
                            </select>
                        </div>
                        
                        <div>
                            <label><?php _e('Plan Type', 'malisafi-mls'); ?></label>
                            <select name="filter_plan" class="regular-text">
                                <option value=""><?php _e('All Plans', 'malisafi-mls'); ?></option>
                                <?php foreach ($plans as $key => $plan): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($search_args['plan_type'], $key); ?>>
                                        <?php echo esc_html($plan['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label><?php _e('User Email', 'malisafi-mls'); ?></label>
                            <input type="text" name="search_user" class="regular-text" 
                                   value="<?php echo esc_attr($search_args['user_email']); ?>" 
                                   placeholder="<?php _e('Search by email...', 'malisafi-mls'); ?>" />
                        </div>
                        
                        <div>
                            <label><?php _e('Expiring Within', 'malisafi-mls'); ?></label>
                            <select name="expiring_days" class="regular-text">
                                <option value="0"><?php _e('Any', 'malisafi-mls'); ?></option>
                                <option value="7" <?php selected($search_args['expiring_days'], 7); ?>><?php _e('7 Days', 'malisafi-mls'); ?></option>
                                <option value="14" <?php selected($search_args['expiring_days'], 14); ?>><?php _e('14 Days', 'malisafi-mls'); ?></option>
                                <option value="30" <?php selected($search_args['expiring_days'], 30); ?>><?php _e('30 Days', 'malisafi-mls'); ?></option>
                                <option value="60" <?php selected($search_args['expiring_days'], 60); ?>><?php _e('60 Days', 'malisafi-mls'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-search"></span>
                            <?php _e('Search', 'malisafi-mls'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=malisafi-subscription-manager&tab=manage'); ?>" class="button">
                            <?php _e('Clear Filters', 'malisafi-mls'); ?>
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Subscriptions Table -->
            <form method="post" id="bulk-action-form">
                <?php wp_nonce_field('malisafi_subscription_action'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action" id="bulk-action-selector">
                            <option value=""><?php _e('Bulk Actions', 'malisafi-mls'); ?></option>
                            <option value="extend_30"><?php _e('Extend by 30 Days', 'malisafi-mls'); ?></option>
                            <option value="extend_60"><?php _e('Extend by 60 Days', 'malisafi-mls'); ?></option>
                            <option value="cancel"><?php _e('Cancel Selected', 'malisafi-mls'); ?></option>
                            <option value="export"><?php _e('Export Selected', 'malisafi-mls'); ?></option>
                        </select>
                        <button type="submit" class="button action"><?php _e('Apply', 'malisafi-mls'); ?></button>
                    </div>
                    
                    <div class="tablenav-pages">
                        <?php if ($total_pages > 1): ?>
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'current' => $search_args['page'],
                                'total' => $total_pages,
                            ));
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all" />
                            </td>
                            <th><?php _e('User', 'malisafi-mls'); ?></th>
                            <th><?php _e('Plan', 'malisafi-mls'); ?></th>
                            <th><?php _e('Status', 'malisafi-mls'); ?></th>
                            <th><?php _e('Start Date', 'malisafi-mls'); ?></th>
                            <th><?php _e('End Date', 'malisafi-mls'); ?></th>
                            <th><?php _e('Days Remaining', 'malisafi-mls'); ?></th>
                            <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subscriptions)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <p><?php _e('No subscriptions found matching your criteria.', 'malisafi-mls'); ?></p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subscriptions as $subscription): ?>
                                <?php
                                $days_remaining = 0;
                                if ($subscription->status === 'active' && $subscription->current_period_end) {
                                    $now = new DateTime();
                                    $end = new DateTime($subscription->current_period_end);
                                    $diff = $now->diff($end);
                                    $days_remaining = $diff->days * ($diff->invert ? -1 : 1);
                                }
                                ?>
                                <tr class="subscription-row <?php echo $days_remaining < 0 ? 'expired' : ($days_remaining <= 7 ? 'expiring-soon' : ''); ?>">
                                    <th class="check-column">
                                        <input type="checkbox" name="subscription_ids[]" value="<?php echo $subscription->id; ?>" />
                                    </th>
                                    <td>
                                        <strong><?php echo esc_html($subscription->display_name); ?></strong><br>
                                        <small><?php echo esc_html($subscription->user_email); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        $plan_name = ucwords(str_replace('_', ' ', $subscription->plan_type));
                                        echo esc_html($plan_name); 
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_colors = array(
                                            'active' => '#46b450',
                                            'canceled' => '#d63638',
                                            'expired' => '#999',
                                            'pending' => '#f39c12',
                                        );
                                        $color = $status_colors[$subscription->status] ?? '#999';
                                        ?>
                                        <span class="status-badge" style="background: <?php echo $color; ?>; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                            <?php echo strtoupper($subscription->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($subscription->current_period_start)); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($subscription->current_period_end)); ?></td>
                                    <td>
                                        <?php if ($subscription->status === 'active'): ?>
                                            <span style="color: <?php echo $days_remaining <= 7 ? '#d63638' : ($days_remaining <= 30 ? '#f39c12' : '#46b450'); ?>; font-weight: bold;">
                                                <?php 
                                                if ($days_remaining < 0) {
                                                    echo sprintf(__('Expired %d days ago', 'malisafi-mls'), abs($days_remaining));
                                                } else {
                                                    echo sprintf(__('%d days', 'malisafi-mls'), $days_remaining);
                                                }
                                                ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="<?php echo admin_url('admin.php?page=malisafi-subscription-manager&tab=edit&subscription_id=' . $subscription->id); ?>">
                                                <?php _e('Edit', 'malisafi-mls'); ?>
                                            </a> |
                                            <a href="<?php echo admin_url('admin.php?page=malisafi-subscription-manager&tab=extend&subscription_id=' . $subscription->id); ?>">
                                                <?php _e('Extend', 'malisafi-mls'); ?>
                                            </a> |
                                            <?php if ($subscription->status === 'active'): ?>
                                                <a href="#" class="cancel-subscription" data-id="<?php echo $subscription->id; ?>">
                                                    <?php _e('Cancel', 'malisafi-mls'); ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="#" class="reactivate-subscription" data-id="<?php echo $subscription->id; ?>">
                                                    <?php _e('Reactivate', 'malisafi-mls'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        
    <?php elseif ($current_tab === 'create'): ?>
        <!-- CREATE NEW SUBSCRIPTION TAB -->
        <div class="malisafi-section" style="background: #fff; padding: 30px; margin: 20px 0; border: 1px solid #ddd; border-radius: 4px;">
            <h2><?php _e('Create New Subscription', 'malisafi-mls'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('malisafi_subscription_action'); ?>
                <input type="hidden" name="malisafi_subscription_action" value="create" />
                
                <table class="form-table">
                    <tr>
                        <th><label for="user_id"><?php _e('Select User', 'malisafi-mls'); ?></label></th>
                        <td>
                            <select name="user_id" id="user_id" class="regular-text" required>
                                <option value=""><?php _e('-- Select User --', 'malisafi-mls'); ?></option>
                                <?php
                                $users = get_users(array('orderby' => 'display_name'));
                                foreach ($users as $user):
                                ?>
                                    <option value="<?php echo $user->ID; ?>">
                                        <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_type"><?php _e('Plan Type', 'malisafi-mls'); ?></label></th>
                        <td>
                            <select name="plan_type" id="plan_type" class="regular-text" required>
                                <option value=""><?php _e('-- Select Plan --', 'malisafi-mls'); ?></option>
                                <?php foreach ($plans as $key => $plan): ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($plan['name']); ?>
                                        <?php if ($plan['price'] > 0): ?>
                                            (<?php echo Malisafi_Stripe::get_currency_symbol($plan['currency']) . number_format($plan['price'], 2); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status"><?php _e('Status', 'malisafi-mls'); ?></label></th>
                        <td>
                            <select name="status" id="status" class="regular-text" required>
                                <option value="active"><?php _e('Active', 'malisafi-mls'); ?></option>
                                <option value="pending"><?php _e('Pending', 'malisafi-mls'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="start_date"><?php _e('Start Date', 'malisafi-mls'); ?></label></th>
                        <td>
                            <input type="date" name="start_date" id="start_date" class="regular-text" 
                                   value="<?php echo date('Y-m-d'); ?>" required />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="end_date"><?php _e('End Date', 'malisafi-mls'); ?></label></th>
                        <td>
                            <input type="date" name="end_date" id="end_date" class="regular-text" 
                                   value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" required />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="notes"><?php _e('Notes', 'malisafi-mls'); ?></label></th>
                        <td>
                            <textarea name="notes" id="notes" class="large-text" rows="4" 
                                      placeholder="<?php _e('Optional notes about this subscription...', 'malisafi-mls'); ?>"></textarea>
                            <p class="description"><?php _e('Internal notes for admin reference only', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Create Subscription', 'malisafi-mls'), 'primary large'); ?>
            </form>
        </div>
        
    <?php elseif ($current_tab === 'bulk'): ?>
        <!-- BULK OPERATIONS TAB -->
        <div class="malisafi-section" style="background: #fff; padding: 30px; margin: 20px 0; border: 1px solid #ddd; border-radius: 4px;">
            <h2><?php _e('Bulk Operations', 'malisafi-mls'); ?></h2>
            <p class="description"><?php _e('Perform actions on multiple subscriptions at once.', 'malisafi-mls'); ?></p>
            
            <div class="bulk-operation-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0;">
                <div class="operation-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
                    <h3><?php _e('Bulk Extend', 'malisafi-mls'); ?></h3>
                    <p><?php _e('Extend multiple subscriptions by a specific period', 'malisafi-mls'); ?></p>
                    <button class="button button-primary" onclick="alert('Feature coming soon')">
                        <?php _e('Start Bulk Extend', 'malisafi-mls'); ?>
                    </button>
                </div>
                
                <div class="operation-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
                    <h3><?php _e('Bulk Cancel', 'malisafi-mls'); ?></h3>
                    <p><?php _e('Cancel multiple subscriptions at once', 'malisafi-mls'); ?></p>
                    <button class="button button-secondary" onclick="alert('Feature coming soon')">
                        <?php _e('Start Bulk Cancel', 'malisafi-mls'); ?>
                    </button>
                </div>
                
                <div class="operation-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
                    <h3><?php _e('Bulk Export', 'malisafi-mls'); ?></h3>
                    <p><?php _e('Export subscription data to CSV', 'malisafi-mls'); ?></p>
                    <button class="button button-secondary" onclick="alert('Feature coming soon')">
                        <?php _e('Export to CSV', 'malisafi-mls'); ?>
                    </button>
                </div>
                
                <div class="operation-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
                    <h3><?php _e('Send Reminders', 'malisafi-mls'); ?></h3>
                    <p><?php _e('Email reminders to expiring subscriptions', 'malisafi-mls'); ?></p>
                    <button class="button button-secondary" onclick="alert('Feature coming soon')">
                        <?php _e('Send Reminders', 'malisafi-mls'); ?>
                    </button>
                </div>
            </div>
        </div>
        
    <?php elseif ($current_tab === 'analytics'): ?>
        <!-- ANALYTICS TAB -->
        <div class="malisafi-section" style="background: #fff; padding: 30px; margin: 20px 0; border: 1px solid #ddd; border-radius: 4px;">
            <h2><?php _e('Subscription Analytics', 'malisafi-mls'); ?></h2>
            
            <div class="analytics-section">
                <h3><?php _e('Subscriptions by Plan', 'malisafi-mls'); ?></h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Plan', 'malisafi-mls'); ?></th>
                            <th><?php _e('Active Subscriptions', 'malisafi-mls'); ?></th>
                            <th><?php _e('Monthly Revenue', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_plan'] as $plan_type => $data): ?>
                            <?php
                            $plan = $plans[$plan_type] ?? null;
                            $revenue = $plan ? floatval($plan['price']) * intval($data->count) : 0;
                            ?>
                            <tr>
                                <td><?php echo esc_html($plan ? $plan['name'] : ucwords(str_replace('_', ' ', $plan_type))); ?></td>
                                <td><?php echo intval($data->count); ?></td>
                                <td><?php echo Malisafi_Stripe::get_currency_symbol('KES') . number_format($revenue, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="analytics-section" style="margin-top: 30px;">
                <h3><?php _e('Monthly Trends', 'malisafi-mls'); ?></h3>
                <p><?php _e('Advanced analytics and charts coming soon', 'malisafi-mls'); ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.malisafi-subscription-manager .malisafi-filters label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.subscription-row.expiring-soon {
    background: #fffbf0 !important;
}

.subscription-row.expired {
    background: #f9f9f9 !important;
    opacity: 0.7;
}

.row-actions {
    font-size: 13px;
}

.row-actions a {
    text-decoration: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        $('input[name="subscription_ids[]"]').prop('checked', this.checked);
    });
    
    // Cancel subscription
    $('.cancel-subscription').on('click', function(e) {
        e.preventDefault();
        if (confirm(malisafiSubManager.strings.confirmDelete)) {
            // AJAX call to cancel
        }
    });
});
</script>
