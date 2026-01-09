<?php
/**
 * User Factory Helper for Tests
 *
 * @package MalisafiMLS\Tests
 */

namespace MalisafiMLS\Tests\Helpers;

/**
 * User Factory class
 */
class UserFactory {
    
    /**
     * Create a test agent user
     *
     * @param array $args User arguments
     * @return int User ID
     */
    public static function create_agent($args = []) {
        $defaults = [
            'user_login' => 'agent_' . rand(1000, 9999),
            'user_email' => 'agent_' . rand(1000, 9999) . '@example.com',
            'user_pass' => 'SecurePass123',
            'first_name' => 'John',
            'last_name' => 'Agent',
            'role' => 'malisafi_agent',
            'meta' => [
                'phone' => '+254712345678',
                'agency_name' => 'Test Agency',
                'license_number' => 'LIC' . rand(10000, 99999),
                'years_experience' => '5',
                'agent_county' => 'Nairobi'
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Extract meta
        $meta = $args['meta'];
        unset($args['meta']);
        
        // Create user
        $user_id = wp_insert_user($args);
        
        if (is_wp_error($user_id)) {
            return 0;
        }
        
        // Add metadata
        foreach ($meta as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }
        
        return $user_id;
    }
    
    /**
     * Create a premium agent user
     *
     * @param array $args User arguments
     * @return int User ID
     */
    public static function create_premium_agent($args = []) {
        $args['role'] = 'malisafi_agent_premium';
        return self::create_agent($args);
    }
    
    /**
     * Create a property owner user
     *
     * @param array $args User arguments
     * @return int User ID
     */
    public static function create_owner($args = []) {
        $defaults = [
            'user_login' => 'owner_' . rand(1000, 9999),
            'user_email' => 'owner_' . rand(1000, 9999) . '@example.com',
            'user_pass' => 'SecurePass123',
            'first_name' => 'Jane',
            'last_name' => 'Owner',
            'role' => 'malisafi_owner',
            'meta' => [
                'phone' => '+254722345678'
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $meta = $args['meta'];
        unset($args['meta']);
        
        $user_id = wp_insert_user($args);
        
        if (is_wp_error($user_id)) {
            return 0;
        }
        
        foreach ($meta as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }
        
        return $user_id;
    }
    
    /**
     * Create a moderator user
     *
     * @param array $args User arguments
     * @return int User ID
     */
    public static function create_moderator($args = []) {
        $defaults = [
            'user_login' => 'moderator_' . rand(1000, 9999),
            'user_email' => 'moderator_' . rand(1000, 9999) . '@example.com',
            'user_pass' => 'SecurePass123',
            'role' => 'malisafi_moderator'
        ];
        
        $args = wp_parse_args($args, $defaults);
        return wp_insert_user($args);
    }
    
    /**
     * Create user with subscription
     *
     * @param string $plan_type Plan type
     * @param array $args User arguments
     * @return int User ID
     */
    public static function create_with_subscription($plan_type = 'agent_premium', $args = []) {
        global $wpdb;
        
        $user_id = self::create_agent($args);
        
        if ($user_id) {
            $table = $wpdb->prefix . 'mf_subscriptions';
            
            $wpdb->insert($table, [
                'user_id' => $user_id,
                'plan_type' => $plan_type,
                'status' => 'active',
                'stripe_subscription_id' => 'sub_test_' . rand(10000, 99999),
                'current_period_start' => current_time('mysql'),
                'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 month')),
                'created_at' => current_time('mysql')
            ]);
        }
        
        return $user_id;
    }
}
