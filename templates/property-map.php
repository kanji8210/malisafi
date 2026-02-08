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
    'fields' => 'ids', // Only get IDs for better performance
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => '_malisafi_latitude',
            'compare' => 'EXISTS'
        ),
        array(
            'key' => '_malisafi_county',
            'compare' => 'EXISTS'
        )
    )
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
        'taxonomy' => 'malisafi_property_status',
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
$total_properties = $properties_query->post_count;
$properties_with_coords = 0;

if ($properties_query->have_posts()) {
    foreach ($properties_query->posts as $property_id) {
        // Get GPS coordinates
        $latitude = get_post_meta($property_id, '_malisafi_latitude', true);
        $longitude = get_post_meta($property_id, '_malisafi_longitude', true);
        
        // If no GPS coordinates, try to geocode from county meta field first
        if (empty($latitude) || empty($longitude)) {
            $location_name = '';
            
            // Priority 1: County meta field (Kenya-specific)
            $county = get_post_meta($property_id, '_malisafi_county', true);
            if (!empty($county)) {
                $location_name = $county . ', Kenya';
            }
            
            // Priority 2: Location taxonomy (fallback)
            if (empty($location_name)) {
                $location_terms = wp_get_post_terms($property_id, 'malisafi_property_location');
                if (!empty($location_terms) && !is_wp_error($location_terms)) {
                    $location_name = $location_terms[0]->name . ', Kenya';
                }
            }
            
            if (!empty($location_name)) {
                
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
                        'q' => $location_name,
                        'limit' => 1,
                        'countrycodes' => 'ke'
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
            // Privacy/Security: Offset coordinates for non-admin users
            // Admins see exact location, public sees offset by 200-400m
            if (!current_user_can('manage_options')) {
                // Generate consistent offset based on property ID (same property = same offset)
                $seed = intval($property_id);
                mt_srand($seed);
                
                // Random offset between 200-400 meters
                // 1 degree latitude ≈ 111km, so 300m ≈ 0.0027 degrees
                $offset_distance = (mt_rand(200, 400) / 1000) / 111; // Convert meters to degrees
                $offset_angle = mt_rand(0, 360); // Random direction
                
                // Apply offset
                $latitude = floatval($latitude) + ($offset_distance * cos(deg2rad($offset_angle)));
                $longitude = floatval($longitude) + ($offset_distance * sin(deg2rad($offset_angle)));
                
                // Reset random seed
                mt_srand();
            }
            
            // Get property data
            $price = get_post_meta($property_id, '_malisafi_price', true);
            $currency = get_post_meta($property_id, '_malisafi_currency', true) ?: 'USD';
            
            // Get thumbnail URL with fallback
            $thumbnail_url = get_the_post_thumbnail_url($property_id, 'thumbnail');
            if (empty($thumbnail_url)) {
                $thumbnail_url = plugins_url('malisafi/assets/images/placeholder-property.svg');
            }
            
            $property_data = array(
                'id' => $property_id,
                'title' => get_the_title($property_id),
                'url' => get_permalink($property_id),
                'lat' => (float)$latitude,
                'lng' => (float)$longitude,
                'price' => $price,
                'currency' => $currency,
                'image' => $thumbnail_url
            );
            
            // Get property type
            $types = wp_get_post_terms($property_id, 'property_type');
            if (!empty($types) && !is_wp_error($types)) {
                $property_data['type'] = $types[0]->name;
            }
            
            // Get property status from listing_type
            $listing_type = get_post_meta($property_id, '_malisafi_listing_type', true);
            if (!empty($listing_type)) {
                $status_map = array(
                    'sale' => 'For Sale',
                    'rent' => 'For Rent',
                    'lease' => 'For Lease',
                    'short_term' => 'Short Term Rent'
                );
                $property_data['status'] = isset($status_map[$listing_type]) ? $status_map[$listing_type] : ucfirst($listing_type);
            }
            
            $properties[] = $property_data;
            $properties_with_coords++;
        }
    }
    
    wp_reset_postdata();
}

// Debug info (only for admins)
if (current_user_can('manage_options')) {
    echo '<!-- Map Debug Info:
    - Total properties queried: ' . $total_properties . '
    - Properties with coordinates: ' . $properties_with_coords . '
    - Properties missing location: ' . ($total_properties - $properties_with_coords) . '
    -->';
    
    // Show visible debug notice if there's a discrepancy
    if ($total_properties > 0 && $properties_with_coords === 0) {
        echo '<div class="notice notice-warning" style="margin: 20px 0; padding: 10px;">';
        echo '<p><strong>Admin Debug:</strong> ' . $total_properties . ' properties found but none have GPS coordinates or county information for geocoding.</p>';
        echo '<p>Properties need either GPS coordinates (_malisafi_latitude/_malisafi_longitude) or a county (_malisafi_county) to appear on the map.</p>';
        echo '</div>';
    } elseif ($properties_with_coords < $total_properties) {
        echo '<div class="notice notice-info" style="margin: 20px 0; padding: 10px;">';
        echo '<p><strong>Admin Info:</strong> ' . $properties_with_coords . ' of ' . $total_properties . ' properties visible on map. ' . ($total_properties - $properties_with_coords) . ' missing location data.</p>';
        echo '</div>';
    }
}

// Enqueue Leaflet CSS and JS (open source alternative to Google Maps)
wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

// Enqueue Leaflet Geocoder for address search
wp_enqueue_style('leaflet-geocoder', 'https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css', array('leaflet'), '2.4.0');
wp_enqueue_script('leaflet-geocoder', 'https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js', array('leaflet'), '2.4.0', true);

// Enqueue marker clustering if enabled
if ($atts['cluster'] === 'yes' && count($properties) > 10) {
    wp_enqueue_style('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css', array('leaflet'), '1.5.3');
    wp_enqueue_style('leaflet-markercluster-default', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css', array('leaflet-markercluster'), '1.5.3');
    wp_enqueue_script('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', array('leaflet'), '1.5.3', true);
}

// Enqueue custom map script
wp_enqueue_script('malisafi-property-map', plugins_url('assets/js/property-map.js', dirname(__FILE__)), array('jquery', 'leaflet'), '1.0', true);

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
        <?php if (current_user_can('manage_options')): ?>
            <div class="admin-notice" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 15px;">
                <p style="margin: 0; font-size: 13px;">
                    <strong>🔒 Admin View:</strong> You are seeing the <strong>exact GPS coordinates</strong> for all properties. 
                    Public users will see locations offset by 200-400 meters for privacy and security.
                </p>
            </div>
        <?php endif; ?>
        <div class="map-controls">
            <div class="map-property-count">
                <?php printf(_n('%s property found', '%s properties found', count($properties), 'malisafi-mls'), number_format_i18n(count($properties))); ?>
            </div>
            <div class="map-control-buttons">
                <button id="near-me-btn" class="map-control-btn" title="<?php _e('Search Near You', 'malisafi-mls'); ?>">
                    <span class="dashicons dashicons-location"></span>
                    <span class="btn-text"><?php _e('Near Me', 'malisafi-mls'); ?></span>
                </button>
                <button id="fullscreen-map-btn" class="fullscreen-btn" title="<?php _e('Fullscreen View', 'malisafi-mls'); ?>">
                    <span class="dashicons dashicons-fullscreen-alt"></span>
                    <span class="btn-text"><?php _e('Fullscreen', 'malisafi-mls'); ?></span>
                </button>
            </div>
        </div>
        <div id="malisafi-property-map" style="height: <?php echo esc_attr($map_height); ?>px;"></div>
    <?php else: ?>
        <div class="no-properties-message">
            <p><?php _e('No properties found with valid locations.', 'malisafi-mls'); ?></p>
        </div>
    <?php endif; ?>
</div>
