<?php
/**
 * Add Property Page Template
 * Checks user permissions and displays appropriate message/action
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$can_add_property = current_user_can('edit_malisafi_properties') || current_user_can('publish_malisafi_properties');
$is_admin = current_user_can('manage_options');
$user_roles = $current_user->roles;

// Check if user is agent (any agent role)
$is_agent = false;
$agent_roles = array('malisafi_agent_basic', 'malisafi_agent_premium');
foreach ($agent_roles as $role) {
    if (in_array($role, $user_roles)) {
        $is_agent = true;
        break;
    }
}

// URLs
$admin_add_property_url = admin_url('post-new.php?post_type=malisafi_property');
$login_url = wp_login_url(get_permalink());
$submit_form_url = home_url('/submit-property'); // Adjust as needed

?>

<div class="malisafi-add-property-page">
    
    <?php if ($is_admin) : ?>
        <!-- ADMIN: Redirect to admin panel -->
        <div class="add-property-card">
            <div class="card-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
            </div>
            
            <h2><?php _e('Add Property - Admin Access', 'malisafi-mls'); ?></h2>
            
            <p class="card-description">
                <?php _e('As an administrator, you have full access to add properties through the WordPress admin panel.', 'malisafi-mls'); ?>
            </p>
            
            <div class="card-actions">
                <a href="<?php echo esc_url($admin_add_property_url); ?>" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <?php _e('Go to Admin Panel', 'malisafi-mls'); ?>
                </a>
                
                <a href="<?php echo admin_url('edit.php?post_type=malisafi_property'); ?>" class="btn btn-secondary">
                    <?php _e('View All Properties', 'malisafi-mls'); ?>
                </a>
            </div>
            
            <div class="info-box">
                <strong><?php _e('Admin Features:', 'malisafi-mls'); ?></strong>
                <ul>
                    <li><?php _e('Add unlimited properties', 'malisafi-mls'); ?></li>
                    <li><?php _e('Approve/reject pending properties', 'malisafi-mls'); ?></li>
                    <li><?php _e('Manage all user properties', 'malisafi-mls'); ?></li>
                    <li><?php _e('Access advanced settings', 'malisafi-mls'); ?></li>
                </ul>
            </div>
        </div>
        
    <?php elseif (!$is_logged_in) : ?>
        <!-- NOT LOGGED IN: Ask to login -->
        <div class="add-property-card">
            <div class="card-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                    <line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
            </div>
            
            <h2><?php _e('Login Required', 'malisafi-mls'); ?></h2>
            
            <p class="card-description">
                <?php _e('You need to be logged in to add properties. Please login or register to continue.', 'malisafi-mls'); ?>
            </p>
            
            <div class="card-actions">
                <a href="<?php echo esc_url($login_url); ?>" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    <?php _e('Login to Continue', 'malisafi-mls'); ?>
                </a>
                
                <a href="<?php echo wp_registration_url(); ?>" class="btn btn-secondary">
                    <?php _e('Register New Account', 'malisafi-mls'); ?>
                </a>
            </div>
            
            <div class="info-box">
                <strong><?php _e('Why Register?', 'malisafi-mls'); ?></strong>
                <ul>
                    <li><?php _e('List your properties for free', 'malisafi-mls'); ?></li>
                    <li><?php _e('Manage your listings', 'malisafi-mls'); ?></li>
                    <li><?php _e('Track inquiries and views', 'malisafi-mls'); ?></li>
                    <li><?php _e('Save favorite properties', 'malisafi-mls'); ?></li>
                </ul>
            </div>
        </div>
        
    <?php elseif ($is_agent) : ?>
        <!-- AGENT: Show property submission form link -->
        <div class="add-property-card">
            <div class="card-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <line x1="12" y1="8" x2="12" y2="16"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
            </div>
            
            <h2><?php _e('Add New Property', 'malisafi-mls'); ?></h2>
            
            <p class="card-description">
                <?php 
                printf(
                    __('Welcome, %s! You can add properties using our property submission form.', 'malisafi-mls'),
                    '<strong>' . esc_html($current_user->display_name) . '</strong>'
                );
                ?>
            </p>
            
            <div class="user-info">
                <div class="info-item">
                    <span class="label"><?php _e('Account Type:', 'malisafi-mls'); ?></span>
                    <span class="value">
                        <?php 
                        if (in_array('malisafi_agent_premium', $user_roles)) {
                            echo '<span class="badge badge-premium">' . __('Premium Agent', 'malisafi-mls') . '</span>';
                        } elseif (in_array('malisafi_agent_basic', $user_roles)) {
                            echo '<span class="badge badge-basic">' . __('Basic Agent', 'malisafi-mls') . '</span>';
                        }
                        ?>
                    </span>
                </div>
                
                <?php
                // Get user's property limits
                global $wpdb;
                $limits_table = $wpdb->prefix . 'mf_user_limits';
                $limits = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $limits_table WHERE user_id = %d",
                    $current_user->ID
                ));
                
                if ($limits) :
                    $properties_count = count_user_posts($current_user->ID, 'malisafi_property', true);
                ?>
                    <div class="info-item">
                        <span class="label"><?php _e('Properties:', 'malisafi-mls'); ?></span>
                        <span class="value"><?php echo $properties_count; ?> / <?php echo $limits->max_properties; ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-actions">
                <a href="<?php echo esc_url($submit_form_url); ?>" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <?php _e('Add Property Now', 'malisafi-mls'); ?>
                </a>
                
                <a href="<?php echo home_url('/my-properties'); ?>" class="btn btn-secondary">
                    <?php _e('View My Properties', 'malisafi-mls'); ?>
                </a>
            </div>
            
            <div class="info-box">
                <strong><?php _e('Property Submission:', 'malisafi-mls'); ?></strong>
                <ul>
                    <?php if (in_array('malisafi_agent_premium', $user_roles)) : ?>
                        <li><?php _e('✓ Instant publication (no approval needed)', 'malisafi-mls'); ?></li>
                        <li><?php _e('✓ Unlimited photos and videos', 'malisafi-mls'); ?></li>
                        <li><?php _e('✓ Featured property options', 'malisafi-mls'); ?></li>
                    <?php else : ?>
                        <li><?php _e('Properties require admin approval', 'malisafi-mls'); ?></li>
                        <li><?php _e('Standard listing features', 'malisafi-mls'); ?></li>
                        <li><?php printf(__('Upgrade to <a href="%s">Premium</a> for instant publication', 'malisafi-mls'), home_url('/pricing')); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
    <?php elseif ($can_add_property) : ?>
        <!-- USER WITH PERMISSION: Show form -->
        <div class="add-property-card">
            <div class="card-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <line x1="12" y1="8" x2="12" y2="16"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
            </div>
            
            <h2><?php _e('Add New Property', 'malisafi-mls'); ?></h2>
            
            <p class="card-description">
                <?php _e('You have permission to add properties. Click below to access the property submission form.', 'malisafi-mls'); ?>
            </p>
            
            <div class="card-actions">
                <a href="<?php echo esc_url($submit_form_url); ?>" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <?php _e('Add Property', 'malisafi-mls'); ?>
                </a>
            </div>
        </div>
        
    <?php else : ?>
        <!-- USER WITHOUT PERMISSION: Inform -->
        <div class="add-property-card">
            <div class="card-icon warning">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            
            <h2><?php _e('Access Restricted', 'malisafi-mls'); ?></h2>
            
            <p class="card-description">
                <?php _e('Your current account does not have permission to add properties. To list properties on our platform, you need an agent account.', 'malisafi-mls'); ?>
            </p>
            
            <div class="card-actions">
                <a href="<?php echo home_url('/pricing'); ?>" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    <?php _e('View Agent Plans', 'malisafi-mls'); ?>
                </a>
                
                <a href="<?php echo home_url('/contact'); ?>" class="btn btn-secondary">
                    <?php _e('Contact Support', 'malisafi-mls'); ?>
                </a>
            </div>
            
            <div class="info-box">
                <strong><?php _e('Become an Agent:', 'malisafi-mls'); ?></strong>
                <ul>
                    <li><?php _e('Basic Agent: KES 1,999/month - List up to 10 properties', 'malisafi-mls'); ?></li>
                    <li><?php _e('Premium Agent: KES 4,999/month - Unlimited properties + instant publication', 'malisafi-mls'); ?></li>
                    <li><?php _e('Professional tools and analytics', 'malisafi-mls'); ?></li>
                    <li><?php _e('Dedicated agent profile', 'malisafi-mls'); ?></li>
                </ul>
            </div>
        </div>
        
    <?php endif; ?>
    
</div>
