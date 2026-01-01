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
        
        // Get property count
        $properties_count = 0;
        if ($linked_user_id) {
            $properties_count = get_posts(array(
                'post_type' => 'malisafi_property',
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_malisafi_agent_id',
                        'value' => $linked_user_id
                    )
                ),
                'fields' => 'ids',
                'posts_per_page' => -1
            ));
            $properties_count = is_array($properties_count) ? count($properties_count) : 0;
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
                </div>
            </div>
    </div>
    
    <?php endwhile; ?>
    <?php wp_reset_postdata(); ?>
    
</div>
