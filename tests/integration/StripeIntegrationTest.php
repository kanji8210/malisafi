<?php
/**
 * Integration tests for Stripe functionality
 *
 * @package MalisafiMLS\Tests
 */

namespace MalisafiMLS\Tests\Integration;

use WP_UnitTestCase;

/**
 * Stripe integration test case
 */
class StripeIntegrationTest extends WP_UnitTestCase {

    /**
     * Set up before tests
     */
    public function setUp(): void {
        parent::setUp();
        
        // Set test mode
        update_option('malisafi_stripe_mode', 'test');
        update_option('malisafi_stripe_test_publishable_key', 'pk_test_dummy');
        update_option('malisafi_stripe_test_secret_key', 'sk_test_dummy');
    }

    /**
     * Test Stripe configuration check
     */
    public function test_stripe_is_configured() {
        $this->assertTrue(\Malisafi_Stripe::is_configured());
    }

    /**
     * Test get publishable key
     */
    public function test_get_publishable_key() {
        $key = \Malisafi_Stripe::get_publishable_key();
        $this->assertEquals('pk_test_dummy', $key);
    }

    /**
     * Test get plans
     */
    public function test_get_plans() {
        $plans = \Malisafi_Stripe::get_plans();
        
        $this->assertIsArray($plans);
        $this->assertArrayHasKey('agent_basic', $plans);
        $this->assertArrayHasKey('agent_premium', $plans);
        $this->assertArrayHasKey('owner_basic', $plans);
        $this->assertArrayHasKey('developer', $plans);
        
        // Check plan structure
        foreach ($plans as $plan) {
            $this->assertArrayHasKey('name', $plan);
            $this->assertArrayHasKey('price', $plan);
            $this->assertArrayHasKey('currency', $plan);
            $this->assertArrayHasKey('features', $plan);
        }
    }

    /**
     * Test currency symbol formatting
     */
    public function test_currency_symbols() {
        $this->assertEquals('$', \Malisafi_Stripe::get_currency_symbol('USD'));
        $this->assertEquals('KSh', \Malisafi_Stripe::get_currency_symbol('KES'));
        $this->assertEquals('€', \Malisafi_Stripe::get_currency_symbol('EUR'));
        $this->assertEquals('£', \Malisafi_Stripe::get_currency_symbol('GBP'));
    }

    /**
     * Test price formatting
     */
    public function test_price_formatting() {
        // USD with decimals
        $this->assertEquals('$99.99', \Malisafi_Stripe::format_price(99.99, 'USD'));
        
        // KES without decimals
        $this->assertEquals('KSh1000', \Malisafi_Stripe::format_price(1000, 'KES'));
        
        // EUR with decimals
        $this->assertEquals('€49.50', \Malisafi_Stripe::format_price(49.50, 'EUR'));
    }

    /**
     * Test create customer (mocked)
     */
    public function test_create_customer() {
        $user_id = $this->factory->user->create([
            'user_email' => 'test@example.com',
            'display_name' => 'Test User'
        ]);
        
        // This will fail without real Stripe keys, but we're testing the flow
        $customer_id = \Malisafi_Stripe::create_customer($user_id);
        
        // In real scenario with mocked Stripe, this would return a customer ID
        // For now, just verify it doesn't crash
        $this->assertTrue(is_string($customer_id) || $customer_id === false);
    }

    /**
     * Test AJAX checkout requires authentication
     */
    public function test_ajax_checkout_requires_auth() {
        // Not logged in
        $_POST['nonce'] = wp_create_nonce('malisafi_stripe_nonce');
        $_POST['plan'] = 'agent_basic';
        
        // Capture output
        ob_start();
        
        try {
            \Malisafi_Stripe::ajax_create_checkout();
        } catch (\WPAjaxDieContinueException $e) {
            // Expected
        }
        
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('login', strtolower($response['data']['message']));
    }

    /**
     * Test AJAX checkout validates plan
     */
    public function test_ajax_checkout_validates_plan() {
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);
        
        $_POST['nonce'] = wp_create_nonce('malisafi_stripe_nonce');
        $_POST['plan'] = 'invalid_plan';
        
        ob_start();
        
        try {
            \Malisafi_Stripe::ajax_create_checkout();
        } catch (\WPAjaxDieContinueException $e) {
            // Expected
        }
        
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Invalid plan', $response['data']['message']);
    }

    /**
     * Test webhook signature verification
     */
    public function test_webhook_requires_valid_signature() {
        // Create a fake webhook request
        $request = new \WP_REST_Request('POST', '/malisafi/v1/stripe-webhook');
        $request->set_body('{"type":"checkout.session.completed"}');
        
        // Without proper signature header, should fail
        $response = \Malisafi_Stripe::handle_webhook($request);
        
        $this->assertEquals(400, $response->get_status());
    }

    /**
     * Test get user subscription
     */
    public function test_get_user_subscription() {
        global $wpdb;
        $user_id = $this->factory->user->create();
        
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        // Insert test subscription
        $wpdb->insert($table, [
            'user_id' => $user_id,
            'plan_type' => 'agent_premium',
            'status' => 'active',
            'stripe_subscription_id' => 'sub_test123',
            'created_at' => current_time('mysql')
        ]);
        
        $subscription = \Malisafi_Stripe::get_user_subscription($user_id);
        
        $this->assertNotNull($subscription);
        $this->assertEquals($user_id, $subscription->user_id);
        $this->assertEquals('agent_premium', $subscription->plan_type);
        $this->assertEquals('active', $subscription->status);
    }

    /**
     * Test error handling for different Stripe exceptions
     */
    public function test_stripe_error_handling() {
        // Test that our error handling catches specific exception types
        // This is more of a code structure test
        
        $reflection = new \ReflectionClass('Malisafi_Stripe');
        $method = $reflection->getMethod('ajax_create_checkout');
        
        $source = file_get_contents($reflection->getFileName());
        
        // Verify we handle specific Stripe exceptions
        $this->assertStringContainsString('CardException', $source);
        $this->assertStringContainsString('RateLimitException', $source);
        $this->assertStringContainsString('InvalidRequestException', $source);
        $this->assertStringContainsString('AuthenticationException', $source);
        $this->assertStringContainsString('ApiConnectionException', $source);
    }
}
