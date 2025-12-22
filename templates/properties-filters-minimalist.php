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

// Get results page URL (you can customize this)
$results_page_url = home_url('/properties'); // Change to your properties listing page
?>

<div class="malisafi-minimalist-wrapper" data-results-url="<?php echo esc_url($results_page_url); ?>">
    
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

    <!-- Info Message -->
    <div class="filter-info-message">
        <p>Select your search criteria above and click Search to find properties.</p>
    </div>

</div>
