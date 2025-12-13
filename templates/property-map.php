<?php
/**
 * Property Map Template
 * 
 * Displays an interactive map with property markers
 * Falls back to city/neighborhood geocoding when GPS coordinates are not available
 */

defined('ABSPATH') || exit;

// Build the query arguments
$query_args = array(
    'post_type' => 'malisafi_property',
    'post_status' => 'publish',
    'posts_per_page' => (int)$atts['count'],
    'fields' => 'ids' // Only get IDs for better performance
);

// Add type filter
if (!empty($atts['type'])) {
    $query_args['tax_query'][] = array(
        'taxonomy' => 'property_type',
        'field' => 'slug',
        'terms' => $atts['type']
    );
}

// Add status filter
if (!empty($atts['status'])) {
    $query_args['tax_query'][] = array(
        'taxonomy' => 'property_status',
        'field' => 'slug',
        'terms' => $atts['status']
    );
}

// Add location filter
if (!empty($atts['location'])) {
    $query_args['tax_query'][] = array(
        'taxonomy' => 'malisafi_property_location',
        'field' => 'slug',
        'terms' => $atts['location']
    );
}

// Query properties
$properties_query = new WP_Query($query_args);
$properties = array();

if ($properties_query->have_posts()) {
    foreach ($properties_query->posts as $property_id) {
        // Get GPS coordinates
        $latitude = get_post_meta($property_id, '_property_latitude', true);
        $longitude = get_post_meta($property_id, '_property_longitude', true);
        
        // If no GPS coordinates, try to geocode from location taxonomy
        if (empty($latitude) || empty($longitude)) {
            $location_terms = wp_get_post_terms($property_id, 'malisafi_property_location');
            
            if (!empty($location_terms) && !is_wp_error($location_terms)) {
                $location_name = $location_terms[0]->name;
                
                // Check if we have cached geocoding result
                $cache_key = 'malisafi_geocode_' . sanitize_title($location_name);
                $cached_coords = get_transient($cache_key);
                
                if ($cached_coords !== false) {
                    $latitude = $cached_coords['lat'];
                    $longitude = $cached_coords['lng'];
                } else {
                    // Use Nominatim (OpenStreetMap) for geocoding - free and no API key required
                    $geocode_url = add_query_arg(array(
                        'format' => 'json',
                        'q' => $location_name . ', Kenya',
                        'limit' => 1
                    ), 'https://nominatim.openstreetmap.org/search');
                    
                    $response = wp_remote_get($geocode_url, array(
                        'timeout' => 5,
                        'headers' => array(
                            'User-Agent' => 'Malisafi MLS WordPress Plugin'
                        )
                    ));
                    
                    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                        $body = json_decode(wp_remote_retrieve_body($response), true);
                        
                        if (!empty($body) && isset($body[0]['lat']) && isset($body[0]['lon'])) {
                            $latitude = $body[0]['lat'];
                            $longitude = $body[0]['lon'];
                            
                            // Cache the result for 30 days
                            set_transient($cache_key, array(
                                'lat' => $latitude,
                                'lng' => $longitude
                            ), 30 * DAY_IN_SECONDS);
                        }
                    }
                }
            }
        }
        
        // Only add properties with valid coordinates
        if (!empty($latitude) && !empty($longitude)) {
            $property_data = array(
                'id' => $property_id,
                'title' => get_the_title($property_id),
                'url' => get_permalink($property_id),
                'lat' => (float)$latitude,
                'lng' => (float)$longitude,
                'price' => get_post_meta($property_id, '_property_price', true),
                'image' => get_the_post_thumbnail_url($property_id, 'thumbnail')
            );
            
            // Get property type
            $types = wp_get_post_terms($property_id, 'property_type');
            if (!empty($types) && !is_wp_error($types)) {
                $property_data['type'] = $types[0]->name;
            }
            
            // Get property status
            $statuses = wp_get_post_terms($property_id, 'property_status');
            if (!empty($statuses) && !is_wp_error($statuses)) {
                $property_data['status'] = $statuses[0]->name;
            }
            
            $properties[] = $property_data;
        }
    }
    
    wp_reset_postdata();
}

// Enqueue Leaflet CSS and JS (open source alternative to Google Maps)
wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

// Enqueue marker clustering if enabled
if ($atts['cluster'] === 'yes' && count($properties) > 10) {
    wp_enqueue_style('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css', array('leaflet'), '1.5.3');
    wp_enqueue_style('leaflet-markercluster-default', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css', array('leaflet-markercluster'), '1.5.3');
    wp_enqueue_script('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', array('leaflet'), '1.5.3', true);
}

// Enqueue custom map script
wp_enqueue_script('malisafi-property-map', plugins_url('assets/js/property-map.js', dirname(__FILE__)), array('jquery', 'leaflet'), '1.0', true);
wp_enqueue_style('malisafi-property-map', plugins_url('assets/css/property-map.css', dirname(__FILE__)), array('leaflet'), '1.0');

// Localize script with property data
wp_localize_script('malisafi-property-map', 'malisafiMapData', array(
    'properties' => $properties,
    'zoom' => (int)$atts['zoom'],
    'cluster' => $atts['cluster'] === 'yes',
    'center' => !empty($properties) ? array(
        'lat' => $properties[0]['lat'],
        'lng' => $properties[0]['lng']
    ) : array(
        'lat' => -1.286389, // Nairobi default
        'lng' => 36.817223
    )
));

$map_height = (int)$atts['height'];
?>

<div class="malisafi-property-map-container">
    <?php if (!empty($properties)): ?>
        <div id="malisafi-property-map" style="height: <?php echo esc_attr($map_height); ?>px;"></div>
        <div class="map-property-count">
            <?php printf(_n('%s property found', '%s properties found', count($properties), 'malisafi-mls'), number_format_i18n(count($properties))); ?>
        </div>
    <?php else: ?>
        <div class="no-properties-message">
            <p><?php _e('No properties found with valid locations.', 'malisafi-mls'); ?></p>
        </div>
    <?php endif; ?>
</div>
