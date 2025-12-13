<?php
/**
 * City List Template
 * 
 * Displays a list/grid of cities with property counts
 * Each city links to the search page with the city filter preselected
 */

defined('ABSPATH') || exit;

// Get all location terms from malisafi_property_location taxonomy
$args = array(
    'taxonomy' => 'malisafi_property_location',
    'hide_empty' => true,
    'orderby' => $atts['orderby'],
    'order' => $atts['order'],
    'number' => 0 // Get all
);

$locations = get_terms($args);

// Filter locations by minimum count
$min_count = (int)$atts['min_count'];
$filtered_locations = array();

if (!empty($locations) && !is_wp_error($locations)) {
    foreach ($locations as $location) {
        if ($location->count >= $min_count) {
            $filtered_locations[] = $location;
        }
    }
}

// Sort by count if needed (get_terms doesn't sort by count properly sometimes)
if ($atts['orderby'] === 'count') {
    usort($filtered_locations, function($a, $b) use ($atts) {
        if ($atts['order'] === 'DESC') {
            return $b->count - $a->count;
        }
        return $a->count - $b->count;
    });
}

// Enqueue styles and scripts
wp_enqueue_style('malisafi-city-list', plugins_url('assets/css/city-list.css', dirname(__FILE__)), array(), '1.0');
wp_enqueue_script('malisafi-city-list', plugins_url('assets/js/city-list.js', dirname(__FILE__)), array('jquery'), '1.0', true);

// Get search page URL
$search_page_url = home_url($atts['search_page']);

// Localize script with settings
wp_localize_script('malisafi-city-list', 'malisafiCityList', array(
    'searchPageUrl' => $search_page_url,
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('malisafi_city_list')
));

$layout = sanitize_text_field($atts['layout']);
$columns = (int)$atts['columns'];
$show_count = $atts['show_count'] === 'yes';
$show_image = $atts['show_image'] === 'yes';

?>

<div class="malisafi-city-list-container" data-layout="<?php echo esc_attr($layout); ?>">
    
    <?php if (!empty($filtered_locations)): ?>
        
        <!-- Search/Filter for cities -->
        <div class="city-list-header">
            <div class="city-search-box">
                <input type="text" 
                       id="city-search-input" 
                       class="city-search-input" 
                       placeholder="<?php esc_attr_e('Search cities...', 'malisafi-mls'); ?>" 
                       autocomplete="off">
                <span class="search-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </span>
            </div>
            <div class="city-count-summary">
                <?php printf(_n('%s city', '%s cities', count($filtered_locations), 'malisafi-mls'), '<span class="total-cities">' . count($filtered_locations) . '</span>'); ?>
            </div>
        </div>
        
        <!-- City Grid/List -->
        <div class="city-list city-list-<?php echo esc_attr($layout); ?>" 
             data-columns="<?php echo esc_attr($columns); ?>"
             style="<?php echo $layout === 'grid' ? '--columns: ' . esc_attr($columns) . ';' : ''; ?>">
            
            <?php foreach ($filtered_locations as $location): ?>
                <?php
                    $location_slug = $location->slug;
                    $location_name = $location->name;
                    $location_count = $location->count;
                    $location_link = add_query_arg('location', $location_slug, $search_page_url);
                    
                    // Get location image (featured image from term meta)
                    $location_image = '';
                    if ($show_image) {
                        $thumbnail_id = get_term_meta($location->term_id, 'thumbnail_id', true);
                        if ($thumbnail_id) {
                            $location_image = wp_get_attachment_image_url($thumbnail_id, 'medium');
                        }
                        
                        // Fallback to a placeholder or default city image
                        if (!$location_image) {
                            $location_image = plugins_url('assets/images/city-placeholder.jpg', dirname(__FILE__));
                        }
                    }
                ?>
                
                <div class="city-item" data-city-name="<?php echo esc_attr(strtolower($location_name)); ?>">
                    <a href="<?php echo esc_url($location_link); ?>" class="city-link">
                        
                        <?php if ($show_image && $location_image): ?>
                            <div class="city-image-wrapper">
                                <img src="<?php echo esc_url($location_image); ?>" 
                                     alt="<?php echo esc_attr($location_name); ?>"
                                     class="city-image">
                                <div class="city-overlay"></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="city-content">
                            <h3 class="city-name"><?php echo esc_html($location_name); ?></h3>
                            
                            <?php if ($show_count): ?>
                                <div class="city-property-count">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                                    </svg>
                                    <span><?php printf(_n('%s property', '%s properties', $location_count, 'malisafi-mls'), number_format_i18n($location_count)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="city-arrow">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </div>
                        
                    </a>
                </div>
                
            <?php endforeach; ?>
            
        </div>
        
        <!-- No results message (hidden by default) -->
        <div class="city-no-results" style="display: none;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <p><?php _e('No cities found matching your search.', 'malisafi-mls'); ?></p>
        </div>
        
    <?php else: ?>
        
        <div class="city-list-empty">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <p><?php _e('No cities available with properties yet.', 'malisafi-mls'); ?></p>
        </div>
        
    <?php endif; ?>
    
</div>
