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
    <div class="wrap">
        <div class="notice notice-error">
            <p>
                <strong><?php _e('Analytics Tables Not Found', 'malisafi-mls'); ?></strong><br>
                <?php _e('The analytics database tables have not been created yet. Click the button below to create them.', 'malisafi-mls'); ?>
            </p>
            <button type="button" class="button button-primary" id="malisafi-create-tables-btn" style="margin-top: 10px;">
                <?php _e('Create Analytics Tables Now', 'malisafi-mls'); ?>
            </button>
        </div>
    </div>
    
    <script>
    document.getElementById('malisafi-create-tables-btn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.textContent = '<?php _e('Creating tables...', 'malisafi-mls'); ?>';
        
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
                alert('<?php _e('Tables created successfully! Reloading...', 'malisafi-mls'); ?>');
                location.reload();
            } else {
                alert('Error: ' + (data.data?.message || 'Unknown error'));
                btn.disabled = false;
                btn.textContent = '<?php _e('Create Analytics Tables Now', 'malisafi-mls'); ?>';
            }
        })
        .catch(error => {
            alert('Error: ' + error);
            btn.disabled = false;
            btn.textContent = '<?php _e('Create Analytics Tables Now', 'malisafi-mls'); ?>';
        });
    });
    </script>
    <?php
    return;
}

// Get date range from query params
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

// Get overview stats
$stats = Analytics_Core::get_overview_stats($days);
$properties_by_role = Analytics_Core::get_properties_by_role($days);
$login_frequency = Analytics_Core::get_login_frequency($days);
$submission_funnel = Analytics_Core::get_submission_funnel($days);
$activity_trends = Analytics_Core::get_activity_trends($days);
$top_properties = Analytics_Properties::get_top_properties('views', 5);
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
