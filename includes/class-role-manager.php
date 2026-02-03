<?php

class Malisafi_Roles_Manager {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'add_custom_capabilities'));
    }
    
    public static function create_roles() {
        self::create_client_role();
        self::create_agent_roles();
        self::create_owner_roles();
        self::create_developer_role();
        self::create_moderator_role();
        self::create_admin_role();
    }
    
    private static function create_client_role() {
        add_role('malisafi_client', __('Client', 'malisafi'), array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false
        ));
    }
    
    private static function create_agent_roles() {
        // Basic Agent
        add_role('malisafi_agent_basic', __('Basic Agent', 'malisafi'), array(
            'read' => true,
            'upload_files' => true,
            'edit_posts' => true,
        ));
        
        // Premium Agent  
        add_role('malisafi_agent_premium', __('Premium Agent', 'malisafi'), array(
            'read' => true,
            'upload_files' => true,
            'edit_posts' => true,
        ));
    }
    
    private static function create_owner_roles() {
        add_role('malisafi_owner', __('Property Owner', 'malisafi'), array(
            'read' => true,
            'upload_files' => true,
            'edit_posts' => true,
        ));
    }
    
    private static function create_developer_role() {
        add_role('malisafi_developer', __('Developer', 'malisafi'), array(
            'read' => true,
            'upload_files' => true,
            'edit_posts' => true,
        ));
    }
    
    private static function create_moderator_role() {
        add_role('malisafi_moderator', __('Moderator', 'malisafi'), array(
            'read' => true,
            'moderate_comments' => true,
            'upload_files' => true,
            'edit_posts' => true,
            'edit_others_posts' => true,
            'edit_published_posts' => true,
        ));
    }
    
    private static function create_admin_role() {
        // Malisafi Admin inherits from WordPress administrator
        // We'll just add custom capabilities
    }
    
    public static function add_custom_capabilities() {
        $roles = array(
            'administrator',
            'malisafi_moderator',
            'malisafi_agent_basic', 
            'malisafi_agent_premium',
            'malisafi_owner',
            'malisafi_developer',
            'malisafi_client'
        );
        
        $caps = array(
            // Property management
            'edit_properties' => true,
            'edit_others_properties' => false,
            'edit_published_properties' => true,
            'publish_properties' => false, // Goes through moderation
            'delete_properties' => true,

            // Project management
            'edit_projects' => true,
            'edit_others_projects' => false,
            'edit_published_projects' => true,
            'publish_projects' => false, // Goes through moderation
            'delete_projects' => true,

            // Dashboard access
            'access_malisafi_dashboard' => true,
            'view_property_analytics' => true,
        );
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($caps as $cap => $grant) {
                    if ($role_name === 'malisafi_client' && strpos($cap, 'project') !== false) {
                        continue;
                    }
                    if ($role_name === 'malisafi_client' && strpos($cap, 'property') !== false) {
                        continue;
                    }
                    $role->add_cap($cap, $grant);
                }
                
                // Role-specific capabilities
                if ($role_name === 'malisafi_agent_premium') {
                    $role->add_cap('feature_properties', true);
                    $role->add_cap('boost_listings', true);
                    $role->add_cap('advanced_analytics', true);
                }

                if ($role_name === 'malisafi_moderator' || $role_name === 'administrator') {
                    $role->add_cap('edit_others_properties', true);
                    $role->add_cap('publish_properties', true);
                    $role->add_cap('moderate_properties', true);
                    $role->add_cap('manage_malisafi_settings', true);
                    $role->add_cap('manage_featured_properties', true); // Allow moderators to manage featured
                    $role->add_cap('edit_others_projects', true);
                    $role->add_cap('publish_projects', true);
                    $role->add_cap('moderate_projects', true);
                }

                // Allow clients to manage required pages only
                if ($role_name === 'malisafi_client') {
                    $role->add_cap('manage_malisafi_pages', true);
                }
            }
        }
    }
}