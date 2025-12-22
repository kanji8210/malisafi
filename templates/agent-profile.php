<?php
/**
 * Agent Profile Template
 * Displays agent profile with ratings, reviews, properties, and contact info
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

$agent_id = isset($atts['id']) ? intval($atts['id']) : 0;

if ($agent_id <= 0) {
    echo '<p>' . __('Invalid agent ID.', 'malisafi-mls') . '</p>';
    return;
}

// Get agent post
$agent = get_post($agent_id);

if (!$agent || $agent->post_type !== 'malisafi_agent') {
    echo '<p>' . __('Agent not found.', 'malisafi-mls') . '</p>';
    return;
}

// Get agent meta
$email = get_post_meta($agent_id, '_malisafi_agent_email', true);
$office_phone = get_post_meta($agent_id, '_malisafi_agent_office_phone', true);
$mobile_phone = get_post_meta($agent_id, '_malisafi_agent_mobile_phone', true);
$whatsapp = get_post_meta($agent_id, '_malisafi_agent_whatsapp', true);
$agency = get_post_meta($agent_id, '_malisafi_agency_name', true);
$license = get_post_meta($agent_id, '_malisafi_license_number', true);
$experience = get_post_meta($agent_id, '_malisafi_years_experience', true);
$languages = get_post_meta($agent_id, '_malisafi_languages', true);

// Get linked WordPress user
$linked_user_id = get_post_meta($agent_id, '_malisafi_linked_user', true);

// Get ratings
global $wpdb;
$ratings_table = $wpdb->prefix . 'mf_agent_ratings';

$rating_stats = $wpdb->get_row($wpdb->prepare(
    "SELECT 
        AVG(rating) as average,
        COUNT(*) as total,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM {$ratings_table}
    WHERE agent_id = %d AND status = 'approved'",
    $agent_id
));

$avg_rating = $rating_stats ? round($rating_stats->average, 1) : 0;
$total_ratings = $rating_stats ? $rating_stats->total : 0;

// Get agent properties
$properties_args = array(
    'post_type' => 'malisafi_property',
    'posts_per_page' => 6,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_malisafi_agent_id',
            'value' => $linked_user_id,
            'compare' => '='
        )
    )
);

$properties_query = new WP_Query($properties_args);
$total_properties = $properties_query->found_posts;

// Hide contact info for non-privileged users
$can_see_contacts = current_user_can('moderate_malisafi_properties') || current_user_can('manage_options');

?>

<div class="malisafi-agent-profile">
    
    <!-- Agent Header -->
    <div class="agent-header">
        <div class="agent-avatar">
            <?php echo get_the_post_thumbnail($agent_id, 'medium', array('class' => 'agent-photo')); ?>
        </div>
        
        <div class="agent-info">
            <h1 class="agent-name"><?php echo esc_html($agent->post_title); ?></h1>
            
            <?php if ($agency) : ?>
                <p class="agent-agency"><?php echo esc_html($agency); ?></p>
            <?php endif; ?>
            
            <!-- Rating Stars -->
            <div class="agent-rating-summary">
                <div class="star-rating">
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <span class="star <?php echo $i <= round($avg_rating) ? 'filled' : ''; ?>">★</span>
                    <?php endfor; ?>
                </div>
                <span class="agent-average-rating"><?php echo number_format($avg_rating, 1); ?></span>
                <span class="agent-total-ratings">(<?php echo $total_ratings; ?> <?php _e('reviews', 'malisafi-mls'); ?>)</span>
            </div>
            
            <div class="agent-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_properties; ?></span>
                    <span class="stat-label"><?php _e('Properties', 'malisafi-mls'); ?></span>
                </div>
                <?php if ($experience) : ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $experience; ?></span>
                    <span class="stat-label"><?php _e('Years Experience', 'malisafi-mls'); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="agent-actions">
                <button class="btn btn-primary rating-form-toggle">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php _e('Rate This Agent', 'malisafi-mls'); ?>
                </button>
                <button class="btn btn-secondary show-report-modal">
                    <span class="dashicons dashicons-flag"></span>
                    <?php _e('Report', 'malisafi-mls'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Agent Bio -->
    <?php if ($agent->post_content) : ?>
    <div class="agent-bio">
        <h2><?php _e('About', 'malisafi-mls'); ?></h2>
        <?php echo wpautop($agent->post_content); ?>
    </div>
    <?php endif; ?>
    
    <!-- Contact Information -->
    <div class="agent-contact-section">
        <h2><?php _e('Contact Information', 'malisafi-mls'); ?></h2>
        
        <?php if ($can_see_contacts) : ?>
            <div class="contact-details">
                <?php if ($email) : ?>
                    <div class="contact-item">
                        <span class="dashicons dashicons-email"></span>
                        <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                    </div>
                <?php endif; ?>
                
                <?php if ($mobile_phone) : ?>
                    <div class="contact-item">
                        <span class="dashicons dashicons-phone"></span>
                        <a href="tel:<?php echo esc_attr($mobile_phone); ?>"><?php echo esc_html($mobile_phone); ?></a>
                    </div>
                <?php endif; ?>
                
                <?php if ($whatsapp) : ?>
                    <div class="contact-item">
                        <span class="dashicons dashicons-whatsapp"></span>
                        <a href="https://wa.me/<?php echo esc_attr(str_replace('+', '', $whatsapp)); ?>" target="_blank">WhatsApp</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <p class="contact-restricted">
                <?php _e('Contact information is only visible to registered members.', 'malisafi-mls'); ?>
                <?php if (!is_user_logged_in()) : ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>"><?php _e('Login to view', 'malisafi-mls'); ?></a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Agent Properties -->
    <?php if ($properties_query->have_posts()) : ?>
    <div class="agent-properties-section">
        <h2><?php printf(__('%s\'s Properties', 'malisafi-mls'), esc_html($agent->post_title)); ?></h2>
        
        <div class="properties-grid">
            <?php while ($properties_query->have_posts()) : $properties_query->the_post(); ?>
                <?php include MALISAFI_MLS_PATH . 'templates/property-card-modern.php'; ?>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        </div>
        
        <?php if ($total_properties > 6) : ?>
            <p class="view-all-properties">
                <a href="?agent=<?php echo $agent_id; ?>" class="btn btn-outline">
                    <?php _e('View All Properties', 'malisafi-mls'); ?> (<?php echo $total_properties; ?>)
                </a>
            </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Reviews Section -->
    <div class="agent-reviews-section">
        <h2><?php _e('Reviews', 'malisafi-mls'); ?></h2>
        
        <!-- Rating Form (Hidden by default) -->
        <?php if (is_user_logged_in()) : ?>
        <form class="agent-rating-form" style="display: none;">
            <input type="hidden" name="agent_id" value="<?php echo $agent_id; ?>">
            
            <div class="form-group">
                <label><?php _e('Your Rating', 'malisafi-mls'); ?> *</label>
                <div class="star-rating-input">
                    <?php for ($i = 5; $i >= 1; $i--) : ?>
                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="rating-<?php echo $i; ?>" required>
                        <label for="rating-<?php echo $i; ?>" class="star" data-rating="<?php echo $i; ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="review-title"><?php _e('Review Title', 'malisafi-mls'); ?></label>
                <input type="text" name="review_title" id="review-title" placeholder="<?php _e('e.g., Great service!', 'malisafi-mls'); ?>">
            </div>
            
            <div class="form-group">
                <label for="review-text"><?php _e('Your Review', 'malisafi-mls'); ?></label>
                <textarea name="review_text" id="review-text" rows="5" placeholder="<?php _e('Share your experience...', 'malisafi-mls'); ?>"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary submit-rating"><?php _e('Submit Review', 'malisafi-mls'); ?></button>
        </form>
        <?php endif; ?>
        
        <!-- Reviews List -->
        <div class="reviews-list">
            <?php
            $reviews = \MalisafiMLS\Agent_Actions_Ajax::get_agent_ratings($agent_id, 10);
            
            if ($reviews) :
                foreach ($reviews as $review) :
            ?>
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <strong class="reviewer-name"><?php echo esc_html($review->reviewer_name); ?></strong>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <span class="star <?php echo $i <= $review->rating ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <span class="review-date"><?php echo human_time_diff(strtotime($review->created_at), current_time('timestamp')); ?> <?php _e('ago', 'malisafi-mls'); ?></span>
                    </div>
                    
                    <?php if ($review->review_title) : ?>
                        <h4 class="review-title"><?php echo esc_html($review->review_title); ?></h4>
                    <?php endif; ?>
                    
                    <?php if ($review->review_text) : ?>
                        <p class="review-text"><?php echo esc_html($review->review_text); ?></p>
                    <?php endif; ?>
                    
                    <div class="review-helpful">
                        <span><?php _e('Was this helpful?', 'malisafi-mls'); ?></span>
                        <button class="helpful-btn helpful-yes" data-review-id="<?php echo $review->id; ?>">
                            👍 <span class="helpful-yes-count"><?php echo $review->helpful_count; ?></span>
                        </button>
                        <button class="helpful-btn helpful-no" data-review-id="<?php echo $review->id; ?>">
                            👎 <span class="helpful-no-count"><?php echo $review->not_helpful_count; ?></span>
                        </button>
                    </div>
                </div>
            <?php
                endforeach;
            else :
                echo '<p>' . __('No reviews yet. Be the first to review this agent!', 'malisafi-mls') . '</p>';
            endif;
            ?>
        </div>
    </div>
    
</div>

<!-- Report Modal -->
<div class="report-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <button class="close-modal">×</button>
        <h3><?php _e('Report Agent', 'malisafi-mls'); ?></h3>
        
        <form class="report-agent-form">
            <input type="hidden" name="agent_id" value="<?php echo $agent_id; ?>">
            
            <div class="form-group">
                <label><?php _e('Report Type', 'malisafi-mls'); ?> *</label>
                <select name="report_type" required>
                    <option value=""><?php _e('Select a reason', 'malisafi-mls'); ?></option>
                    <option value="spam"><?php _e('Spam', 'malisafi-mls'); ?></option>
                    <option value="inappropriate"><?php _e('Inappropriate Behavior', 'malisafi-mls'); ?></option>
                    <option value="fraud"><?php _e('Fraud/Scam', 'malisafi-mls'); ?></option>
                    <option value="harassment"><?php _e('Harassment', 'malisafi-mls'); ?></option>
                    <option value="fake_info"><?php _e('Fake Information', 'malisafi-mls'); ?></option>
                    <option value="other"><?php _e('Other', 'malisafi-mls'); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label><?php _e('Details', 'malisafi-mls'); ?> *</label>
                <textarea name="report_reason" rows="5" required placeholder="<?php _e('Please provide details about your report...', 'malisafi-mls'); ?>"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary submit-report"><?php _e('Submit Report', 'malisafi-mls'); ?></button>
        </form>
    </div>
</div>
