<?php
/**
 * Analytics Overview Dashboard
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.0
 */

use MalisafiMLS\Analytics\Analytics_Core;
use MalisafiMLS\Analytics\Analytics_Properties;
use MalisafiMLS\Analytics\Analytics_Migration;

if (!defined('ABSPATH')) {
    exit;
}

// Check if tables exist
if (!Analytics_Migration::tables_exist()) {
    ?>
    <div class="wrap" style="margin: 20px;">
        <div class="notice notice-error" style="padding: 30px; border-left: 5px solid #dc3545;">
            <h2 style="margin-top: 0; color: #dc3545;">
                ⚠️ <?php _e('Analytics Tables Not Found', 'malisafi-mls'); ?>
            </h2>
            <p style="font-size: 16px; line-height: 1.6;">
                <strong><?php _e('The analytics system cannot display statistics because the required database tables have not been created yet.', 'malisafi-mls'); ?></strong>
            </p>
            <p style="font-size: 14px; color: #666;">
                <?php _e('This is normal for a first-time setup. Click the button below to automatically create all 9 analytics tables:', 'malisafi-mls'); ?>
            </p>
            <ul style="font-size: 14px; color: #666; margin: 15px 0; padding-left: 25px;">
                <li><?php _e('User Activity Tracking', 'malisafi-mls'); ?> (wp_mf_user_activity)</li>
                <li><?php _e('Property Views Analytics', 'malisafi-mls'); ?> (wp_mf_property_views)</li>
                <li><?php _e('Property Interactions', 'malisafi-mls'); ?> (wp_mf_property_interactions)</li>
                <li><?php _e('Search Analytics', 'malisafi-mls'); ?> (wp_mf_search_analytics)</li>
                <li><?php _e('Submission Funnel', 'malisafi-mls'); ?> (wp_mf_submission_funnel)</li>
                <li><?php _e('Fraud Detection', 'malisafi-mls'); ?> (wp_mf_fraud_detection)</li>
                <li><?php _e('Fraud Reports', 'malisafi-mls'); ?> (wp_mf_fraud_reports)</li>
                <li><?php _e('Revenue Tracking', 'malisafi-mls'); ?> (wp_mf_revenue_tracking)</li>
                <li><?php _e('System Health', 'malisafi-mls'); ?> (wp_mf_system_health)</li>
            </ul>
            <p>
                <button type="button" class="button button-primary button-hero" id="malisafi-create-tables-btn" style="margin-top: 20px; background: #737d5d; border-color: #737d5d; padding: 15px 30px; font-size: 16px; height: auto;">
                    🔧 <?php _e('Create Analytics Tables Now', 'malisafi-mls'); ?>
                </button>
                <span id="table-creation-status" style="margin-left: 15px; font-weight: bold;"></span>
            </p>
        </div>
    </div>
    
    <script>
    document.getElementById('malisafi-create-tables-btn').addEventListener('click', function() {
        const btn = this;
        const statusEl = document.getElementById('table-creation-status');
        
        btn.disabled = true;
        btn.textContent = '⏳ <?php _e('Creating tables...', 'malisafi-mls'); ?>';
        statusEl.textContent = '';
        statusEl.style.color = '#0073aa';
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=malisafi_migrate_analytics&nonce=<?php echo wp_create_nonce('malisafi_nonce'); ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusEl.textContent = '✅ ' + '<?php _e('Success! Reloading page...', 'malisafi-mls'); ?>';
                statusEl.style.color = '#28a745';
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                const errorMsg = data.data?.message || '<?php _e('Unknown error', 'malisafi-mls'); ?>';
                statusEl.textContent = '❌ Error: ' + errorMsg;
                statusEl.style.color = '#dc3545';
                btn.disabled = false;
                btn.textContent = '🔧 <?php _e('Create Analytics Tables Now', 'malisafi-mls'); ?>';
            }
        })
        .catch(error => {
            statusEl.textContent = '❌ Error: ' + error;
            statusEl.style.color = '#dc3545';
            btn.disabled = false;
            btn.textContent = '🔧 <?php _e('Create Analytics Tables Now', 'malisafi-mls'); ?>';
        });
    });
    </script>
    <?php
    return;
}

