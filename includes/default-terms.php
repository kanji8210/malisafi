<?php
/**
 * Initialize Default Property Types and Statuses
 * 
 * This file can be run manually or called during plugin activation
 * to ensure default terms exist.
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize default property types and statuses
 */
function malisafi_initialize_default_terms() {
    // Default property types
    $property_types = array(
        'Apartment' => array(
            'description' => 'Modern apartments and flats',
            'parent' => 0
        ),
        'House' => array(
            'description' => 'Single-family houses',
            'parent' => 0
        ),
        'Villa' => array(
            'description' => 'Luxury villas',
            'parent' => 0
        ),
        'Townhouse' => array(
            'description' => 'Townhouses and row houses',
            'parent' => 0
        ),
        'Bungalow' => array(
            'description' => 'Single-story bungalows',
            'parent' => 0
        ),
        'Mansion' => array(
            'description' => 'Large luxury mansions',
            'parent' => 0
        ),
        'Land' => array(
            'description' => 'Vacant land and plots',
            'parent' => 0
        ),
        'Commercial' => array(
            'description' => 'Commercial properties',
            'parent' => 0
        ),
        'Office' => array(
            'description' => 'Office spaces',
            'parent' => 0
        ),
        'Shop' => array(
            'description' => 'Retail shops and stores',
            'parent' => 0
        ),
        'Warehouse' => array(
            'description' => 'Warehouses and storage facilities',
            'parent' => 0
        ),
        'Farm' => array(
            'description' => 'Farms and agricultural land',
            'parent' => 0
        ),
    );
    
    $created_types = array();
    foreach ($property_types as $type => $args) {
        if (!term_exists($type, 'malisafi_property_type')) {
            $result = wp_insert_term($type, 'malisafi_property_type', array(
                'description' => $args['description'],
                'slug' => sanitize_title($type),
                'parent' => $args['parent']
            ));
            
            if (!is_wp_error($result)) {
                $created_types[] = $type;
            }
        }
    }
    
    // Default listing statuses
    $listing_statuses = array(
        'For Sale' => array(
            'description' => 'Property available for purchase',
            'color' => '#00a32a'
        ),
        'For Rent' => array(
            'description' => 'Property available for long-term rental (monthly/yearly)',
            'color' => '#2271b1'
        ),
        'Short Term Rent' => array(
            'description' => 'Property available for short-term rental - daily/weekly (Airbnb type)',
            'color' => '#f0c33c'
        ),
        'Sold' => array(
            'description' => 'Property has been sold',
            'color' => '#646970'
        ),
        'Rented' => array(
            'description' => 'Property has been rented',
            'color' => '#646970'
        ),
        'Off Market' => array(
            'description' => 'Property temporarily off market',
            'color' => '#dba617'
        ),
    );
    
    $created_statuses = array();
    foreach ($listing_statuses as $status => $args) {
        if (!term_exists($status, 'malisafi_property_status')) {
            $result = wp_insert_term($status, 'malisafi_property_status', array(
                'description' => $args['description'],
                'slug' => sanitize_title($status)
            ));
            
            if (!is_wp_error($result)) {
                $created_statuses[] = $status;
                
                // Store color as term meta
                if (isset($args['color'])) {
                    update_term_meta($result['term_id'], 'status_color', $args['color']);
                }
            }
        }
    }
    
    return array(
        'types' => $created_types,
        'statuses' => $created_statuses
    );
}

/**
 * Get status color
 */
function malisafi_get_status_color($term_id) {
    $color = get_term_meta($term_id, 'status_color', true);
    return $color ? $color : '#2271b1';
}
