<?php
/**
 * Agent Profile Template
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

$agent_id = isset($agent_id) ? $agent_id : 0;
$agent = get_post($agent_id);

if (!$agent) {
    echo '<div class="notice notice-error"><p>' . __('Agent profile not found.', 'malisafi-mls') . '</p></div>';
    return;
}

// Get all agent meta
$email = get_post_meta($agent_id, '_agent_email', true);
$phone = get_post_meta($agent_id, '_agent_phone', true);
$mobile = get_post_meta($agent_id, '_agent_mobile', true);
$whatsapp = get_post_meta($agent_id, '_agent_whatsapp', true);
$office_address = get_post_meta($agent_id, '_agent_office_address', true);
$website = get_post_meta($agent_id, '_agent_website', true);
$license_number = get_post_meta($agent_id, '_agent_license_number', true);
$agency_name = get_post_meta($agent_id, '_agent_agency_name', true);
$experience_years = get_post_meta($agent_id, '_agent_experience_years', true);
$languages = get_post_meta($agent_id, '_agent_languages', true);
$service_areas = get_post_meta($agent_id, '_agent_service_areas', true);
$commission_rate = get_post_meta($agent_id, '_agent_commission_rate', true);

// Social media
$facebook = get_post_meta($agent_id, '_agent_facebook', true);
$twitter = get_post_meta($agent_id, '_agent_twitter', true);
$linkedin = get_post_meta($agent_id, '_agent_linkedin', true);
$instagram = get_post_meta($agent_id, '_agent_instagram', true);
$youtube = get_post_meta($agent_id, '_agent_youtube', true);

$status = get_post_meta($agent_id, '_agent_status', true);
$featured = get_post_meta($agent_id, '_agent_featured', true);
?>

<div class="wrap malisafi-agent-profile">
    <h1>
        <?php _e('My Profile', 'malisafi-mls'); ?>
        <?php if (current_user_can('manage_options')): ?>
            <a href="<?php echo admin_url('post.php?post=' . $agent_id . '&action=edit'); ?>" class="page-title-action">
                <?php _e('Edit Profile', 'malisafi-mls'); ?>
            </a>
        <?php endif; ?>
    </h1>
    
    <div class="profile-header">
        <div class="profile-photo">
            <?php if (has_post_thumbnail($agent_id)): ?>
                <?php echo get_the_post_thumbnail($agent_id, 'medium'); ?>
            <?php else: ?>
                <span class="dashicons dashicons-businessman" style="font-size: 150px; color: #ccc;"></span>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h2><?php echo esc_html(get_the_title($agent_id)); ?></h2>
            <?php if ($agency_name): ?>
                <p class="agency-name"><?php echo esc_html($agency_name); ?></p>
            <?php endif; ?>
            <?php if ($license_number): ?>
                <p class="license"><strong><?php _e('License:', 'malisafi-mls'); ?></strong> <?php echo esc_html($license_number); ?></p>
            <?php endif; ?>
            <div class="status-badge status-<?php echo esc_attr($status); ?>">
                <?php
                $status_labels = array(
                    'active' => __('Active', 'malisafi-mls'),
                    'inactive' => __('Inactive', 'malisafi-mls'),
                    'on_vacation' => __('On Vacation', 'malisafi-mls'),
                    'suspended' => __('Suspended', 'malisafi-mls'),
                );
                echo isset($status_labels[$status]) ? $status_labels[$status] : $status_labels['active'];
                ?>
            </div>
            <?php if ($featured): ?>
                <span class="featured-badge">★ <?php _e('Featured Agent', 'malisafi-mls'); ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bio -->
    <?php if ($agent->post_content): ?>
    <div class="malisafi-card">
        <h3><?php _e('About Me', 'malisafi-mls'); ?></h3>
        <div class="agent-bio">
            <?php echo wpautop($agent->post_content); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Contact Information -->
    <div class="malisafi-card">
        <h3><?php _e('Contact Information', 'malisafi-mls'); ?></h3>
        <table class="profile-table">
            <?php if ($email): ?>
            <tr>
                <th><?php _e('Email', 'malisafi-mls'); ?></th>
                <td><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td>
            </tr>
            <?php endif; ?>
            
            <?php if ($phone): ?>
            <tr>
                <th><?php _e('Office Phone', 'malisafi-mls'); ?></th>
                <td><?php echo esc_html($phone); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if ($mobile): ?>
            <tr>
                <th><?php _e('Mobile', 'malisafi-mls'); ?></th>
                <td><?php echo esc_html($mobile); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if ($whatsapp): ?>
            <tr>
                <th><?php _e('WhatsApp', 'malisafi-mls'); ?></th>
                <td>
                    <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $whatsapp)); ?>" target="_blank">
                        <?php echo esc_html($whatsapp); ?>
                    </a>
                </td>
            </tr>
            <?php endif; ?>
            
            <?php if ($website): ?>
            <tr>
                <th><?php _e('Website', 'malisafi-mls'); ?></th>
                <td><a href="<?php echo esc_url($website); ?>" target="_blank"><?php echo esc_html($website); ?></a></td>
            </tr>
            <?php endif; ?>
            
            <?php if ($office_address): ?>
            <tr>
                <th><?php _e('Office Address', 'malisafi-mls'); ?></th>
                <td><?php echo nl2br(esc_html($office_address)); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Professional Information -->
    <div class="malisafi-card">
        <h3><?php _e('Professional Information', 'malisafi-mls'); ?></h3>
        <table class="profile-table">
            <?php if ($experience_years): ?>
            <tr>
                <th><?php _e('Years of Experience', 'malisafi-mls'); ?></th>
                <td><?php echo intval($experience_years); ?> <?php _e('years', 'malisafi-mls'); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if ($languages): ?>
            <tr>
                <th><?php _e('Languages', 'malisafi-mls'); ?></th>
                <td><?php echo esc_html($languages); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if ($service_areas): ?>
            <tr>
                <th><?php _e('Service Areas', 'malisafi-mls'); ?></th>
                <td><?php echo nl2br(esc_html($service_areas)); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php
            $specialties = wp_get_post_terms($agent_id, 'malisafi_agent_specialty');
            if (!empty($specialties) && !is_wp_error($specialties)):
            ?>
            <tr>
                <th><?php _e('Specialties', 'malisafi-mls'); ?></th>
                <td>
                    <?php
                    $specialty_names = array_map(function($term) { return $term->name; }, $specialties);
                    echo esc_html(implode(', ', $specialty_names));
                    ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Social Media -->
    <?php if ($facebook || $twitter || $linkedin || $instagram || $youtube): ?>
    <div class="malisafi-card">
        <h3><?php _e('Social Media', 'malisafi-mls'); ?></h3>
        <div class="social-media-links">
            <?php if ($facebook): ?>
                <a href="<?php echo esc_url($facebook); ?>" target="_blank" class="social-link facebook">
                    <span class="dashicons dashicons-facebook"></span> Facebook
                </a>
            <?php endif; ?>
            
            <?php if ($twitter): ?>
                <a href="<?php echo esc_url($twitter); ?>" target="_blank" class="social-link twitter">
                    <span class="dashicons dashicons-twitter"></span> Twitter
                </a>
            <?php endif; ?>
            
            <?php if ($linkedin): ?>
                <a href="<?php echo esc_url($linkedin); ?>" target="_blank" class="social-link linkedin">
                    <span class="dashicons dashicons-linkedin"></span> LinkedIn
                </a>
            <?php endif; ?>
            
            <?php if ($instagram): ?>
                <a href="<?php echo esc_url($instagram); ?>" target="_blank" class="social-link instagram">
                    <span class="dashicons dashicons-instagram"></span> Instagram
                </a>
            <?php endif; ?>
            
            <?php if ($youtube): ?>
                <a href="<?php echo esc_url($youtube); ?>" target="_blank" class="social-link youtube">
                    <span class="dashicons dashicons-video-alt3"></span> YouTube
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.malisafi-agent-profile {
    padding: 20px;
}

.profile-header {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 20px;
    display: flex;
    gap: 30px;
    align-items: center;
}

.profile-photo img {
    border-radius: 50%;
    width: 150px;
    height: 150px;
    object-fit: cover;
}

.profile-info h2 {
    margin: 0 0 10px;
    font-size: 28px;
}

.profile-info .agency-name {
    font-size: 18px;
    color: #666;
    margin: 0 0 10px;
}

.profile-info .license {
    margin: 0 0 10px;
    color: #666;
}

.status-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 4px;
    font-weight: bold;
    margin-right: 10px;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #e2e3e5;
    color: #383d41;
}

.status-on_vacation {
    background: #fff3cd;
    color: #856404;
}

.status-suspended {
    background: #f8d7da;
    color: #721c24;
}

.featured-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 4px;
    background: #ffd700;
    color: #000;
    font-weight: bold;
}

.malisafi-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.malisafi-card h3 {
    margin-top: 0;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.profile-table {
    width: 100%;
    border-collapse: collapse;
}

.profile-table th {
    text-align: left;
    padding: 10px;
    width: 30%;
    background: #f8f9fa;
    font-weight: bold;
}

.profile-table td {
    padding: 10px;
}

.profile-table tr {
    border-bottom: 1px solid #e2e3e5;
}

.agent-bio {
    line-height: 1.8;
}

.social-media-links {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.social-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    color: #fff;
    font-weight: bold;
    transition: all 0.3s;
}

.social-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.social-link.facebook { background: #3b5998; }
.social-link.twitter { background: #1da1f2; }
.social-link.linkedin { background: #0077b5; }
.social-link.instagram { background: #e4405f; }
.social-link.youtube { background: #ff0000; }

.social-link .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}
</style>