// Get date range from query params
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('════════════════════════════════════════════');
    error_log('📊 [Analytics Overview] Page Loaded');
    error_log('📅 [Days Filter]: ' . $days);
    error_log('════════════════════════════════════════════');
}

// Get overview stats
$stats = Analytics_Core::get_overview_stats($days);
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('📊 [Overview Stats Retrieved]: ' . print_r($stats, true));
}

$properties_by_role = Analytics_Core::get_properties_by_role($days);
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('👥 [Properties by Role Count]: ' . count($properties_by_role));
}

$login_frequency = Analytics_Core::get_login_frequency($days);
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('🔐 [Login Frequency Count]: ' . count($login_frequency));
}

$submission_funnel = Analytics_Core::get_submission_funnel($days);
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('📝 [Submission Funnel Count]: ' . count($submission_funnel));
}

$activity_trends = Analytics_Core::get_activity_trends($days);
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('📈 [Activity Trends Count]: ' . count($activity_trends));
}

$top_properties = Analytics_Properties::get_top_properties('views', 5);
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('🏆 [Top Properties Count]: ' . count($top_properties));
    error_log('════════════════════════════════════════════');
}
?>

<div class="wrap malisafi-analytics-wrapper">
    <!-- Header -->
    <div class="analytics-header">
        <h1><?php _e('Malisafi Analytics Overview', 'malisafi-mls'); ?></h1>
        <div class="date-range-selector">
            <label for="analytics-date-range"><?php _e('Time Period:', 'malisafi-mls'); ?></label>
            <select id="analytics-date-range" onchange="window.location.href='?page=malisafi-analytics&days=' + this.value">
                <option value="7" <?php selected($days, 7); ?>><?php _e('Last 7 Days', 'malisafi-mls'); ?></option>
                <option value="30" <?php selected($days, 30); ?>><?php _e('Last 30 Days', 'malisafi-mls'); ?></option>
                <option value="90" <?php selected($days, 90); ?>><?php _e('Last 90 Days', 'malisafi-mls'); ?></option>
                <option value="365" <?php selected($days, 365); ?>><?php _e('Last Year', 'malisafi-mls'); ?></option>
            </select>
            
            <button class="button analytics-export-btn" onclick="exportAnalytics()">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Report', 'malisafi-mls'); ?>
            </button>
        </div>
    </div>

    <?php
    // Show info notice if analytics are showing fallback data
    $show_notice = false;
    $notice_messages = [];
    
    if (($stats['active_users'] ?? 0) > 0) {
        // Check if this is from fallback by checking WP_User_Query vs activity table
        global $wpdb;
        $tracked_users = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id)
            FROM {$wpdb->prefix}mf_user_activity
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
        
        if ($tracked_users == 0 || $tracked_users < ($stats['active_users'] ?? 0)) {
            $show_notice = true;
            $notice_messages[] = sprintf(
                __('Showing %d total Malisafi users (no activity tracked yet in the selected period)', 'malisafi-mls'),
                $stats['active_users']
            );
        }
    }
    
    if (($stats['total_views'] ?? 0) == 0) {
        $show_notice = true;
        $notice_messages[] = __('Property views will start tracking when users visit property pages', 'malisafi-mls');
    }
    
    if (($stats['total_inquiries'] ?? 0) == 0) {
        $show_notice = true;
        $notice_messages[] = __('Inquiries will appear when users submit contact forms', 'malisafi-mls');
    }
    
    if ($show_notice):
    ?>
    <div class="notice notice-info" style="margin: 20px 0; padding: 15px; background: #e7f3ff; border-left: 4px solid #0073aa;">
        <p><strong>ℹ️ <?php _e('Analytics Data Collection', 'malisafi-mls'); ?>:</strong></p>
        <ul style="margin: 10px 0 5px 20px; list-style: disc;">
            <?php foreach ($notice_messages as $msg): ?>
                <li><?php echo esc_html($msg); ?></li>
            <?php endforeach; ?>
        </ul>
        <p style="font-size: 12px; margin: 10px 0 0 0; color: #666;">
            <?php _e('Analytics tables track real-time activity. Historical data from before plugin activation is shown where available.', 'malisafi-mls'); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="analytics-stats-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
            </div>
            <div class="stat-card-value"><?php echo number_format($stats['active_users'] ?? 0); ?></div>
            <div class="stat-card-label"><?php _e('Active Users', 'malisafi-mls'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <span class="dashicons dashicons-admin-multisite"></span>
                </div>
            </div>
            <div class="stat-card-value"><?php echo number_format($stats['properties_added'] ?? 0); ?></div>
            <div class="stat-card-label"><?php _e('Properties Added', 'malisafi-mls'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
            </div>
            <div class="stat-card-value"><?php echo number_format($stats['total_views'] ?? 0); ?></div>
            <div class="stat-card-label"><?php _e('Property Views', 'malisafi-mls'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <span class="dashicons dashicons-email"></span>
                </div>
            </div>
            <div class="stat-card-value"><?php echo number_format($stats['total_inquiries'] ?? 0); ?></div>
            <div class="stat-card-label"><?php _e('Inquiries Received', 'malisafi-mls'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <span class="dashicons dashicons-chart-area"></span>
                </div>
            </div>
            <div class="stat-card-value"><?php echo number_format($stats['avg_properties_per_user'] ?? 0, 1); ?></div>
            <div class="stat-card-label"><?php _e('Avg Properties/User', 'malisafi-mls'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
            </div>
            <div class="stat-card-value"><?php echo number_format($stats['funnel_completion_rate'] ?? 0, 1); ?>%</div>
            <div class="stat-card-label"><?php _e('Submission Success Rate', 'malisafi-mls'); ?></div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="analytics-charts-grid">
        <!-- Properties by Role -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h2 class="chart-card-title"><?php _e('Properties by Role', 'malisafi-mls'); ?></h2>
                <p class="chart-card-subtitle"><?php printf(__('Last %d days', 'malisafi-mls'), $days); ?></p>
            </div>
            <div class="chart-canvas-wrapper">
                <canvas id="propertiesByRoleChart"></canvas>
            </div>
        </div>

        <!-- Login Frequency -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h2 class="chart-card-title"><?php _e('Login Activity', 'malisafi-mls'); ?></h2>
                <p class="chart-card-subtitle"><?php printf(__('Last %d days', 'malisafi-mls'), $days); ?></p>
            </div>
            <div class="chart-canvas-wrapper">
                <canvas id="loginFrequencyChart"></canvas>
            </div>
        </div>

        <!-- Activity Trends -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h2 class="chart-card-title"><?php _e('Activity Over Time', 'malisafi-mls'); ?></h2>
                <p class="chart-card-subtitle"><?php printf(__('Last %d days', 'malisafi-mls'), $days); ?></p>
            </div>
            <div class="chart-canvas-wrapper">
                <canvas id="activityTrendsChart"></canvas>
            </div>
        </div>

        <!-- Submission Funnel -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h2 class="chart-card-title"><?php _e('Submission Funnel', 'malisafi-mls'); ?></h2>
                <p class="chart-card-subtitle"><?php _e('Form completion stages', 'malisafi-mls'); ?></p>
            </div>
            <div class="chart-canvas-wrapper">
                <canvas id="submissionFunnelChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Properties Table -->
    <div class="analytics-table-card">
        <h2><?php _e('Top Performing Properties', 'malisafi-mls'); ?></h2>
        <table class="analytics-table">
            <thead>
                <tr>
                    <th><?php _e('Property', 'malisafi-mls'); ?></th>
                    <th><?php _e('Author', 'malisafi-mls'); ?></th>
                    <th><?php _e('Price', 'malisafi-mls'); ?></th>
                    <th><?php _e('Views', 'malisafi-mls'); ?></th>
                    <th><?php _e('Inquiries', 'malisafi-mls'); ?></th>
                    <th><?php _e('Conversion', 'malisafi-mls'); ?></th>
                    <th><?php _e('Status', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($top_properties) : ?>
                    <?php foreach ($top_properties as $property) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($property->post_title); ?></strong></td>
                            <td><?php echo esc_html($property->author_name ?? 'Unknown'); ?></td>
                            <td><?php echo number_format($property->price ?? 0); ?> KES</td>
                            <td><?php echo number_format($property->views_count ?? 0); ?></td>
                            <td><?php echo number_format($property->inquiries_count ?? 0); ?></td>
                            <td><?php echo number_format($property->conversion_rate ?? 0, 1); ?>%</td>
                            <td>
                                <span class="analytics-badge <?php echo $property->status === 'published' ? 'success' : 'warning'; ?>">
                                    <?php echo esc_html($property->status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <?php _e('No properties data available', 'malisafi-mls'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Prepare data for charts
const propertiesByRoleData = <?php echo json_encode($properties_by_role); ?>;
const loginFrequencyData = <?php echo json_encode($login_frequency); ?>;
const submissionFunnelData = <?php echo json_encode($submission_funnel); ?>;
const activityTrendsData = <?php echo json_encode($activity_trends); ?>;

// Initialize charts when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart !== 'undefined') {
        initializeOverviewCharts();
    }
});

function initializeOverviewCharts() {
    // Properties by Role - Pie Chart
    if (propertiesByRoleData && propertiesByRoleData.length > 0) {
        const ctx1 = document.getElementById('propertiesByRoleChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'pie',
                data: {
                    labels: propertiesByRoleData.map(d => d.role.replace('_', ' ')),
                    datasets: [{
                        data: propertiesByRoleData.map(d => d.total_properties),
                        backgroundColor: ['#737d5d', '#9ca88a', '#4a5a3a', '#2d3d1d']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    }

    // Login Frequency - Bar Chart
    if (loginFrequencyData && loginFrequencyData.length > 0) {
        const ctx2 = document.getElementById('loginFrequencyChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: loginFrequencyData.map(d => d.role.replace('_', ' ')),
                    datasets: [{
                        label: 'Total Logins',
                        data: loginFrequencyData.map(d => d.total_logins),
                        backgroundColor: '#737d5d'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    }

    // Activity Trends - Line Chart
    if (activityTrendsData && activityTrendsData.length > 0) {
        const ctx3 = document.getElementById('activityTrendsChart');
        if (ctx3) {
            new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: activityTrendsData.map(d => d.date),
                    datasets: [
                        {
                            label: 'Logins',
                            data: activityTrendsData.map(d => d.logins),
                            borderColor: '#737d5d',
                            tension: 0.1
                        },
                        {
                            label: 'Properties Added',
                            data: activityTrendsData.map(d => d.properties_added),
                            borderColor: '#4a5a3a',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    }

    // Submission Funnel - Bar Chart
    if (submissionFunnelData && submissionFunnelData.length > 0) {
        const ctx4 = document.getElementById('submissionFunnelChart');
        if (ctx4) {
            new Chart(ctx4, {
                type: 'bar',
                data: {
                    labels: submissionFunnelData.map(d => d.step_name.replace('_', ' ')),
                    datasets: [
                        {
                            label: 'Sessions Reached',
                            data: submissionFunnelData.map(d => d.sessions_reached),
                            backgroundColor: '#737d5d'
                        },
                        {
                            label: 'Sessions Completed',
                            data: submissionFunnelData.map(d => d.sessions_completed),
                            backgroundColor: '#4a5a3a'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    }
}

function exportAnalytics() {
    alert('Export functionality coming soon!');
}
</script>
