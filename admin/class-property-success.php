<?php
/**
 * Property Submission Success Page
 * Shows success message with action options after property creation/update
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Property_Success {
    
    /**
     * Initialize
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_success_page'), 99);
    }
    
    /**
     * Add hidden success page to admin menu
     */
    public static function add_success_page() {
        add_submenu_page(
            null, // Parent slug (null = hidden from menu)
            __('Property Submission Success', 'malisafi-mls'),
            __('Success', 'malisafi-mls'),
            'edit_posts', // allow agents
            'malisafi-property-success',
            array(__CLASS__, 'render_success_page')
        );
    }
    
    /**
     * Render success page
     */
    public static function render_success_page() {
        $property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'created';
        
        if (!$property_id) {
            wp_die(__('Invalid property ID', 'malisafi-mls'));
        }
        
        $property = get_post($property_id);
        
        if (!$property || $property->post_type !== 'malisafi_property') {
            wp_die(__('Property not found', 'malisafi-mls'));
        }
        
        // Check capability on this specific post
        if (! current_user_can('edit_post', $property_id)) {
            wp_die(__('You do not have permission to view this property', 'malisafi-mls'));
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
        
        // Generate URLs
        $view_url = get_permalink($property_id);
        $my_properties_url = admin_url('edit.php?post_type=malisafi_property');
        $add_new_url = add_query_arg(
            array(
                'page' => 'malisafi-properties',
                'action' => 'add'
            ),
            admin_url('admin.php')
        );
        $edit_url = add_query_arg(
            array(
                'page' => 'malisafi-properties',
                'action' => 'edit',
                'property_id' => $property_id
            ),
            admin_url('admin.php')
        );
        
        // Check if property is pending
        $is_pending = $property->post_status === 'pending';
        
        ?>
        <div class="wrap malisafi-property-success">
            <div class="success-container">
                
                <!-- Success Icon & Message -->
                <div class="success-header">
                    <div class="success-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <h1>
                        <?php 
                        if ($action === 'updated') {
                            _e('Property Updated Successfully!', 'malisafi-mls');
                        } else {
                            _e('Property Created Successfully!', 'malisafi-mls');
                        }
                        ?>
                    </h1>
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
                
                <!-- Property Summary Card -->
                <div class="property-summary-card">
                    <div class="property-thumbnail">
                        <?php if (has_post_thumbnail($property_id)): ?>
                            <?php echo get_the_post_thumbnail($property_id, 'medium'); ?>
                        <?php else: ?>
                            <div class="no-thumbnail">
                                <span class="dashicons dashicons-admin-home"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="property-details">
                        <h2><?php echo esc_html($property->post_title); ?></h2>
                        
                        <?php if ($is_pending): ?>
                            <span class="status-badge pending">
                                <span class="dashicons dashicons-clock"></span>
                                <?php _e('Pending Review', 'malisafi-mls'); ?>
                            </span>
                        <?php else: ?>
                            <span class="status-badge published">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Published', 'malisafi-mls'); ?>
                            </span>
                        <?php endif; ?>
                        
                        <div class="property-meta">
                            <?php if ($price): ?>
                                <div class="meta-item">
                                    <span class="dashicons dashicons-tag"></span>
                                    <strong><?php echo esc_html($currency . ' ' . number_format($price)); ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($property_type_name): ?>
                                <div class="meta-item">
                                    <span class="dashicons dashicons-admin-home"></span>
                                    <?php echo esc_html($property_type_name); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($bedrooms || $bathrooms): ?>
                                <div class="meta-item">
                                    <span class="dashicons dashicons-admin-multisite"></span>
                                    <?php 
                                    $details = array();
                                    if ($bedrooms) $details[] = $bedrooms . ' ' . __('bed', 'malisafi-mls');
                                    if ($bathrooms) $details[] = $bathrooms . ' ' . __('bath', 'malisafi-mls');
                                    echo esc_html(implode(', ', $details));
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($city || $county): ?>
                                <div class="meta-item">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html(($city ? $city . ', ' : '') . $county); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Status Information -->
                <?php if ($is_pending): ?>
                    <div class="info-box pending-info">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <h3><?php _e('What happens next?', 'malisafi-mls'); ?></h3>
                            <p>
                                <?php _e('Your property is currently under review by our moderation team. You will be notified via email once it has been approved or if any changes are needed.', 'malisafi-mls'); ?>
                            </p>
                            <p>
                                <strong><?php _e('Review time:', 'malisafi-mls'); ?></strong>
                                <?php _e('Usually within 24 hours', 'malisafi-mls'); ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="info-box success-info">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <div>
                            <h3><?php _e('Your property is live!', 'malisafi-mls'); ?></h3>
                            <p>
                                <?php _e('Your property is now visible to potential buyers/renters on the website. You can view it at any time or make edits if needed.', 'malisafi-mls'); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="<?php echo esc_url($view_url); ?>" class="button button-primary button-hero" target="_blank">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('View Property', 'malisafi-mls'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url($my_properties_url); ?>" class="button button-secondary button-hero">
                        <span class="dashicons dashicons-admin-multisite"></span>
                        <?php _e('Go to My Properties', 'malisafi-mls'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url($add_new_url); ?>" class="button button-secondary button-hero">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add Another Property', 'malisafi-mls'); ?>
                    </a>
                </div>
                
                <!-- Secondary Actions -->
                <div class="secondary-actions">
                    <a href="<?php echo esc_url($edit_url); ?>">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Edit This Property', 'malisafi-mls'); ?>
                    </a>
                    
                    <?php if (!$is_pending): ?>
                        <a href="#" onclick="window.print(); return false;">
                            <span class="dashicons dashicons-printer"></span>
                            <?php _e('Print Details', 'malisafi-mls'); ?>
                        </a>
                        
                        <a href="mailto:?subject=<?php echo urlencode($property->post_title); ?>&body=<?php echo urlencode($view_url); ?>">
                            <span class="dashicons dashicons-email"></span>
                            <?php _e('Share via Email', 'malisafi-mls'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        
        <style>
        .malisafi-property-success {
            margin-top: 20px;
        }
        
        .success-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Success Header */
        .success-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .success-icon {
            display: inline-block;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease;
        }
        
        .success-icon .dashicons {
            color: #fff;
            font-size: 48px;
            width: 48px;
            height: 48px;
            line-height: 80px;
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
            margin: 0 0 10px;
            font-weight: 700;
        }
        
        .success-subtitle {
            font-size: 16px;
            color: #6b7280;
            margin: 0;
        }
        
        /* Property Summary Card */
        .property-summary-card {
            display: flex;
            gap: 24px;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .property-thumbnail {
            flex-shrink: 0;
            width: 200px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            background: #f3f4f6;
        }
        
        .property-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-thumbnail {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
        }
        
        .no-thumbnail .dashicons {
            font-size: 64px;
            width: 64px;
            height: 64px;
            color: #9ca3af;
        }
        
        .property-details h2 {
            margin: 0 0 12px;
            font-size: 24px;
            color: #1f2937;
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
        
        .status-badge .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        .property-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #4b5563;
            font-size: 14px;
        }
        
        .meta-item .dashicons {
            color: #9ca3af;
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        
        /* Info Boxes */
        .info-box {
            display: flex;
            gap: 16px;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .info-box.pending-info {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        
        .info-box.success-info {
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }
        
        .info-box > .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }
        
        .info-box.pending-info > .dashicons {
            color: #f59e0b;
        }
        
        .info-box.success-info > .dashicons {
            color: #10b981;
        }
        
        .info-box h3 {
            margin: 0 0 8px;
            font-size: 16px;
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
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .action-buttons .button-hero {
            padding: 12px 24px;
            height: auto;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-buttons .button-hero .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        
        .action-buttons .button-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border-color: #1d4ed8;
            text-shadow: none;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        
        .action-buttons .button-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        
        /* Secondary Actions */
        .secondary-actions {
            display: flex;
            gap: 24px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .secondary-actions a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        
        .secondary-actions a:hover {
            color: #2563eb;
        }
        
        .secondary-actions .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .property-summary-card {
                flex-direction: column;
            }
            
            .property-thumbnail {
                width: 100%;
                height: 200px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .button-hero {
                width: 100%;
                justify-content: center;
            }
            
            .secondary-actions {
                flex-direction: column;
                align-items: center;
            }
        }
        
        /* Print Styles */
        @media print {
            .action-buttons,
            .secondary-actions {
                display: none;
            }
        }
        </style>
        <?php
    }
}
