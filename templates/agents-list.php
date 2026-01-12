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
        
        // Agent profile URL
        $profile_url = get_permalink($agent_id);
    ?>
    
    <div class="agent-card">
        <a href="<?php echo esc_url($profile_url); ?>" class="agent-card-link">
            
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
                <div class="agent-card-actions" style="margin-top:8px;text-align:center;">
                    <a href="<?php echo esc_url($profile_url); ?>" class="button button-small" target="_blank"><?php _e('Voir le profil', 'malisafi-mls'); ?></a>
                    <a href="<?php echo esc_url($profile_url); ?>" class="button button-small button-accent" target="_blank"><?php _e('Noter', 'malisafi-mls'); ?></a>
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
    </div>
    

    <?php endwhile; ?>
    <?php wp_reset_postdata(); ?>

    </div>

    <!-- Agent Details Modal -->
    <div id="agent-details-modal" class="malisafi-modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Détails de l'agent</h3>
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
            document.getElementById('modal-agent-experience').textContent = btn.dataset.agentExperience ? btn.dataset.agentExperience + ' ans d’expérience' : '';
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
                ratingHtml += ' <span style="margin-left:6px;">' + rating + ' / 5 (' + total + ' avis)</span>';
            } else {
                ratingHtml = '<span style="color:var(--mls-text-secondary);">Aucune note</span>';
            }
            document.getElementById('modal-agent-rating-summary').innerHTML = ratingHtml;
        });
    });
    // Fermer le modal au clic sur fond
    document.getElementById('agent-details-modal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
    </script>
