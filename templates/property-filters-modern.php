<?php
/**
 * Modern Property Filters Template
 * Minimalist filter system with mobile-responsive design
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get filter options from taxonomies
$property_types = get_terms(array('taxonomy' => 'malisafi_property_type', 'hide_empty' => false));
$locations = get_terms(array('taxonomy' => 'malisafi_property_location', 'hide_empty' => false));
$statuses = array(
    'for-sale' => 'For Sale',
    'for-rent' => 'For Rent',
    'sold' => 'Sold',
    'rented' => 'Rented'
);
$features = array(
    'pool' => 'Pool',
    'garage' => 'Garage',
    'garden' => 'Garden',
    'balcony' => 'Balcony',
    'gym' => 'Gym',
    'security' => '24/7 Security'
);

// Get initial properties
$args = array(
    'post_type' => 'malisafi_property',
    'posts_per_page' => 12,
    'post_status' => 'publish',
);
$properties_query = new WP_Query($args);
?>

<div class="malisafi-properties-container">
    
    <!-- Filters Sidebar -->
    <aside class="malisafi-filters-sidebar">
        <div class="malisafi-filters-container">
            
            <form class="malisafi-search-form expanded" id="propertySearchForm">
                <div class="property-search">
                    
                    <!-- Essential Filters (Always visible on all devices) -->
                    <div class="search-field filter-essential">
                        <label for="location">Location</label>
                        <select id="location" name="location" data-filter="location">
                            <option value="">Any Location</option>
                            <?php if ($locations && !is_wp_error($locations)) : ?>
                                <?php foreach ($locations as $location) : ?>
                                    <option value="<?php echo esc_attr($location->slug); ?>">
                                        <?php echo esc_html($location->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="search-field filter-essential">
                        <label for="property_type">Property Type</label>
                        <select id="property_type" name="property_type" data-filter="property_type">
                            <option value="">Any Type</option>
                            <?php if ($property_types && !is_wp_error($property_types)) : ?>
                                <?php foreach ($property_types as $type) : ?>
                                    <option value="<?php echo esc_attr($type->slug); ?>">
                                        <?php echo esc_html($type->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="search-field filter-essential">
                        <label for="status">Status</label>
                        <select id="status" name="status" data-filter="status">
                            <option value="">Any Status</option>
                            <?php foreach ($statuses as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($key, 'for-sale'); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
            
                    
                    <!-- Optional Filters (Price ranges, hidden on mobile by default) -->
                    <div class="search-field filter-optional">
                        <label for="min_price">Min Price</label>
                        <select id="min_price" name="min_price" data-filter="price_min">
                            <option value="">Min Price</option>
                            <option value="10000">$10,000</option>
                            <option value="50000">$50,000</option>
                            <option value="100000">$100,000</option>
                            <option value="200000">$200,000</option>
                            <option value="500000">$500,000</option>
                        </select>
                    </div>
                    
                    <div class="search-field filter-optional">
                        <label for="max_price">Max Price</label>
                        <select id="max_price" name="max_price" data-filter="price_max">
                            <option value="">Max Price</option>
                            <option value="100000">$100,000</option>
                            <option value="200000">$200,000</option>
                            <option value="500000">$500,000</option>
                            <option value="1000000">$1,000,000</option>
                            <option value="2000000">$2,000,000+</option>
                        </select>
                    </div>
                    
                    <div class="search-field filter-optional">
                        <label for="bedrooms">Bedrooms</label>
                        <select id="bedrooms" name="bedrooms" data-filter="bedrooms">
                            <option value="">Any</option>
                            <option value="1">1+</option>
                            <option value="2">2+</option>
                            <option value="3">3+</option>
                            <option value="4">4+</option>
                            <option value="5">5+</option>
                        </select>
                    </div>
                    
                    <!-- Optional Filters (Hidden on mobile by default) -->
                    <div class="search-field filter-optional">
                        <label for="bathrooms">Bathrooms</label>
                        <select id="bathrooms" name="bathrooms" data-filter="bathrooms">
                            <option value="">Any</option>
                            <option value="1">1+</option>
                            <option value="2">2+</option>
                            <option value="3">3+</option>
                            <option value="4">4+</option>
                        </select>
                    </div>
                    
                    <div class="search-field filter-optional">
                        <label for="area_min">Min Area (sq ft)</label>
                        <input type="number" id="area_min" name="area_min" placeholder="e.g., 1000" data-filter="area_min">
                    </div>
                    
                    <div class="search-field filter-optional">
                        <label for="features">Features</label>
                        <select id="features" name="features" data-filter="features">
                            <option value="">Any Features</option>
                            <?php foreach ($features as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="search-submit filter-essential">
                        <button type="submit" class="search-button">
                            <span class="dashicons dashicons-search"></span>
                            Search Properties
                        </button>
                    </div>
                    
                </div>
                
                <!-- Advanced Filters Toggle -->
                <button type="button" class="advanced-filters-toggle" id="advancedFiltersToggle">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                    <span class="toggle-text">Show More Filters</span>
                    <span class="filter-count" id="filterCount">0</span>
                </button>
                
                <!-- Clear Filters Button -->
                <button type="button" class="clear-filters-button" id="clearFilters">
                    <span class="dashicons dashicons-dismiss"></span>
                    Clear All Filters
                </button>
                
            </form>
            
        </div>
    </aside>
    
    <!-- Properties Grid Wrapper -->
    <div class="malisafi-properties-grid-wrapper">
        
        <!-- Sorting Controls -->
        <div class="malisafi-sorting-controls">
            <div class="sort-control">
                <span class="sort-label">Sort by:</span>
                <select class="sort-by-select" id="sortBy" data-filter="sort">
                    <option value="date_desc">Newest First</option>
                    <option value="date_asc">Oldest First</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="area_desc">Largest First</option>
                    <option value="featured">Featured First</option>
                </select>
            </div>
            
            <div class="results-count">
                <strong id="totalResults"><?php echo $properties_query->found_posts; ?></strong>
                <span id="resultsText"><?php echo $properties_query->found_posts === 1 ? 'property' : 'properties'; ?> found</span>
            </div>
        </div>

        <!-- Properties Grid -->
        <div class="malisafi-properties-grid" id="propertiesGrid">
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

        <!-- Load More / Pagination -->
        <?php if ($properties_query->max_num_pages > 1) : ?>
        <div class="properties-load-more">
            <button class="load-more-button" data-page="2" data-max-pages="<?php echo $properties_query->max_num_pages; ?>">
                <span class="dashicons dashicons-update"></span>
                Load More Properties
            </button>
            <div class="load-more-info">
                Showing <span class="current-count"><?php echo $properties_query->post_count; ?></span> of <span class="total-count"><?php echo $properties_query->found_posts; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>
        
    </div>
    
</div>
