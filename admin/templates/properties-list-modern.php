<?php
/**
 * Admin Properties List with Modern Filters
 * WP Admin version with filters on left, thumbnails on right
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get filter options
$property_types = get_terms(array('taxonomy' => 'property_type', 'hide_empty' => true));
$locations = get_terms(array('taxonomy' => 'property_location', 'hide_empty' => true));
$statuses = array('For Sale', 'For Rent', 'Sold', 'Rented', 'Pending', 'Draft');

// Get current filters
$current_type = isset($_GET['property_type']) ? sanitize_text_field($_GET['property_type']) : '';
$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$current_search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Get properties
$args = array(
    'post_type' => 'property',
    'posts_per_page' => 20,
    'post_status' => 'any',
    'orderby' => 'date',
    'order' => 'DESC',
);

if ($current_search) {
    $args['s'] = $current_search;
}

if ($current_type) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'property_type',
            'field' => 'slug',
            'terms' => $current_type,
        ),
    );
}

if ($current_status) {
    $args['meta_query'] = array(
        array(
            'key' => '_malisafi_status',
            'value' => $current_status,
            'compare' => '=',
        ),
    );
}

$properties_query = new WP_Query($args);
$property_manager = MalisafiMLS\Property_Manager::get_instance();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-home" style="font-size: 30px; width: 30px; height: 30px; margin-right: 10px;"></span>
        Properties Management
    </h1>
    <a href="<?php echo admin_url('post-new.php?post_type=property'); ?>" class="page-title-action">
        Add New Property
    </a>
    <hr class="wp-header-end">

    <div class="malisafi-properties-wrapper">
        
        <!-- Admin Filters Sidebar -->
        <aside class="malisafi-filters-sidebar">
            
            <div class="filters-header">
                <h3>
                    <span class="dashicons dashicons-filter"></span>
                    Filters
                </h3>
                <a href="<?php echo admin_url('admin.php?page=malisafi-properties'); ?>" class="filter-reset">
                    Reset All
                </a>
            </div>

            <form method="get" action="" id="admin-property-filters">
                <input type="hidden" name="page" value="malisafi-properties">
                
                <!-- Search -->
                <div class="filter-group">
                    <label class="filter-group-label">
                        <span class="dashicons dashicons-search"></span>
                        Search
                    </label>
                    <div class="filter-search">
                        <input type="text" name="s" placeholder="Search properties..." value="<?php echo esc_attr($current_search); ?>">
                        <span class="dashicons dashicons-search"></span>
                    </div>
                </div>

                <!-- Property Type -->
                <div class="filter-group">
                    <label class="filter-group-label">
                        <span class="dashicons dashicons-admin-home"></span>
                        Property Type
                    </label>
                    <select class="filter-select" name="property_type">
                        <option value="">All Types</option>
                        <?php if ($property_types && !is_wp_error($property_types)) : ?>
                            <?php foreach ($property_types as $type) : ?>
                                <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($current_type, $type->slug); ?>>
                                    <?php echo esc_html($type->name); ?> (<?php echo $type->count; ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Status -->
                <div class="filter-group">
                    <label class="filter-group-label">
                        <span class="dashicons dashicons-tag"></span>
                        Status
                    </label>
                    <select class="filter-select" name="status">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $status) : ?>
                            <option value="<?php echo esc_attr($status); ?>" <?php selected($current_status, $status); ?>>
                                <?php echo esc_html($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Location -->
                <?php if ($locations && !is_wp_error($locations)) : ?>
                <div class="filter-group">
                    <label class="filter-group-label">
                        <span class="dashicons dashicons-location"></span>
                        Location
                    </label>
                    <select class="filter-select" name="location">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $location) : ?>
                            <option value="<?php echo esc_attr($location->slug); ?>">
                                <?php echo esc_html($location->name); ?> (<?php echo $location->count; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Featured -->
                <div class="filter-group">
                    <label class="filter-group-label">
                        <span class="dashicons dashicons-star-filled"></span>
                        Special
                    </label>
                    <div class="filter-checkboxes">
                        <div class="filter-checkbox">
                            <input type="checkbox" id="featured" name="featured" value="1">
                            <label for="featured">
                                <span>Featured Only</span>
                            </label>
                        </div>
                        <div class="filter-checkbox">
                            <input type="checkbox" id="pending_moderation" name="pending_moderation" value="1">
                            <label for="pending_moderation">
                                <span>Pending Moderation</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="filter-actions">
                    <button type="submit" class="filter-apply">Apply Filters</button>
                    <a href="<?php echo admin_url('admin.php?page=malisafi-properties'); ?>" class="filter-clear">Clear All</a>
                </div>
            </form>

        </aside>

        <!-- Properties Content -->
        <main class="malisafi-properties-content">
            
            <!-- Results Header -->
            <div class="results-header">
                <div class="results-count">
                    <strong><?php echo $properties_query->found_posts; ?></strong> 
                    <?php echo $properties_query->found_posts === 1 ? 'property' : 'properties'; ?> found
                </div>
                <div class="results-sort">
                    <div class="view-toggle">
                        <button type="button" data-view="grid" class="active" title="Grid View">
                            <span class="dashicons dashicons-grid-view"></span>
                        </button>
                        <button type="button" data-view="list" title="List View">
                            <span class="dashicons dashicons-list-view"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Properties Grid -->
            <div class="properties-grid">
                <?php if ($properties_query->have_posts()) : ?>
                    <?php while ($properties_query->have_posts()) : $properties_query->the_post(); ?>
                        <?php
                        $property_id = get_the_ID();
                        $property_data = $property_manager->get_property_data($property_id);
                        $status = get_post_meta($property_id, '_malisafi_status', true);
                        $featured = get_post_meta($property_id, '_malisafi_featured', true);
                        $image_url = get_the_post_thumbnail_url($property_id, 'medium');
                        if (!$image_url) {
                            $image_url = plugins_url('malisafi/assets/images/placeholder-property.svg');
                        }
                        $edit_url = admin_url('post.php?post=' . $property_id . '&action=edit');
                        $view_url = get_permalink($property_id);
                        ?>
                        
                        <article class="property-card-modern" data-property-id="<?php echo $property_id; ?>">
                            
                            <div class="property-image-wrapper">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                
                                <div class="property-badges">
                                    <?php if ($featured) : ?>
                                        <span class="property-badge featured">Featured</span>
                                    <?php endif; ?>
                                    
                                    <?php if (get_post_status() === 'pending') : ?>
                                        <span class="property-badge new" style="background: rgba(255, 152, 0, 0.95);">Pending</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="property-admin-actions" style="position: absolute; bottom: 15px; left: 15px; right: 15px; display: flex; gap: 10px;">
                                    <a href="<?php echo esc_url($edit_url); ?>" class="button button-primary" style="flex: 1; text-align: center;">
                                        Edit
                                    </a>
                                    <a href="<?php echo esc_url($view_url); ?>" target="_blank" class="button" style="flex: 1; text-align: center;">
                                        View
                                    </a>
                                </div>
                            </div>
                            
                            <div class="property-card-body">
                                
                                <div class="property-price">
                                    <?php echo esc_html($property_manager->format_price($property_data['price'])); ?>
                                </div>
                                
                                <h3 class="property-title">
                                    <a href="<?php echo esc_url($edit_url); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h3>
                                
                                <?php if ($property_data['address']) : ?>
                                <div class="property-location">
                                    <span class="dashicons dashicons-location"></span>
                                    <span><?php echo esc_html($property_data['address']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="property-features">
                                    <?php if ($property_data['bedrooms']) : ?>
                                    <div class="property-feature">
                                        <span class="dashicons dashicons-building"></span>
                                        <span><?php echo esc_html($property_data['bedrooms']); ?> Bed</span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($property_data['bathrooms']) : ?>
                                    <div class="property-feature">
                                        <span class="dashicons dashicons-admin-multisite"></span>
                                        <span><?php echo esc_html($property_data['bathrooms']); ?> Bath</span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($property_data['area']) : ?>
                                    <div class="property-feature">
                                        <span class="dashicons dashicons-editor-expand"></span>
                                        <span><?php echo number_format(floatval($property_data['area'])); ?> sq ft</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="property-meta-info" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f0; font-size: 12px; color: #999;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span>Status:</span>
                                        <strong><?php echo esc_html($status ?: 'N/A'); ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span>Post Status:</span>
                                        <strong><?php echo esc_html(get_post_status()); ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span>ID:</span>
                                        <strong>#<?php echo $property_id; ?></strong>
                                    </div>
                                </div>
                                
                            </div>
                            
                        </article>
                        
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="no-results">
                        <span class="dashicons dashicons-search"></span>
                        <h3>No properties found</h3>
                        <p>Try adjusting your filters or <a href="<?php echo admin_url('post-new.php?post_type=property'); ?>">add a new property</a>.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php wp_reset_postdata(); ?>

        </main>

    </div>
</div>

<style>
/* Additional admin-specific styles */
.property-admin-actions {
    opacity: 0;
    transition: opacity 0.3s;
}

.property-card-modern:hover .property-admin-actions {
    opacity: 1;
}

.property-card-modern h3 a {
    color: inherit;
    text-decoration: none;
}

.property-card-modern h3 a:hover {
    color: #3498db;
}
</style>
