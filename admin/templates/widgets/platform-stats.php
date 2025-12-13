<?php
/**
 * Platform Stats Widget Template
 *
 * @package MalisafiMLS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// $stats variable is passed from the widget class
?>

<div class="malisafi-platform-stats-widget">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?php echo esc_html($stats['total_properties'] ?: 0); ?></span>
                <span class="stat-label"><?php _e('Total Properties', 'malisafi-mls'); ?></span>
            </div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?php echo esc_html($stats['pending_moderation'] ?: 0); ?></span>
                <span class="stat-label"><?php _e('Pending Review', 'malisafi-mls'); ?></span>
            </div>
        </div>
        
        <div class="stat-card agents">
            <div class="stat-icon">
                <span class="dashicons dashicons-businessperson"></span>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?php echo esc_html($stats['active_agents'] ?: 0); ?></span>
                <span class="stat-label"><?php _e('Active Agents', 'malisafi-mls'); ?></span>
            </div>
        </div>
        
        <div class="stat-card inquiries">
            <div class="stat-icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?php echo esc_html($stats['total_inquiries'] ?: 0); ?></span>
                <span class="stat-label"><?php _e('Today\'s Inquiries', 'malisafi-mls'); ?></span>
            </div>
        </div>
    </div>
    
    <div class="widget-footer">
        <a href="<?php echo admin_url('admin.php?page=malisafi-dashboard'); ?>" class="button button-primary">
            <?php _e('View Full Dashboard', 'malisafi-mls'); ?>
        </a>
        <?php if ($stats['pending_moderation'] > 0) : ?>
        <a href="<?php echo admin_url('admin.php?page=malisafi-moderation'); ?>" class="button">
            <?php _e('Review Pending', 'malisafi-mls'); ?>
        </a>
        <?php endif; ?>
    </div>
</div>

<style>
.malisafi-platform-stats-widget {
    margin: -12px -12px 0;
}

.malisafi-platform-stats-widget .stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    padding: 12px;
}

.malisafi-platform-stats-widget .stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f6f7f7;
    border-radius: 4px;
    border-left: 3px solid #2271b1;
}

.malisafi-platform-stats-widget .stat-card.pending {
    border-left-color: #dba617;
}

.malisafi-platform-stats-widget .stat-card.agents {
    border-left-color: #00a32a;
}

.malisafi-platform-stats-widget .stat-card.inquiries {
    border-left-color: #8c8f94;
}

.malisafi-platform-stats-widget .stat-icon {
    flex-shrink: 0;
}

.malisafi-platform-stats-widget .stat-icon .dashicons {
    width: 32px;
    height: 32px;
    font-size: 32px;
    color: #2271b1;
}

.malisafi-platform-stats-widget .stat-card.pending .stat-icon .dashicons {
    color: #dba617;
}

.malisafi-platform-stats-widget .stat-card.agents .stat-icon .dashicons {
    color: #00a32a;
}

.malisafi-platform-stats-widget .stat-card.inquiries .stat-icon .dashicons {
    color: #8c8f94;
}

.malisafi-platform-stats-widget .stat-info {
    display: flex;
    flex-direction: column;
}

.malisafi-platform-stats-widget .stat-value {
    font-size: 24px;
    font-weight: 600;
    line-height: 1;
    color: #1d2327;
}

.malisafi-platform-stats-widget .stat-label {
    font-size: 12px;
    color: #646970;
    margin-top: 4px;
}

.malisafi-platform-stats-widget .widget-footer {
    padding: 12px;
    background: #fff;
    border-top: 1px solid #dcdcde;
    display: flex;
    gap: 8px;
}

.malisafi-platform-stats-widget .widget-footer .button {
    margin: 0;
}
</style>
