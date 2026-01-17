<?php
/**
 * Fraud Detection Analytics Dashboard
 *
 * Automated fraud detection and suspicious activity monitoring
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

// Get fraud detection data
global $wpdb;

// Get recent fraud alerts
$fraud_alerts = $wpdb->get_results("
    SELECT 
        fd.*,
        u.display_name,
        u.user_email,
        p.post_title as property_title
    FROM {$wpdb->prefix}mf_fraud_detection fd
    LEFT JOIN {$wpdb->users} u ON fd.user_id = u.ID
    LEFT JOIN {$wpdb->posts} p ON fd.property_id = p.ID
    ORDER BY fd.created_at DESC
    LIMIT 50
");

// Get fraud stats by type
$fraud_by_type = $wpdb->get_results("
    SELECT 
        fraud_type,
        COUNT(*) as count,
        AVG(confidence_score) as avg_confidence,
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
    FROM {$wpdb->prefix}mf_fraud_detection
    GROUP BY fraud_type
    ORDER BY count DESC
");

// Get fraud stats by status
$fraud_by_status = $wpdb->get_results("
    SELECT 
        status,
        COUNT(*) as count
    FROM {$wpdb->prefix}mf_fraud_detection
    GROUP BY status
");

// Run fraud detection scan
$duplicate_listings = Analytics_Advanced::detect_duplicate_listings();
$rapid_edits = Analytics_Advanced::detect_rapid_edits(5, 10);
$suspicious_ips = Analytics_Advanced::detect_suspicious_ips(30);

// Calculate KPIs
$total_alerts = count($fraud_alerts);
$pending_review = count(array_filter($fraud_alerts, fn($a) => $a->status === 'pending'));
$confirmed_fraud = count(array_filter($fraud_alerts, fn($a) => $a->status === 'confirmed'));
$false_positives = count(array_filter($fraud_alerts, fn($a) => $a->status === 'false_positive'));
?>

<div class="wrap">
    <h1><?php _e('Fraud Detection & Prevention', 'malisafi-mls'); ?></h1>
    
    <!-- Scan Actions -->
    <div style="margin-bottom: 20px;">
        <button type="button" class="button button-primary" id="run-fraud-scan">
            <?php _e('Run Full Fraud Scan', 'malisafi-mls'); ?>
        </button>
        <button type="button" class="button" id="mark-all-reviewed">
            <?php _e('Mark All as Reviewed', 'malisafi-mls'); ?>
        </button>
    </div>

    <!-- KPI Cards -->
    <div class="malisafi-stats-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Total Alerts', 'malisafi-mls'); ?></div>
            <div class="stat-value"><?php echo $total_alerts; ?></div>
            <div class="stat-change neutral">All time</div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Pending Review', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: #f59e0b;"><?php echo $pending_review; ?></div>
            <div class="stat-change <?php echo $pending_review > 0 ? 'negative' : 'positive'; ?>">
                Needs action
            </div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('Confirmed Fraud', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: #ef4444;"><?php echo $confirmed_fraud; ?></div>
            <div class="stat-change negative">Verified</div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-label"><?php _e('False Positives', 'malisafi-mls'); ?></div>
            <div class="stat-value" style="color: #10b981;"><?php echo $false_positives; ?></div>
            <div class="stat-change positive">Dismissed</div>
        </div>
    </div>

    <!-- Fraud by Type -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Fraud Detection by Type', 'malisafi-mls'); ?></h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div style="height: 300px; position: relative;">
                <canvas id="fraudTypeChart"></canvas>
            </div>
            <div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 10px; text-align: left;"><?php _e('Type', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: center;"><?php _e('Count', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: center;"><?php _e('Confidence', 'malisafi-mls'); ?></th>
                            <th style="padding: 10px; text-align: center;"><?php _e('Confirmed', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($fraud_by_type)) : ?>
                            <?php foreach ($fraud_by_type as $type) : 
                                $confidence_color = $type->avg_confidence > 80 ? '#ef4444' : ($type->avg_confidence > 60 ? '#f59e0b' : '#6b7280');
                            ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 10px;">
                                    <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $type->fraud_type ?? 'unknown'))); ?></strong>
                                </td>
                                <td style="padding: 10px; text-align: center;">
                                    <span style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                                        <?php echo intval($type->count ?? 0); ?>
                                    </span>
                                </td>
                                <td style="padding: 10px; text-align: center;">
                                    <span style="color: <?php echo $confidence_color; ?>; font-weight: 600;">
                                        <?php echo number_format($type->avg_confidence ?? 0, 0); ?>%
                                    </span>
                                </td>
                                <td style="padding: 10px; text-align: center;">
                                    <?php echo intval($type->confirmed ?? 0); ?> / <?php echo intval($type->pending ?? 0); ?> pending
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" style="padding: 20px; text-align: center; color: #999;">
                                    <?php _e('No fraud detected. System is clean!', 'malisafi-mls'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Fraud Alerts -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Recent Fraud Alerts', 'malisafi-mls'); ?></h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left;"><?php _e('Date', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('Type', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('User', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: left;"><?php _e('Property', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Confidence', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Status', 'malisafi-mls'); ?></th>
                    <th style="padding: 12px; text-align: center;"><?php _e('Actions', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($fraud_alerts)) : ?>
                    <?php foreach ($fraud_alerts as $alert) : 
                        $status_colors = [
                            'pending' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                            'reviewed' => ['bg' => '#e7f3ff', 'text' => '#0c5aa0'],
                            'confirmed' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                            'false_positive' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                        ];
                        $status = $alert->status ?? 'pending';
                        $color = $status_colors[$status] ?? ['bg' => '#f3f4f6', 'text' => '#6b7280'];
                        $confidence = intval($alert->confidence_score ?? 0);
                        $confidence_color = $confidence > 80 ? '#ef4444' : ($confidence > 60 ? '#f59e0b' : '#6b7280');
                    ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;" data-alert-id="<?php echo $alert->id; ?>">
                        <td style="padding: 12px; font-size: 13px;">
                            <?php echo date('M d, H:i', strtotime($alert->created_at)); ?>
                        </td>
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $alert->fraud_type))); ?></strong>
                        </td>
                        <td style="padding: 12px;">
                            <?php if ($alert->user_id) : ?>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $alert->user_id); ?>" target="_blank">
                                    <?php echo esc_html($alert->display_name); ?>
                                </a>
                                <br><small style="color: #999;"><?php echo esc_html($alert->user_email); ?></small>
                            <?php else : ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php if ($alert->property_id) : ?>
                                <a href="<?php echo get_edit_post_link($alert->property_id); ?>" target="_blank">
                                    <?php echo esc_html($alert->property_title ?: 'Property #' . $alert->property_id); ?>
                                </a>
                            <?php else : ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <div style="width: 60px; margin: 0 auto;">
                                <div style="width: 100%; height: 6px; background: #f0f0f0; border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?php echo $confidence; ?>%; height: 100%; background: <?php echo $confidence_color; ?>;"></div>
                                </div>
                                <small style="color: <?php echo $confidence_color; ?>; font-weight: 600;"><?php echo $confidence; ?>%</small>
                            </div>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <span class="fraud-status-badge" style="background: <?php echo $color['bg']; ?>; color: <?php echo $color['text']; ?>; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $status))); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if ($status === 'pending') : ?>
                                <button class="button button-small fraud-action" data-action="confirm" data-id="<?php echo $alert->id; ?>" style="background: #ef4444; color: white; border: none;">Confirm</button>
                                <button class="button button-small fraud-action" data-action="dismiss" data-id="<?php echo $alert->id; ?>">Dismiss</button>
                            <?php else : ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" style="padding: 20px; text-align: center; color: #999;">
                            <?php _e('No fraud alerts. System is clean!', 'malisafi-mls'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Fraud Detection Rules -->
    <div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Active Detection Rules', 'malisafi-mls'); ?></h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div style="border: 1px solid #e5e7eb; padding: 15px; border-radius: 6px;">
                <h4 style="margin: 0 0 10px 0; color: #374151;">🔍 Duplicate Listings</h4>
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 10px;">Detects properties with same GPS coordinates or similar addresses</p>
                <div style="background: #f9fafb; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                    GPS Match: 90% confidence<br>
                    Address Soundex: 75% confidence
                </div>
                <p style="margin-top: 10px;"><strong>Detected:</strong> <?php echo count($duplicate_listings); ?> potential duplicates</p>
            </div>

            <div style="border: 1px solid #e5e7eb; padding: 15px; border-radius: 6px;">
                <h4 style="margin: 0 0 10px 0; color: #374151;">⚡ Rapid Edits</h4>
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 10px;">Flags users making excessive property edits in short time</p>
                <div style="background: #f9fafb; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                    Threshold: 5 edits in 10 minutes<br>
                    Confidence: 85%
                </div>
                <p style="margin-top: 10px;"><strong>Detected:</strong> <?php echo count($rapid_edits); ?> suspicious users</p>
            </div>

            <div style="border: 1px solid #e5e7eb; padding: 15px; border-radius: 6px;">
                <h4 style="margin: 0 0 10px 0; color: #374151;">🌐 Suspicious IPs</h4>
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 10px;">Detects multiple accounts from same IP address</p>
                <div style="background: #f9fafb; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                    Users: >5 from same IP<br>
                    Sessions: >10 from same IP
                </div>
                <p style="margin-top: 10px;"><strong>Detected:</strong> <?php echo count($suspicious_ips); ?> suspicious IPs</p>
            </div>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Fraud Type Chart
    const fraudTypeCtx = document.getElementById('fraudTypeChart');
    if (fraudTypeCtx) {
        new Chart(fraudTypeCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    if (!empty($fraud_by_type)) {
                        echo implode(',', array_map(fn($t) => "'" . ucfirst(str_replace('_', ' ', $t->fraud_type ?? 'unknown')) . "'", $fraud_by_type));
                    }
                ?>],
                datasets: [{
                    label: 'Fraud Alerts',
                    data: [<?php echo implode(',', array_map(fn($t) => intval($t->count ?? 0), $fraud_by_type ?? [])); ?>],
                    backgroundColor: '#ef4444'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // Fraud action buttons
    $('.fraud-action').on('click', function() {
        const btn = $(this);
        const action = btn.data('action');
        const alertId = btn.data('id');
        
        btn.prop('disabled', true).text('Processing...');
        
        $.post(ajaxurl, {
            action: 'malisafi_handle_fraud_alert',
            alert_id: alertId,
            fraud_action: action,
            nonce: '<?php echo wp_create_nonce('malisafi_fraud_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                const row = btn.closest('tr');
                const statusBadge = row.find('.fraud-status-badge');
                
                if (action === 'confirm') {
                    statusBadge.css({background: '#fee2e2', color: '#991b1b'}).text('Confirmed');
                    btn.closest('td').html('<span style="color: #999;">-</span>');
                } else if (action === 'dismiss') {
                    statusBadge.css({background: '#d1fae5', color: '#065f46'}).text('False Positive');
                    btn.closest('td').html('<span style="color: #999;">-</span>');
                }
            } else {
                alert('Error: ' + (response.data?.message || 'Unknown error'));
                btn.prop('disabled', false).text(action === 'confirm' ? 'Confirm' : 'Dismiss');
            }
        });
    });

    // Run fraud scan
    $('#run-fraud-scan').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).text('Scanning...');
        
        $.post(ajaxurl, {
            action: 'malisafi_run_fraud_scan',
            nonce: '<?php echo wp_create_nonce('malisafi_fraud_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Fraud scan completed!\n\nNew alerts: ' + (response.data?.new_alerts || 0));
                location.reload();
            } else {
                alert('Error running scan');
                btn.prop('disabled', false).text('Run Full Fraud Scan');
            }
        });
    });
});
</script>
