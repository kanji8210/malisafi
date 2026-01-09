<?php
/**
 * Integration tests for user registration
 *
 * @package MalisafiMLS\Tests
 */

namespace MalisafiMLS\Tests\Integration;

use WP_UnitTestCase;

/**
 * User registration test case
 */
class UserRegistrationTest extends WP_UnitTestCase {

    /**
     * Test user registration with validation
     */
    public function test_user_registration_with_validation() {
        $registration_data = [
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => 'SecurePass123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '+254712345678',
            'account_type' => 'agent'
        ];
        
        // Create user
        $user_id = wp_create_user(
            $registration_data['username'],
            $registration_data['password'],
            $registration_data['email']
        );
        
        $this->assertIsInt($user_id);
        $this->assertGreaterThan(0, $user_id);
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $registration_data['first_name']);
        update_user_meta($user_id, 'last_name', $registration_data['last_name']);
        update_user_meta($user_id, 'phone', $registration_data['phone']);
        
        // Verify user data
        $user = get_user_by('id', $user_id);
        $this->assertEquals($registration_data['email'], $user->user_email);
        $this->assertEquals($registration_data['first_name'], get_user_meta($user_id, 'first_name', true));
    }

    /**
     * Test duplicate email prevention
     */
    public function test_duplicate_email_prevention() {
        $email = 'duplicate@example.com';
        
        // Create first user
        $user1 = wp_create_user('user1', 'password123', $email);
        $this->assertIsInt($user1);
        
        // Try to create second user with same email
        $user2 = wp_create_user('user2', 'password456', $email);
        
        // Should fail
        $this->assertInstanceOf(\WP_Error::class, $user2);
    }

    /**
     * Test duplicate username prevention
     */
    public function test_duplicate_username_prevention() {
        $username = 'duplicateuser';
        
        // Create first user
        $user1 = wp_create_user($username, 'password123', 'user1@example.com');
        $this->assertIsInt($user1);
        
        // Try to create second user with same username
        $user2 = wp_create_user($username, 'password456', 'user2@example.com');
        
        // Should fail
        $this->assertInstanceOf(\WP_Error::class, $user2);
    }

    /**
     * Test user role assignment
     */
    public function test_user_role_assignment() {
        $user_id = $this->factory->user->create();
        
        $user = get_user_by('id', $user_id);
        $user->set_role('malisafi_agent');
        
        $this->assertTrue($user->has_cap('malisafi_agent'));
    }

    /**
     * Test agent metadata
     */
    public function test_agent_metadata() {
        $user_id = $this->factory->user->create();
        
        // Add agent-specific metadata
        update_user_meta($user_id, 'agency_name', 'Test Agency');
        update_user_meta($user_id, 'license_number', 'LIC12345');
        update_user_meta($user_id, 'years_experience', '5');
        
        // Verify metadata
        $this->assertEquals('Test Agency', get_user_meta($user_id, 'agency_name', true));
        $this->assertEquals('LIC12345', get_user_meta($user_id, 'license_number', true));
        $this->assertEquals('5', get_user_meta($user_id, 'years_experience', true));
    }
}
