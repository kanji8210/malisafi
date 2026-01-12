<?php
/**
 * Database Tools Admin Page
 *
 * @package MalisafiMLS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'malisafi-mls'));
}

// Handle form submission
if (isset($_POST['malisafi_repair_database']) && check_admin_referer('malisafi_repair_database')) {
    require_once MALISAFI_MLS_PATH . 'includes/class-database.php';
    MalisafiMLS\Database::update_schema();
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Database tables have been checked and repaired successfully!', 'malisafi-mls') . '</p></div>';
}

// Generate missing reference IDs
if (isset($_POST['malisafi_generate_reference_ids']) && check_admin_referer('malisafi_generate_reference_ids')) {
    require_once MALISAFI_MLS_PATH . 'includes/class-reference-id.php';
    $count = \MalisafiMLS\Reference_ID::generate_missing_ids();
    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Generated reference IDs for %d properties.', 'malisafi-mls'), $count) . '</p></div>';
}

// Regenerate thumbnails
if (isset($_POST['malisafi_regenerate_thumbnails']) && check_admin_referer('malisafi_regenerate_thumbnails')) {
require_once ABSPATH . 'wp-admin/includes/image.php';
$attachments = get_posts(array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'posts_per_page' => -1,
    'post_status' => 'inherit',
    'fields' => 'ids',
));

$processed = 0;
foreach ($attachments as $attachment_id) {
    $file = get_attached_file($attachment_id);
    if ($file && file_exists($file)) {
        $metadata = wp_generate_attachment_metadata($attachment_id, $file);
        if (!is_wp_error($metadata) && !empty($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
            $processed++;
        }
    }
}

echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Regenerated thumbnails for %d images.', 'malisafi-mls'), $processed) . '</p></div>';
}

// Check table status
global $wpdb;
$tables_status = array();
$table_names = array(
    'mf_subscriptions' => __('Subscriptions', 'malisafi-mls'),
    'mf_user_limits' => __('User Limits', 'malisafi-mls'),
    'mf_properties' => __('Properties', 'malisafi-mls'),
    'mf_property_amenities' => __('Property Amenities', 'malisafi-mls'),
    'mf_property_media' => __('Property Media', 'malisafi-mls'),
    'mf_inquiries' => __('Inquiries', 'malisafi-mls'),
    'mf_saved_searches' => __('Saved Searches', 'malisafi-mls'),
    'mf_favorites' => __('Favorites', 'malisafi-mls'),
    'mf_moderation_queue' => __('Moderation Queue', 'malisafi-mls'),
    'mf_property_reports' => __('Property Reports', 'malisafi-mls'),
    'mf_analytics' => __('Analytics', 'malisafi-mls')
);

foreach ($table_names as $table => $label) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table;
    
    $row_count = 0;
    if ($exists) {
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
    }
    
    $tables_status[] = array(
        'name' => $label,
        'table' => $full_table,
        'exists' => $exists,
        'rows' => $row_count
    );
}
?>

<div class="wrap">
    <h1><?php _e('Database Tools', 'malisafi-mls'); ?></h1>
    
    <div class="card">
        <h2><?php _e('Database Status', 'malisafi-mls'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Table Name', 'malisafi-mls'); ?></th>
                    <th><?php _e('Full Table Name', 'malisafi-mls'); ?></th>
                    <th><?php _e('Status', 'malisafi-mls'); ?></th>
                    <th><?php _e('Row Count', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables_status as $table) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($table['name']); ?></strong></td>
                        <td><code><?php echo esc_html($table['table']); ?></code></td>
                        <td>
                            <?php if ($table['exists']) : ?>
                                <span style="color: #00a32a; font-weight: 600;">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('Exists', 'malisafi-mls'); ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #d63638; font-weight: 600;">
                                    <span class="dashicons dashicons-dismiss"></span>
                                    <?php _e('Missing', 'malisafi-mls'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $table['exists'] ? number_format($table['rows']) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <br>
        
        <form method="post" onsubmit="return confirm('<?php esc_attr_e('This will create missing database tables. Are you sure?', 'malisafi-mls'); ?>');">
            <?php wp_nonce_field('malisafi_repair_database'); ?>
            <button type="submit" name="malisafi_repair_database" class="button button-primary button-large">
                <span class="dashicons dashicons-database-add" style="margin-top: 3px;"></span>
                <?php _e('Create/Repair Missing Tables', 'malisafi-mls'); ?>
            </button>
            <p class="description">
                <?php _e('This will check all required tables and create any that are missing. Existing tables will not be affected.', 'malisafi-mls'); ?>
            </p>
        </form>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <h2><?php _e('Generate Property Reference IDs', 'malisafi-mls'); ?></h2>
        <p><?php _e('Generate MLS# reference IDs for all properties that are missing them. This will add a unique PROP-YYYYMMDD-ID format to each property.', 'malisafi-mls'); ?></p>
        <form method="post" onsubmit="return confirm('<?php esc_attr_e('Generate reference IDs for all properties missing them?', 'malisafi-mls'); ?>');">
            <?php wp_nonce_field('malisafi_generate_reference_ids'); ?>
            <button type="submit" name="malisafi_generate_reference_ids" class="button button-primary">
                <span class="dashicons dashicons-admin-network" style="margin-top: 3px;"></span>
                <?php _e('Generate Missing Reference IDs', 'malisafi-mls'); ?>
            </button>
        </form>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2><?php _e('Regenerate Thumbnails', 'malisafi-mls'); ?></h2>
        <p><?php _e('Use this tool after changing image sizes to regenerate thumbnails for all existing images. This may take a while on large libraries.', 'malisafi-mls'); ?></p>
        <form method="post" onsubmit="return confirm('<?php esc_attr_e('Regenerate thumbnails for all images? This may take a while.', 'malisafi-mls'); ?>');">
            <?php wp_nonce_field('malisafi_regenerate_thumbnails'); ?>
            <button type="submit" name="malisafi_regenerate_thumbnails" class="button button-secondary">
                <span class="dashicons dashicons-image-rotate" style="margin-top: 3px;"></span>
                <?php _e('Regenerate All Thumbnails', 'malisafi-mls'); ?>
            </button>
        </form>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2><?php _e('Database Information', 'malisafi-mls'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Database Version:', 'malisafi-mls'); ?></th>
                <td><code><?php echo esc_html(get_option('malisafi_mls_db_version', 'Not set')); ?></code></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Plugin Version:', 'malisafi-mls'); ?></th>
                <td><code><?php echo esc_html(MALISAFI_MLS_VERSION); ?></code></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('WordPress Database Prefix:', 'malisafi-mls'); ?></th>
                <td><code><?php echo esc_html($wpdb->prefix); ?></code></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Database Charset:', 'malisafi-mls'); ?></th>
                <td><code><?php echo esc_html($wpdb->get_charset_collate()); ?></code></td>
            </tr>
        </table>
    </div>
</div>

<style>
.card {
    padding: 20px;
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card h2 {
    margin-top: 0;
}

.button-large .dashicons {
    vertical-align: middle;
}
</style>
