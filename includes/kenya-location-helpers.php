<?php
/**
 * Kenya Location Helpers
 * 
 * Helper functions for Kenya-specific location data
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all Kenya counties
 *
 * @return array
 */
function malisafi_get_kenya_counties() {
    $counties = array(
        'Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Malindi', 'Kitale',
        'Garissa', 'Kakamega', 'Machakos', 'Meru', 'Nyeri', 'Kiambu', 'Kajiado', 'Kilifi',
        'Trans Nzoia', 'Uasin Gishu', 'Bungoma', 'Siaya', 'Kisii', 'Kericho', 'Kitui', 'Migori',
        'Baringo', 'Bomet', 'Busia', 'Elgeyo-Marakwet', 'Embu', 'Homa Bay', 'Isiolo',
        'Kirinyaga', 'Kwale', 'Laikipia', 'Lamu', 'Makueni', 'Mandera', 'Marsabit',
        'Murang\'a', 'Nandi', 'Narok', 'Nyandarua', 'Nyamira', 'Samburu', 'Taita-Taveta',
        'Tana River', 'Tharaka-Nithi', 'Turkana', 'Vihiga', 'Wajir', 'West Pokot'
    );
    sort($counties);
    return $counties;
}

/**
 * Get subcounties by county name
 *
 * Uses the malisafi_property_location taxonomy with county as parent
 *
 * @param string $county County name
 * @return array
 */
function malisafi_get_subcounties_by_county($county) {
    $county = is_string($county) ? trim($county) : '';
    if ($county === '') {
        return array();
    }

    $json_path = defined('MALISAFI_MLS_PATH') ? MALISAFI_MLS_PATH . 'data/kenya-subcounties.json' : '';
    if ($json_path && file_exists($json_path)) {
        $raw = file_get_contents($json_path);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            $match = null;
            foreach ($data as $county_name => $subcounties) {
                if (strcasecmp($county_name, $county) === 0) {
                    $match = $subcounties;
                    break;
                }
            }

            if (is_array($match)) {
                return array_values(array_filter(array_map(function($item) {
                    if (is_array($item) && isset($item['name'])) {
                        return $item['name'];
                    }
                    if (is_string($item)) {
                        return $item;
                    }
                    return null;
                }, $match)));
            }
        }
    }

    // Fallback to taxonomy hierarchy if JSON not available
    $county_term = get_term_by('name', $county, 'malisafi_property_location');
    if (!$county_term || is_wp_error($county_term)) {
        $county_term = get_term_by('slug', sanitize_title($county), 'malisafi_property_location');
    }

    if (!$county_term || is_wp_error($county_term)) {
        return array();
    }

    $subcounty_terms = get_terms(array(
        'taxonomy' => 'malisafi_property_location',
        'hide_empty' => false,
        'parent' => $county_term->term_id
    ));

    if (empty($subcounty_terms) || is_wp_error($subcounty_terms)) {
        return array();
    }

    return array_map(function($term) {
        return $term->name;
    }, $subcounty_terms);
}

/**
 * Get area settings
 *
 * @return array
 */
function malisafi_get_area_settings() {
    return array(
        'urban' => __('Urban', 'malisafi-mls'),
        'semi-rural' => __('Semi-Rural', 'malisafi-mls'),
        'rural' => __('Rural', 'malisafi-mls'),
        'isolated' => __('Isolated', 'malisafi-mls')
    );
}

/**
 * Get area setting label
 *
 * @param string $setting Setting key
 * @return string
 */
function malisafi_get_setting_label($setting) {
    $settings = malisafi_get_area_settings();
    return isset($settings[$setting]) ? $settings[$setting] : $setting;
}

/**
 * Get property location display string
 *
 * @param int $property_id Property ID
 * @param string $format Format: 'full', 'short', 'county-only'
 * @return string
 */
function malisafi_get_property_location($property_id, $format = 'full') {
    $neighbourhood = get_post_meta($property_id, '_malisafi_neighbourhood', true);
    $subcounty = get_post_meta($property_id, '_malisafi_subcounty', true);
    $city = get_post_meta($property_id, '_malisafi_city', true);
    $county = get_post_meta($property_id, '_malisafi_county', true);
    $setting = get_post_meta($property_id, '_malisafi_setting', true);
    
    $location_parts = array();
    
    switch ($format) {
        case 'county-only':
            return $county ? $county : __('Kenya', 'malisafi-mls');
            
        case 'short':
            if ($neighbourhood) {
                $location_parts[] = $neighbourhood;
            }
            if ($city) {
                $location_parts[] = $city;
            }
            if ($subcounty) {
                $location_parts[] = $subcounty;
            }
            break;
            
        case 'full':
        default:
            if ($neighbourhood) {
                $location_parts[] = $neighbourhood;
            }
            if ($city) {
                $location_parts[] = $city;
            }
            if ($subcounty) {
                $location_parts[] = $subcounty;
            }
            if ($county) {
                $location_parts[] = $county;
            }
            if ($setting) {
                $location_parts[] = '(' . malisafi_get_setting_label($setting) . ')';
            }
            break;
    }
    
    return !empty($location_parts) ? implode(', ', $location_parts) : __('Kenya', 'malisafi-mls');
}

/**
 * Get popular neighbourhoods by county
 *
 * @param string $county County name
 * @return array
 */
function malisafi_get_popular_neighbourhoods($county = '') {
    $neighbourhoods = array(
        'Nairobi' => array(
            'Karen', 'Westlands', 'Kilimani', 'Lavington', 'Parklands', 'Kileleshwa',
            'South B', 'South C', 'Embakasi', 'Kasarani', 'Kahawa West', 'Runda',
            'Muthaiga', 'Kitisuru', 'Spring Valley', 'Upperhill', 'Kilimani', 'Ngara',
            'Eastleigh', 'Donholm', 'Umoja', 'Buruburu', 'Athi River', 'Syokimau'
        ),
        'Mombasa' => array(
            'Nyali', 'Bamburi', 'Shanzu', 'Kizingo', 'Old Town', 'Likoni',
            'Changamwe', 'Kisauni', 'Mvita', 'Buxton'
        ),
        'Kisumu' => array(
            'Milimani', 'Mamboleo', 'Tom Mboya', 'Kondele', 'Nyalenda', 'Migosi'
        ),
        'Nakuru' => array(
            'Milimani', 'Section 58', 'Naka', 'Lanet', 'Free Area', 'Pipeline'
        ),
        'Kiambu' => array(
            'Ruaka', 'Ruiru', 'Kikuyu', 'Limuru', 'Kiambu Town', 'Thika Road', 'Juja'
        ),
    );
    
    if ($county && isset($neighbourhoods[$county])) {
        return $neighbourhoods[$county];
    }
    
    return $neighbourhoods;
}
