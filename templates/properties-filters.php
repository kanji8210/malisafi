<?php
/**
 * Modern Property Filters Template
 * Frontend display with filters on left, thumbnails on right
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get shortcode attributes (set by shortcode handler)
$shortcode_atts = isset($atts) ? $atts : array(
    'type'      => '',
    'status'    => '',
    'location'  => '',
    'featured'  => '',
    'count'     => 21,
    'offset'    => 0,
    'orderby'   => 'date',
    'order'     => 'DESC',
);

// Get filter options
$property_types = get_terms(array('taxonomy' => 'malisafi_property_type', 'hide_empty' => true));
$locations = get_terms(array('taxonomy' => 'malisafi_property_location', 'hide_empty' => true));
$statuses = array('For Sale', 'For Rent', 'Sold', 'Rented');
$bedrooms_options = array(1, 2, 3, 4, 5, '6+');
$bathrooms_options = array(1, 2, 3, 4, '5+');
$features = array(
    'pool' => 'Swimming Pool',
    'garage' => 'Garage',
    'garden' => 'Garden',
    'balcony' => 'Balcony',
    'gym' => 'Gym',
    'security' => '24/7 Security',
    'elevator' => 'Elevator',
    'parking' => 'Parking',
    'furnished' => 'Furnished',
    'air_conditioning' => 'Air Conditioning'
);

// Build WP_Query args from shortcode attributes
$args = array(
    'post_type' => 'malisafi_property',
    'posts_per_page' => intval($shortcode_atts['count']),
    'offset' => intval($shortcode_atts['offset']),
    'post_status' => 'publish',
    'orderby' => sanitize_text_field($shortcode_atts['orderby']),
    'order' => strtoupper(sanitize_text_field($shortcode_atts['order'])),
);

// Add taxonomy filters if specified
$tax_query = array();

if (!empty($shortcode_atts['type'])) {
    $tax_query[] = array(
        'taxonomy' => 'malisafi_property_type',
        'field' => 'slug',
        'terms' => sanitize_text_field($shortcode_atts['type']),
    );
}

if (!empty($shortcode_atts['status'])) {
    $tax_query[] = array(
        'taxonomy' => 'malisafi_property_status',
        'field' => 'slug',
        'terms' => sanitize_text_field($shortcode_atts['status']),
    );
}

if (!empty($shortcode_atts['location'])) {
    $tax_query[] = array(
        'taxonomy' => 'malisafi_property_location',
        'field' => 'slug',
        'terms' => sanitize_text_field($shortcode_atts['location']),
    );
}

if (count($tax_query) > 1) {
    $tax_query['relation'] = 'AND';
}

if (!empty($tax_query)) {
    $args['tax_query'] = $tax_query;
}

// Add featured filter if specified
if (!empty($shortcode_atts['featured'])) {
    $args['meta_query'] = array(
        array(
            'key' => '_malisafi_featured',
            'value' => '1',
            'compare' => '=',
        ),
    );
}

$properties_query = new WP_Query($args);
$total_properties = $properties_query->found_posts;
?>

<div class="malisafi-properties-wrapper">
    
    <!-- Filters Sidebar -->
    <aside class="malisafi-filters-sidebar">
        
        <div class="filters-header">
            <h3>
                <span class="dashicons dashicons-filter"></span>
                Filters
            </h3>
            <div class="filters-header-actions">
                <button type="button" class="filter-reset">
                    Reset All
                </button>
                <button type="button" class="filters-toggle" aria-expanded="true">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                    <span class="filters-toggle-text">Hide</span>
                </button>
            </div>
        </div>

        <div class="filters-body">

        <!-- Search -->
        <div class="filter-group">
            <label class="filter-group-label">
                <span class="dashicons dashicons-search"></span>
                Search
            </label>
            <div class="filter-search">
                <input type="text" placeholder="Search by keyword..." data-filter="search">
                <span class="dashicons dashicons-search"></span>
            </div>
        </div>

        <!-- Property Type -->
        <div class="filter-group">
            <label class="filter-group-label">
                <span class="dashicons dashicons-admin-home"></span>
                Property Type
            </label>
            <select class="filter-select" data-filter="property_type">
                <option value="">All Types</option>
                <?php if ($property_types && !is_wp_error($property_types)) : ?>
                    <?php foreach ($property_types as $type) : ?>
                        <option value="<?php echo esc_attr($type->slug); ?>">
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
            <select class="filter-select" data-filter="status">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $status) : ?>
                    <option value="<?php echo esc_attr(sanitize_title($status)); ?>">
                        <?php echo esc_html($status); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Bedrooms & Bathrooms -->
        <div class="filter-group">
            <label class="filter-group-label">
                <span class="dashicons dashicons-building"></span>
                Rooms
            </label>
            <select class="filter-select" data-filter="bedrooms" style="margin-bottom: 10px;">
                <option value="">Bedrooms</option>
                <?php foreach ($bedrooms_options as $bed) : ?>
                    <option value="<?php echo esc_attr($bed); ?>">
                        <?php echo esc_html($bed); ?> Bed<?php echo $bed !== 1 ? 's' : ''; ?>+
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="filter-select" data-filter="bathrooms">
                <option value="">Bathrooms</option>
                <?php foreach ($bathrooms_options as $bath) : ?>
                    <option value="<?php echo esc_attr($bath); ?>">
                        <?php echo esc_html($bath); ?> Bath<?php echo $bath !== 1 ? 's' : ''; ?>+
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Price Range -->
        <div class="filter-group">
            <label class="filter-group-label">
                <span class="dashicons dashicons-money-alt"></span>
                Price Range
            </label>
            <div class="price-range-inputs">
                <div class="price-input-wrapper">
                    <span class="currency-symbol">$</span>
                    <input type="number" placeholder="Min" data-filter="price_min">
                </div>
                <div class="price-input-wrapper">
                    <span class="currency-symbol">$</span>
                    <input type="number" placeholder="Max" data-filter="price_max">
                </div>
            </div>
        </div>

        <!-- Area Range -->
        <div class="filter-group">
            <label class="filter-group-label">
                <span class="dashicons dashicons-grid-view"></span>
                Area (sq ft)
            </label>
            <div class="price-range-inputs">
                <div class="area-input-wrapper">
                    <input type="number" placeholder="Min" data-filter="area_min">
                </div>
                <div class="area-input-wrapper">
                    <input type="number" placeholder="Max" data-filter="area_max">
                </div>
            </div>
        </div>

        <!-- Location -->
        <?php if ($locations && !is_wp_error($locations)) : ?>
        <div class="filter-group">
            <label class="filter-group-label">
                <span class="dashicons dashicons-location"></span>
                Location
            </label>
            <select class="filter-select" data-filter="location">
                <option value="">All Locations</option>
                <?php foreach ($locations as $location) : ?>
                    <option value="<?php echo esc_attr($location->slug); ?>">
                        <?php echo esc_html($location->name); ?> (<?php echo $location->count; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Features -->
        <div class="filter-group">
            <label class="filter-group-label">
                <span class="dashicons dashicons-star-filled"></span>
                Features
            </label>
            <div class="filter-checkboxes">
                <?php foreach ($features as $key => $label) : ?>
                    <div class="filter-checkbox">
                        <input type="checkbox" id="feature_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($key); ?>" data-filter="features">
                        <label for="feature_<?php echo esc_attr($key); ?>">
                            <span><?php echo esc_html($label); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filter Actions -->
        <div class="filter-actions">
            <button type="button" class="filter-apply">Apply Filters</button>
            <button type="button" class="filter-clear">Clear All</button>
        </div>

        </div>

    </aside>

    <!-- Properties Content -->
    <main class="malisafi-properties-content">
        
        <!-- Active Filters -->
        <div class="active-filters hidden"></div>

        <!-- Results Header -->
        <div class="results-header">
            <div class="results-count">
                <strong><?php echo $properties_query->found_posts; ?></strong> 
                <?php echo $properties_query->found_posts === 1 ? 'property' : 'properties'; ?> found
            </div>
            <div class="results-sort">
                <label for="sort-select">Sort by:</label>
                <select id="sort-select" data-filter="sort">
                    <option value="date_desc">Newest First</option>
                    <option value="date_asc">Oldest First</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="area_desc">Largest First</option>
                    <option value="area_asc">Smallest First</option>
                    <option value="title_asc">Name: A-Z</option>
                    <option value="title_desc">Name: Z-A</option>
                </select>
            </div>
        </div>

        <!-- Properties Grid -->
        <div class="properties-grid">
            <?php if ($properties_query->have_posts()) : ?>
                <?php while ($properties_query->have_posts()) : $properties_query->the_post(); ?>
                    <?php include(MALISAFI_MLS_PATH . 'templates/property-card-modern.php'); ?>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="no-results">
                    <span class="dashicons dashicons-search"></span>
                    <h3>No properties found</h3>
                    <p>Try adjusting your filters to find what you're looking for.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Load More Button -->
        <?php if ($properties_query->max_num_pages > 1) : ?>
        <div class="properties-load-more">
            <button class="load-more-button" data-page="2" data-max-pages="<?php echo $properties_query->max_num_pages; ?>">
                <span class="dashicons dashicons-update"></span>
                Load More Properties
            </button>
            <div class="load-more-info">
                Showing <span class="current-count"><?php echo $properties_query->post_count; ?></span> of <span class="total-count"><?php echo $total_properties; ?></span> properties
            </div>
        </div>
        <?php endif; ?>

    </main>

</div>

<?php wp_reset_postdata(); ?>
