<?php
/**
 * Property Approval Workflow
 * 
 * Handles property status changes and approval workflow for agents
 * Ensures edited properties return to pending approval
 * 
 * @package MalisafiMLS
 */

class Malisafi_Property_Approval_Workflow {
    
    /**
     * Initialize the approval workflow
     */
    public static function init() {
        // Hook before property is saved
        add_action('save_post_malisafi_property', array(__CLASS__, 'handle_property_status'), 10, 3);
        
        // Hook when property is updated via admin form
        add_action('admin_post_malisafi_submit_property', array(__CLASS__, 'intercept_property_submission'), 5);
        
        // Track original property status before edit
        add_action('load-post.php', array(__CLASS__, 'store_original_status'));
        
        // Add admin notices for pending approval
        add_action('admin_notices', array(__CLASS__, 'show_pending_approval_notice'));
    }
    
    /**
     * Check if user is an agent (not admin or moderator)
     */
    private static function is_agent($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $agent_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer');
        
        // Admins and moderators can publish directly
        if (array_intersect(array('administrator', 'malisafi_moderator'), $user->roles)) {
            return false;
        }
        
        return array_intersect($agent_roles, $user->roles) ? true : false;
    }
    
    /**
     * Check if user can publish properties directly
     */
    private static function can_publish_directly($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Check if user has moderation capabilities
        if (user_can($user_id, 'moderate_properties') || user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Premium agents might be able to publish directly (based on settings)
        $user = get_userdata($user_id);
        if ($user && in_array('malisafi_agent_premium', $user->roles)) {
            // You can add a setting here to allow/disallow premium agents to auto-publish
            $allow_premium_auto_publish = get_option('malisafi_allow_premium_auto_publish', false);
            return $allow_premium_auto_publish;
        }
        
        return false;
    }
    
    /**
     * Store original property status before edit
     */
    public static function store_original_status() {
        global $post;
        
        if (!$post || $post->post_type !== 'malisafi_property') {
            return;
        }
        
        // Store original status in transient for comparison
        set_transient('malisafi_original_status_' . $post->ID . '_' . get_current_user_id(), $post->post_status, 3600);
    }
    
    /**
     * Handle property status changes
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update or new post
     */
    public static function handle_property_status($post_id, $post, $update) {
        // Skip if this is an autosave or revision
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Skip if this is a revision
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Get the user who is saving the property
        $current_user_id = get_current_user_id();
        
        // If admin or moderator, allow them to set any status
        if (self::can_publish_directly($current_user_id)) {
            return;
        }
        
        // If agent is creating or editing a property
        if (self::is_agent($current_user_id)) {
            // Get original status
            $original_status = get_transient('malisafi_original_status_' . $post_id . '_' . $current_user_id);
            
            // If this is an update to an existing property
            if ($update && $original_status) {
                // If property was published and agent edited it, set to pending
                if ($original_status === 'publish' && $post->post_status !== 'pending') {
                    // Remove the hook temporarily to avoid infinite loop
                    remove_action('save_post_malisafi_property', array(__CLASS__, 'handle_property_status'), 10);
                    
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_status' => 'pending'
                    ));
                    
                    // Add the hook back
                    add_action('save_post_malisafi_property', array(__CLASS__, 'handle_property_status'), 10, 3);
                    
                    // Store notice
                    update_post_meta($post_id, '_malisafi_edited_pending_approval', 1);
                    
                    // Clean up transient
                    delete_transient('malisafi_original_status_' . $post_id . '_' . $current_user_id);
                }
            } else {
                // New property - should be pending by default
                if ($post->post_status === 'publish' || $post->post_status === 'draft') {
                    remove_action('save_post_malisafi_property', array(__CLASS__, 'handle_property_status'), 10);
                    
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_status' => 'pending'
                    ));
                    
                    add_action('save_post_malisafi_property', array(__CLASS__, 'handle_property_status'), 10, 3);
                }
            }
        }
    }
    
    /**
     * Show pending approval notice
     */
    public static function show_pending_approval_notice() {
        $screen = get_current_screen();
        
        if (!$screen || !in_array($screen->id, array('malisafi_property', 'toplevel_page_malisafi-agent-dashboard'))) {
            return;
        }
        
        // Check if we should show the notice
        if (isset($_GET['property_id'])) {
            $property_id = intval($_GET['property_id']);
            $post = get_post($property_id);
            
            if ($post && $post->post_type === 'malisafi_property' && $post->post_status === 'pending') {
                $was_edited = get_post_meta($property_id, '_malisafi_edited_pending_approval', true);
                
                if ($was_edited) {
                    echo '<div class="notice notice-warning malisafi-notice is-dismissible">';
                    echo '<p><strong>' . __('Property Updated - Pending Approval', 'malisafi-mls') . '</strong></p>';
                    echo '<p>' . __('Your changes have been saved. This property is now pending approval by a moderator or administrator before it will be visible to the public.', 'malisafi-mls') . '</p>';
                    echo '</div>';
                    
                    // Clear the flag
                    delete_post_meta($property_id, '_malisafi_edited_pending_approval');
                }
            }
        }
    }
    
    /**
     * Get property status label
     */
    public static function get_status_label($status) {
        $labels = array(
            'publish' => __('Published', 'malisafi-mls'),
            'pending' => __('Pending Approval', 'malisafi-mls'),
            'draft' => __('Draft', 'malisafi-mls'),
            'trash' => __('Trashed', 'malisafi-mls'),
        );
        
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }
    
    /**
     * Add status badge to property list
     */
    public static function add_status_badge($status) {
        $badge_class = 'malisafi-status-badge';
        
        switch ($status) {
            case 'publish':
                $badge_class .= ' status-published';
                $icon = '✓';
                break;
            case 'pending':
                $badge_class .= ' status-pending';
                $icon = '⏳';
                break;
            case 'draft':
                $badge_class .= ' status-draft';
                $icon = '📝';
                break;
            default:
                $icon = '';
        }
        
        return sprintf(
            '<span class="%s">%s %s</span>',
            esc_attr($badge_class),
            $icon,
            esc_html(self::get_status_label($status))
        );
    }
}
