<?php
/**
 * Fraud Reports Admin Page
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.1
 */

use MalisafiMLS\Analytics\Analytics_Advanced;

if (!defined('ABSPATH')) {
    exit;
}

class Malisafi_Admin_Fraud_Reports {
    
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'), 11);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Add submenu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'malisafi-analytics',
            __('Fraud Reports', 'malisafi-mls'),
            __('Fraud Reports', 'malisafi-mls'),
            'moderate_malisafi_properties',
            'malisafi-analytics-fraud-reports',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'analytics_page_malisafi-analytics-fraud-reports') {
            return;
        }

        // jQuery UI Dialog
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
        
        // jQuery UI Autocomplete
        wp_enqueue_script('jquery-ui-autocomplete');

        // Custom styles
        wp_enqueue_style(
            'malisafi-fraud-reports-admin',
            MALISAFI_MLS_PLUGIN_URL . 'assets/css/admin-fraud-reports.css',
            array(),
            MALISAFI_MLS_VERSION
        );

        // Custom script
        wp_enqueue_script(
            'malisafi-fraud-reports-admin',
            MALISAFI_MLS_PLUGIN_URL . 'assets/js/admin-fraud-reports.js',
            array('jquery', 'jquery-ui-dialog', 'jquery-ui-autocomplete'),
            MALISAFI_MLS_VERSION,
            true
        );

        // Localize script
        wp_localize_script('malisafi-fraud-reports-admin', 'malisafiFraudAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_admin_nonce'),
            'i18n' => array(
                'confirmDismiss' => __('Are you sure you want to dismiss this report?', 'malisafi-mls'),
                'confirmResolve' => __('Are you sure you want to mark this as resolved?', 'malisafi-mls'),
                'createSuspicionSuccess' => __('Suspicion created successfully', 'malisafi-mls'),
                'createSuspicionError' => __('Failed to create suspicion', 'malisafi-mls'),
                'updateStatusSuccess' => __('Report status updated', 'malisafi-mls'),
                'updateStatusError' => __('Failed to update status', 'malisafi-mls')
            )
        ));
    }

    /**
     * Render admin page
     */
    public function render_page() {
        if (!current_user_can('moderate_malisafi_properties')) {
            wp_die(__('Unauthorized', 'malisafi-mls'));
        }

        global $wpdb;
        
        // Get filter parameters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $type_filter = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : '';
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;

        // Build query
        $where = array('1=1');
        if ($status_filter) {
            $where[] = $wpdb->prepare('status = %s', $status_filter);
        }
        if ($type_filter) {
            $where[] = $wpdb->prepare('report_type = %s', $type_filter);
        }
        $where_clause = implode(' AND ', $where);
        
        $offset = ($paged - 1) * $per_page;

        // Get reports
        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mf_fraud_reports 
             WHERE {$where_clause}
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        // Get total count
        $total_reports = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_reports WHERE {$where_clause}");
        $total_pages = ceil($total_reports / $per_page);

        // Get stats
        $stats = $this->get_stats();

        ?>
        <div class="wrap malisafi-fraud-reports-admin">
            <h1 class="wp-heading-inline">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
                    <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995z"/>
                </svg>
                <?php _e('Fraud Reports', 'malisafi-mls'); ?>
            </h1>
            
            <button class="page-title-action" id="create-manual-suspicion-btn">
                <?php _e('Create Manual Suspicion', 'malisafi-mls'); ?>
            </button>

            <hr class="wp-header-end">

            <!-- Stats Cards -->
            <div class="fraud-stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                        <div class="stat-label"><?php _e('Total Reports', 'malisafi-mls'); ?></div>
                    </div>
                </div>

                <div class="stat-card <?php echo $stats['new'] > 0 ? 'alert' : ''; ?>">
                    <div class="stat-icon">🔴</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['new']); ?></div>
                        <div class="stat-label"><?php _e('New Reports', 'malisafi-mls'); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">👁️</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['under_review']); ?></div>
                        <div class="stat-label"><?php _e('Under Review', 'malisafi-mls'); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['resolved_this_week']); ?></div>
                        <div class="stat-label"><?php _e('Resolved This Week', 'malisafi-mls'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="tablenav top">
                <form method="get" class="fraud-filters">
                    <input type="hidden" name="page" value="malisafi-analytics-fraud-reports">
                    
                    <select name="status" id="status-filter">
                        <option value=""><?php _e('All Statuses', 'malisafi-mls'); ?></option>
                        <option value="new" <?php selected($status_filter, 'new'); ?>><?php _e('New', 'malisafi-mls'); ?></option>
                        <option value="under_review" <?php selected($status_filter, 'under_review'); ?>><?php _e('Under Review', 'malisafi-mls'); ?></option>
                        <option value="resolved" <?php selected($status_filter, 'resolved'); ?>><?php _e('Resolved', 'malisafi-mls'); ?></option>
                        <option value="dismissed" <?php selected($status_filter, 'dismissed'); ?>><?php _e('Dismissed', 'malisafi-mls'); ?></option>
                    </select>

                    <select name="report_type" id="type-filter">
                        <option value=""><?php _e('All Types', 'malisafi-mls'); ?></option>
                        <option value="fake_listing" <?php selected($type_filter, 'fake_listing'); ?>><?php _e('Fake Listing', 'malisafi-mls'); ?></option>
                        <option value="duplicate_property" <?php selected($type_filter, 'duplicate_property'); ?>><?php _e('Duplicate Property', 'malisafi-mls'); ?></option>
                        <option value="misleading_info" <?php selected($type_filter, 'misleading_info'); ?>><?php _e('Misleading Info', 'malisafi-mls'); ?></option>
                        <option value="fake_agent" <?php selected($type_filter, 'fake_agent'); ?>><?php _e('Fake Agent', 'malisafi-mls'); ?></option>
                        <option value="price_scam" <?php selected($type_filter, 'price_scam'); ?>><?php _e('Price Scam', 'malisafi-mls'); ?></option>
                        <option value="fake_photos" <?php selected($type_filter, 'fake_photos'); ?>><?php _e('Fake Photos', 'malisafi-mls'); ?></option>
                        <option value="contact_fraud" <?php selected($type_filter, 'contact_fraud'); ?>><?php _e('Contact Fraud', 'malisafi-mls'); ?></option>
                        <option value="identity_theft" <?php selected($type_filter, 'identity_theft'); ?>><?php _e('Identity Theft', 'malisafi-mls'); ?></option>
                        <option value="spam" <?php selected($type_filter, 'spam'); ?>><?php _e('Spam', 'malisafi-mls'); ?></option>
                        <option value="other" <?php selected($type_filter, 'other'); ?>><?php _e('Other', 'malisafi-mls'); ?></option>
                    </select>

                    <button type="submit" class="button action"><?php _e('Filter', 'malisafi-mls'); ?></button>
                    
                    <?php if ($status_filter || $type_filter) : ?>
                        <a href="<?php echo admin_url('admin.php?page=malisafi-analytics-fraud-reports'); ?>" class="button">
                            <?php _e('Clear Filters', 'malisafi-mls'); ?>
                        </a>
                    <?php endif; ?>
                </form>

                <div class="tablenav-pages">
                    <?php if ($total_pages > 1) : ?>
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $paged,
                            'total' => $total_pages,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;'
                        ));
                        ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reports Table -->
            <table class="wp-list-table widefat fixed striped fraud-reports-table">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'malisafi-mls'); ?></th>
                        <th><?php _e('Type', 'malisafi-mls'); ?></th>
                        <th><?php _e('Reporter', 'malisafi-mls'); ?></th>
                        <th><?php _e('Agent', 'malisafi-mls'); ?></th>
                        <th><?php _e('Property', 'malisafi-mls'); ?></th>
                        <th><?php _e('Reason', 'malisafi-mls'); ?></th>
                        <th><?php _e('Status', 'malisafi-mls'); ?></th>
                        <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)) : ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding: 30px;">
                                <?php _e('No reports found.', 'malisafi-mls'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($reports as $report) : ?>
                            <tr data-report-id="<?php echo $report->id; ?>">
                                <td><?php echo date_i18n('M j, Y g:i A', strtotime($report->created_at)); ?></td>
                                <td><?php echo $this->get_type_badge($report->report_type); ?></td>
                                <td>
                                    <?php if ($report->reporter_user_id) : ?>
                                        <?php $user = get_userdata($report->reporter_user_id); ?>
                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $report->reporter_user_id); ?>">
                                            <?php echo $user->display_name; ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo esc_html($report->reporter_email); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($report->agent_id) : ?>
                                        <a href="<?php echo get_edit_post_link($report->agent_id); ?>">
                                            <?php echo get_the_title($report->agent_id); ?>
                                        </a>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($report->property_id) : ?>
                                        <a href="<?php echo get_edit_post_link($report->property_id); ?>">
                                            <?php echo get_the_title($report->property_id); ?>
                                        </a>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(wp_trim_words($report->reason, 10)); ?></td>
                                <td><?php echo $this->get_status_badge($report->status); ?></td>
                                <td>
                                    <button class="button button-small view-details" data-report-id="<?php echo $report->id; ?>">
                                        <?php _e('View', 'malisafi-mls'); ?>
                                    </button>
                                    
                                    <?php if ($report->status === 'new' || $report->status === 'under_review') : ?>
                                        <button class="button button-small create-suspicion" data-report-id="<?php echo $report->id; ?>" data-agent-id="<?php echo $report->agent_id; ?>" data-property-id="<?php echo $report->property_id; ?>">
                                            <?php _e('Create Suspicion', 'malisafi-mls'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination (bottom) -->
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php if ($total_pages > 1) : ?>
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $paged,
                            'total' => $total_pages,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;'
                        ));
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modal: View Report Details -->
        <div id="report-details-modal" style="display:none;">
            <div id="report-details-content"></div>
        </div>

        <!-- Modal: Create Manual Suspicion -->
        <div id="manual-suspicion-modal" style="display:none;">
            <?php include MALISAFI_MLS_PLUGIN_DIR . 'admin/templates/modal-create-suspicion.php'; ?>
        </div>
        <?php
    }

    /**
     * Get statistics
     */
    private function get_stats() {
        global $wpdb;
        
        return array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_reports"),
            'new' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_reports WHERE status = 'new'"),
            'under_review' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_reports WHERE status = 'under_review'"),
            'resolved_this_week' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_reports WHERE status = 'resolved' AND reviewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
        );
    }

    /**
     * Get status badge HTML
     */
    private function get_status_badge($status) {
        $badges = array(
            'new' => '<span class="status-badge status-new">New</span>',
            'under_review' => '<span class="status-badge status-review">Under Review</span>',
            'resolved' => '<span class="status-badge status-resolved">Resolved</span>',
            'dismissed' => '<span class="status-badge status-dismissed">Dismissed</span>'
        );
        
        return isset($badges[$status]) ? $badges[$status] : $status;
    }

    /**
     * Get type badge HTML
     */
    private function get_type_badge($type) {
        $types = array(
            'fake_listing' => 'Fake Listing',
            'duplicate_property' => 'Duplicate',
            'misleading_info' => 'Misleading',
            'fake_agent' => 'Fake Agent',
            'price_scam' => 'Price Scam',
            'fake_photos' => 'Fake Photos',
            'contact_fraud' => 'Contact Fraud',
            'identity_theft' => 'Identity Theft',
            'spam' => 'Spam',
            'other' => 'Other'
        );
        
        $label = isset($types[$type]) ? $types[$type] : $type;
        return '<span class="type-badge type-' . esc_attr($type) . '">' . esc_html($label) . '</span>';
    }
}

// Initialize
Malisafi_Admin_Fraud_Reports::get_instance();
