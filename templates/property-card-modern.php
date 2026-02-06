<?php
/**
 * Modern Property Card Template
 * Sleek card design for property display
 */

if (!defined('ABSPATH')) {
    exit;
}

$property_id = get_the_ID();

// Get property meta directly
$price = get_post_meta($property_id, '_malisafi_price', true);
$currency = get_post_meta($property_id, '_malisafi_currency', true);
if (empty($currency)) {
    $currency = 'USD';
}
$bedrooms = get_post_meta($property_id, '_malisafi_bedrooms', true);
$bathrooms = get_post_meta($property_id, '_malisafi_bathrooms', true);
$size = get_post_meta($property_id, '_malisafi_size', true);
$size_unit = get_post_meta($property_id, '_malisafi_size_unit', true) ?: 'sqm';

// Get status from taxonomy instead of meta
$status_terms = wp_get_post_terms($property_id, 'malisafi_property_status');
$status = (!empty($status_terms) && !is_wp_error($status_terms)) ? $status_terms[0]->name : '';

$is_verified = get_post_meta($property_id, '_malisafi_verified', true);
$is_verified = $is_verified == 1;

$featured = get_post_meta($property_id, '_malisafi_featured', true);
$setting = get_post_meta($property_id, '_malisafi_setting', true);
$city = get_post_meta($property_id, '_malisafi_city', true);
$subcounty = get_post_meta($property_id, '_malisafi_subcounty', true);
$county = get_post_meta($property_id, '_malisafi_county', true);
$location_parts = array_filter(array($city, $subcounty, $county));
$location = !empty($location_parts) ? implode(', ', $location_parts) : '';

// Get featured image with srcset for crisper thumbnails
$thumbnail_id = get_post_thumbnail_id($property_id);
if ($thumbnail_id) {
    $image_html = wp_get_attachment_image(
        $thumbnail_id,
        'malisafi_landscape',
        false,
        array(
            'class' => 'property-card-image',
            'loading' => 'lazy',
            'decoding' => 'async',
            'sizes' => '(max-width: 768px) 100vw, (max-width: 1280px) 50vw, 33vw'
        )
    );
} else {
    $placeholder = plugins_url('malisafi/assets/images/placeholder-property.svg');
    $image_html = '<img class="property-card-image" src="' . esc_url($placeholder) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy" decoding="async" />';
}

// Get property permalink
$property_url = get_permalink($property_id);

// Format price with currency
$currency_symbol = ($currency === 'KES') ? 'KSh' : '$';
$formatted_price = $currency_symbol . ' ' . number_format(floatval($price ?: 0));

// Check if property is new (posted within last 7 days)
$post_date = get_the_date('U');
$is_new = (time() - $post_date) < (7 * 24 * 60 * 60);
?>

<article class="property-card-modern" data-url="<?php echo esc_url($property_url); ?>" data-property-id="<?php echo $property_id; ?>">
    
    <div class="property-image-wrapper">
        <a href="<?php echo esc_url($property_url); ?>" class="property-image-link" aria-label="<?php echo esc_attr(get_the_title()); ?>">
            <?php echo $image_html; ?>
        
        <?php 
        ?>
        <div class="property-badges">
            <?php if ($is_verified) : ?>
                <span class="verified" title="<?php esc_attr_e('Verified', 'malisafi-mls'); ?>" aria-label="<?php esc_attr_e('Verified', 'malisafi-mls'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#2ecc71" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-8.93 3.382 5.657-5.657-1.414-1.414-4.243 4.243-2.121-2.121-1.414 1.414 3.535 3.535z"/>
                    </svg>
                </span>
            <?php endif; ?>

            <?php 
            // Always show status badge - default to 'Status Not Recorded' if empty
            if (!empty($status)) {
                $status_display = ucwords(str_replace('-', ' ', $status));
                $status_class = 'status-' . sanitize_html_class(strtolower(str_replace(' ', '-', $status)));
            } else {
                $status_display = 'Status Not Recorded';
                $status_class = 'status-not-recorded';
            }
            ?>
            <span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_display); ?></span>
        </div>
        </a>
        
        <?php
        // Check if property is favorited
        $is_favorited = false;
        $is_reported = false;
        
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            
            // Check favorites
            $favorites = get_user_meta($user_id, '_malisafi_favorites', true);
            $favorites = $favorites ? explode(',', $favorites) : array();
            $is_favorited = in_array($property_id, $favorites);
            
            // Check if user has already reported this property
            $reports = get_post_meta($property_id, '_malisafi_reports', true);
            $reports = $reports ? maybe_unserialize($reports) : array();
            $user_email = wp_get_current_user()->user_email;
            
            foreach ($reports as $report) {
                if (isset($report['reporter_email']) && $report['reporter_email'] === $user_email) {
                    $is_reported = true;
                    break;
                }
            }
        }
        ?>
    </div>
    
    <div class="property-card-body">
        
        <div class="property-header-inline">
            <div class="property-price-wrapper">
                <div class="property-price">
                    <?php echo esc_html($formatted_price); ?>
                </div>
            </div>
            
            <div class="property-actions-inline">
                <button class="property-favorite-inline<?php echo $is_favorited ? ' favorited' : ''; ?>" data-property-id="<?php echo $property_id; ?>" title="<?php echo $is_favorited ? 'Remove from favorites' : 'Add to favorites'; ?>">
                    <span class="dashicons dashicons-heart" style="color: #dc2626 !important;"></span>
                </button>
                <button class="property-report-inline report-button<?php echo $is_reported ? ' reported' : ''; ?>" data-property-id="<?php echo $property_id; ?>" title="<?php echo $is_reported ? 'Already reported' : 'Report Property'; ?>">
                    <span class="dashicons dashicons-flag" style="color: #dc2626 !important;"></span>
                </button>
            </div>
        </div>
        
        <h4 class="property-title">
            <?php the_title(); ?>
        </h4>
        
        <?php if ($location) : ?>
        <div class="property-location">
            <span class="dashicons dashicons-location"></span>
            <span><?php echo esc_html($location); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="property-features">
            <?php if ($bedrooms) : ?>
            <div class="property-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 9.556V3h-2v2H6V3H4v6.557C2.81 10.25 2 11.526 2 13v4a1 1 0 0 0 1 1h1v4h2v-4h12v4h2v-4h1a1 1 0 0 0 1-1v-4c0-1.474-.811-2.75-2-3.444zM11 9H6V7h5v2zm7 0h-5V7h5v2z"/>
                </svg>
                <span><?php echo esc_html($bedrooms); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($bathrooms) : ?>
            <div class="property-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21 10H7V7c0-1.103.897-2 2-2s2 .897 2 2h2c0-2.206-1.794-4-4-4S5 4.794 5 7v3H3a1 1 0 0 0-1 1v2c0 2.606 1.674 4.823 4 5.65V22h2v-3h8v3h2v-3.35c2.326-.827 4-3.044 4-5.65v-2a1 1 0 0 0-1-1z"/>
                </svg>
                <span><?php echo esc_html($bathrooms); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($size) : ?>
            <div class="property-feature">
                <span class="dashicons dashicons-editor-expand"></span>
                <span>
                    <?php 
                    $unit_label = $size_unit === 'sqft' ? 'Sq Ft' : ($size_unit === 'sqm' ? 'Sq M' : strtoupper($size_unit));
                    echo esc_html(number_format((float)$size) . ' ' . $unit_label);
                    ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
    
</article>
