<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) : the_post();
    $property_id = get_the_ID();
    
    // Get property meta
    $price = get_post_meta($property_id, '_malisafi_price', true);
    $currency = get_post_meta($property_id, '_malisafi_currency', true) ?: 'USD';
    $bedrooms = get_post_meta($property_id, '_malisafi_bedrooms', true);
    $bathrooms = get_post_meta($property_id, '_malisafi_bathrooms', true);

    // Size and area
    $size = get_post_meta($property_id, '_malisafi_size', true); // numeric floor area
    $size_unit = get_post_meta($property_id, '_malisafi_size_unit', true) ?: 'sqm';
    $area_name = get_post_meta($property_id, '_malisafi_area', true); // neighborhood/text area

    // Get status from listing_type meta
    $listing_type = get_post_meta($property_id, '_malisafi_listing_type', true);
    $status = '';
    if (!empty($listing_type)) {
        $status_map = array(
            'sale' => 'For Sale',
            'rent' => 'For Rent',
            'lease' => 'For Lease',
            'short_term' => 'Short Term Rent'
        );
        $status = isset($status_map[$listing_type]) ? $status_map[$listing_type] : ucfirst($listing_type);
    }
    
    $featured = get_post_meta($property_id, '_malisafi_featured', true);
    $verified = get_post_meta($property_id, '_malisafi_verified', true);
    
    // Location data
    $city = get_post_meta($property_id, '_malisafi_city', true);
    $subcounty = get_post_meta($property_id, '_malisafi_subcounty', true);
    $county = get_post_meta($property_id, '_malisafi_county', true);
    $country = get_post_meta($property_id, '_malisafi_country', true) ?: 'Kenya';
    $latitude  = get_post_meta($property_id, '_malisafi_latitude', true);
    $longitude = get_post_meta($property_id, '_malisafi_longitude', true);

    // Geocode from county if no GPS coords stored
    if ((empty($latitude) || empty($longitude)) && !empty($county)) {
        $location_name = $county . ', Kenya';
        $cache_key = 'malisafi_geocode_' . sanitize_title($location_name);
        $cached_coords = get_transient($cache_key);
        if ($cached_coords !== false) {
            $latitude  = $cached_coords['lat'];
            $longitude = $cached_coords['lng'];
        } else {
            $geocode_url = add_query_arg(array(
                'format'       => 'json',
                'q'            => $location_name,
                'limit'        => 1,
                'countrycodes' => 'ke',
            ), 'https://nominatim.openstreetmap.org/search');
            $response = wp_remote_get($geocode_url, array(
                'timeout' => 5,
                'headers' => array('User-Agent' => 'Malisafi MLS WordPress Plugin'),
            ));
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body) && isset($body[0]['lat'], $body[0]['lon'])) {
                    $latitude  = $body[0]['lat'];
                    $longitude = $body[0]['lon'];
                    set_transient($cache_key, array('lat' => $latitude, 'lng' => $longitude), 30 * DAY_IN_SECONDS);
                }
            }
        }
    }

    // Privacy offset for non-admins (consistent per property ID)
    $map_lat = '';
    $map_lng = '';
    if (!empty($latitude) && !empty($longitude)) {
        if (!current_user_can('manage_options')) {
            $offset_meters = absint(get_option('malisafi_mls_map_public_offset_meters', 100));
            $offset_meters = min(800, max(0, $offset_meters));
            $offset_deg    = ($offset_meters / 1000) / 111;
            mt_srand(intval($property_id));
            $angle   = mt_rand(0, 360);
            mt_srand();
            $map_lat = floatval($latitude)  + ($offset_deg * cos(deg2rad($angle)));
            $map_lng = floatval($longitude) + ($offset_deg * sin(deg2rad($angle)));
        } else {
            $map_lat = floatval($latitude);
            $map_lng = floatval($longitude);
        }
    }

    // Enqueue Leaflet for the single-property map
    if (!empty($map_lat) && !empty($map_lng)) {
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
    }
    
    // Additional details
    $year_built = get_post_meta($property_id, '_malisafi_year_built', true);
    $garage = get_post_meta($property_id, '_malisafi_garage', true);
    $property_type = wp_get_post_terms($property_id, 'malisafi_property_type', array('fields' => 'names'));
    $property_location = wp_get_post_terms($property_id, 'malisafi_property_location', array('fields' => 'names'));
    
    // Agent/Author info
    $author_id = get_post_field('post_author', $property_id);
    $author_name = get_the_author_meta('display_name', $author_id);
    $author_email = get_the_author_meta('user_email', $author_id);
    $author_phone = get_user_meta($author_id, 'phone', true);
    
    // Gallery - load all attached images
    $gallery_ids = get_post_meta($property_id, '_malisafi_gallery_ids', true);
    
    // If gallery_ids meta exists, use it
    if ($gallery_ids) {
        $gallery_images = array_filter(array_map('trim', explode(',', $gallery_ids)));
    } else {
        // Fallback: get all images attached to this post
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_parent' => $property_id,
            'post_mime_type' => 'image',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        $gallery_images = array();
        foreach ($attachments as $attachment) {
            $gallery_images[] = $attachment->ID;
        }
    }
    
    // Features and amenities from meta arrays (primary)
    $features = get_post_meta($property_id, '_malisafi_features', true);
    $amenities = get_post_meta($property_id, '_malisafi_amenities', true);
    $features = is_array($features) ? $features : array();
    $amenities = is_array($amenities) ? $amenities : array();

    // Fallback: use taxonomy terms if meta arrays are empty
    if (empty($features)) {
        $feature_terms = wp_get_post_terms($property_id, 'malisafi_property_features', array('fields' => 'names'));
        if (!is_wp_error($feature_terms) && !empty($feature_terms)) {
            $features = $feature_terms;
        }
    }
    
    // Format price
    $currency_symbol = ($currency === 'KES') ? 'KSh' : '$';
    $price = is_numeric($price) ? (float) $price : 0.0;
