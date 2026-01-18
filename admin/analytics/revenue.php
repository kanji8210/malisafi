<?php
/**
 * Revenue Analytics Dashboard
 *
 * Detailed revenue tracking and subscription analytics with Stripe integration
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

// Get date range
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

// Get revenue data
$revenue_summary = Analytics_Advanced::get_revenue_summary($days);
$revenue_by_type = Analytics_Advanced::get_revenue_metrics($days);
$subscription_analytics = Analytics_Advanced::get_subscription_analytics();

// Calculate KPIs
$total_revenue = floatval($revenue_summary->total_revenue ?? 0);
$total_refunds = floatval($revenue_summary->total_refunds ?? 0);
$avg_transaction = floatval($revenue_summary->avg_transaction_value ?? 0);
$failed_transactions = intval($revenue_summary->failed_transactions ?? 0);
$success_rate = $total_revenue > 0 ? (($total_revenue / ($total_revenue + $total_refunds)) * 100) : 0;

// Get recent transactions
global $wpdb;
$recent_transactions = $wpdb->get_results($wpdb->prepare("
    SELECT 
        rt.*,
        u.display_name,
        u.user_email
    FROM {$wpdb->prefix}mf_revenue_tracking rt
    INNER JOIN {$wpdb->users} u ON rt.user_id = u.ID
    WHERE rt.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
    ORDER BY rt.created_at DESC
    LIMIT 20
", $days));

// Get subscription counts
$subscription_counts = $wpdb->get_results("
    SELECT 
        plan_type,
        COUNT(*) as count,
        SUM(amount) as total
    FROM {$wpdb->prefix}mf_revenue_tracking
    WHERE transaction_type = 'subscription'
    AND status = 'completed'
    GROUP BY plan_type
    ORDER BY total DESC
");
?>

<div class="wrap">
    <h1><?php _e('Revenue Analytics', 'malisafi-mls'); ?></h1>
    
    <!-- Date Range Filter -->
    <div class="malisafi-analytics-filter" style="margin-bottom: 20px;">
        <label><?php _e('Time Period:', 'malisafi-mls'); ?></label>
        <select onchange="window.location = '<?php echo admin_url('admin.php?page=malisafi-analytics-revenue&days='); ?>' + this.value">
            <option value="7" <?php selected($days, 7); ?>>Last 7 days</option>
            <option value="30" <?php selected($days, 30); ?>>Last 30 days</option>
            <option value="90" <?php selected($days, 90); ?>>Last 90 days</option>
            <option value="365" <?php selected($days, 365); ?>>Last year</option>
        </select>
    </div>

    <!-- KPI Cards -->
    <div class="malisafi-stats-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Total Revenue', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: #10b981;">KES <?php echo number_format($total_revenue, 2); ?></div>
            <div class="stat-change positive">+<?php echo $days; ?>d</div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Total Refunds', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: #ef4444;">KES <?php echo number_format($total_refunds, 2); ?></div>
            <div class="stat-change negative"><?php echo number_format(($total_refunds / max(1, $total_revenue)) * 100, 1); ?>%</div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Avg Transaction', 'malisafi-mls'); ?></div>
            <div class="stat-value">KES <?php echo number_format($avg_transaction, 2); ?></div>
            <div class="stat-change neutral">Per order</div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Success Rate', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: #3b82f6;"><?php echo number_format($success_rate, 1); ?>%</div>
            <div class="stat-change <?php echo $success_rate > 90 ? 'positive' : 'negative'; ?>">
                <?php echo $failed_transactions; ?> failed
            </div>
        </div>
    </div>

    <!-- Revenue by Type -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Revenue by Transaction Type', 'malisafi-mls'); ?></h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div style="height: 300px; position: relative;">
                <canvas id="revenueByTypeChart"></canvas>
            </div>
            <div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 10px; text-align: left;"><?php _e('Type', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: right;"><?php _e('Count', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: right;"><?php _e('Revenue', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: right;"><?php _e('Avg', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($revenue_by_type)) : ?>
                            <?php foreach ($revenue_by_type as $type) : ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 10px;">
                                    <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $type->transaction_type ?? 'unknown'))); ?></strong>
                                </td>
                                <td style="padding: 10px; text-align: right;"><?php echo intval($type->transaction_count ?? 0); ?></td>
                                <td style="padding: 10px; text-align: right; color: #10b981; font-weight: 600;">
                                    KES <?php echo number_format($type->total_revenue ?? 0, 2); ?>
                                </td>
                                <td style="padding: 10px; text-align: right;">
                                    KES <?php echo number_format($type->avg_amount ?? 0, 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" style="padding: 20px; text-align: center; color: #999;">
                                    <?php _e('No revenue data available', 'malisafi-mls'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Subscription Analytics -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Subscription Analytics', 'malisafi-mls'); ?></h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div style="height: 300px; position: relative;">
                <canvas id="subscriptionChart"></canvas>
            </div>
            <div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 10px; text-align: left;"><?php _e('Plan', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: center;"><?php _e('Subscribers', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: right;"><?php _e('MRR', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($subscription_counts)) : 
                            $colors = ['#737d5d', '#9ca88a', '#4a5a3a', '#2d3d1d', '#c8d4b8'];
                            $i = 0;
                            foreach ($subscription_counts as $sub) : ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 10px;">
                                    <span style="display: inline-block; width: 12px; height: 12px; background: <?php echo $colors[$i % count($colors)]; ?>; border-radius: 50%; margin-right: 8px;"></span>
                                    <strong><?php echo esc_html(ucfirst(str_replace(['malisafi_', '_'], ['', ' '], $sub->plan_type ?? 'unknown'))); ?></strong>
                                </td>
                                <td style="padding: 10px; text-align: center;">
                                    <span style="background: #e7f3ff; color: #0c5aa0; padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                                        <?php echo intval($sub->count ?? 0); ?>
                                    </span>
                                </td>
                                <td style="padding: 10px; text-align: right; font-weight: 600; color: #10b981;">
                                    KES <?php echo number_format($sub->total ?? 0, 2); ?>
                                </td>
                            </tr>
                            <?php $i++; endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="3" style="padding: 20px; text-align: center; color: #999;">
                                    <?php _e('No subscription data available', 'malisafi-mls'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Recent Transactions', 'malisafi-mls'); ?></h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left;"><?php _e('Date', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('User', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('Type', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('Plan', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: right;"><?php _e('Amount', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Status', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('Stripe ID', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_transactions)) : ?>
                    <?php foreach ($recent_transactions as $txn) : 
                        $status_colors = [
                            'completed' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                            'pending' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                            'failed' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                            'refunded' => ['bg' => '#e5e7eb', 'text' => '#374151'],
                        ];
                        $status = $txn->status ?? 'pending';
                        $color = $status_colors[$status] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280'];
                    ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px;">
                            <?php echo date('M d, Y H:i', strtotime($txn->created_at)); ?>
                        </td>
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html($txn->display_name); ?></strong>
                            <br><small style="color: #999;"><?php echo esc_html($txn->user_email); ?></small>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $txn->transaction_type))); ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo $txn->plan_type ? esc_html(ucfirst(str_replace(['malisafi_', '_'], ['', ' '], $txn->plan_type))) : '-'; ?>
                        </td>
                        <td style="padding: 12px; text-align: right; font-weight: 600; color: <?php echo $status === 'refunded' ? '#ef4444' : '#10b981'; ?>;">
                            <?php echo $status === 'refunded' ? '-' : ''; ?>KES <?php echo number_format($txn->amount, 2); ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span style="background: <?php echo $color['bg']; ?>; color: <?php echo $color['text']; ?>; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                <?php echo esc_html(ucfirst($status)); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; font-family: monospace; font-size: 12px;">
                            <?php if ($txn->stripe_payment_id) : ?>
                                <a href="https://dashboard.stripe.com/payments/<?php echo esc_attr($txn->stripe_payment_id); ?>" target="_blank" style="color: #3b82f6; text-decoration: none;">
                                    <?php echo esc_html(substr($txn->stripe_payment_id, 0, 20)); ?>...
                                </a>
                            <?php else : ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" style="padding: 20px; text-align: center; color: #999;">
                            <?php _e('No transactions found in this period', 'malisafi-mls'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Payment Method Stats -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Revenue Timeline', 'malisafi-mls'); ?></h3>
        <div style="height: 300px; position: relative;">
            <canvas id="revenueTimelineChart"></canvas>
        </div>
    </div>

</div>

<?php
// Get daily revenue for chart
$daily_revenue = $wpdb->get_results($wpdb->prepare("
    SELECT 
        DATE(created_at) as date,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue,
        SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) as refunds,
        COUNT(*) as transactions
    FROM {$wpdb->prefix}mf_revenue_tracking
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
", $days));
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue by Type Chart
    const revenueByTypeCtx = document.getElementById('revenueByTypeChart');
    if (revenueByTypeCtx) {
        new Chart(revenueByTypeCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php 
                    if (!empty($revenue_by_type)) {
                        echo implode(',', array_map(fn($t) => "'" . ucfirst(str_replace('_', ' ', $t->transaction_type ?? 'unknown')) . "'", $revenue_by_type));
                    }
                ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(fn($t) => floatval($t->total_revenue ?? 0), $revenue_by_type ?? [])); ?>],
                    backgroundColor: ['#737d5d', '#9ca88a', '#4a5a3a', '#2d3d1d', '#c8d4b8', '#3b82f6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': KES ' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Subscription Chart
    const subscriptionCtx = document.getElementById('subscriptionChart');
    if (subscriptionCtx) {
        new Chart(subscriptionCtx, {
            type: 'pie',
            data: {
                labels: [<?php 
                    if (!empty($subscription_counts)) {
                        echo implode(',', array_map(fn($s) => "'" . ucfirst(str_replace(['malisafi_', '_'], ['', ' '], $s->plan_type ?? 'unknown')) . "'", $subscription_counts));
                    }
                ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(fn($s) => intval($s->count ?? 0), $subscription_counts ?? [])); ?>],
                    backgroundColor: ['#737d5d', '#9ca88a', '#4a5a3a', '#2d3d1d', '#c8d4b8']
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

    // Revenue Timeline
    const timelineCtx = document.getElementById('revenueTimelineChart');
    if (timelineCtx) {
        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    if (!empty($daily_revenue)) {
                        echo implode(',', array_map(fn($d) => "'" . date('M d', strtotime($d->date)) . "'", $daily_revenue));
                    }
                ?>],
                datasets: [
                    {
                        label: 'Revenue',
                        data: [<?php echo implode(',', array_map(fn($d) => floatval($d->revenue ?? 0), $daily_revenue ?? [])); ?>],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Refunds',
                        data: [<?php echo implode(',', array_map(fn($d) => floatval($d->refunds ?? 0), $daily_revenue ?? [])); ?>],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': KES ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toLocaleString();
                            }
                        }
                    }
                }
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

.malisafi-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.malisafi-stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-label {
    color: #6b7280;
    font-size: 13px;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.stat-change {
    font-size: 12px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    display: inline-block;
}

.stat-change.positive {
    background: #d1fae5;
    color: #065f46;
}

.stat-change.negative {
    background: #fee2e2;
    color: #991b1b;
}

.stat-change.neutral {
    background: #f3f4f6;
    color: #6b7280;
}

.malisafi-stat-card table tr:hover {
    background: #f9fafb;
}
</style>
