<?php
/**
 * Properties Management Template
 *
 * @package MalisafiMLS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current action
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

// Display messages
if (isset($_GET['message'])) {
    $messages = array(
        'property_published' => __('Property published successfully.', 'malisafi-mls'),
        'property_pending' => __('Property submitted and pending review.', 'malisafi-mls'),
        'property_updated' => __('Property updated successfully.', 'malisafi-mls'),
        'property_deleted' => __('Property deleted successfully.', 'malisafi-mls')
    );
    
    if (isset($messages[$_GET['message']])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$_GET['message']]) . '</p></div>';
    }
}

if (isset($_GET['error'])) {
    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($_GET['error'])) . '</p></div>';
}
?>

<div class="wrap malisafi-properties-page">
    
    <?php if ($action === 'list') : ?>
        
        <!-- Properties List View -->
        <h1 class="wp-heading-inline"><?php _e('Properties', 'malisafi-mls'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=malisafi-properties&action=add'); ?>" class="page-title-action">
            <?php _e('Add New Property', 'malisafi-mls'); ?>
        </a>
        <hr class="wp-header-end">
        
        <!-- Filters -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get" action="">
                    <input type="hidden" name="page" value="malisafi-properties">
                    
                    <select name="property_status_filter" id="status-filter">
                        <option value=""><?php _e('All Statuses', 'malisafi-mls'); ?></option>
                        <option value="publish" <?php selected(isset($_GET['property_status_filter']) ? $_GET['property_status_filter'] : '', 'publish'); ?>>
                            <?php _e('Published', 'malisafi-mls'); ?>
                        </option>
                        <option value="pending" <?php selected(isset($_GET['property_status_filter']) ? $_GET['property_status_filter'] : '', 'pending'); ?>>
                            <?php _e('Pending Review', 'malisafi-mls'); ?>
                        </option>
                        <option value="draft" <?php selected(isset($_GET['property_status_filter']) ? $_GET['property_status_filter'] : '', 'draft'); ?>>
                            <?php _e('Draft', 'malisafi-mls'); ?>
                        </option>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'malisafi-mls'); ?>">
                </form>
            </div>
        </div>
        
        <?php
        // Get properties
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        $args = array(
            'post_type' => 'malisafi_property',
            'posts_per_page' => 20,
            'paged' => $paged,
            'post_status' => array('publish', 'pending', 'draft')
        );
        
        // Filter by status if selected
        if (isset($_GET['property_status_filter']) && !empty($_GET['property_status_filter'])) {
            $args['post_status'] = sanitize_text_field($_GET['property_status_filter']);
        }
        
        // Only show own properties for non-moderators
        if (!current_user_can('moderate_properties')) {
            $args['author'] = get_current_user_id();
        }
        
        $properties_query = new WP_Query($args);
        ?>
        
        <!-- Properties Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all">
                    </th>
                    <th class="manage-column column-thumbnail"><?php _e('Image', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-title"><?php _e('Title', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-price"><?php _e('Price', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-type"><?php _e('Type', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-location"><?php _e('Location', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-status"><?php _e('Status', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-author"><?php _e('Author', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-date"><?php _e('Date', 'malisafi-mls'); ?></th>
                    <th class="manage-column column-actions"><?php _e('Actions', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($properties_query->have_posts()) : ?>
                    <?php while ($properties_query->have_posts()) : $properties_query->the_post(); ?>
                        <?php
                        $property_id = get_the_ID();
                        $price = get_post_meta($property_id, '_malisafi_price', true);
                        $price = !empty($price) ? floatval($price) : 0;
                        $city = get_post_meta($property_id, '_malisafi_city', true);
                        $property_types = wp_get_post_terms($property_id, 'malisafi_property_type');
                        ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" name="properties[]" value="<?php echo esc_attr($property_id); ?>">
                            </td>
                            <td class="column-thumbnail">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('thumbnail', array('style' => 'width: 60px; height: 60px; object-fit: cover;')); ?>
                                <?php else : ?>
                                    <div style="width: 60px; height: 60px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                        <span class="dashicons dashicons-admin-home" style="color: #ccc; font-size: 30px;"></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="column-title">
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=malisafi-properties&action=edit&property_id=' . $property_id); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </strong>
                            </td>
                            <td class="column-price">
                                <strong>$<?php echo number_format($price); ?></strong>
                            </td>
                            <td class="column-type">
                                <?php 
                                if (!empty($property_types)) {
                                    echo esc_html($property_types[0]->name);
                                } else {
                                    echo '<span style="color: #999;">—</span>';
                                }
                                ?>
                            </td>
                            <td class="column-location">
                                <?php echo $city ? esc_html($city) : '<span style="color: #999;">—</span>'; ?>
                            </td>
                            <td class="column-status">
                                <?php
                                $status = get_post_status();
                                $status_labels = array(
                                    'publish' => array('label' => __('Published', 'malisafi-mls'), 'color' => '#00a32a'),
                                    'pending' => array('label' => __('Pending', 'malisafi-mls'), 'color' => '#dba617'),
                                    'draft' => array('label' => __('Draft', 'malisafi-mls'), 'color' => '#8c8f94'),
                                );
                                
                                if (isset($status_labels[$status])) {
                                    printf(
                                        '<span class="status-badge" style="background: %s; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">%s</span>',
                                        $status_labels[$status]['color'],
                                        $status_labels[$status]['label']
                                    );
                                }
                                ?>
                            </td>
                            <td class="column-author">
                                <?php the_author(); ?>
                            </td>
                            <td class="column-date">
                                <?php echo get_the_date('M j, Y'); ?>
                            </td>
                            <td class="column-actions">
                                <a href="<?php echo admin_url('admin.php?page=malisafi-properties&action=edit&property_id=' . $property_id); ?>" class="button button-small">
                                    <?php _e('Edit', 'malisafi-mls'); ?>
                                </a>
                                <a href="<?php the_permalink(); ?>" class="button button-small" target="_blank">
                                    <?php _e('View', 'malisafi-mls'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else : ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px;">
                            <?php _e('No properties found.', 'malisafi-mls'); ?>
                            <br><br>
                            <a href="<?php echo admin_url('admin.php?page=malisafi-properties&action=add'); ?>" class="button button-primary">
                                <?php _e('Add Your First Property', 'malisafi-mls'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($properties_query->max_num_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $properties_query->max_num_pages,
                        'current' => $paged
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php elseif ($action === 'add' || $action === 'edit') : ?>
        
        <!-- Add/Edit Property Form -->
        <?php
        $is_edit = ($action === 'edit' && $property_id);
        $property = $is_edit ? get_post($property_id) : null;
        
        if ($is_edit && !$property) {
            echo '<div class="notice notice-error"><p>' . __('Property not found.', 'malisafi-mls') . '</p></div>';
            return;
        }
        
        // Check if user can edit this property
        if ($is_edit && !current_user_can('moderate_properties') && $property->post_author != get_current_user_id()) {
            wp_die(__('You do not have permission to edit this property.', 'malisafi-mls'));
        }
        ?>
        
        <h1><?php echo $is_edit ? __('Edit Property', 'malisafi-mls') : __('Add New Property', 'malisafi-mls'); ?></h1>
        <hr class="wp-header-end">
        
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="malisafi-property-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="malisafi_submit_property">
            <?php if ($is_edit) : ?>
                <input type="hidden" name="property_id" value="<?php echo esc_attr($property_id); ?>">
            <?php endif; ?>
            <?php wp_nonce_field('malisafi_submit_property', 'malisafi_property_nonce'); ?>
            
            <div class="property-form-sections">
                
                <!-- Basic Information -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Basic Information', 'malisafi-mls'); ?></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><label for="property_title"><?php _e('Property Title', 'malisafi-mls'); ?> <span class="required">*</span></label></th>
                                <td>
                                    <input type="text" name="property_title" id="property_title" class="large-text" 
                                           value="<?php echo $is_edit ? esc_attr($property->post_title) : ''; ?>" required>
                                    <p class="description"><?php _e('Enter a descriptive title for the property', 'malisafi-mls'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="property_description"><?php _e('Description', 'malisafi-mls'); ?> <span class="required">*</span></label></th>
                                <td>
                                    <?php
                                    $content = $is_edit ? $property->post_content : '';
                                    wp_editor($content, 'property_description', array(
                                        'textarea_name' => 'property_description',
                                        'textarea_rows' => 10,
                                        'media_buttons' => false,
                                        'teeny' => true
                                    ));
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="property_excerpt"><?php _e('Short Description', 'malisafi-mls'); ?></label></th>
                                <td>
                                    <textarea name="property_excerpt" id="property_excerpt" rows="3" class="large-text"><?php 
                                        echo $is_edit ? esc_textarea($property->post_excerpt) : ''; 
                                    ?></textarea>
                                    <p class="description"><?php _e('Brief summary (optional)', 'malisafi-mls'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Property Details -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Property Details', 'malisafi-mls'); ?></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><label for="property_type"><?php _e('Property Type', 'malisafi-mls'); ?> <span class="required">*</span></label></th>
                                <td>
                                    <?php
                                    $property_types = get_terms(array('taxonomy' => 'malisafi_property_type', 'hide_empty' => false));
                                    $selected_types = $is_edit ? wp_get_post_terms($property_id, 'malisafi_property_type', array('fields' => 'ids')) : array();
                                    ?>
                                    <select name="property_type[]" id="property_type" class="regular-text" required>
                                        <option value=""><?php _e('Select Type', 'malisafi-mls'); ?></option>
                                        <?php foreach ($property_types as $type) : ?>
                                            <option value="<?php echo esc_attr($type->term_id); ?>" <?php selected(in_array($type->term_id, $selected_types)); ?>>
                                                <?php echo esc_html($type->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="property_status"><?php _e('Property Status', 'malisafi-mls'); ?> <span class="required">*</span></label></th>
                                <td>
                                    <?php
                                    $property_statuses = get_terms(array('taxonomy' => 'malisafi_property_status', 'hide_empty' => false));
                                    $selected_statuses = $is_edit ? wp_get_post_terms($property_id, 'malisafi_property_status', array('fields' => 'ids')) : array();
                                    ?>
                                    <select name="property_status[]" id="property_status" class="regular-text" required>
                                        <option value=""><?php _e('Select Status', 'malisafi-mls'); ?></option>
                                        <?php foreach ($property_statuses as $status) : ?>
                                            <option value="<?php echo esc_attr($status->term_id); ?>" <?php selected(in_array($status->term_id, $selected_statuses)); ?>>
                                                <?php echo esc_html($status->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="property_price"><?php _e('Price', 'malisafi-mls'); ?> <span class="required">*</span></label></th>
                                <td>
                                    <input type="number" name="property_price" id="property_price" class="regular-text" 
                                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_price', true)) : ''; ?>" 
                                           step="0.01" min="0" required>
                                    <input type="text" name="property_price_suffix" placeholder="<?php _e('e.g., per month', 'malisafi-mls'); ?>" class="regular-text" 
                                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_price_suffix', true)) : ''; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="property_bedrooms"><?php _e('Bedrooms', 'malisafi-mls'); ?></label></th>
                                <td>
                                    <input type="number" name="property_bedrooms" id="property_bedrooms" class="small-text" 
                                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_bedrooms', true)) : ''; ?>" min="0">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="property_bathrooms"><?php _e('Bathrooms', 'malisafi-mls'); ?></label></th>
                                <td>
                                    <input type="number" name="property_bathrooms" id="property_bathrooms" class="small-text" 
                                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_bathrooms', true)) : ''; ?>" 
                                           step="0.5" min="0">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="property_area"><?php _e('Area (sq ft)', 'malisafi-mls'); ?></label></th>
                                <td>
                                    <input type="number" name="property_area" id="property_area" class="regular-text" 
                                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_area', true)) : ''; ?>" 
                                           step="0.01" min="0">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="property_lot_size"><?php _e('Lot Size (sq ft)', 'malisafi-mls'); ?></label></th>
                                <td>
                                    <input type="number" name="property_lot_size" id="property_lot_size" class="regular-text" 
                                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_lot_size', true)) : ''; ?>" 
                                           step="0.01" min="0">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="property_year_built"><?php _e('Year Built', 'malisafi-mls'); ?></label></th>
                                <td>
                                    <input type="number" name="property_year_built" id="property_year_built" class="regular-text" 
                                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_year_built', true)) : ''; ?>" 
                                           min="1800" max="<?php echo date('Y') + 5; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="property_garage"><?php _e('Garage Spaces', 'malisafi-mls'); ?></label></th>
                                <td>
                                    <input type="number" name="property_garage" id="property_garage" class="small-text" 
                                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_garage', true)) : ''; ?>" min="0">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Continue in next file part due to length... -->
                <?php include(MALISAFI_MLS_PATH . 'admin/templates/property-form-parts/location.php'); ?>
                <?php include(MALISAFI_MLS_PATH . 'admin/templates/property-form-parts/media.php'); ?>
                <?php include(MALISAFI_MLS_PATH . 'admin/templates/property-form-parts/agent.php'); ?>
                
            </div>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary button-large" 
                       value="<?php echo $is_edit ? __('Update Property', 'malisafi-mls') : __('Submit Property', 'malisafi-mls'); ?>">
                <a href="<?php echo admin_url('admin.php?page=malisafi-properties'); ?>" class="button button-large">
                    <?php _e('Cancel', 'malisafi-mls'); ?>
                </a>
            </p>
        </form>
        
    <?php endif; ?>
    
</div>

<style>
.malisafi-properties-page .column-thumbnail {
    width: 80px;
}
.malisafi-properties-page .column-title {
    width: 20%;
}
.malisafi-properties-page .column-price {
    width: 10%;
}
.malisafi-properties-page .postbox {
    margin-bottom: 20px;
}
.malisafi-properties-page .postbox .hndle {
    padding: 12px;
    font-size: 14px;
    font-weight: 600;
}
.malisafi-properties-page .postbox .inside {
    padding: 0 12px 12px;
}
.malisafi-properties-page .required {
    color: #d63638;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#cb-select-all').on('change', function() {
        $('input[name="properties[]"]').prop('checked', $(this).prop('checked'));
    });
});
</script>
