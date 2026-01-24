<?php
/**
 * Public Agent Profile Template
 * Shows agent details, properties, reviews/ratings
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) exit;

// Get agent ID from query or shortcode attribute
$agent_id = isset($atts['agent_id']) ? intval($atts['agent_id']) : get_query_var('agent_id', 0);

if (!$agent_id && isset($_GET['agent_id'])) {
    $agent_id = intval($_GET['agent_id']);
}

if (!$agent_id) {
    echo '<p>' . __('Agent not found.', 'malisafi-mls') . '</p>';
    return;
}

$agent = get_post($agent_id);
if (!$agent || $agent->post_type !== 'malisafi_agent') {
    echo '<p>' . __('Agent not found.', 'malisafi-mls') . '</p>';
    return;
}

// Get agent meta
$agent_photo = get_post_meta($agent_id, '_agent_photo', true);
$agent_bio = get_post_meta($agent_id, '_agent_bio', true);
$agent_email = get_post_meta($agent_id, '_agent_email', true);
$agent_phone = get_post_meta($agent_id, '_agent_phone', true);
$agent_whatsapp = get_post_meta($agent_id, '_agent_whatsapp', true);
$agent_specialties = get_post_meta($agent_id, '_agent_specialties', true);
$agent_experience = get_post_meta($agent_id, '_agent_experience', true);
$agent_license = get_post_meta($agent_id, '_agent_license', true);
$agent_languages = get_post_meta($agent_id, '_agent_languages', true);
$agent_social = get_post_meta($agent_id, '_agent_social', true);
$linked_user_id = get_post_meta($agent_id, '_agent_user_id', true);

// Enqueue agent actions JavaScript
wp_enqueue_style('malisafi-agent-profile-public', MALISAFI_MLS_URL . 'assets/css/agent-profile-public.css', array('malisafi-mls-variables'), MALISAFI_MLS_VERSION);
wp_enqueue_script('malisafi-agent-actions', MALISAFI_MLS_URL . 'assets/js/agent-actions.js', array('jquery'), MALISAFI_MLS_VERSION, true);

// Localize script
wp_localize_script('malisafi-agent-actions', 'malisafiAgentAjax', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('agent_actions_nonce'),
    'isLoggedIn' => is_user_logged_in(),
    'messages' => array(
        'loginRequired' => __('You must be logged in to perform this action.', 'malisafi-mls'),
        'error' => __('An error occurred. Please try again.', 'malisafi-mls'),
        'pleaseSelectRating' => __('Please select a rating.', 'malisafi-mls'),
        'reviewTooShort' => __('Please write at least 10 characters.', 'malisafi-mls'),
        'submitting' => __('Submitting...', 'malisafi-mls'),
        'loading' => __('Loading...', 'malisafi-mls')
    )
));

// Get ratings
global $wpdb;
$ratings_table = $wpdb->prefix . 'mf_agent_ratings';
$avg_rating = $wpdb->get_var($wpdb->prepare(
    "SELECT AVG(rating) FROM $ratings_table WHERE agent_id = %d AND status = 'approved'",
    $agent_id
));
$rating_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $ratings_table WHERE agent_id = %d AND status = 'approved'",
    $agent_id
));

// Get agent properties (published only for display)
$properties = get_posts([
    'post_type' => 'malisafi_property',
    'author' => $linked_user_id,
    'post_status' => 'publish',
    'posts_per_page' => 6,
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Count all agent properties (published + pending)
$total_published = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_author = %d AND post_status = 'publish'",
    $linked_user_id
));
$total_pending = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'malisafi_property' AND post_author = %d AND post_status = 'pending'",
    $linked_user_id
));
$total_properties = intval($total_published) + intval($total_pending);
?>

<div class="malisafi-agent-profile-public">
    <!-- Agent Header -->
    <div class="agent-header">
        <div class="agent-header-content">
            <div class="agent-photo-container">
                <?php if ($agent_photo): ?>
                    <img src="<?php echo esc_url(wp_get_attachment_url($agent_photo)); ?>" 
                         alt="<?php echo esc_attr($agent->post_title); ?>" 
                         class="agent-photo">
                <?php else: ?>
                    <div class="agent-photo-placeholder">
                        <span class="dashicons dashicons-businessman"></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($avg_rating): ?>
                    <div class="agent-rating-badge">
                        <span class="rating-stars"><?php echo str_repeat('⭐', round($avg_rating)); ?></span>
                        <span class="rating-value"><?php echo number_format($avg_rating, 1); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="agent-info">
                <div class="agent-main-info">
                    <h1 class="agent-name"><?php echo esc_html($agent->post_title); ?></h1>
                    
                    <?php if ($agent_license): ?>
                        <p class="agent-license">
                            <span class="dashicons dashicons-awards"></span>
                            <?php printf(__('License #%s', 'malisafi-mls'), esc_html($agent_license)); ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($rating_count): ?>
                        <div class="agent-rating">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= round($avg_rating) ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text">
                                <?php printf(__('%s (%d reviews)', 'malisafi-mls'), number_format($avg_rating, 1), $rating_count); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="agent-meta-details">
                        <?php if ($agent_experience): ?>
                            <p class="agent-experience">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php printf(__('%s years of experience', 'malisafi-mls'), esc_html($agent_experience)); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($agent_languages): ?>
                            <p class="agent-languages">
                                <span class="dashicons dashicons-translation"></span>
                                <?php echo esc_html($agent_languages); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="agent-contact">
                    <div class="contact-methods">
                        <h3><?php _e('Contact Methods', 'malisafi-mls'); ?></h3>
                        
                        <div class="contact-links">
                            <?php if ($agent_phone): ?>
                                <a href="tel:<?php echo esc_attr($agent_phone); ?>" class="contact-link" title="<?php _e('Call agent', 'malisafi-mls'); ?>">
                                    <span class="dashicons dashicons-phone"></span>
                                    <span><?php echo esc_html($agent_phone); ?></span>
                                </a>
                            <?php endif; ?>

                            <?php if ($agent_whatsapp): ?>
                                <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $agent_whatsapp)); ?>" 
                                   class="contact-link" target="_blank" rel="noopener" title="<?php _e('WhatsApp', 'malisafi-mls'); ?>">
                                    <span class="dashicons dashicons-whatsapp"></span>
                                    <span><?php _e('WhatsApp', 'malisafi-mls'); ?></span>
                                </a>
                            <?php endif; ?>

                            <?php if ($agent_email): ?>
                                <a href="mailto:<?php echo esc_attr($agent_email); ?>" class="contact-link" title="<?php _e('Email agent', 'malisafi-mls'); ?>">
                                    <span class="dashicons dashicons-email"></span>
                                    <span><?php _e('Email', 'malisafi-mls'); ?></span>
                                </a>
                            <?php endif; ?>

                            <?php if (is_user_logged_in()): ?>
                                <button class="contact-link" id="openContactForm" title="<?php _e('Send direct message', 'malisafi-mls'); ?>">
                                    <span class="dashicons dashicons-email-alt"></span>
                                    <span><?php _e('Message', 'malisafi-mls'); ?></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rating-action">
                        <?php 
                        // Check if user can rate this agent
                        $current_user = wp_get_current_user();
                        $can_rate = false;
                        
                        // User must be logged in
                        if ($current_user->ID) {
                            // Cannot rate yourself
                            if ($current_user->ID != $linked_user_id) {
                                // Agents cannot rate other agents
                                $is_agent = in_array('malisafi_agent_basic', $current_user->roles) || 
                                           in_array('malisafi_agent_premium', $current_user->roles);
                                
                                if (!$is_agent) {
                                    $can_rate = true;
                                }
                            }
                        }
                        ?>
                        
                        <?php if ($can_rate): ?>
                            <button class="btn-rate-agent" id="writeReviewBtn">
                                <span class="dashicons dashicons-star-filled"></span>
                                <?php _e('Rate Agent', 'malisafi-mls'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Agent Stats -->
    <div class="agent-stats">
        <div class="stat-item">
            <div class="stat-value"><?php echo $total_properties; ?></div>
            <div class="stat-label">
                <?php _e('Active Listings', 'malisafi-mls'); ?>
                <?php if ($total_pending > 0): ?>
                    <small style="display: block; font-size: 11px; color: #999; margin-top: 4px;">
                        <?php printf(__('%d published, %d pending', 'malisafi-mls'), $total_published, $total_pending); ?>
                    </small>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?php echo $rating_count; ?></div>
            <div class="stat-label"><?php _e('Reviews', 'malisafi-mls'); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?php echo $agent_experience ?: '0'; ?></div>
            <div class="stat-label"><?php _e('Years Experience', 'malisafi-mls'); ?></div>
        </div>
    </div>

    <!-- Agent Bio & Specialties -->
    <div class="agent-details">
        <?php if ($agent_bio): ?>
            <div class="agent-bio">
                <h2><?php _e('About Me', 'malisafi-mls'); ?></h2>
                <div class="bio-content"><?php echo wpautop(esc_html($agent_bio)); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($agent_specialties): ?>
            <div class="agent-specialties">
                <h2><?php _e('Specialties', 'malisafi-mls'); ?></h2>
                <div class="specialties-tags">
                    <?php 
                    $specialties_array = explode(',', $agent_specialties);
                    foreach ($specialties_array as $specialty): ?>
                        <span class="specialty-tag"><?php echo esc_html(trim($specialty)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Agent Properties -->
    <?php if (!empty($properties)): ?>
        <div class="agent-properties">
            <h2><?php _e('Current Listings', 'malisafi-mls'); ?></h2>
            <div class="properties-grid">
                <?php foreach ($properties as $property): 
                    $images = get_post_meta($property->ID, '_property_images', true);
                    $price = get_post_meta($property->ID, '_property_price', true);
                    $bedrooms = get_post_meta($property->ID, '_property_bedrooms', true);
                    $bathrooms = get_post_meta($property->ID, '_property_bathrooms', true);
                    // Get status from taxonomy
                    $status_terms = wp_get_post_terms($property->ID, 'malisafi_property_status');
                    $property_status = (!empty($status_terms) && !is_wp_error($status_terms)) ? $status_terms[0]->name : '';
                ?>
                    <div class="property-card">
                        <a href="<?php echo get_permalink($property->ID); ?>" class="property-image">
                            <?php if (!empty($images) && is_array($images)): ?>
                                <?php echo wp_get_attachment_image($images[0], 'medium'); ?>
                            <?php else: ?>
                                <div class="property-placeholder">
                                    <span class="dashicons dashicons-admin-home"></span>
                                </div>
                            <?php endif; ?>
                            <?php 
                            // Always show status badge
                            if (!empty($property_status)) {
                                $status_display = ucwords(str_replace('-', ' ', $property_status));
                                $status_class = 'status-' . sanitize_html_class(strtolower(str_replace(' ', '-', $property_status)));
                            } else {
                                $status_display = 'Status Not Recorded';
                                $status_class = 'status-not-recorded';
                            }
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_display); ?></span>
                        </a>
                        <div class="property-info">
                            <h3><a href="<?php echo get_permalink($property->ID); ?>"><?php echo esc_html($property->post_title); ?></a></h3>
                            <?php if ($price): ?>
                                <p class="property-price">KSh <?php echo number_format($price); ?></p>
                            <?php endif; ?>
                            <div class="property-features">
                                <?php if ($bedrooms): ?>
                                    <span><span class="dashicons dashicons-admin-home"></span><?php echo $bedrooms; ?> Beds</span>
                                <?php endif; ?>
                                <?php if ($bathrooms): ?>
                                    <span><span class="dashicons dashicons-tub"></span><?php echo $bathrooms; ?> Baths</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Reviews Section -->
    <div class="agent-reviews">
        <h2><?php _e('Client Reviews', 'malisafi-mls'); ?></h2>
        
        <?php 
        // Check if user can write review (already checked above)
        if (is_user_logged_in() && isset($can_rate) && $can_rate): 
        ?>
            <button class="button" id="writeReviewBtn"><?php _e('Write a Review', 'malisafi-mls'); ?></button>
        <?php elseif (is_user_logged_in()): ?>
            <?php 
            $current_user = wp_get_current_user();
            if ($current_user->ID == $linked_user_id): ?>
                <p class="rate-notice"><?php _e('You cannot rate yourself.', 'malisafi-mls'); ?></p>
            <?php else: ?>
                <p class="rate-notice"><?php _e('Only clients can rate agents.', 'malisafi-mls'); ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <div id="reviewsList">
            <?php
            $reviews = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $ratings_table WHERE agent_id = %d AND status = 'approved' ORDER BY created_at DESC LIMIT 10",
                $agent_id
            ));

            if ($reviews):
                foreach ($reviews as $review):
                    $reviewer = get_userdata($review->user_id);
                    ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <?php echo get_avatar($review->user_id, 40); ?>
                                <div>
                                    <strong><?php echo $reviewer ? esc_html($reviewer->display_name) : __('Anonymous', 'malisafi-mls'); ?></strong>
                                    <?php if ($review->verified_client): ?>
                                        <span class="verified-badge" title="<?php esc_attr_e('Verified Client', 'malisafi-mls'); ?>">
                                            <span class="dashicons dashicons-yes-alt"></span> <?php _e('Verified', 'malisafi-mls'); ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="review-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= $review->rating ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <span class="review-date"><?php echo human_time_diff(strtotime($review->created_at), current_time('timestamp')) . ' ' . __('ago', 'malisafi-mls'); ?></span>
                        </div>
                        
                        <?php if ($review->review_title): ?>
                            <h4 class="review-title"><?php echo esc_html($review->review_title); ?></h4>
                        <?php endif; ?>
                        
                        <?php if ($review->review_text): ?>
                            <p class="review-comment"><?php echo esc_html($review->review_text); ?></p>
                        <?php endif; ?>
                        
                        <!-- Helpful votes -->
                        <div class="review-helpful">
                            <span class="helpful-label"><?php _e('Was this review helpful?', 'malisafi-mls'); ?></span>
                            <button class="helpful-btn helpful-yes" data-review-id="<?php echo $review->id; ?>">
                                <span class="dashicons dashicons-thumbs-up"></span>
                                <?php _e('Yes', 'malisafi-mls'); ?>
                                <span class="helpful-yes-count"><?php echo intval($review->helpful_count); ?></span>
                            </button>
                            <button class="helpful-btn helpful-no" data-review-id="<?php echo $review->id; ?>">
                                <span class="dashicons dashicons-thumbs-down"></span>
                                <?php _e('No', 'malisafi-mls'); ?>
                                <span class="helpful-no-count"><?php echo intval($review->not_helpful_count); ?></span>
                            </button>
                        </div>
                        
                        <?php if ($review->agent_response): ?>
                            <div class="agent-response">
                                <div class="response-header">
                                    <span class="dashicons dashicons-businessman"></span>
                                    <strong><?php _e('Agent Response:', 'malisafi-mls'); ?></strong>
                                    <span class="response-date"><?php echo human_time_diff(strtotime($review->agent_responded_at), current_time('timestamp')) . ' ' . __('ago', 'malisafi-mls'); ?></span>
                                </div>
                                <p><?php echo esc_html($review->agent_response); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach;
            else: ?>
                <p class="no-reviews"><?php _e('No reviews yet. Be the first to review this agent!', 'malisafi-mls'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="malisafi-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h2><?php _e('Write a Review', 'malisafi-mls'); ?></h2>
        <form class="agent-rating-form">
            <input type="hidden" name="agent_id" value="<?php echo $agent_id; ?>">
            <input type="hidden" name="property_id" value="">
            
            <div class="form-group">
                <label><?php _e('Rating *', 'malisafi-mls'); ?></label>
                <div class="star-rating-input">
                    <input type="radio" name="rating" value="5" id="star5">
                    <span class="star" data-rating="5">★</span>
                    <input type="radio" name="rating" value="4" id="star4">
                    <span class="star" data-rating="4">★</span>
                    <input type="radio" name="rating" value="3" id="star3">
                    <span class="star" data-rating="3">★</span>
                    <input type="radio" name="rating" value="2" id="star2">
                    <span class="star" data-rating="2">★</span>
                    <input type="radio" name="rating" value="1" id="star1">
                    <span class="star" data-rating="1">★</span>
                </div>
            </div>
            
            <div class="form-group">
                <label><?php _e('Review Title', 'malisafi-mls'); ?></label>
                <input type="text" name="review_title" placeholder="<?php esc_attr_e('e.g., Great service!', 'malisafi-mls'); ?>" maxlength="100">
            </div>
            
            <div class="form-group">
                <label><?php _e('Your Review *', 'malisafi-mls'); ?></label>
                <textarea name="review_text" rows="5" placeholder="<?php esc_attr_e('Share your experience working with this agent...', 'malisafi-mls'); ?>" required maxlength="500"></textarea>
                <small class="char-count"><span class="current">0</span> / 500 <?php _e('characters', 'malisafi-mls'); ?></small>
            </div>
            
            <div class="form-actions">
                <button type="button" class="button cancel-review"><?php _e('Cancel', 'malisafi-mls'); ?></button>
                <button type="submit" class="button button-primary submit-rating"><?php _e('Submit Review', 'malisafi-mls'); ?></button>
            </div>
        </form>
    </div>
</div>
