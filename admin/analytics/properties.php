<?php
/**
 * Property Analytics Dashboard
 *
 * Detailed property performance metrics and insights
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.0
 */

use MalisafiMLS\Analytics\Analytics_Properties;
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

// Get property analytics
$top_by_views = Analytics_Properties::get_top_properties('views', 10);
$top_by_inquiries = Analytics_Properties::get_top_properties('inquiries', 10);
$top_by_conversion = Analytics_Properties::get_top_properties('conversion', 10);
$geographic_insights = Analytics_Properties::get_geographic_insights();
$traffic_sources = Analytics_Properties::get_traffic_sources($days);
$device_breakdown = Analytics_Properties::get_device_breakdown($days);

// Get conversion metrics
$conversion_metrics = Analytics_Properties::get_conversion_metrics($days);
?>

<div class="wrap">
    <h1><?php _e('Property Performance Analytics', 'malisafi-mls'); ?></h1>
    
    <!-- Date Range Filter -->
    <div class="malisafi-analytics-filter" style="margin-bottom: 20px;">
        <label><?php _e('Time Period:', 'malisafi-mls'); ?></label>
        <select onchange="window.location = '<?php echo admin_url('admin.php?page=malisafi-analytics-properties&days='); ?>' + this.value">
            <option value="7" <?php selected($days, 7); ?>>Last 7 days</option>
            <option value="30" <?php selected($days, 30); ?>>Last 30 days</option>
            <option value="90" <?php selected($days, 90); ?>>Last 90 days</option>
            <option value="365" <?php selected($days, 365); ?>>Last year</option>
        </select>
    </div>

    <!-- Top Properties Tabs -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <div style="border-bottom: 2px solid #e5e7eb; margin-bottom: 20px;">
            <button class="property-tab active" data-tab="views" style="padding: 10px 20px; border: none; background: none; border-bottom: 3px solid #737d5d; font-weight: 600; cursor: pointer;">
                <?php _e('Top by Views', 'malisafi-mls'); ?>
            </button>
            <button class="property-tab" data-tab="inquiries" style="padding: 10px 20px; border: none; background: none; border-bottom: 3px solid transparent; cursor: pointer;">
                <?php _e('Top by Inquiries', 'malisafi-mls'); ?>
            </button>
            <button class="property-tab" data-tab="conversion" style="padding: 10px 20px; border: none; background: none; border-bottom: 3px solid transparent; cursor: pointer;">
                <?php _e('Top by Conversion', 'malisafi-mls'); ?>
            </button>
        </div>

        <!-- Views Tab -->
        <div class="property-tab-content" data-content="views">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px; text-align: left;"><?php _e('Property', 'malisafi-mls'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('Views', 'malisafi-mls'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('Unique Visitors', 'malisafi-mls'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('Avg Time', 'malisafi-mls'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('Engagement', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_by_views)) : ?>
                        <?php foreach ($top_by_views as $property) : 
                            $post = get_post($property->property_id);
                            $engagement = floatval($property->engagement_score ?? 0);
                        ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;">
                                <a href="<?php echo get_edit_post_link($property->property_id); ?>" target="_blank">
                                    <strong><?php echo esc_html($post->post_title); ?></strong>
                                </a>
                                <br><small style="color: #999;"><?php echo get_post_meta($property->property_id, '_malisafi_county', true); ?></small>
                            </td>
                            <td style="padding: 12px; text-align: center; font-weight: 600; color: #3b82f6;">
                                <?php echo number_format($property->view_count ?? 0); ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php echo number_format($property->unique_visitors ?? 0); ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php echo gmdate('i:s', $property->avg_duration ?? 0); ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <div style="width: 100%; height: 20px; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
                                    <div style="width: <?php echo min(100, $engagement); ?>%; height: 100%; background: <?php echo $engagement > 75 ? '#10b981' : '#f59e0b'; ?>;"></div>
                                </div>
                                <small><?php echo number_format($engagement, 0); ?>%</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: #999;">
                                <?php _e('No property data available', 'malisafi-mls'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Inquiries Tab -->
        <div class="property-tab-content" data-content="inquiries" style="display: none;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px; text-align: left;"><?php _e('Property', 'malisafi-mls'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('Inquiries', 'malisafi-mls'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('Views', 'malisafi-mls'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('Conversion Rate', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_by_inquiries)) : ?>
                        <?php foreach ($top_by_inquiries as $property) : 
                            $post = get_post($property->property_id);
                            $conversion = $property->view_count > 0 ? ($property->inquiry_count / $property->view_count * 100) : 0;
                        ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;">
                                <a href="<?php echo get_edit_post_link($property->property_id); ?>" target="_blank">
                                    <strong><?php echo esc_html($post->post_title); ?></strong>
                                </a>
                                <br><small style="color: #999;"><?php echo get_post_meta($property->property_id, '_malisafi_county', true); ?></small>
                            </td>
                            <td style="padding: 12px; text-align: center; font-weight: 600; color: #10b981;">
                                <?php echo number_format($property->inquiry_count ?? 0); ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php echo number_format($property->view_count ?? 0); ?>
                            </td>
                            <td style="padding: 12px; text-align: center; font-weight: 600; color: #737d5d;">
                                <?php echo number_format($conversion, 1); ?>%
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: #999;">
                                <?php _e('No inquiry data available', 'malisafi-mls'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Conversion Tab -->
        <div class="property-tab-content" data-content="conversion" style="display: none;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px; text-align: left;"><?php _e('Property', 'malisafi-mls'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('Conversion Rate', 'malisafi-mls'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('Views', 'malisafi-mls'); ?></th>
                        <th style="padding: 12px; text-align: center;"><?php _e('Inquiries', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_by_conversion)) : ?>
                        <?php foreach ($top_by_conversion as $property) : 
                            $post = get_post($property->property_id);
                            $conversion = floatval($property->conversion_rate ?? 0);
                        ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;">
                                <a href="<?php echo get_edit_post_link($property->property_id); ?>" target="_blank">
                                    <strong><?php echo esc_html($post->post_title); ?></strong>
                                </a>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <span style="background: <?php echo $conversion > 10 ? '#d1fae5' : '#fef3c7'; ?>; color: <?php echo $conversion > 10 ? '#065f46' : '#92400e'; ?>; padding: 6px 16px; border-radius: 12px; font-weight: 600; font-size: 14px;">
                                    <?php echo number_format($conversion, 1); ?>%
                                </span>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php echo number_format($property->view_count ?? 0); ?>
                            </td>
                            <td style="padding: 12px; text-align: center; font-weight: 600; color: #10b981;">
                                <?php echo number_format($property->inquiry_count ?? 0); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" style="padding: 20px; text-align: center; color: #999;">
                                <?php _e('No conversion data available', 'malisafi-mls'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Geographic Performance -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Performance by Location (Kenya Counties)', 'malisafi-mls'); ?></h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
                <canvas id="geoChart" style="height: 350px;"></canvas>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: white;">
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 10px; text-align: left;"><?php _e('County', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: center;"><?php _e('Properties', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: center;"><?php _e('Views', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: center;"><?php _e('Avg Price', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($geographic_insights)) : ?>
                            <?php foreach ($geographic_insights as $geo) : ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 10px;"><strong><?php echo esc_html($geo->location ?? 'Unknown'); ?></strong></td>
                                <td style="padding: 10px; text-align: center;"><?php echo intval($geo->property_count ?? 0); ?></td>
                                <td style="padding: 10px; text-align: center;"><?php echo number_format($geo->total_views ?? 0); ?></td>
                                <td style="padding: 10px; text-align: center;">KES <?php echo number_format($geo->avg_price ?? 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" style="padding: 20px; text-align: center; color: #999;">
                                    <?php _e('No geographic data available', 'malisafi-mls'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Traffic Sources & Device Breakdown -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <div class="malisafi-stat-card">
            <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Traffic Sources', 'malisafi-mls'); ?></h3>
            <canvas id="trafficChart" style="height: 250px;"></canvas>
        </div>

        <div class="malisafi-stat-card">
            <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Device Breakdown', 'malisafi-mls'); ?></h3>
            <canvas id="deviceChart" style="height: 250px;"></canvas>
        </div>
    </div>

    <!-- Conversion Funnel -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('View-to-Inquiry Conversion Timeline', 'malisafi-mls'); ?></h3>
        <div style="height: 300px; position: relative;">
            <canvas id="conversionChart"></canvas>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.property-tab').on('click', function() {
        const tab = $(this).data('tab');
        $('.property-tab').removeClass('active').css('border-bottom-color', 'transparent');
        $(this).addClass('active').css('border-bottom-color', '#737d5d');
        $('.property-tab-content').hide();
        $('[data-content="' + tab + '"]').show();
    });

    // Geographic Chart
    const geoCtx = document.getElementById('geoChart');
    if (geoCtx) {
        new Chart(geoCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(fn($g) => "'" . esc_js($g->location ?? 'Unknown') . "'", array_slice($geographic_insights ?? [], 0, 10))); ?>],
                datasets: [{
                    label: 'Total Views',
                    data: [<?php echo implode(',', array_map(fn($g) => intval($g->total_views ?? 0), array_slice($geographic_insights ?? [], 0, 10))); ?>],
                    backgroundColor: '#737d5d'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { beginAtZero: true } }
            }
        });
    }

    // Traffic Sources Chart
    const trafficCtx = document.getElementById('trafficChart');
    if (trafficCtx) {
        new Chart(trafficCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(fn($t) => "'" . esc_js($t->source ?? 'Direct') . "'", $traffic_sources ?? [])); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(fn($t) => intval($t->visit_count ?? 0), $traffic_sources ?? [])); ?>],
                    backgroundColor: ['#737d5d', '#9ca88a', '#4a5a3a', '#2d3d1d', '#c8d4b8', '#3b82f6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });
    }

    // Device Chart
    const deviceCtx = document.getElementById('deviceChart');
    if (deviceCtx) {
        new Chart(deviceCtx, {
            type: 'pie',
            data: {
                labels: [<?php echo implode(',', array_map(fn($d) => "'" . ucfirst($d->device_type ?? 'unknown') . "'", $device_breakdown ?? [])); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(fn($d) => intval($d->view_count ?? 0), $device_breakdown ?? [])); ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#3b82f6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Conversion Timeline
    const conversionCtx = document.getElementById('conversionChart');
    if (conversionCtx) {
        new Chart(conversionCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    if (!empty($conversion_metrics)) {
                        echo implode(',', array_map(fn($c) => "'" . date('M d', strtotime($c->date)) . "'", $conversion_metrics));
                    }
                ?>],
                datasets: [
                    {
                        label: 'Views',
                        data: [<?php echo implode(',', array_map(fn($c) => intval($c->views ?? 0), $conversion_metrics ?? [])); ?>],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Inquiries',
                        data: [<?php echo implode(',', array_map(fn($c) => intval($c->inquiries ?? 0), $conversion_metrics ?? [])); ?>],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Conversion Rate (%)',
                        data: [<?php echo implode(',', array_map(fn($c) => floatval($c->conversion_rate ?? 0), $conversion_metrics ?? [])); ?>],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { type: 'linear', display: true, position: 'left' },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    }
});
</script>
