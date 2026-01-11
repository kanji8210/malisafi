<?php
/**
 * Frontend Property Submission Success Template
 *
 * @package MalisafiMLS
 */
if (!defined('ABSPATH')) exit;

$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

if (!$property_id) {
    return '<div class="malisafi-notice error"><p>' . __('Invalid property ID', 'malisafi-mls') . '</p></div>';
}

$property = get_post($property_id);

if (!$property || $property->post_type !== 'malisafi_property') {
    return '<div class="malisafi-notice error"><p>' . __('Property not found', 'malisafi-mls') . '</p></div>';
}

// Check ownership
$current_user = wp_get_current_user();
if ($property->post_author != $current_user->ID && !current_user_can('administrator')) {
    return '<div class="malisafi-notice error"><p>' . __('Permission denied', 'malisafi-mls') . '</p></div>';
}

// Get property details
$price = get_post_meta($property_id, '_malisafi_price', true);
$currency = get_post_meta($property_id, '_malisafi_currency', true) ?: 'KES';
$county = get_post_meta($property_id, '_malisafi_county', true);
$city = get_post_meta($property_id, '_malisafi_city', true);
$bedrooms = get_post_meta($property_id, '_malisafi_bedrooms', true);
$bathrooms = get_post_meta($property_id, '_malisafi_bathrooms', true);
$property_type = wp_get_object_terms($property_id, 'malisafi_property_type');
$property_type_name = !empty($property_type) && !is_wp_error($property_type) ? $property_type[0]->name : '';

// Check status
$is_pending = $property->post_status === 'pending';

// URLs
$view_url = get_permalink($property_id);
$submit_page_url = get_permalink(); // Current page
$dashboard_url = home_url('/dashboard'); // Update with your dashboard URL
?>

<div class="malisafi-submission-success">
    
    <!-- Success Header -->
    <div class="success-header">
        <div class="success-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h1><?php _e('Property Submitted Successfully!', 'malisafi-mls'); ?></h1>
        <p class="success-subtitle">
            <?php 
            if ($is_pending) {
                _e('Your property has been submitted and is now pending review.', 'malisafi-mls');
            } else {
                _e('Your property is now live on the website.', 'malisafi-mls');
            }
            ?>
        </p>
    </div>
    
    <!-- Property Summary -->
    <div class="property-summary">
        <?php if (has_post_thumbnail($property_id)): ?>
            <div class="property-image">
                <?php echo get_the_post_thumbnail($property_id, 'large'); ?>
            </div>
        <?php endif; ?>
        
        <div class="property-info">
            <h2><?php echo esc_html($property->post_title); ?></h2>
            
            <?php if ($is_pending): ?>
                <span class="status-badge pending">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?php _e('Pending Review', 'malisafi-mls'); ?>
                </span>
            <?php else: ?>
                <span class="status-badge published">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <?php _e('Published', 'malisafi-mls'); ?>
                </span>
            <?php endif; ?>
            
            <div class="property-details">
                <?php if ($price): ?>
                    <div class="detail-item price">
                        <strong><?php echo esc_html($currency . ' ' . number_format($price)); ?></strong>
                    </div>
                <?php endif; ?>
                
                <?php if ($property_type_name): ?>
                    <div class="detail-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        <?php echo esc_html($property_type_name); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($bedrooms || $bathrooms): ?>
                    <div class="detail-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <?php 
                        $details = array();
                        if ($bedrooms) $details[] = $bedrooms . ' ' . __('bed', 'malisafi-mls');
                        if ($bathrooms) $details[] = $bathrooms . ' ' . __('bath', 'malisafi-mls');
                        echo esc_html(implode(', ', $details));
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($city || $county): ?>
                    <div class="detail-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <?php echo esc_html(($city ? $city . ', ' : '') . $county); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Status Info -->
    <?php if ($is_pending): ?>
        <div class="info-box pending">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
                <h3><?php _e('What happens next?', 'malisafi-mls'); ?></h3>
                <p>
                    <?php _e('Your property is under review by our team. You will receive an email notification once it has been approved or if any changes are needed.', 'malisafi-mls'); ?>
                </p>
                <p><strong><?php _e('Typical review time:', 'malisafi-mls'); ?></strong> <?php _e('24 hours', 'malisafi-mls'); ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="info-box success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <div>
                <h3><?php _e('Your property is live!', 'malisafi-mls'); ?></h3>
                <p>
                    <?php _e('Your property is now visible to potential buyers and renters. You can view it anytime or make edits if needed.', 'malisafi-mls'); ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="<?php echo esc_url($view_url); ?>" class="btn btn-primary" target="_blank">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <?php _e('View Property', 'malisafi-mls'); ?>
        </a>
        
        <a href="<?php echo esc_url($dashboard_url); ?>" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <?php _e('Go to Dashboard', 'malisafi-mls'); ?>
        </a>
        
        <a href="<?php echo esc_url($submit_page_url); ?>" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <?php _e('Add Another Property', 'malisafi-mls'); ?>
        </a>
    </div>
    
</div>

<style>
.malisafi-submission-success {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
}

/* Success Header */
.success-header {
    text-align: center;
    margin-bottom: 40px;
}

.success-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 50%;
    color: #fff;
    margin-bottom: 24px;
    animation: scaleIn 0.5s ease;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.success-header h1 {
    font-size: 32px;
    color: #1f2937;
    margin: 0 0 12px;
    font-weight: 700;
}

.success-subtitle {
    font-size: 16px;
    color: #6b7280;
    margin: 0;
}

/* Property Summary */
.property-summary {
    background: #fff;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.property-image {
    margin-bottom: 20px;
    border-radius: 8px;
    overflow: hidden;
}

.property-image img {
    width: 100%;
    height: auto;
    display: block;
}

.property-info h2 {
    font-size: 24px;
    color: #1f2937;
    margin: 0 0 12px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 16px;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.published {
    background: #d1fae5;
    color: #065f46;
}

.property-details {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 16px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4b5563;
    font-size: 15px;
}

.detail-item.price {
    font-size: 24px;
    color: #2563eb;
    font-weight: 700;
}

.detail-item svg {
    color: #9ca3af;
}

/* Info Box */
.info-box {
    display: flex;
    gap: 16px;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 32px;
}

.info-box.pending {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
}

.info-box.success {
    background: #d1fae5;
    border-left: 4px solid #10b981;
}

.info-box svg {
    flex-shrink: 0;
}

.info-box.pending svg {
    color: #f59e0b;
}

.info-box.success svg {
    color: #10b981;
}

.info-box h3 {
    margin: 0 0 8px;
    font-size: 18px;
    color: #1f2937;
}

.info-box p {
    margin: 0 0 8px;
    color: #4b5563;
    line-height: 1.6;
}

.info-box p:last-child {
    margin-bottom: 0;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}

.btn-secondary {
    background: #fff;
    color: #374151;
    border: 2px solid #e5e7eb;
}

.btn-secondary:hover {
    border-color: #2563eb;
    color: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .success-header h1 {
        font-size: 24px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .property-details {
        flex-direction: column;
    }
}
</style>
