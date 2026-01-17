<?php
/**
 * User Activity Analytics Dashboard
 *
 * Detailed view of user engagement, logins, submissions, and activity patterns
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.0
 */

use MalisafiMLS\Analytics\Analytics_Core;
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

// Get date range
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

// Get data
$login_frequency = Analytics_Core::get_login_frequency($days);
$top_contributors = Analytics_Core::get_top_contributors('all', 20);
$activity_trends = Analytics_Core::get_activity_trends($days);
$dropoff_points = Analytics_Core::get_dropoff_points($days);
?>

<div class="wrap">
    <h1><?php _e('User Activity Analytics', 'malisafi-mls'); ?></h1>
    
    <!-- Date Range Filter -->
    <div class="malisafi-analytics-filter" style="margin-bottom: 20px;">
        <label><?php _e('Time Period:', 'malisafi-mls'); ?></label>
        <select onchange="window.location = '<?php echo admin_url('admin.php?page=malisafi-analytics-users&days='); ?>' + this.value">
            <option value="7" <?php selected($days, 7); ?>>Last 7 days</option>
            <option value="30" <?php selected($days, 30); ?>>Last 30 days</option>
            <option value="90" <?php selected($days, 90); ?>>Last 90 days</option>
            <option value="365" <?php selected($days, 365); ?>>Last year</option>
        </select>
    </div>

    <!-- Login Frequency Section -->
    <div class="malisafi-stats-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Login Frequency by Role', 'malisafi-mls'); ?></h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 8px; text-align: left;"><?php _e('Role', 'malisafi-mls'); ?></th>
                        <th style="padding: 8px; text-align: center;"><?php _e('Users', 'malisafi-mls'); ?></th>
                        <th style="padding: 8px; text-align: center;"><?php _e('Logins', 'malisafi-mls'); ?></th>
                        <th style="padding: 8px; text-align: center;"><?php _e('Avg/User', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($login_frequency)) : ?>
                        <?php foreach ($login_frequency as $freq) : ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 8px;"><strong><?php echo esc_html(ucfirst(str_replace('malisafi_', '', $freq->role ?? 'unknown'))); ?></strong></td>
                            <td style="padding: 8px; text-align: center;"><?php echo intval($freq->unique_users ?? 0); ?></td>
                            <td style="padding: 8px; text-align: center;"><?php echo intval($freq->total_logins ?? 0); ?></td>
                            <td style="padding: 8px; text-align: center;"><span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px;"><?php echo number_format($freq->avg_logins_per_user ?? 0, 1); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: #999;"><?php _e('No activity data available', 'malisafi-mls'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Activity Trends Chart -->
        <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Daily Activity Trends', 'malisafi-mls'); ?></h3>
            <div style="height: 250px; position: relative;">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Contributors Section -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Top Contributors', 'malisafi-mls'); ?></h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left;"><?php _e('User', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Role', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Properties', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Views', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Inquiries', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Engagement', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($top_contributors)) : ?>
                    <?php foreach ($top_contributors as $contributor) : 
                        $user = get_user_by('ID', $contributor->user_id);
                        $role = !empty($user->roles) ? ucfirst(str_replace('malisafi_', '', $user->roles[0])) : 'Unknown';
                    ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                            <br><small style="color: #999;"><?php echo esc_html($user->user_email); ?></small>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="background: #e7f3ff; color: #0c5aa0; padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?php echo esc_html($role); ?></span>
                        </td>
                        <td style="padding: 12px; text-align: center;"><?php echo intval($contributor->properties ?? 0); ?></td>
                        <td style="padding: 12px; text-align: center;"><?php echo intval($contributor->views ?? 0); ?></td>
                        <td style="padding: 12px; text-align: center;"><?php echo intval($contributor->inquiries ?? 0); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <div style="width: 100%; height: 20px; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
                                <?php 
                                $engagement_score = min(100, intval(($contributor->views ?? 0) / max(1, $contributor->properties ?? 1)));
                                $bar_color = $engagement_score > 75 ? '#10b981' : ($engagement_score > 50 ? '#f59e0b' : '#ef4444');
                                ?>
                                <div style="width: <?php echo $engagement_score; ?>%; height: 100%; background: <?php echo $bar_color; ?>;"></div>
                            </div>
                            <small><?php echo intval($engagement_score); ?>%</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: center; color: #999;"><?php _e('No contributor data available', 'malisafi-mls'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Form Submission Funnel Dropoff -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Form Submission Dropoff Analysis', 'malisafi-mls'); ?></h3>
        <p style="color: #666; font-size: 13px;"><?php _e('Identifies where users abandon the property submission form', 'malisafi-mls'); ?></p>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left;"><?php _e('Stage', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Reached', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Completed', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Dropout', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Completion %', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($dropoff_points)) : ?>
                    <?php foreach ($dropoff_points as $stage) : 
                        $reached = intval($stage->sessions_reached ?? 0);
                        $completed = intval($stage->sessions_completed ?? 0);
                        $dropout = $reached - $completed;
                        $completion_rate = $reached > 0 ? ($completed / $reached * 100) : 0;
                        $color = $completion_rate > 75 ? '#10b981' : ($completion_rate > 50 ? '#f59e0b' : '#ef4444');
                    ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html($stage->step_name ?? 'Unknown'); ?></strong>
                        </td>
                        <td style="padding: 12px; text-align: center;"><?php echo $reached; ?></td>
                        <td style="padding: 12px; text-align: center;"><span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px;"><?php echo $completed; ?></span></td>
                        <td style="padding: 12px; text-align: center;"><span style="background: #ffe5e5; color: #a93f3f; padding: 4px 8px; border-radius: 4px;"><?php echo $dropout; ?></span></td>
                        <td style="padding: 12px; text-align: center;">
                            <div style="width: 100%; height: 20px; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
                                <div style="width: <?php echo number_format($completion_rate, 1); ?>%; height: 100%; background: <?php echo $color; ?>;"></div>
                            </div>
                            <small><?php echo number_format($completion_rate, 1); ?>%</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="padding: 20px; text-align: center; color: #999;"><?php _e('No funnel data available', 'malisafi-mls'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Activity Timeline Chart -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('User Actions Timeline', 'malisafi-mls'); ?></h3>
        <div style="height: 300px; position: relative;">
            <canvas id="timelineChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activity Trends Chart
    const activityCtx = document.getElementById('activityChart');
    if (activityCtx) {
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    if (!empty($activity_trends)) {
                        echo implode(',', array_map(function($trend) {
                            return "'" . date('M d', strtotime($trend->date)) . "'";
                        }, $activity_trends));
                    }
                    ?>
                ],
                datasets: [
                    {
                        label: '<?php _e('Logins', 'malisafi-mls'); ?>',
                        data: [<?php echo implode(',', array_map(fn($t) => $t->logins ?? 0, $activity_trends ?? [])); ?>],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: '<?php _e('Properties Added', 'malisafi-mls'); ?>',
                        data: [<?php echo implode(',', array_map(fn($t) => $t->properties_added ?? 0, $activity_trends ?? [])); ?>],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: '<?php _e('Searches', 'malisafi-mls'); ?>',
                        data: [<?php echo implode(',', array_map(fn($t) => $t->searches ?? 0, $activity_trends ?? [])); ?>],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // Timeline Chart
    const timelineCtx = document.getElementById('timelineChart');
    if (timelineCtx) {
        new Chart(timelineCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php 
                    if (!empty($activity_trends)) {
                        echo implode(',', array_map(function($trend) {
                            return "'" . date('M d', strtotime($trend->date)) . "'";
                        }, $activity_trends));
                    }
                    ?>
                ],
                datasets: [
                    {
                        label: '<?php _e('Active Users', 'malisafi-mls'); ?>',
                        data: [<?php echo implode(',', array_map(fn($t) => $t->active_users ?? 0, $activity_trends ?? [])); ?>],
                        backgroundColor: '#737d5d'
                    },
                    {
                        label: '<?php _e('Properties Edited', 'malisafi-mls'); ?>',
                        data: [<?php echo implode(',', array_map(fn($t) => $t->properties_edited ?? 0, $activity_trends ?? [])); ?>],
                        backgroundColor: '#9ca88a'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'x',
                plugins: { legend: { display: true, position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
});
</script>

<style>
.malisafi-analytics-filter {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.malisafi-analytics-filter label {
    font-weight: 600;
    margin-right: 10px;
}

.malisafi-analytics-filter select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.malisafi-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.malisafi-stat-card table tr:hover {
    background: #f9fafb;
}
</style>
