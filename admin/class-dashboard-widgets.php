<?php
/**
 * Dashboard Widgets for WordPress Main Dashboard
 *
 * @package MalisafiMLS
 */

use MalisafiMLS\Page_Manager;

/**
 * Malisafi_Dashboard_Widgets class
 */
class Malisafi_Dashboard_Widgets {
    
    /**
     * Initialize dashboard widgets
     */
    public static function init() {
        add_action('wp_dashboard_setup', array(__CLASS__, 'add_dashboard_widgets'));
    }
    
    /**
     * Add dashboard widgets to WordPress main dashboard
     */
    public static function add_dashboard_widgets() {
        if (!current_user_can('manage_malisafi_settings')) {
            return;
        }
        
        // Quick Setup Guide Widget (Priority)
        wp_add_dashboard_widget(
            'malisafi_quick_setup',
            __('🚀 Malisafi Quick Setup', 'malisafi-mls'),
            array(__CLASS__, 'render_quick_setup'),
            null,
            null,
            'high'
        );
        
        wp_add_dashboard_widget(
            'malisafi_platform_stats',
            __('Malisafi Platform Overview', 'malisafi-mls'),
            array(__CLASS__, 'render_platform_stats')
        );
        
        wp_add_dashboard_widget(
            'malisafi_moderation_alerts',
            __('Moderation Required', 'malisafi-mls'),
            array(__CLASS__, 'render_moderation_alerts')
        );
    }
    
    /**
     * Render quick setup widget
     */
    public static function render_quick_setup() {
        $pages_status = Page_Manager::get_pages_status();
        $total_pages = count($pages_status);
        $existing_pages = count(array_filter($pages_status, function($status) { return $status['exists']; }));
        $missing_count = $total_pages - $existing_pages;
        $completion_percentage = $total_pages > 0 ? round(($existing_pages / $total_pages) * 100) : 0;
        
        ?>
        <div class="malisafi-quick-setup-widget">
            <?php if ($missing_count > 0): ?>
                <div class="setup-incomplete">
                    <div class="setup-icon">⚠️</div>
                    <h3><?php _e('Setup Required', 'malisafi-mls'); ?></h3>
                    <p><?php printf(__('Your Malisafi platform is <strong>%d%% complete</strong>. You have %d missing pages.', 'malisafi-mls'), $completion_percentage, $missing_count); ?></p>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $completion_percentage; ?>%;"></div>
                    </div>
                    
                    <div class="setup-actions">
                        <a href="<?php echo admin_url('admin.php?page=malisafi-pages'); ?>" class="button button-primary button-large">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php _e('Complete Setup Now', 'malisafi-mls'); ?>
                        </a>
                        <a href="<?php echo MALISAFI_MLS_URL; ?>PAGES-SETUP-GUIDE.md" target="_blank" class="button button-secondary">
                            <span class="dashicons dashicons-book"></span>
                            <?php _e('View Guide', 'malisafi-mls'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="setup-complete">
                    <div class="setup-icon">✓</div>
                    <h3><?php _e('Setup Complete!', 'malisafi-mls'); ?></h3>
                    <p><?php _e('All required pages are created and ready. Your platform is fully configured!', 'malisafi-mls'); ?></p>
                    
                    <div class="quick-links">
                        <h4><?php _e('Quick Links:', 'malisafi-mls'); ?></h4>
                        <ul>
                            <li><a href="<?php echo admin_url('admin.php?page=malisafi-properties'); ?>"><?php _e('Manage Properties', 'malisafi-mls'); ?></a></li>
                            <li><a href="<?php echo admin_url('admin.php?page=malisafi-users'); ?>"><?php _e('Manage Users', 'malisafi-mls'); ?></a></li>
                            <li><a href="<?php echo admin_url('admin.php?page=malisafi-moderation'); ?>"><?php _e('Moderation Queue', 'malisafi-mls'); ?></a></li>
                            <li><a href="<?php echo admin_url('admin.php?page=malisafi-settings'); ?>"><?php _e('Settings', 'malisafi-mls'); ?></a></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .malisafi-quick-setup-widget {
            padding: 10px 0;
        }
        
        .setup-incomplete,
        .setup-complete {
            text-align: center;
        }
        
        .setup-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .setup-incomplete h3 {
            color: #d63638;
            margin: 10px 0;
        }
        
        .setup-complete h3 {
            color: #00a32a;
            margin: 10px 0;
        }
        
        .setup-incomplete p,
        .setup-complete p {
            font-size: 14px;
            line-height: 1.6;
            margin: 10px 0;
        }
        
        .progress-bar {
            width: 100%;
            height: 24px;
            background: #f0f0f1;
            border-radius: 12px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa 0%, #00a0d2 100%);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .setup-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .setup-actions .button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .setup-actions .button .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        .quick-links {
            text-align: left;
            margin-top: 15px;
            padding: 15px;
            background: #f6f7f7;
            border-radius: 4px;
        }
        
        .quick-links h4 {
            margin: 0 0 10px 0;
            color: #1d2327;
        }
        
        .quick-links ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .quick-links li {
            padding: 5px 0;
        }
        
        .quick-links a {
            text-decoration: none;
            color: #0073aa;
            font-weight: 500;
        }
        
        .quick-links a:hover {
            color: #005a87;
        }
        </style>
        <?php
    }
    
    /**
     * Render platform statistics widget
     */
    public static function render_platform_stats() {
        global $wpdb;
        
        $prefix = $wpdb->prefix;
        
        // Get statistics - Use wp_posts for properties (custom post type)
        $stats = array(
            'total_properties' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_status IN ('publish', 'pending', 'draft')"),
            'pending_moderation' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_status = 'pending'"),
            'active_agents' => $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}mf_subscriptions WHERE status = 'active' AND plan_type LIKE 'agent_%'"),
            'total_inquiries' => $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}mf_inquiries WHERE DATE(created_at) = CURDATE()"),
        );
        
