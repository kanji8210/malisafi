<?php
/**
 * Agent Leads Template
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

$agent_id = isset($agent_id) ? $agent_id : 0;
?>

<div class="wrap malisafi-agent-leads">
    <h1><?php _e('Leads & Inquiries', 'malisafi-mls'); ?></h1>
    
    <div class="leads-stats">
        <div class="stat-box">
            <h3><?php echo count($leads); ?></h3>
            <p><?php _e('Total Leads', 'malisafi-mls'); ?></p>
        </div>
        <div class="stat-box">
            <h3><?php echo count(array_filter($leads, function($l) { return $l->status === 'new'; })); ?></h3>
            <p><?php _e('New', 'malisafi-mls'); ?></p>
        </div>
        <div class="stat-box">
            <h3><?php echo count(array_filter($leads, function($l) { return $l->status === 'contacted'; })); ?></h3>
            <p><?php _e('Contacted', 'malisafi-mls'); ?></p>
        </div>
        <div class="stat-box">
            <h3><?php echo count(array_filter($leads, function($l) { return $l->status === 'closed'; })); ?></h3>
            <p><?php _e('Closed', 'malisafi-mls'); ?></p>
        </div>
    </div>
    
    <?php if (!empty($leads)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date', 'malisafi-mls'); ?></th>
                    <th><?php _e('Name', 'malisafi-mls'); ?></th>
                    <th><?php _e('Contact', 'malisafi-mls'); ?></th>
                    <th><?php _e('Property', 'malisafi-mls'); ?></th>
                    <th><?php _e('Message', 'malisafi-mls'); ?></th>
                    <th><?php _e('Status', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($lead->created_at)); ?></td>
                        <td><strong><?php echo esc_html($lead->name); ?></strong></td>
                        <td>
                            <a href="mailto:<?php echo esc_attr($lead->email); ?>"><?php echo esc_html($lead->email); ?></a><br/>
                            <?php if ($lead->phone): ?>
                                <a href="tel:<?php echo esc_attr($lead->phone); ?>"><?php echo esc_html($lead->phone); ?></a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo get_edit_post_link($lead->property_id); ?>" target="_blank">
                                <?php echo esc_html($lead->property_title); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html(substr($lead->message, 0, 100)) . (strlen($lead->message) > 100 ? '...' : ''); ?></td>
                        <td>
                            <?php
                            $status_labels = array(
                                'new' => '<span style="color: #f0b849;">● ' . __('New', 'malisafi-mls') . '</span>',
                                'contacted' => '<span style="color: #0073aa;">● ' . __('Contacted', 'malisafi-mls') . '</span>',
                                'closed' => '<span style="color: #46b450;">● ' . __('Closed', 'malisafi-mls') . '</span>',
                            );
                            echo isset($status_labels[$lead->status]) ? $status_labels[$lead->status] : $lead->status;
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="notice notice-info">
            <p><?php _e('No leads yet. Keep marketing your properties!', 'malisafi-mls'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.malisafi-agent-leads {
    padding: 20px;
}

.leads-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.stat-box h3 {
    margin: 0;
    font-size: 36px;
    color: #0073aa;
}

.stat-box p {
    margin: 10px 0 0;
    color: #666;
}
</style>
