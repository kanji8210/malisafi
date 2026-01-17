<?php
/**
 * Search Analytics Dashboard
 *
 * Search behavior, popular filters, and zero-result analysis
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

// Get search analytics
$search_analytics = Analytics_Properties::get_search_analytics($days);
$popular_filters = Analytics_Properties::get_popular_filters($days);

// Get additional search data
global $wpdb;

// Zero-result searches
$zero_result_searches = $wpdb->get_results($wpdb->prepare("
    SELECT 
        search_query,
        filters_used,
        COUNT(*) as frequency,
        DATE(created_at) as last_searched
    FROM {$wpdb->prefix}mf_search_analytics
    WHERE zero_results = 1
    AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
    GROUP BY search_query, filters_used
    ORDER BY frequency DESC
    LIMIT 20
", $days));

// Search queries (successful)
$top_searches = $wpdb->get_results($wpdb->prepare("
    SELECT 
        search_query,
        COUNT(*) as search_count,
        AVG(results_count) as avg_results,
        AVG(results_viewed) as avg_viewed,
        COUNT(CASE WHEN first_result_clicked IS NOT NULL THEN 1 END) as click_count
    FROM {$wpdb->prefix}mf_search_analytics
    WHERE search_query IS NOT NULL
    AND search_query != ''
    AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
    GROUP BY search_query
    ORDER BY search_count DESC
    LIMIT 20
", $days));

// Search by type
$search_by_type = $wpdb->get_results($wpdb->prepare("
    SELECT 
        search_type,
        COUNT(*) as count,
        AVG(results_count) as avg_results
    FROM {$wpdb->prefix}mf_search_analytics
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
    GROUP BY search_type
", $days));

// Calculate KPIs
$total_searches = array_sum(array_map(fn($s) => $s->count, $search_by_type));
$success_rate = $search_analytics->success_rate ?? 0;
$zero_result_count = count($zero_result_searches);
$avg_results = $search_analytics->avg_results_per_search ?? 0;
?>

<div class="wrap">
    <h1><?php _e('Search Analytics', 'malisafi-mls'); ?></h1>
    
    <!-- Date Range Filter -->
    <div class="malisafi-analytics-filter" style="margin-bottom: 20px;">
        <label><?php _e('Time Period:', 'malisafi-mls'); ?></label>
        <select onchange="window.location = '<?php echo admin_url('admin.php?page=malisafi-analytics-searches&days='); ?>' + this.value">
            <option value="7" <?php selected($days, 7); ?>>Last 7 days</option>
            <option value="30" <?php selected($days, 30); ?>>Last 30 days</option>
            <option value="90" <?php selected($days, 90); ?>>Last 90 days</option>
            <option value="365" <?php selected($days, 365); ?>>Last year</option>
        </select>
    </div>

    <!-- KPI Cards -->
    <div class="malisafi-stats-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Total Searches', 'malisafi-mls'); ?></div>
            <div class="stat-value"><?php echo number_format($total_searches); ?></div>
            <div class="stat-change neutral"><?php echo $days; ?>d</div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Success Rate', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: <?php echo $success_rate > 80 ? '#10b981' : '#f59e0b'; ?>;">
                <?php echo number_format($success_rate, 1); ?>%
            </div>
            <div class="stat-change <?php echo $success_rate > 80 ? 'positive' : 'warning'; ?>">
                Found results
            </div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Zero Results', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: #ef4444;"><?php echo $zero_result_count; ?></div>
            <div class="stat-change <?php echo $zero_result_count > 10 ? 'negative' : 'positive'; ?>">
                Needs attention
            </div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Avg Results', 'malisafi-mls'); ?></div>
            <div class="stat-value"><?php echo number_format($avg_results, 1); ?></div>
            <div class="stat-change neutral">Per search</div>
        </div>
    </div>

    <!-- Search by Type -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Search Types Distribution', 'malisafi-mls'); ?></h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div style="height: 250px; position: relative;">
                <canvas id="searchTypeChart"></canvas>
            </div>
            <div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 10px; text-align: left;"><?php _e('Search Type', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: center;"><?php _e('Count', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: center;"><?php _e('Avg Results', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($search_by_type)) : ?>
                            <?php foreach ($search_by_type as $type) : ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 10px;">
                                    <strong><?php echo esc_html(ucfirst($type->search_type ?? 'unknown')); ?></strong>
                                </td>
                                <td style="padding: 10px; text-align: center; font-weight: 600;">
                                    <?php echo number_format($type->count ?? 0); ?>
                                </td>
                                <td style="padding: 10px; text-align: center;">
                                    <?php echo number_format($type->avg_results ?? 0, 1); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="3" style="padding: 20px; text-align: center; color: #999;">
                                    <?php _e('No search data available', 'malisafi-mls'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Popular Search Queries -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Top Search Queries', 'malisafi-mls'); ?></h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left;"><?php _e('Query', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Searches', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Avg Results', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Viewed', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Clicks', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('CTR', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($top_searches)) : ?>
                    <?php foreach ($top_searches as $search) : 
                        $ctr = $search->search_count > 0 ? ($search->click_count / $search->search_count * 100) : 0;
                    ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html($search->search_query); ?></strong>
                        </td>
                        <td style="padding: 12px; text-align: center; font-weight: 600; color: #3b82f6;">
                            <?php echo number_format($search->search_count); ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php echo number_format($search->avg_results, 1); ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php echo number_format($search->avg_viewed, 1); ?>
                        </td>
                        <td style="padding: 12px; text-align: center; color: #10b981; font-weight: 600;">
                            <?php echo number_format($search->click_count); ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="background: <?php echo $ctr > 50 ? '#d1fae5' : '#fef3c7'; ?>; color: <?php echo $ctr > 50 ? '#065f46' : '#92400e'; ?>; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                <?php echo number_format($ctr, 1); ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: center; color: #999;">
                            <?php _e('No search queries available', 'malisafi-mls'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Popular Filters -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Most Used Filter Combinations', 'malisafi-mls'); ?></h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
            <?php if (!empty($popular_filters)) : ?>
                <?php foreach (array_slice($popular_filters, 0, 6) as $filter) : 
                    $filters = json_decode($filter->filters_used ?? '{}', true);
                ?>
                <div style="border: 1px solid #e5e7eb; padding: 15px; border-radius: 8px; background: #f9fafb;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-weight: 600; color: #374151;">Filter Set</span>
                        <span style="background: #737d5d; color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                            <?php echo number_format($filter->usage_count ?? 0); ?> uses
                        </span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                        <?php if (!empty($filters)) : ?>
                            <?php foreach ($filters as $key => $value) : ?>
                                <span style="background: white; border: 1px solid #e5e7eb; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <strong><?php echo esc_html(ucfirst($key)); ?>:</strong> <?php echo esc_html($value); ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <span style="color: #999; font-size: 13px;">No filters</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p style="color: #999; text-align: center; grid-column: 1 / -1;"><?php _e('No filter data available', 'malisafi-mls'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Zero-Result Searches -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #ef4444;">⚠️ <?php _e('Zero-Result Searches (Needs Attention)', 'malisafi-mls'); ?></h3>
        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
            <?php _e('These searches returned no results. Consider adding content or improving search relevance.', 'malisafi-mls'); ?>
        </p>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left;"><?php _e('Query', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('Filters Used', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Frequency', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('Last Searched', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Action', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($zero_result_searches)) : ?>
                    <?php foreach ($zero_result_searches as $zero) : 
                        $filters = json_decode($zero->filters_used ?? '{}', true);
                    ?>
                    <tr style="border-bottom: 1px solid #e5e7eb; background: #fef2f2;">
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html($zero->search_query ?: 'Empty query'); ?></strong>
                        </td>
                        <td style="padding: 12px;">
                            <?php if (!empty($filters)) : ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    <?php foreach (array_slice($filters, 0, 3) as $key => $value) : ?>
                                        <span style="background: white; border: 1px solid #e5e7eb; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                            <?php echo esc_html(ucfirst($key) . ': ' . $value); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (count($filters) > 3) : ?>
                                        <span style="color: #999; font-size: 11px;">+<?php echo count($filters) - 3; ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php else : ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 13px;">
                                <?php echo number_format($zero->frequency); ?>x
                            </span>
                        </td>
                        <td style="padding: 12px; font-size: 13px;">
                            <?php echo date('M d, Y', strtotime($zero->last_searched)); ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <button class="button button-small" onclick="alert('Consider:\n1. Adding properties matching: <?php echo esc_js($zero->search_query); ?>\n2. Improving search algorithm\n3. Adding synonyms/related terms')">
                                💡 Suggestions
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="padding: 20px; text-align: center; color: #10b981;">
                            ✓ <?php _e('Great! All searches are returning results.', 'malisafi-mls'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Search Type Chart
    const searchTypeCtx = document.getElementById('searchTypeChart');
    if (searchTypeCtx) {
        new Chart(searchTypeCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    if (!empty($search_by_type)) {
                        echo implode(',', array_map(fn($t) => "'" . ucfirst($t->search_type ?? 'unknown') . "'", $search_by_type));
                    }
                ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(fn($t) => intval($t->count ?? 0), $search_by_type ?? [])); ?>],
                    backgroundColor: ['#737d5d', '#9ca88a', '#4a5a3a', '#2d3d1d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    }
});
</script>