        // Check if template exists
        $template = MALISAFI_MLS_PATH . 'admin/templates/widgets/platform-stats.php';
        if (file_exists($template)) {
            include $template;
        } else {
            // Fallback inline display
            self::render_platform_stats_inline($stats);
        }
    }
    
    /**
     * Render platform stats inline (fallback)
     */
    private static function render_platform_stats_inline($stats) {
        ?>
        <div class="malisafi-widget-stats">
            <div class="stat-item">
                <span class="stat-label"><?php _e('Total Properties', 'malisafi-mls'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['total_properties'] ?: 0); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Pending Moderation', 'malisafi-mls'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['pending_moderation'] ?: 0); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Active Agents', 'malisafi-mls'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['active_agents'] ?: 0); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label"><?php _e('Today\'s Inquiries', 'malisafi-mls'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['total_inquiries'] ?: 0); ?></span>
            </div>
        </div>
        <style>
            .malisafi-widget-stats {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin-top: 10px;
            }
            .malisafi-widget-stats .stat-item {
                display: flex;
                flex-direction: column;
                padding: 10px;
                background: #f6f7f7;
                border-radius: 4px;
            }
            .malisafi-widget-stats .stat-label {
                font-size: 12px;
                color: #646970;
                margin-bottom: 5px;
            }
            .malisafi-widget-stats .stat-value {
                font-size: 24px;
                font-weight: 600;
                color: #2271b1;
            }
        </style>
        <?php
    }
    
    /**
     * Render moderation alerts widget
     */
    public static function render_moderation_alerts() {
        global $wpdb;
        
        $prefix = $wpdb->prefix;
        
        // Get pending count from custom table
        $pending_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}mf_properties WHERE status = 'pending_review'"); // legacy table global
        
        // Also check WordPress posts with pending status
        $wp_pending_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_status = 'pending'");
        $wp_pending_count = isset($wp_pending->pending) ? $wp_pending->pending : 0;
        
        // Use the higher count (in case data is in either location)
        $total_pending = max((int)$pending_count, (int)$wp_pending_count);
        
        if ($total_pending > 0) {
            echo '<div class="malisafi-alert malisafi-alert-warning">';
            echo '<p><strong>' . sprintf(
                _n('%d property awaiting moderation', '%d properties awaiting moderation', $total_pending, 'malisafi-mls'),
                $total_pending
            ) . '</strong></p>';
            echo '<a href="' . admin_url('admin.php?page=malisafi-moderation') . '" class="button button-primary">';
            echo __('Review Now', 'malisafi-mls');
            echo '</a>';
            echo '</div>';
            ?>
            <style>
                .malisafi-alert {
                    padding: 12px;
                    border-radius: 4px;
                    margin: 10px 0;
                }
                .malisafi-alert-warning {
                    background: #fcf3cd;
                    border-left: 4px solid #dba617;
                }
                .malisafi-alert p {
                    margin: 0 0 10px 0;
                }
            </style>
            <?php
        } else {
            echo '<div class="malisafi-alert malisafi-alert-success">';
            echo '<p>' . __('✓ No properties awaiting moderation.', 'malisafi-mls') . '</p>';
            echo '</div>';
            ?>
            <style>
                .malisafi-alert-success {
                    padding: 12px;
                    background: #d7f5dd;
                    border-left: 4px solid #00a32a;
                    border-radius: 4px;
                }
                .malisafi-alert-success p {
                    margin: 0;
                    color: #00702e;
                }
            </style>
            <?php
        }
    }
}
