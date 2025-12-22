<?php
/**
 * Minimalist Property Filters Template
 * Row 1: Button selection for status (For Rent, For Sale, Short Stay)
 * Row 2: Property type dropdown, City dropdown, Search input
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get filter options
$property_types = get_terms(array('taxonomy' => 'malisafi_property_type', 'hide_empty' => true));
$statuses = get_terms(array('taxonomy' => 'malisafi_property_status', 'hide_empty' => true));

// Get Kenya counties for city dropdown
$counties = function_exists('malisafi_get_kenya_counties') ? malisafi_get_kenya_counties() : array();

// Get initial properties
$args = array(
    'post_type' => 'malisafi_property',
    'posts_per_page' => 12,
    'post_status' => 'publish',
);
$properties_query = new WP_Query($args);
$total_properties = $properties_query->found_posts;
?>

<div class="malisafi-minimalist-wrapper">
    
    <!-- Minimalist Filters -->
    <div class="malisafi-minimalist-filters">
        
        <!-- Row 1: Status Buttons -->
        <div class="filter-row filter-row-status">
            <div class="status-buttons">
                <button type="button" class="status-btn active" data-status="">
                    All Properties
                </button>
                <?php if ($statuses && !is_wp_error($statuses)) : ?>
                    <?php foreach ($statuses as $status) : ?>
                        <button type="button" class="status-btn" data-status="<?php echo esc_attr($status->slug); ?>">
                            <?php echo esc_html($status->name); ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Row 2: Dropdowns & Search -->
        <div class="filter-row filter-row-controls">
            <div class="filter-controls">
                
                <!-- Property Type Dropdown -->
                <div class="filter-control">
                    <select class="filter-select" data-filter="property_type">
                        <option value="">Property Type</option>
                        <?php if ($property_types && !is_wp_error($property_types)) : ?>
                            <?php foreach ($property_types as $type) : ?>
                                <option value="<?php echo esc_attr($type->slug); ?>">
                                    <?php echo esc_html($type->name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- City Dropdown -->
                <div class="filter-control">
                    <select class="filter-select" data-filter="county">
                        <option value="">City/County</option>
                        <?php if (!empty($counties)) : ?>
                            <?php foreach ($counties as $county) : ?>
                                <option value="<?php echo esc_attr($county); ?>">
                                    <?php echo esc_html($county); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="filter-control filter-control-search">
                    <input type="text" 
                           class="filter-search-input" 
                           data-filter="search" 
                           placeholder="Search properties...">
                    <span class="search-icon dashicons dashicons-search"></span>
                </div>

                <!-- Search Button (optional, can trigger filter) -->
                <button type="button" class="filter-search-btn">
                    <span class="dashicons dashicons-search"></span>
                    Search
                </button>

            </div>
        </div>

    </div>

    <!-- Results Header -->
    <div class="malisafi-results-header">
        <div class="results-count">
            <span class="count-number"><?php echo esc_html($total_properties); ?></span>
            <span class="count-text">properties found</span>
        </div>
        <div class="results-controls">
            <select class="sort-select" data-sort="date-desc">
                <option value="date-desc">Newest First</option>
                <option value="date-asc">Oldest First</option>
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
                <option value="area-desc">Area: Largest First</option>
                <option value="area-asc">Area: Smallest First</option>
            </select>
        </div>
    </div>

    <!-- Properties Grid -->
    <div class="malisafi-properties-grid loading">
        
        <?php if ($properties_query->have_posts()) : ?>
            <?php while ($properties_query->have_posts()) : $properties_query->the_post(); ?>
                <?php include MALISAFI_MLS_PATH . 'templates/property-card-modern.php'; ?>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <div class="no-properties">
                <div class="no-properties-icon">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <h3>No properties found</h3>
                <p>Try adjusting your filters to see more results.</p>
            </div>
        <?php endif; ?>

    </div>

    <!-- Pagination -->
    <div class="malisafi-pagination">
        <!-- Pagination will be updated via AJAX -->
        <button class="pagination-btn prev-page" disabled>
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            Previous
        </button>
        <span class="pagination-info">
            Page <span class="current-page">1</span> of <span class="total-pages">1</span>
        </span>
        <button class="pagination-btn next-page" disabled>
            Next
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading properties...</p>
        </div>
    </div>

</div>
