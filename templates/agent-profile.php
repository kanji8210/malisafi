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
$can_see_contacts = true; // Allowed for everyone as per guest inquiry requirements

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
                <?php
                $can_rate = false;
                if (is_user_logged_in()) {
                    $current_user = wp_get_current_user();
                    $user_roles = (array) $current_user->roles;
                    $forbidden_roles = array('agent_basic', 'agent_premium', 'owner', 'developer');
                    if (!array_intersect($forbidden_roles, $user_roles) && $current_user->ID != $linked_user_id) {
                        $can_rate = true;
                    }
                }
                ?>
                <?php if ($can_rate): ?>
                <button class="btn btn-primary" onclick="document.getElementById('rate-agent-modal').classList.add('open');">
                    <span class="dashicons dashicons-star-half"></span> <?php _e('Noter cet agent', 'malisafi-mls'); ?>
                </button>
                <?php endif; ?>
                <button class="btn btn-secondary show-report-modal">
                    <span class="dashicons dashicons-flag"></span>
                    <?php _e('Report', 'malisafi-mls'); ?>
                </button>
                <button class="btn btn-primary" id="openContactForm">
                    <span class="dashicons dashicons-email-alt"></span>
                    <?php _e('Message', 'malisafi-mls'); ?>
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
        
        <!-- Rate Agent Modal -->
        <div id="rate-agent-modal" class="malisafi-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e('Noter cet agent', 'malisafi-mls'); ?></h3>
                    <button class="modal-close" onclick="document.getElementById('rate-agent-modal').classList.remove('open');">&times;</button>
                </div>
                <div class="modal-body">
                    <?php if (!$can_rate): ?>
                        <p><?php _e('Vous devez être connecté et ne pas être agent pour noter un agent.', 'malisafi-mls'); ?></p>
                    <?php else: ?>
                    <form id="rate-agent-form" method="post">
                        <input type="hidden" name="agent_id" value="<?php echo $agent_id; ?>">
                        <div class="form-group">
                            <label for="rating">Votre note :</label>
                            <select name="rating" id="rating" required>
                                <option value="">Choisir...</option>
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Très bien</option>
                                <option value="3">3 - Bien</option>
                                <option value="2">2 - Moyen</option>
                                <option value="1">1 - Mauvais</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="review_title">Titre de l'avis :</label>
                            <input type="text" name="review_title" id="review_title" maxlength="255" placeholder="Titre court" required>
                        </div>
                        <div class="form-group">
                            <label for="review_text">Votre avis :</label>
                            <textarea name="review_text" id="review_text" rows="4" placeholder="Votre expérience..." required></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="button-secondary modal-close" onclick="document.getElementById('rate-agent-modal').classList.remove('open');">Annuler</button>
                            <button type="submit" class="button-primary">Envoyer</button>
                        </div>
                    </form>
                    <div id="rate-agent-success" style="display:none;color:var(--mls-accent);margin-top:10px;"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <script>
        document.getElementById('rate-agent-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            var form = e.target;
            var data = new FormData(form);
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                body: new URLSearchParams({
                    action: 'malisafi_rate_agent',
                    agent_id: data.get('agent_id'),
                    rating: data.get('rating'),
                    review_title: data.get('review_title'),
                    review_text: data.get('review_text'),
                    nonce: '<?php echo wp_create_nonce('malisafi_agent_nonce'); ?>'
                })
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    form.style.display = 'none';
                    document.getElementById('rate-agent-success').style.display = 'block';
                    document.getElementById('rate-agent-success').textContent = 'Merci pour votre avis !';
                } else {
                    alert(resp.data?.message || 'Erreur lors de l’envoi.');
                }
            });
        });
        </script>
        
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

<!-- Inquiry Modal -->
<div id="inquiry-modal" class="malisafi-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Contact Agent', 'malisafi-mls'); ?></h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="inquiry-form">
                <input type="hidden" name="agent_id" value="<?php echo $agent_id; ?>">
                <input type="hidden" name="property_id" value="0">
                <input type="hidden" name="hp_name" value="">
                <input type="hidden" name="form_ts" value="<?php echo time(); ?>">
                
                <div class="form-group">
                    <label><?php _e('Your Name', 'malisafi-mls'); ?></label>
                    <input type="text" name="inquiry_name" placeholder="<?php _e('Enter your name', 'malisafi-mls'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label><?php _e('Your Email', 'malisafi-mls'); ?></label>
                    <input type="email" name="inquiry_email" placeholder="your@email.com" required>
                </div>
                
                <div class="form-group">
                    <label><?php _e('Phone Number', 'malisafi-mls'); ?></label>
                    <input type="tel" name="inquiry_phone" placeholder="<?php _e('Your phone number', 'malisafi-mls'); ?>">
                </div>
                
                <div class="form-group">
                    <label><?php _e('Message', 'malisafi-mls'); ?></label>
                    <textarea name="inquiry_message" rows="4" placeholder="<?php _e('I would like to inquire about your services. Please contact me.', 'malisafi-mls'); ?>" required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="button-secondary modal-close"><?php _e('Cancel', 'malisafi-mls'); ?></button>
                    <?php if (get_option('malisafi_inquiry_recaptcha_enabled') && get_option('malisafi_inquiry_recaptcha_site_key')): ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr(get_option('malisafi_inquiry_recaptcha_site_key')); ?>" style="margin-bottom:8px;"></div>
                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                    <?php endif; ?>
                    <button type="submit" class="button-primary"><?php _e('Send Inquiry', 'malisafi-mls'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
