<?php
/**
 * Test case for Cache_Manager class
 *
 * @package MalisafiMLS\Tests
 */

namespace MalisafiMLS\Tests\Unit;

use MalisafiMLS\Cache_Manager;
use WP_UnitTestCase;

/**
 * Cache_Manager test case
 */
class CacheManagerTest extends WP_UnitTestCase {

    /**
     * Set up before each test
     */
    public function setUp(): void {
        parent::setUp();
        // Clear all caches before each test
        Cache_Manager::clear_all();
    }

    /**
     * Tear down after each test
     */
    public function tearDown(): void {
        Cache_Manager::clear_all();
        parent::tearDown();
    }

    /**
     * Test remember method - cache miss
     */
    public function test_remember_cache_miss() {
        $callback_executed = false;
        
        $result = Cache_Manager::remember('test_key', function() use (&$callback_executed) {
            $callback_executed = true;
            return 'test_value';
        }, 60);
        
        $this->assertTrue($callback_executed);
        $this->assertEquals('test_value', $result);
    }

    /**
     * Test remember method - cache hit
     */
    public function test_remember_cache_hit() {
        $callback_count = 0;
        
        // First call - cache miss
        $result1 = Cache_Manager::remember('test_key', function() use (&$callback_count) {
            $callback_count++;
            return 'test_value';
        }, 60);
        
        // Second call - cache hit
        $result2 = Cache_Manager::remember('test_key', function() use (&$callback_count) {
            $callback_count++;
            return 'test_value';
        }, 60);
        
        $this->assertEquals(1, $callback_count, 'Callback should only execute once');
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test forget method
     */
    public function test_forget() {
        // Cache a value
        Cache_Manager::remember('test_key', function() {
            return 'test_value';
        }, 60);
        
        // Verify it's cached
        $this->assertNotFalse(get_transient('malisafi_mls_test_key'));
        
        // Forget it
        Cache_Manager::forget('test_key');
        
        // Verify it's gone
        $this->assertFalse(get_transient('malisafi_mls_test_key'));
    }

    /**
     * Test get_user_property_stats
     */
    public function test_get_user_property_stats() {
        $user_id = $this->factory->user->create();
        
        // Create some properties
        $property1 = $this->factory->post->create([
            'post_type' => 'malisafi_property',
            'post_author' => $user_id,
            'post_status' => 'publish'
        ]);
        
        $property2 = $this->factory->post->create([
            'post_type' => 'malisafi_property',
            'post_author' => $user_id,
            'post_status' => 'pending'
        ]);
        
        add_post_meta($property1, '_malisafi_views', 100);
        add_post_meta($property1, '_malisafi_inquiries', 5);
        
        // Get stats (should be cached)
        $stats1 = Cache_Manager::get_user_property_stats($user_id);
        $stats2 = Cache_Manager::get_user_property_stats($user_id);
        
        $this->assertEquals($stats1, $stats2);
        $this->assertArrayHasKey('total', $stats1);
        $this->assertArrayHasKey('active', $stats1);
        $this->assertArrayHasKey('pending', $stats1);
        $this->assertArrayHasKey('total_views', $stats1);
        $this->assertArrayHasKey('total_inquiries', $stats1);
    }

    /**
     * Test get_agent_ratings
     */
    public function test_get_agent_ratings() {
        global $wpdb;
        $agent_id = $this->factory->user->create();
        
        // Create ratings table if not exists
        $table = $wpdb->prefix . 'mf_agent_ratings';
        
        // Get ratings (should be cached)
        $ratings1 = Cache_Manager::get_agent_ratings($agent_id);
        $ratings2 = Cache_Manager::get_agent_ratings($agent_id);
        
        $this->assertEquals($ratings1, $ratings2);
        $this->assertArrayHasKey('total_reviews', $ratings1);
        $this->assertArrayHasKey('average_rating', $ratings1);
    }

    /**
     * Test get_featured_properties
     */
    public function test_get_featured_properties() {
        // Create featured properties
        $property1 = $this->factory->post->create([
            'post_type' => 'malisafi_property',
            'post_status' => 'publish'
        ]);
        
        $property2 = $this->factory->post->create([
            'post_type' => 'malisafi_property',
            'post_status' => 'publish'
        ]);
        
        add_post_meta($property1, '_malisafi_featured', '1');
        add_post_meta($property2, '_malisafi_featured', '1');
        
        // Get featured properties (should be cached)
        $featured1 = Cache_Manager::get_featured_properties(10);
        $featured2 = Cache_Manager::get_featured_properties(10);
        
        $this->assertEquals($featured1, $featured2);
        $this->assertIsArray($featured1);
    }

    /**
     * Test invalidate_user_cache
     */
    public function test_invalidate_user_cache() {
        $user_id = $this->factory->user->create();
        
        // Cache some data
        Cache_Manager::get_user_property_stats($user_id);
        $this->assertNotFalse(get_transient('malisafi_mls_user_stats_' . $user_id));
        
        // Invalidate
        Cache_Manager::invalidate_user_cache($user_id);
        
        // Verify it's gone
        $this->assertFalse(get_transient('malisafi_mls_user_stats_' . $user_id));
    }

    /**
     * Test invalidate_property_cache
     */
    public function test_invalidate_property_cache() {
        $user_id = $this->factory->user->create();
        $property_id = $this->factory->post->create([
            'post_type' => 'malisafi_property',
            'post_author' => $user_id,
            'post_status' => 'publish'
        ]);
        
        add_post_meta($property_id, '_malisafi_featured', '1');
        
        // Cache data
        Cache_Manager::get_user_property_stats($user_id);
        Cache_Manager::get_featured_properties(10);
        
        // Invalidate property cache
        Cache_Manager::invalidate_property_cache($property_id);
        
        // User stats should be cleared
        $this->assertFalse(get_transient('malisafi_mls_user_stats_' . $user_id));
        
        // Featured properties should be cleared
        $this->assertFalse(get_transient('malisafi_mls_featured_properties_10'));
    }

    /**
     * Test clear_all
     */
    public function test_clear_all() {
        // Cache multiple items
        Cache_Manager::remember('test_1', function() { return 'value1'; }, 60);
        Cache_Manager::remember('test_2', function() { return 'value2'; }, 60);
        Cache_Manager::remember('test_3', function() { return 'value3'; }, 60);
        
        // Verify they exist
        $this->assertNotFalse(get_transient('malisafi_mls_test_1'));
        $this->assertNotFalse(get_transient('malisafi_mls_test_2'));
        $this->assertNotFalse(get_transient('malisafi_mls_test_3'));
        
        // Clear all
        Cache_Manager::clear_all();
        
        // Verify all are gone
        $this->assertFalse(get_transient('malisafi_mls_test_1'));
        $this->assertFalse(get_transient('malisafi_mls_test_2'));
        $this->assertFalse(get_transient('malisafi_mls_test_3'));
    }

    /**
     * Test cache expiration
     */
    public function test_cache_expiration() {
        $callback_count = 0;
        
        // Cache with 1 second expiration
        Cache_Manager::remember('expire_test', function() use (&$callback_count) {
            $callback_count++;
            return 'value';
        }, 1);
        
        $this->assertEquals(1, $callback_count);
        
        // Wait 2 seconds
        sleep(2);
        
        // Should re-execute callback
        Cache_Manager::remember('expire_test', function() use (&$callback_count) {
            $callback_count++;
            return 'value';
        }, 1);
        
        $this->assertEquals(2, $callback_count);
    }

    /**
     * Test cache with different data types
     */
    public function test_cache_different_data_types() {
        // String
        $string = Cache_Manager::remember('string_test', function() {
            return 'test string';
        }, 60);
        $this->assertIsString($string);
        
        // Integer
        $integer = Cache_Manager::remember('int_test', function() {
            return 42;
        }, 60);
        $this->assertIsInt($integer);
        
        // Array
        $array = Cache_Manager::remember('array_test', function() {
            return ['key' => 'value'];
        }, 60);
        $this->assertIsArray($array);
        
        // Object
        $object = Cache_Manager::remember('object_test', function() {
            return (object)['property' => 'value'];
        }, 60);
        $this->assertIsObject($object);
    }
}
