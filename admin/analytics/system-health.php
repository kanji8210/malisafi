<?php
/**
 * System Health Dashboard
 *
 * Performance monitoring, API health, and system status
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.0
 */

use MalisafiMLS\Analytics\Analytics_Advanced;
use MalisafiMLS\Analytics\Analytics_Migration;

if (!defined('ABSPATH')) {
    exit;
}

// Check if tables exist
if (!Analytics_Migration::tables_exist()) {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Analytics tables not found. Please create them first.', 'malisafi-mls'); ?></p>
    </div>
    <?php
    return;
}

// Get time range
$hours = isset($_GET['hours']) ? intval($_GET['hours']) : 24;

// Get system health data
$health_metrics = Analytics_Advanced::get_system_health($hours);

global $wpdb;

// Get error log entries
$error_logs = $wpdb->get_results($wpdb->prepare("
    SELECT 
        metric_type,
        metric_value,
        status,
        created_at
    FROM {$wpdb->prefix}mf_system_health
    WHERE status IN ('warning', 'critical')
    AND created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
    ORDER BY created_at DESC
    LIMIT 50
", $hours));

// Get performance trends
$performance_trends = $wpdb->get_results($wpdb->prepare("
    SELECT 
        DATE(created_at) as date,
        AVG(CASE WHEN metric_type = 'api_response_time' THEN metric_value END) as avg_api_time,
        AVG(CASE WHEN metric_type = 'memory_usage_mb' THEN metric_value END) as avg_memory,
        AVG(CASE WHEN metric_type = 'disk_usage_percent' THEN metric_value END) as avg_disk,
        COUNT(CASE WHEN status = 'critical' THEN 1 END) as critical_count,
        COUNT(CASE WHEN status = 'warning' THEN 1 END) as warning_count
    FROM {$wpdb->prefix}mf_system_health
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
", $hours));

// Current system status
$current_status = $wpdb->get_row("
    SELECT 
        MAX(CASE WHEN metric_type = 'api_response_time' THEN metric_value END) as api_time,
        MAX(CASE WHEN metric_type = 'memory_usage_mb' THEN metric_value END) as memory_mb,
        MAX(CASE WHEN metric_type = 'disk_usage_percent' THEN metric_value END) as disk_percent,
        MAX(created_at) as last_check
    FROM {$wpdb->prefix}mf_system_health
    ORDER BY id DESC
    LIMIT 1
");

// Calculate uptime
$uptime_data = $wpdb->get_row($wpdb->prepare("
    SELECT 
        COUNT(*) as total_checks,
        COUNT(CASE WHEN status = 'ok' THEN 1 END) as successful_checks
    FROM {$wpdb->prefix}mf_system_health
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d HOUR)
", $hours));

$uptime_percentage = $uptime_data && $uptime_data->total_checks > 0 
    ? ($uptime_data->successful_checks / $uptime_data->total_checks * 100) 
    : 100;

// WordPress environment
$wp_version = get_bloginfo('version');
$php_version = phpversion();
$mysql_version = $wpdb->db_version();
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time');
$upload_max_filesize = ini_get('upload_max_filesize');

// Plugin status
$active_plugins = count(get_option('active_plugins', array()));
$total_users = count_users();
$total_properties = wp_count_posts('malisafi_property');
?>

<div class="wrap">
    <h1><?php _e('System Health', 'malisafi-mls'); ?></h1>
    
    <!-- Time Range Filter -->
    <div class="malisafi-analytics-filter" style="margin-bottom: 20px;">
        <label><?php _e('Time Period:', 'malisafi-mls'); ?></label>
        <select onchange="window.location = '<?php echo admin_url('admin.php?page=malisafi-analytics-health&hours='); ?>' + this.value">
            <option value="6" <?php selected($hours, 6); ?>>Last 6 hours</option>
            <option value="24" <?php selected($hours, 24); ?>>Last 24 hours</option>
            <option value="72" <?php selected($hours, 72); ?>>Last 3 days</option>
            <option value="168" <?php selected($hours, 168); ?>>Last week</option>
        </select>
        <button class="button" onclick="location.reload()" style="margin-left: 10px;">
            🔄 <?php _e('Refresh', 'malisafi-mls'); ?>
        </button>
    </div>

    <!-- Status Overview -->
    <div class="malisafi-stats-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('System Uptime', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: <?php echo $uptime_percentage >= 99 ? '#10b981' : ($uptime_percentage >= 95 ? '#f59e0b' : '#ef4444'); ?>;">
                <?php echo number_format($uptime_percentage, 2); ?>%
            </div>
            <div class="stat-change <?php echo $uptime_percentage >= 99 ? 'positive' : 'warning'; ?>">
                <?php echo $hours; ?>h period
            </div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('API Response', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: <?php echo ($current_status->api_time ?? 0) < 200 ? '#10b981' : '#f59e0b'; ?>;">
                <?php echo number_format($current_status->api_time ?? 0); ?> ms
            </div>
            <div class="stat-change neutral">Current</div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Memory Usage', 'malisafi-mls'); ?></div>
            <div class="stat-value"><?php echo number_format($current_status->memory_mb ?? 0); ?> MB</div>
            <div class="stat-change neutral">of <?php echo $memory_limit; ?></div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Disk Usage', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: <?php echo ($current_status->disk_percent ?? 0) > 80 ? '#ef4444' : '#10b981'; ?>;">
                <?php echo number_format($current_status->disk_percent ?? 0, 1); ?>%
            </div>
            <div class="stat-change <?php echo ($current_status->disk_percent ?? 0) > 80 ? 'negative' : 'positive'; ?>">
                Storage
            </div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Critical Issues', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: <?php echo count($error_logs) > 0 ? '#ef4444' : '#10b981'; ?>;">
                <?php echo count(array_filter($error_logs, fn($e) => $e->status === 'critical')); ?>
            </div>
            <div class="stat-change <?php echo count($error_logs) > 0 ? 'negative' : 'positive'; ?>">
                Active
            </div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Warnings', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: #f59e0b;">
                <?php echo count(array_filter($error_logs, fn($e) => $e->status === 'warning')); ?>
            </div>
            <div class="stat-change warning">Active</div>
        </div>
    </div>

    <!-- WordPress Environment -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('WordPress Environment', 'malisafi-mls'); ?></h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div>
                <h4 style="color: #374151; margin: 0 0 15px 0;"><?php _e('Software Versions', 'malisafi-mls'); ?></h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px; font-weight: 600;">WordPress</td>
                        <td style="padding: 8px;"><?php echo esc_html($wp_version); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px; font-weight: 600;">PHP</td>
                        <td style="padding: 8px;"><?php echo esc_html($php_version); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px; font-weight: 600;">MySQL</td>
                        <td style="padding: 8px;"><?php echo esc_html($mysql_version); ?></td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h4 style="color: #374151; margin: 0 0 15px 0;"><?php _e('PHP Configuration', 'malisafi-mls'); ?></h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px; font-weight: 600;">Memory Limit</td>
                        <td style="padding: 8px;"><?php echo esc_html($memory_limit); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px; font-weight: 600;">Max Execution</td>
                        <td style="padding: 8px;"><?php echo esc_html($max_execution_time); ?>s</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px; font-weight: 600;">Upload Max</td>
                        <td style="padding: 8px;"><?php echo esc_html($upload_max_filesize); ?></td>
                    </tr>
                </table>
            </div>
            
            <div>
                <h4 style="color: #374151; margin: 0 0 15px 0;"><?php _e('Site Statistics', 'malisafi-mls'); ?></h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px; font-weight: 600;">Active Plugins</td>
                        <td style="padding: 8px;"><?php echo number_format($active_plugins); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px; font-weight: 600;">Total Users</td>
                        <td style="padding: 8px;"><?php echo number_format($total_users['total_users']); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 8px; font-weight: 600;">Properties</td>
                        <td style="padding: 8px;"><?php echo number_format($total_properties->publish + $total_properties->pending); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Performance Trends -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Performance Trends', 'malisafi-mls'); ?></h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div style="height: 300px; position: relative;">
                <canvas id="performanceChart"></canvas>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="issuesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Issues -->
    <?php if (!empty($error_logs)) : ?>
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #ef4444;">⚠️ <?php _e('Recent Issues & Warnings', 'malisafi-mls'); ?></h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left;"><?php _e('Date/Time', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('Metric', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Value', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Status', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('Recommendation', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($error_logs as $log) : 
                    $recommendations = array(
                        'api_response_time' => 'Optimize database queries or upgrade server',
                        'memory_usage_mb' => 'Increase PHP memory_limit or optimize code',
                        'disk_usage_percent' => 'Clean up old files or upgrade storage',
                        'error_rate' => 'Check error logs and fix critical issues',
                    );
                    $rec = $recommendations[$log->metric_type] ?? 'Review system configuration';
                ?>
                <tr style="border-bottom: 1px solid #e5e7eb; background: <?php echo $log->status === 'critical' ? '#fef2f2' : '#fef3c7'; ?>;">
                    <td style="padding: 12px; font-size: 13px;">
                        <?php echo date('M d, H:i', strtotime($log->created_at)); ?>
                    </td>
                    <td style="padding: 12px;">
                        <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $log->metric_type))); ?></strong>
                    </td>
                    <td style="padding: 12px; text-align: center; font-weight: 600; color: <?php echo $log->status === 'critical' ? '#dc2626' : '#d97706'; ?>;">
                        <?php echo number_format($log->metric_value, 2); ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <span style="background: <?php echo $log->status === 'critical' ? '#fee2e2' : '#fef3c7'; ?>; color: <?php echo $log->status === 'critical' ? '#991b1b' : '#92400e'; ?>; padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 12px; text-transform: uppercase;">
                            <?php echo esc_html($log->status); ?>
                        </span>
                    </td>
                    <td style="padding: 12px; font-size: 13px; color: #666;">
                        <?php echo esc_html($rec); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else : ?>
    <div class="malisafi-stat-card" style="background: #f0fdf4; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px; border-left: 4px solid #10b981;">
        <h3 style="margin-top: 0; color: #10b981;">✓ <?php _e('All Systems Operational', 'malisafi-mls'); ?></h3>
        <p style="margin: 0; color: #065f46;">
            <?php _e('No critical issues or warnings detected in the last', 'malisafi-mls'); ?> <?php echo $hours; ?> <?php _e('hours. System is running smoothly.', 'malisafi-mls'); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Health Monitoring Actions -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('System Actions', 'malisafi-mls'); ?></h3>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <button class="button button-primary" onclick="runHealthCheck()">
                🔍 <?php _e('Run Full Health Check', 'malisafi-mls'); ?>
            </button>
            <button class="button" onclick="clearHealthLogs()">
                🗑️ <?php _e('Clear Old Logs', 'malisafi-mls'); ?>
            </button>
            <a href="<?php echo admin_url('site-health.php'); ?>" class="button">
                🏥 <?php _e('WordPress Site Health', 'malisafi-mls'); ?>
            </a>
            <a href="<?php echo admin_url('tools.php'); ?>" class="button">
                🔧 <?php _e('WordPress Tools', 'malisafi-mls'); ?>
            </a>
        </div>
        <div id="actionResult" style="margin-top: 15px; padding: 15px; border-radius: 6px; display: none;"></div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Performance Trends Chart
    const perfCtx = document.getElementById('performanceChart');
    if (perfCtx) {
        new Chart(perfCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    if (!empty($performance_trends)) {
                        echo implode(',', array_map(fn($t) => "'" . date('M d', strtotime($t->date)) . "'", array_reverse($performance_trends)));
                    }
                ?>],
                datasets: [
                    {
                        label: 'API Response (ms)',
                        data: [<?php echo implode(',', array_map(fn($t) => floatval($t->avg_api_time ?? 0), array_reverse($performance_trends ?? []))); ?>],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Memory (MB)',
                        data: [<?php echo implode(',', array_map(fn($t) => floatval($t->avg_memory ?? 0), array_reverse($performance_trends ?? []))); ?>],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Performance Metrics' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Issues Chart
    const issuesCtx = document.getElementById('issuesChart');
    if (issuesCtx) {
        new Chart(issuesCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    if (!empty($performance_trends)) {
                        echo implode(',', array_map(fn($t) => "'" . date('M d', strtotime($t->date)) . "'", array_reverse($performance_trends)));
                    }
                ?>],
                datasets: [
                    {
                        label: 'Critical',
                        data: [<?php echo implode(',', array_map(fn($t) => intval($t->critical_count ?? 0), array_reverse($performance_trends ?? []))); ?>],
                        backgroundColor: '#ef4444'
                    },
                    {
                        label: 'Warnings',
                        data: [<?php echo implode(',', array_map(fn($t) => intval($t->warning_count ?? 0), array_reverse($performance_trends ?? []))); ?>],
                        backgroundColor: '#f59e0b'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Issues Over Time' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});

function runHealthCheck() {
    const resultDiv = document.getElementById('actionResult');
    resultDiv.style.display = 'block';
    resultDiv.style.background = '#fef3c7';
    resultDiv.style.color = '#92400e';
    resultDiv.innerHTML = '⏳ Running health check...';
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'malisafi_run_health_check',
            nonce: '<?php echo wp_create_nonce('malisafi_health_check'); ?>'
        },
        success: function(response) {
            if (response.success) {
                resultDiv.style.background = '#d1fae5';
                resultDiv.style.color = '#065f46';
                resultDiv.innerHTML = '✓ Health check completed successfully. Refreshing...';
                setTimeout(() => location.reload(), 2000);
            } else {
                resultDiv.style.background = '#fee2e2';
                resultDiv.style.color = '#991b1b';
                resultDiv.innerHTML = '✗ Health check failed: ' + (response.data || 'Unknown error');
            }
        },
        error: function() {
            resultDiv.style.background = '#fee2e2';
            resultDiv.style.color = '#991b1b';
            resultDiv.innerHTML = '✗ Request failed. Please try again.';
        }
    });
}

function clearHealthLogs() {
    if (!confirm('Clear health logs older than 7 days?')) return;
    
    const resultDiv = document.getElementById('actionResult');
    resultDiv.style.display = 'block';
    resultDiv.style.background = '#fef3c7';
    resultDiv.style.color = '#92400e';
    resultDiv.innerHTML = '⏳ Clearing old logs...';
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'malisafi_clear_health_logs',
            nonce: '<?php echo wp_create_nonce('malisafi_clear_logs'); ?>'
        },
        success: function(response) {
            if (response.success) {
                resultDiv.style.background = '#d1fae5';
                resultDiv.style.color = '#065f46';
                resultDiv.innerHTML = '✓ Logs cleared successfully. Refreshing...';
                setTimeout(() => location.reload(), 2000);
            } else {
                resultDiv.style.background = '#fee2e2';
                resultDiv.style.color = '#991b1b';
                resultDiv.innerHTML = '✗ Clear failed: ' + (response.data || 'Unknown error');
            }
        }
    });
}
</script>
