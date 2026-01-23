<?php
/**
 * Agents List Template
 * Displays grid/list of agents
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get attributes
$layout = isset($atts['layout']) ? $atts['layout'] : 'grid';
$columns = isset($atts['columns']) ? intval($atts['columns']) : 3;
$per_page = isset($atts['per_page']) ? intval($atts['per_page']) : 12;
$featured_only = isset($atts['featured']) && $atts['featured'] === 'true';

// Query agents
$args = array(
    'post_type' => 'malisafi_agent',
    'posts_per_page' => $per_page,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC'
);

if ($featured_only) {
    $args['meta_query'] = array(
        array(
            'key' => '_malisafi_featured_agent',
            'value' => '1',
            'compare' => '='
        )
    );
}

$agents_query = new WP_Query($args);

if (!$agents_query->have_posts()) {
    echo '<p>' . __('No agents found.', 'malisafi-mls') . '</p>';
    return;
}

global $wpdb;
$ratings_table = $wpdb->prefix . 'mf_agent_ratings';

?>

<div class="malisafi-agents-list layout-<?php echo esc_attr($layout); ?> columns-<?php echo esc_attr($columns); ?>">
    
    <?php while ($agents_query->have_posts()) : $agents_query->the_post(); 
        $agent_id = get_the_ID();
        $email = get_post_meta($agent_id, '_malisafi_agent_email', true);
        $phone = get_post_meta($agent_id, '_malisafi_agent_mobile_phone', true);
        $agency = get_post_meta($agent_id, '_malisafi_agency_name', true);
        $linked_user_id = get_post_meta($agent_id, '_malisafi_linked_user', true);
        $experience = get_post_meta($agent_id, '_malisafi_years_experience', true);
        
        // Get rating stats
        $rating_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as total
            FROM {$ratings_table}
            WHERE agent_id = %d AND status = 'approved'",
            $agent_id
        ));
        
        $avg_rating = ($rating_stats && $rating_stats->average !== null) ? round($rating_stats->average, 1) : 0;
        $total_ratings = $rating_stats ? $rating_stats->total : 0;
        
        // Get property count (optimized with direct SQL COUNT)
        $properties_count = 0;
        if ($linked_user_id) {
            $properties_count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_type = 'malisafi_property' 
                AND post_author = %d
                AND post_status = 'publish'",
                $linked_user_id
            ));
        }
        
        // Agent profile URL - use page with query param for compatibility
        $profile_url = home_url('/agent-profile/?agent_id=' . $agent_id);
    ?>
    
    <div class="agent-card">
        <a href="<?php echo esc_url($profile_url); ?>" class="agent-card-link">
            <div class="agent-card-photo">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium', array('class' => 'agent-thumbnail')); ?>
                <?php else : ?>
                    <img src="<?php echo MALISAFI_MLS_URL . 'assets/images/default-agent.png'; ?>" alt="<?php the_title(); ?>" class="agent-thumbnail">
                <?php endif; ?>
            </div>
            <div class="agent-card-info">
                <h3 class="agent-card-name"><?php the_title(); ?></h3>
                <?php if ($agency) : ?><div class="agent-card-agency"><?php echo esc_html($agency); ?></div><?php endif; ?>
                <div class="agent-card-meta">
                    <?php if ($properties_count) : ?><span class="meta-item"><span class="dashicons dashicons-admin-home"></span> <?php echo $properties_count; ?> <?php _e('Properties', 'malisafi-mls'); ?></span><?php endif; ?>
                    <?php if ($experience) : ?><span class="meta-item"><span class="dashicons dashicons-awards"></span> <?php echo esc_html($experience); ?> <?php _e('yrs', 'malisafi-mls'); ?></span><?php endif; ?>
                </div>
            </div>
        </a>
        <div class="agent-card-actions">
            <a href="<?php echo esc_url($profile_url); ?>" class="button button-small"><?php _e('View Profile', 'malisafi-mls'); ?></a>
            <button type="button" class="button button-small button-accent button-rate-agent" 
                data-agent-id="<?php echo esc_attr($agent_id); ?>"
                data-agent-name="<?php echo esc_attr(get_the_title()); ?>"><?php _e('Rate Agent', 'malisafi-mls'); ?></button>
            <button type="button" class="button button-small button-view-details" 
                data-agent-id="<?php echo esc_attr($agent_id); ?>"
                data-agent-name="<?php echo esc_attr(get_the_title()); ?>"
                data-agent-agency="<?php echo esc_attr($agency); ?>"
                data-agent-experience="<?php echo esc_attr($experience); ?>"
                data-agent-phone="<?php echo esc_attr($phone); ?>"
                data-agent-email="<?php echo esc_attr($email); ?>"
                data-agent-rating="<?php echo esc_attr($avg_rating); ?>"
                data-agent-total-ratings="<?php echo esc_attr($total_ratings); ?>"
                data-agent-photo="<?php echo has_post_thumbnail() ? esc_url(get_the_post_thumbnail_url($agent_id, 'medium')) : MALISAFI_MLS_URL . 'assets/images/default-agent.png'; ?>"
            ><?php _e('View details', 'malisafi-mls'); ?></button>
        </div>
    </div>
    

    <?php endwhile; ?>
    <?php wp_reset_postdata(); ?>

    </div>

    <!-- Agent Details Modal -->
    <div id="agent-details-modal" class="malisafi-modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php _e('Agent Details', 'malisafi-mls'); ?></h3>
                <button class="modal-close" onclick="document.getElementById('agent-details-modal').style.display='none';">&times;</button>
            </div>
            <div class="modal-body">
                <div class="agent-modal-info">
                    <div class="agent-avatar" style="margin-bottom:10px;text-align:center;">
                        <img id="modal-agent-photo" src="" alt="Agent" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
                    </div>
                    <div class="agent-details" style="text-align:center;">
                        <h4 class="agent-name" id="modal-agent-name" style="margin-bottom:4px;"></h4>
                        <div class="agent-agency" id="modal-agent-agency" style="margin-bottom:8px;color:var(--mls-text-secondary);"></div>
                        <div class="agent-experience" id="modal-agent-experience" style="margin-bottom:8px;"></div>
                        <div class="agent-contact-details" style="margin-bottom:8px;">
                            <div class="contact-item" id="modal-agent-phone"></div>
                            <div class="contact-item" id="modal-agent-email"></div>
                        </div>
                        <div class="agent-rating-summary" id="modal-agent-rating-summary" style="margin-bottom:10px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.querySelectorAll('.button-view-details').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('agent-details-modal').style.display = 'block';
            document.getElementById('modal-agent-photo').src = btn.dataset.agentPhoto;
            document.getElementById('modal-agent-name').textContent = btn.dataset.agentName;
            document.getElementById('modal-agent-agency').textContent = btn.dataset.agentAgency ? btn.dataset.agentAgency : '';
        document.getElementById('modal-agent-experience').textContent = btn.dataset.agentExperience ? btn.dataset.agentExperience + ' <?php _e('years of experience', 'malisafi-mls'); ?>' : '';
            document.getElementById('modal-agent-phone').innerHTML = btn.dataset.agentPhone ? '<span class="dashicons dashicons-phone"></span> <a href="tel:' + btn.dataset.agentPhone + '">' + btn.dataset.agentPhone + '</a>' : '';
            document.getElementById('modal-agent-email').innerHTML = btn.dataset.agentEmail ? '<span class="dashicons dashicons-email"></span> <a href="mailto:' + btn.dataset.agentEmail + '">' + btn.dataset.agentEmail + '</a>' : '';
            // Rating
            var rating = parseFloat(btn.dataset.agentRating);
            var total = parseInt(btn.dataset.agentTotalRatings);
            var ratingHtml = '';
            if (rating > 0) {
                for (var i = 1; i <= 5; i++) {
                    if (i <= Math.floor(rating)) {
                        ratingHtml += '<span class="dashicons dashicons-star-filled"></span>';
                    } else if (i - rating < 1) {
                        ratingHtml += '<span class="dashicons dashicons-star-half"></span>';
                    } else {
                        ratingHtml += '<span class="dashicons dashicons-star-empty"></span>';
                    }
                }
                ratingHtml += ' <span style="margin-left:6px;">' + rating + ' / 5 (' + total + ' <?php _e('reviews', 'malisafi-mls'); ?>)</span>';
            } else {
                ratingHtml = '<span style="color:var(--mls-text-secondary);"><?php _e('No ratings yet', 'malisafi-mls'); ?></span>';
            }
            document.getElementById('modal-agent-rating-summary').innerHTML = ratingHtml;
        });
    });
    // Close modal when clicking on background
    document.getElementById('agent-details-modal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
    </script>

<!-- Rate Agent Modal -->
<div id="rate-agent-modal" class="malisafi-modal" style="display:none;">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h3><?php _e('Rate Agent', 'malisafi-mls'); ?></h3>
            <button class="modal-close" onclick="document.getElementById('rate-agent-modal').style.display='none';">&times;</button>
        </div>
        <div class="modal-body">
            <div id="rate-agent-content">
                <p style="text-align:center;margin-bottom:20px;">
                    <?php _e('Rating', 'malisafi-mls'); ?>: <strong id="rating-agent-name"></strong>
                </p>
                
                <?php if (is_user_logged_in()): ?>
                    <form class="agent-rating-form" id="quick-rating-form">
                        <input type="hidden" name="agent_id" id="rating-agent-id" value="">
                        
                        <div class="form-group" style="margin-bottom:20px;">
                            <label style="display:block;margin-bottom:10px;font-weight:600;"><?php _e('Your Rating *', 'malisafi-mls'); ?></label>
                            <div class="star-rating-input" style="display:flex;justify-content:center;gap:10px;font-size:32px;direction:rtl;">
                                <input type="radio" name="rating" value="5" id="star5" style="display:none;">
                                <label for="star5" class="star" data-rating="5" style="cursor:pointer;color:#ddd;">★</label>
                                <input type="radio" name="rating" value="4" id="star4" style="display:none;">
                                <label for="star4" class="star" data-rating="4" style="cursor:pointer;color:#ddd;">★</label>
                                <input type="radio" name="rating" value="3" id="star3" style="display:none;">
                                <label for="star3" class="star" data-rating="3" style="cursor:pointer;color:#ddd;">★</label>
                                <input type="radio" name="rating" value="2" id="star2" style="display:none;">
                                <label for="star2" class="star" data-rating="2" style="cursor:pointer;color:#ddd;">★</label>
                                <input type="radio" name="rating" value="1" id="star1" style="display:none;">
                                <label for="star1" class="star" data-rating="1" style="cursor:pointer;color:#ddd;">★</label>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom:15px;">
                            <label style="display:block;margin-bottom:5px;"><?php _e('Review Title', 'malisafi-mls'); ?></label>
                            <input type="text" name="review_title" placeholder="<?php esc_attr_e('e.g., Great service!', 'malisafi-mls'); ?>" maxlength="100" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom:20px;">
                            <label style="display:block;margin-bottom:5px;"><?php _e('Your Review *', 'malisafi-mls'); ?></label>
                            <textarea name="review_text" rows="4" placeholder="<?php esc_attr_e('Share your experience...', 'malisafi-mls'); ?>" maxlength="500" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;"></textarea>
                            <small style="color:#666;"><span class="char-count">0</span> / 500 <?php _e('characters', 'malisafi-mls'); ?></small>
                        </div>
                        
                        <div class="form-actions" style="text-align:right;">
                            <button type="button" class="button" onclick="document.getElementById('rate-agent-modal').style.display='none';" style="margin-right:10px;"><?php _e('Cancel', 'malisafi-mls'); ?></button>
                            <button type="submit" class="button button-primary submit-rating"><?php _e('Submit Review', 'malisafi-mls'); ?></button>
                        </div>
                    </form>
                <?php else: ?>
                    <p style="text-align:center;padding:20px;">
                        <?php _e('Please log in to rate this agent.', 'malisafi-mls'); ?>
                    </p>
                    <div style="text-align:center;">
                        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="button button-primary"><?php _e('Login', 'malisafi-mls'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
            <div id="rate-agent-success" style="display:none;text-align:center;padding:20px;">
                <span class="dashicons dashicons-yes-alt" style="font-size:48px;color:#10b981;width:48px;height:48px;"></span>
                <h4 style="margin-top:10px;"><?php _e('Thank you for your review!', 'malisafi-mls'); ?></h4>
                <p><?php _e('Your review has been submitted and will be visible after approval.', 'malisafi-mls'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Rate Agent Modal
    var rateButtons = document.querySelectorAll('.button-rate-agent');
    
    rateButtons.forEach(function(btn, index) {
        btn.addEventListener('click', function() {
            var agentId = btn.dataset.agentId;
            var agentName = btn.dataset.agentName;
            
            var modal = document.getElementById('rate-agent-modal');
            var agentIdInput = document.getElementById('rating-agent-id');
            var agentNameSpan = document.getElementById('rating-agent-name');
            var contentDiv = document.getElementById('rate-agent-content');
            var successDiv = document.getElementById('rate-agent-success');
            
            if (!modal) {
                console.error('❌ Modal element #rate-agent-modal not found!');
                return;
            }
            
            agentIdInput.value = agentId;
            agentNameSpan.textContent = agentName;
            contentDiv.style.display = 'block';
            successDiv.style.display = 'none';
            modal.style.display = 'block';
            
            // Reset form
            var form = document.getElementById('quick-rating-form');
            if (form) form.reset();
            document.querySelectorAll('.star-rating-input .star').forEach(function(star) {
                star.style.color = '#ddd';
            });
        });
    });

    // Star rating interaction
    var stars = document.querySelectorAll('.star-rating-input .star');
    
    stars.forEach(function(star) {
        star.addEventListener('click', function() {
            var rating = parseInt(this.dataset.rating);
            document.getElementById('star' + rating).checked = true;
            
            // Highlight stars
            document.querySelectorAll('.star-rating-input .star').forEach(function(s) {
                var sRating = parseInt(s.dataset.rating);
                if (sRating <= rating) {
                    s.style.color = '#ffa500';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
        
        star.addEventListener('mouseenter', function() {
            var rating = parseInt(this.dataset.rating);
            document.querySelectorAll('.star-rating-input .star').forEach(function(s) {
                var sRating = parseInt(s.dataset.rating);
                if (sRating <= rating) {
                    s.style.color = '#ffa500';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });

    var starContainer = document.querySelector('.star-rating-input');
    if (starContainer) {
        starContainer.addEventListener('mouseleave', function() {
            var checked = document.querySelector('.star-rating-input input:checked');
            var rating = checked ? parseInt(checked.value) : 0;
            document.querySelectorAll('.star-rating-input .star').forEach(function(s) {
                var sRating = parseInt(s.dataset.rating);
                if (sRating <= rating) {
                    s.style.color = '#ffa500';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    }

    // Character count
    var reviewText = document.querySelector('textarea[name="review_text"]');
    if (reviewText) {
        reviewText.addEventListener('input', function() {
            var charCount = document.querySelector('.char-count');
            if (charCount) charCount.textContent = this.value.length;
        });
    }
    
    // Form submission
    var ratingForm = document.getElementById('quick-rating-form');
    
    if (ratingForm) {
        ratingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Validate only this form's required fields
            var rating = this.querySelector('input[name="rating"]:checked');
            var reviewText = this.querySelector('textarea[name="review_text"]');
            
            if (!rating || !rating.value) {
                alert('<?php _e('Please select a star rating', 'malisafi-mls'); ?>');
                return false;
            }
            
            if (!reviewText || !reviewText.value.trim()) {
                alert('<?php _e('Please write a review', 'malisafi-mls'); ?>');
                reviewText.focus();
                return false;
            }
            
            var submitBtn = this.querySelector('.submit-rating');
            var originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '<?php _e('Submitting...', 'malisafi-mls'); ?>';
            
            var formData = new FormData(this);
            formData.append('action', 'malisafi_rate_agent');
            formData.append('nonce', '<?php echo wp_create_nonce('malisafi_agent_nonce'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('rate-agent-content').style.display = 'none';
                    document.getElementById('rate-agent-success').style.display = 'block';
                    setTimeout(function() {
                        document.getElementById('rate-agent-modal').style.display = 'none';
                    }, 3000);
                } else {
                    alert(data.data.message || '<?php _e('Error submitting review', 'malisafi-mls'); ?>');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(error => {
                alert('<?php _e('Error submitting review', 'malisafi-mls'); ?>');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
    
    // Close modal when clicking on background
    var rateModal = document.getElementById('rate-agent-modal');
    
    if (rateModal) {
        rateModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    }
});
</script>

</div>
