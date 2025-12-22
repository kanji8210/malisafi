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
        
        // Get rating stats
        $rating_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as total
            FROM {$ratings_table}
            WHERE agent_id = %d AND status = 'approved'",
            $agent_id
        ));
        
        $avg_rating = $rating_stats ? round($rating_stats->average, 1) : 0;
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
            
            <div class="agent-card-photo">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium', array('class' => 'agent-thumbnail')); ?>
                <?php else : ?>
                    <img src="<?php echo MALISAFI_MLS_URL . 'assets/images/default-agent.png'; ?>" alt="<?php the_title(); ?>" class="agent-thumbnail">
                <?php endif; ?>
            </div>
            
            <div class="agent-card-content">
                <h3 class="agent-card-name"><?php the_title(); ?></h3>
                
                <?php if ($agency) : ?>
                    <p class="agent-card-agency"><?php echo esc_html($agency); ?></p>
                <?php endif; ?>
                
                <div class="agent-card-rating">
                    <div class="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <span class="star <?php echo $i <= round($avg_rating) ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-number"><?php echo number_format($avg_rating, 1); ?></span>
                    <span class="rating-count">(<?php echo $total_ratings; ?>)</span>
                </div>
                
                <div class="agent-card-stats">
                    <span class="stat-item">
                        <span class="dashicons dashicons-admin-home"></span>
                        <?php echo $properties_count; ?> <?php _e('Properties', 'malisafi-mls'); ?>
                    </span>
                </div>
                
                <?php if (get_the_excerpt()) : ?>
                    <p class="agent-card-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
                <?php endif; ?>
                
            </div>
            
        </a>
    </div>
    
    <?php endwhile; ?>
    <?php wp_reset_postdata(); ?>
    
</div>