$formatted_price = $price > 0 ? ($currency_symbol . ' ' . number_format($price)) : __('Price on request', 'malisafi-mls');
    
    // Check if user has favorited this property
    $user_id = get_current_user_id();
    $is_favorited = false;
    if ($user_id) {
        $favorites = get_user_meta($user_id, '_malisafi_favorites', true);
        $favorites = $favorites ? explode(',', $favorites) : array();
        $is_favorited = in_array($property_id, $favorites);
    }

?>

<div class="malisafi-single-property">
    
    <!-- Property Gallery -->
    <section class="property-gallery">
        <?php 
        // Prepare all images for thumbnails
        $all_images = array();
        $featured_img = get_post_thumbnail_id($property_id);
        
        // Add featured image first
        if ($featured_img) {
            $all_images[] = $featured_img;
        }
        
        // Add gallery images (exclude featured if it's already in gallery)
        if (!empty($gallery_images)) {
            foreach ($gallery_images as $img_id) {
                if ($img_id != $featured_img) {
                    $all_images[] = $img_id;
                }
            }
        }
        ?>
        
        <div class="gallery-main-wrapper">
            <div class="gallery-main">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="main-image-container">
                        <img src="<?php echo get_the_post_thumbnail_url($property_id, 'malisafi_landscape'); ?>" 
                             alt="<?php echo esc_attr(get_the_title()); ?>" 
                             class="main-image" 
                             data-current-index="0">
                        
                        <!-- Property Badges -->
                        <div class="gallery-badges">
                            <?php if ($verified) : ?>
                                <span class="badge verified">
                                    <svg class="badge-icon" width="14" height="14" viewBox="0 0 24 24" fill="none">
                                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" 
                                              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Verified
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($featured) : ?>
                                <span class="badge featured">Featured</span>
                            <?php endif; ?>
                            
                            <?php if ($status) : ?>
                                <span class="badge status" data-status="<?php echo esc_attr(strtolower($status)); ?>"><?php echo esc_html($status); ?></span>
                            <?php else : ?>
                                <span class="badge status" data-status="not-recorded" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);">Status Not Recorded</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="main-image-container">
                        <img src="<?php echo plugins_url('malisafi/assets/images/placeholder-property.svg'); ?>" 
                             alt="<?php echo esc_attr(get_the_title()); ?>" 
                             class="main-image" 
                             data-current-index="0">
                        <?php if ($verified) : ?>
                            <div class="gallery-badges">
                                <span class="badge verified">
                                    <svg class="badge-icon" width="14" height="14" viewBox="0 0 24 24" fill="none">
                                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" 
                                              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Verified
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($all_images) && count($all_images) > 1) : ?>
                <button class="gallery-nav gallery-nav-prev" aria-label="Previous Image">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18L9 12L15 6"/>
                    </svg>
                </button>

                <button class="gallery-nav gallery-nav-next" aria-label="Next Image">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 6L15 12L9 18"/>
                    </svg>
                </button>
                <?php endif; ?>

                <?php if (!empty($all_images) && count($all_images) > 1) : ?>
                <div class="gallery-counter">
                    <span class="current">1</span> / <span class="total"><?php echo count($all_images); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($all_images)) : ?>
        <div class="gallery-thumbnails">
           <button class="thumbs-nav thumbs-prev" aria-label="Scroll left" type="button">&#10094;</button>
           <button class="thumbs-nav thumbs-next" aria-label="Scroll right" type="button">&#10095;</button>
            <?php foreach ($all_images as $index => $img_id) : 
                $img_url = wp_get_attachment_image_url($img_id, 'malisafi_grid');
                if ($img_url) :
                    $alt_text = get_post_meta($img_id, '_wp_attachment_image_alt', true);
                    // If no alt text exists, use size if available
                    if (empty($alt_text) && $size) {
                        $unit_label = $size_unit === 'sqft' ? 'sq ft' : ($size_unit === 'sqm' ? 'sq m' : $size_unit);
                        $alt_text = is_numeric($size) ? (number_format((float)$size) . ' ' . $unit_label) : '';
                    }
            ?>
                <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                     role="button" tabindex="0" aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                     data-index="<?php echo $index; ?>" 
                     data-image="<?php echo esc_url(wp_get_attachment_image_url($img_id, 'malisafi_landscape')); ?>">
                    <div class="thumbnail-wrapper">
                        <img src="<?php echo esc_url($img_url); ?>" 
                             alt="<?php echo esc_attr(get_the_title() . ' - Image ' . ($index + 1)); ?>">
                        <?php if (!empty($alt_text)) : ?>
                            <span class="thumbnail-badge area-badge">
                                <?php echo esc_html($alt_text); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    
    <div class="property-content-wrapper">
        
        <!-- Main Content -->
        <div class="property-main-content">
            
            <!-- Header -->
            <header class="property-header">
                <div class="property-title-section">
                    <div class="title-badges">
                        <?php if ($verified) : ?>
                            <span class="inline-badge verified-inline">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" 
                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Verified
                            </span>
                        <?php endif; ?>
                        <?php if ($featured) : ?>
                            <span class="inline-badge featured-inline">Featured</span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="property-title"><?php the_title(); ?></h1>

                </div>
                
                <div class="property-actions">
                    <button class="action-button favorite-button <?php echo $is_favorited ? 'favorited' : ''; ?>" 
                            data-property-id="<?php echo $property_id; ?>"
                            aria-label="<?php echo $is_favorited ? 'Remove from favorites' : 'Add to favorites'; ?>">
                        <svg class="action-icon" width="20" height="20" viewBox="0 0 24 24" fill="<?php echo $is_favorited ? 'currentColor' : 'none'; ?>" 
                             stroke="currentColor" stroke-width="1.5">
                            <path d="M12 21.35L10.55 20.03C5.4 15.36 2 12.28 2 8.5C2 5.42 4.42 3 7.5 3C9.24 3 10.91 3.81 12 5.09C13.09 3.81 14.76 3 16.5 3C19.58 3 22 5.42 22 8.5C22 12.28 18.6 15.36 13.45 20.04L12 21.35Z"/>
                        </svg>
                        <span class="action-text"><?php echo $is_favorited ? 'Favorited' : 'Favorite'; ?></span>
                    </button>
                    
                    <button class="action-button share-button" aria-label="Share property">
                        <svg class="action-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="18" cy="5" r="3"/>
                            <circle cx="6" cy="12" r="3"/>
                            <circle cx="18" cy="19" r="3"/>
                            <path d="M8.59 13.51L15.42 17.49"/>
                            <path d="M15.41 6.51L8.59 10.49"/>
                        </svg>
                        <span class="action-text">Share</span>
                    </button>
                    
                    <button class="action-button inquiry-button" data-property-id="<?php echo $property_id; ?>" aria-label="Send inquiry">
                        <svg class="action-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21.2 8.4c.5.38.8.97.8 1.6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10a2 2 0 0 1 .8-1.6l8-6a2 2 0 0 1 2.4 0l8 6Z"/>
                            <path d="m22 10-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 10"/>
                        </svg>
                        <span class="action-text">Inquiry</span>
                    </button>
                    
                    <button class="action-button report-button" data-property-id="<?php echo $property_id; ?>" aria-label="Report property">
                        <svg class="action-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z"/>
                            <path d="M14 2V8H20"/>
                            <path d="M12 18V12"/>
                            <path d="M12 9H12.01"/>
                        </svg>
                        <span class="action-text">Report</span>
                    </button>
                </div>
            </header>
            
            <!-- Property Specs - Inline Icons -->
            <div class="property-specs-inline">
                
                <!-- Price -->
                <div class="spec-item-inline spec-price">
                    <span class="spec-icon-inline price-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L1 21H23L12 2Z"/>
                            <path d="M12 6V18"/>
                            <path d="M17 12H7"/>
                        </svg>
                    </span>
                    <div class="spec-text">
                        <span class="spec-value"><?php echo esc_html($formatted_price); ?></span>
                        <span class="spec-label">Price</span>
                    </div>
                </div>
                
                <!-- Bedrooms -->
                <?php if ($bedrooms) : ?>
                <div class="spec-item-inline spec-bedrooms">
                    <span class="spec-icon-inline bed-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 9.556V3h-2v2H6V3H4v6.557C2.81 10.25 2 11.526 2 13v4a1 1 0 0 0 1 1h1v4h2v-4h12v4h2v-4h1a1 1 0 0 0 1-1v-4c0-1.474-.811-2.75-2-3.444zM11 9H6V7h5v2zm7 0h-5V7h5v2z"/>
                        </svg>
                    </span>
                    <div class="spec-text">
                        <span class="spec-value"><?php echo esc_html($bedrooms); ?></span>
                        <span class="spec-label">Bedrooms</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Bathrooms -->
                <?php if ($bathrooms) : ?>
                <div class="spec-item-inline spec-bathrooms">
                    <span class="spec-icon-inline bathtub-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21 10H7V7c0-1.103.897-2 2-2s2 .897 2 2h2c0-2.206-1.794-4-4-4S5 4.794 5 7v3H3a1 1 0 0 0-1 1v2c0 2.606 1.674 4.823 4 5.65V22h2v-3h8v3h2v-3.35c2.326-.827 4-3.044 4-5.65v-2a1 1 0 0 0-1-1z"/>
                        </svg>
                    </span>
                    <div class="spec-text">
                        <span class="spec-value"><?php echo esc_html($bathrooms); ?></span>
                        <span class="spec-label">Bathrooms</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Area -->
                <?php if ($size) : ?>
                <div class="spec-item-inline spec-area">
                    <span class="spec-icon-inline area-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 6H21V18H3V6Z"/>
                            <path d="M3 10H21"/>
                            <path d="M10 6V18"/>
                        </svg>
                    </span>
                    <div class="spec-text">
                        <span class=\"spec-value\"><?php echo is_numeric($size) ? number_format((float)$size) . ' ' . ($size_unit === 'sqft' ? 'sq ft' : ($size_unit === 'sqm' ? 'sq m' : esc_html($size_unit))) : ''; ?></span>
                        <span class="spec-label"><?php echo $size_unit === 'sqft' ? 'Sq Ft' : ($size_unit === 'sqm' ? 'Sq M' : strtoupper(esc_html($size_unit))); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Location -->
                <?php if ($city || $subcounty || $county) : ?>
                <div class="spec-item-inline spec-location">
                    <span class="spec-icon-inline location-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.364 3.63604C20.0518 5.32387 21 7.61305 21 10Z"/>
                            <path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z"/>
                        </svg>
                    </span>
                    <div class="spec-text">
                        <span class="spec-value">
                            <?php 
                            $location_parts = array_filter(array($city, $subcounty, $county, $country));
                            echo esc_html(implode(', ', $location_parts));
                            ?>
                        </span>
                        <span class="spec-label">Location</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Garage (optional) -->
                <?php if ($garage) : ?>
                <div class="spec-item-inline spec-garage">
                    <span class="spec-icon-inline garage-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 20H18C19.1046 20 20 19.1046 20 18V10C20 8.89543 19.1046 8 18 8H6C4.89543 8 4 8.89543 4 10V18C4 19.1046 4.89543 20 6 20Z"/>
                            <path d="M8 8V5C8 3.89543 8.89543 3 10 3H14C15.1046 3 16 3.89543 16 5V8"/>
                            <path d="M8 16H16"/>
                        </svg>
                    </span>
                    <div class="spec-text">
                        <span class="spec-value"><?php echo esc_html($garage); ?></span>
                        <span class="spec-label">Garage</span>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Description -->
            <section class="property-section">
                <h2 class="section-title">Description</h2>
                <div class="property-description">
                    <?php the_content(); ?>
                </div>
            </section>
            
            <!-- Property Details -->
            <section class="property-section">
                <h2 class="section-title">Property Details</h2>
                <div class="property-details-grid">
                    <?php if (!empty($property_type)) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Property Type</span>
                            <span class="detail-value"><?php echo esc_html($property_type[0]); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($status) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Listing Status</span>
                            <span class="detail-value">
                                <span class="status-badge <?php 
                                    $status_class = 'status-' . sanitize_html_class(strtolower(str_replace(' ', '-', $status)));
                                    echo esc_attr($status_class); 
                                ?>" style="padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;">
                                    <?php echo esc_html($status); ?>
                                </span>
                            </span>
                        </div>
                    <?php else : ?>
                        <div class="detail-item">
                            <span class="detail-label">Listing Status</span>
                            <span class="detail-value">
                                <span class="status-badge status-not-recorded" style="padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white;">
                                    Status Not Recorded
                                </span>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($year_built) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Year Built</span>
                            <span class="detail-value"><?php echo esc_html($year_built); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($bedrooms) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Bedrooms</span>
                            <span class="detail-value"><?php echo esc_html($bedrooms); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($bathrooms) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Bathrooms</span>
                            <span class="detail-value"><?php echo esc_html($bathrooms); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($size) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Area</span>
                            <span class=\"detail-value\"><?php echo is_numeric($size) ? number_format((float)$size) . ' ' . ($size_unit === 'sqft' ? 'sq ft' : ($size_unit === 'sqm' ? 'sq m' : esc_html($size_unit))) : ''; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($garage) : ?>
                        <div class="detail-item">
                            <span class="detail-label">Garage</span>
                            <span class="detail-value"><?php echo esc_html($garage); ?> car<?php echo $garage > 1 ? 's' : ''; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Features & Amenities -->
            <?php 
            $has_any = !empty($features) || !empty($amenities);
            if ($has_any) : ?>
            <section class="property-section">
                <h2 class="section-title">Features & Amenities</h2>
                <div class="property-features-grid">
                    <?php 
                    // Map for known feature keys
                    $feature_labels = array(
                        'pool' => 'Swimming Pool',
                        'gym' => 'Gym',
                        'garden' => 'Garden',
                        'balcony' => 'Balcony',
                        'parking' => 'Parking',
                        'security' => '24/7 Security',
                        'elevator' => 'Elevator',
                        'furnished' => 'Furnished',
                        'air_conditioning' => 'Air Conditioning'
                    );

                    // Normalize features: if associative (key => bool), keep true keys; if list of strings, use them directly
                    $normalized_features = array();
                    if (!empty($features)) {
                        foreach ($features as $k => $v) {
                            if (is_string($k)) {
                                if ($v) { $normalized_features[] = isset($feature_labels[$k]) ? $feature_labels[$k] : ucwords(str_replace('_', ' ', $k)); }
                            } else {
                                // numeric key => value is label
                                if (!empty($v)) { $normalized_features[] = is_string($v) ? $v : strval($v); }
                            }
                        }
                    }

                    // Normalize amenities (list of strings expected)
                    $normalized_amenities = array();
                    if (!empty($amenities)) {
                        foreach ($amenities as $am) {
                            if (!empty($am)) { $normalized_amenities[] = is_string($am) ? ucwords(str_replace('_', ' ', $am)) : strval($am); }
                        }
                    }

                    // Render combined list
                    $items = array_merge($normalized_features, $normalized_amenities);
                    foreach ($items as $label): ?>
                        <div class="feature-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6L9 17L4 12"/>
                            </svg>
                            <span><?php echo esc_html($label); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!empty($map_lat) && !empty($map_lng)) : ?>
            <!-- Location Map -->
            <section class="property-section property-map-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <svg class="section-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?php _e('Location', 'malisafi-mls'); ?>
                    </h2>
                    <?php if (!current_user_can('manage_options')) : ?>
                    <span class="map-privacy-note">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <?php _e('Approximate location shown for privacy', 'malisafi-mls'); ?>
                    </span>
                    <?php else: ?>
                    <span class="map-admin-note">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <?php _e('Admin: exact GPS shown', 'malisafi-mls'); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div id="mls-single-property-map"
                     data-lat="<?php echo esc_attr($map_lat); ?>"
                     data-lng="<?php echo esc_attr($map_lng); ?>"
                     data-title="<?php echo esc_attr(get_the_title($property_id)); ?>"
                     data-label="<?php echo esc_attr(implode(', ', array_filter(array($city, $county)))); ?>"
                     style="height: 340px; border-radius: var(--radius-lg); overflow: hidden;"
                     class="mls-single-map"></div>
                <script>
                (function(){
                    function initMlsSingleMap() {
                        if (typeof L === 'undefined') { setTimeout(initMlsSingleMap, 100); return; }
                        var el = document.getElementById('mls-single-property-map');
                        if (!el || el.dataset.init) return;
                        el.dataset.init = '1';
                        var lat   = parseFloat(el.dataset.lat);
                        var lng   = parseFloat(el.dataset.lng);
                        var title = el.dataset.title;
                        var label = el.dataset.label;
                        var map = L.map(el, { scrollWheelZoom: false }).setView([lat, lng], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
                            maxZoom: 19
                        }).addTo(map);
                        var icon = L.divIcon({
                            html: '<div class="mls-map-pin"><svg width="28" height="36" viewBox="0 0 28 36" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 0C6.268 0 0 6.268 0 14c0 9.625 14 22 14 22s14-12.375 14-22C28 6.268 21.732 0 14 0z" fill="#1e5277"/><circle cx="14" cy="14" r="6" fill="#b4ab74"/></svg></div>',
                            iconSize: [28, 36],
                            iconAnchor: [14, 36],
                            popupAnchor: [0, -36],
                            className: ''
                        });
                        var marker = L.marker([lat, lng], { icon: icon }).addTo(map);
                        marker.bindPopup(
                            '<div class="mls-map-popup"><strong>' + title + '</strong>' +
                            (label ? '<br><small style="color:#666">' + label + '</small>' : '') + '</div>'
                        );
                    }
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initMlsSingleMap);
                    } else {
                        initMlsSingleMap();
                    }
                })();
                </script>
            </section>
            <?php endif; ?>
            
        </div>
        
        <!-- Sidebar -->
        <aside class="property-sidebar">
            
            <!-- Agent Contact Card -->
            <div class="agent-contact-card">
                <?php
                $can_rate = false;
                if (is_user_logged_in()) {
                    $current_user = wp_get_current_user();
                    $user_roles = (array) $current_user->roles;
                    // Restrict certain roles from rating
                    $forbidden_roles = array('agent_basic', 'agent_premium', 'owner', 'developer');
                    if (!array_intersect($forbidden_roles, $user_roles) && $current_user->ID != $author_id) {
                        $can_rate = true;
                    }
                }
                ?>
                
                <div class="agent-header">
                    <h3 class="card-title">Contact Agent</h3>
                    <?php if ($verified) : ?>
                        <span class="agent-badge verified-badge">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" 
                                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Verified
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="agent-info">
                    <div class="agent-avatar">
                        <?php echo get_avatar($author_id, 64); ?>
                    </div>
                    <div class="agent-details">
                        <?php 
                        // Get agent post ID if exists
                        global $wpdb;
                        $agent_post_id = $wpdb->get_var($wpdb->prepare(
                            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'malisafi_agent' AND ID IN (
                                SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_agent_user_id' AND meta_value = %d
                            ) LIMIT 1",
                            $author_id
                        ));
                        
                        if ($agent_post_id): 
                            $agent_profile_url = '';
                            if (class_exists('MalisafiMLS\\Page_Manager') && method_exists('MalisafiMLS\\Page_Manager', 'get_page_url')) {
                                $agent_profile_url = \MalisafiMLS\Page_Manager::get_page_url('agent_profile');
                                if ($agent_profile_url) {
                                    $agent_profile_url = add_query_arg('agent_id', $agent_post_id, $agent_profile_url);
                                }
                            }
                            if (!$agent_profile_url) {
                                $agent_profile_url = get_permalink($agent_post_id);
                            }
                        ?>
                            <h4 class="agent-name">
                                <a href="<?php echo esc_url($agent_profile_url); ?>" class="agent-link" title="<?php esc_attr_e('View Agent Profile', 'malisafi-mls'); ?>">
                                    <?php echo esc_html($author_name); ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; margin-left: 4px; vertical-align: middle;">
                                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                        <polyline points="15 3 21 3 21 9"></polyline>
                                        <line x1="10" y1="14" x2="21" y2="3"></line>
                                    </svg>
                                </a>
                            </h4>
                        <?php else: ?>
                            <h4 class="agent-name"><?php echo esc_html($author_name); ?></h4>
                        <?php endif; ?>
                        <p class="agent-role">Property Agent</p>
                        <?php if ($agent_post_id): ?>
                        <a href="<?php echo esc_url($agent_profile_url); ?>" class="view-profile-link">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <?php _e('View Full Profile', 'malisafi-mls'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                

                
                <button class="contact-agent-button" data-agent-id="<?php echo $author_id; ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M22 16.92V19.92C22 20.47 21.55 20.92 21 20.92H19C18.45 20.92 18 20.47 18 19.92V16.92C18 16.37 18.45 15.92 19 15.92H21C21.55 15.92 22 16.37 22 16.92Z"/>
                        <path d="M16 8.92V11.92C16 12.47 15.55 12.92 15 12.92H13C12.45 12.92 12 12.47 12 11.92V8.92C12 8.37 12.45 7.92 13 7.92H15C15.55 7.92 16 8.37 16 8.92Z"/>
                        <path d="M22 12.92V9.92C22 9.37 21.55 8.92 21 8.92H19C18.45 8.92 18 9.37 18 9.92V12.92C18 13.47 18.45 13.92 19 13.92H21C21.55 13.92 22 13.47 22 12.92Z"/>
                        <path d="M16 16.92V19.92C16 20.47 15.55 20.92 15 20.92H13C12.45 20.92 12 20.47 12 19.92V16.92C12 16.37 12.45 15.92 13 15.92H15C15.55 15.92 16 16.37 16 16.92Z"/>
                        <path d="M22 4.92V7.92C22 8.47 21.55 8.92 21 8.92H3C2.45 8.92 2 8.47 2 7.92V4.92C2 4.37 2.45 3.92 3 3.92H21C21.55 3.92 22 4.37 22 4.92Z"/>
                    </svg>
                    Show Contact
                </button>
                
                <div class="agent-contact-details hidden">
                    <?php if ($author_phone) : ?>
                        <div class="contact-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M22 16.92V19.92C22 20.47 21.55 20.92 21 20.92H19C18.45 20.92 18 20.47 18 19.92V16.92C18 16.37 18.45 15.92 19 15.92H21C21.55 15.92 22 16.37 22 16.92Z"/>
                                <path d="M16 8.92V11.92C16 12.47 15.55 12.92 15 12.92H13C12.45 12.92 12 12.47 12 11.92V8.92C12 8.37 12.45 7.92 13 7.92H15C15.55 7.92 16 8.37 16 8.92Z"/>
                                <path d="M22 12.92V9.92C22 9.37 21.55 8.92 21 8.92H19C18.45 8.92 18 9.37 18 9.92V12.92C18 13.47 18.45 13.92 19 13.92H21C21.55 13.92 22 13.47 22 12.92Z"/>
                                <path d="M16 16.92V19.92C16 20.47 15.55 20.92 15 20.92H13C12.45 20.92 12 20.47 12 19.92V16.92C12 16.37 12.45 15.92 13 15.92H15C15.55 15.92 16 16.37 16 16.92Z"/>
                                <path d="M22 4.92V7.92C22 8.47 21.55 8.92 21 8.92H3C2.45 8.92 2 8.47 2 7.92V4.92C2 4.37 2.45 3.92 3 3.92H21C21.55 3.92 22 4.37 22 4.92Z"/>
                            </svg>
                            <a href="tel:<?php echo esc_attr($author_phone); ?>"><?php echo esc_html($author_phone); ?></a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="contact-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z"/>
                            <path d="M22 6L12 13L2 6"/>
                        </svg>
                        <a href="mailto:<?php echo esc_attr($author_email); ?>"><?php echo esc_html($author_email); ?></a>
                    </div>
                </div>
                
                <div class="contact-form hidden">
                    <form class="quick-contact-form">
                        <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                        <input type="hidden" name="agent_id" value="<?php echo $author_id; ?>">
                        
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Your Name" required>
                        </div>
                        
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Your Email" required>
                        </div>
                        
                        <div class="form-group">
                            <input type="tel" name="phone" placeholder="Your Phone">
                        </div>
                        
                        <div class="form-group">
                            <textarea name="message" rows="4" placeholder="Your Message" required></textarea>
                        </div>
                        
                        <button type="submit" class="submit-button">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Property Meta -->
            <div class="property-meta-card">
                <h3 class="card-title">Property Information</h3>
                <div class="meta-grid">
                    <div class="meta-item">
                        <span class="meta-label">Property ID</span>
                        <span class="meta-value">#<?php echo str_pad($property_id, 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Published</span>
                        <span class="meta-value"><?php echo get_the_date('M j, Y'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Last Updated</span>
                        <span class="meta-value"><?php echo get_the_modified_date('M j, Y'); ?></span>
                    </div>
                </div>
            </div>
            
        </aside>
        
    </div>
    
</div>



<!-- Report Modal -->
<div id="report-modal" class="malisafi-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Report Property</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="report-form">
                <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                
                <div class="form-group">
                    <label>Reason for reporting</label>
                    <select name="reason" required>
                        <option value="">Select a reason...</option>
                        <option value="incorrect_info">Incorrect Information</option>
                        <option value="duplicate">Duplicate Listing</option>
                        <option value="scam">Suspected Scam</option>
                        <option value="sold">Already Sold/Rented</option>
                        <option value="inappropriate">Inappropriate Content</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Additional details (optional)</label>
                    <textarea name="details" rows="4" placeholder="Provide more information..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="button-secondary modal-close">Cancel</button>
                    <button type="submit" class="button-primary">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Inquiry Modal -->
<div id="inquiry-modal" class="malisafi-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Send Inquiry</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="inquiry-form">
                <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                <input type="hidden" name="hp_name" value="">
                <input type="hidden" name="form_ts" value="<?php echo time(); ?>">
                
                <div class="form-group">
                    <label>Your Name</label>
                    <input type="text" name="inquiry_name" placeholder="Enter your name" required>
                </div>
                
                <div class="form-group">
                    <label>Your Email</label>
                    <input type="email" name="inquiry_email" placeholder="your@email.com" required>
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="inquiry_phone" placeholder="Your phone number">
                </div>
                
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="inquiry_message" rows="4" placeholder="I'm interested in this property. Please contact me with more details." required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="button-secondary modal-close">Cancel</button>
                    <?php if (get_option('malisafi_inquiry_recaptcha_enabled') && get_option('malisafi_inquiry_recaptcha_site_key')): ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr(get_option('malisafi_inquiry_recaptcha_site_key')); ?>" style="margin-bottom:8px;"></div>
                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                    <?php endif; ?>
                    <button type="submit" class="button-primary">Send Inquiry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
endwhile;

get_footer();