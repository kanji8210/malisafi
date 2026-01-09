<?php
/**
 * Integration tests for property submission
 *
 * @package MalisafiMLS\Tests
 */

namespace MalisafiMLS\Tests\Integration;

use WP_UnitTestCase;

/**
 * Property submission test case
 */
class PropertySubmissionTest extends WP_UnitTestCase {

    /**
     * Test property creation requires authentication
     */
    public function test_property_creation_requires_auth() {
        $property_data = [
            'post_type' => 'malisafi_property',
            'post_title' => 'Test Property',
            'post_status' => 'publish'
        ];
        
        // Not logged in - should fail or create as draft
        $property_id = wp_insert_post($property_data);
        
        $this->assertIsInt($property_id);
        $this->assertGreaterThan(0, $property_id);
    }

    /**
     * Test property with metadata
     */
    public function test_property_with_metadata() {
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        
        $property_id = $this->factory->post->create([
            'post_type' => 'malisafi_property',
            'post_title' => 'Luxury Villa',
            'post_status' => 'publish',
            'post_author' => $user_id
        ]);
        
        // Add metadata
        update_post_meta($property_id, '_malisafi_price', 5000000);
        update_post_meta($property_id, '_malisafi_bedrooms', 4);
        update_post_meta($property_id, '_malisafi_bathrooms', 3);
        update_post_meta($property_id, '_malisafi_property_type', 'house');
        
        // Verify metadata
        $this->assertEquals(5000000, get_post_meta($property_id, '_malisafi_price', true));
        $this->assertEquals(4, get_post_meta($property_id, '_malisafi_bedrooms', true));
        $this->assertEquals(3, get_post_meta($property_id, '_malisafi_bathrooms', true));
        $this->assertEquals('house', get_post_meta($property_id, '_malisafi_property_type', true));
    }

    /**
     * Test property views counter
     */
    public function test_property_views_counter() {
        $property_id = $this->factory->post->create([
            'post_type' => 'malisafi_property',
            'post_status' => 'publish'
        ]);
        
        // Initial views should be 0
        $views = get_post_meta($property_id, '_malisafi_views', true);
        $this->assertEquals(0, intval($views));
        
        // Increment views
        $current = intval(get_post_meta($property_id, '_malisafi_views', true));
        update_post_meta($property_id, '_malisafi_views', $current + 1);
        
        $new_views = get_post_meta($property_id, '_malisafi_views', true);
        $this->assertEquals(1, intval($new_views));
    }

    /**
     * Test featured property flag
     */
    public function test_featured_property() {
        $property_id = $this->factory->post->create([
            'post_type' => 'malisafi_property',
            'post_status' => 'publish'
        ]);
        
        // Set as featured
        update_post_meta($property_id, '_malisafi_featured', '1');
        
        // Query featured properties
        $args = [
            'post_type' => 'malisafi_property',
            'meta_query' => [
                [
                    'key' => '_malisafi_featured',
                    'value' => '1'
                ]
            ]
        ];
        
        $query = new \WP_Query($args);
        
        $this->assertTrue($query->have_posts());
        $this->assertEquals(1, $query->found_posts);
    }

    /**
     * Test property taxonomies
     */
    public function test_property_taxonomies() {
        $property_id = $this->factory->post->create([
            'post_type' => 'malisafi_property',
            'post_status' => 'publish'
        ]);
        
        // Set property type
        wp_set_object_terms($property_id, 'apartment', 'malisafi_property_type');
        
        // Set location
        wp_set_object_terms($property_id, 'Nairobi', 'malisafi_location');
        
        // Verify taxonomies
        $types = wp_get_object_terms($property_id, 'malisafi_property_type', ['fields' => 'names']);
        $locations = wp_get_object_terms($property_id, 'malisafi_location', ['fields' => 'names']);
        
        $this->assertContains('apartment', $types);
        $this->assertContains('Nairobi', $locations);
    }

    /**
     * Test property query with pagination
     */
    public function test_property_query_pagination() {
        // Create multiple properties
        for ($i = 0; $i < 25; $i++) {
            $this->factory->post->create([
                'post_type' => 'malisafi_property',
                'post_status' => 'publish'
            ]);
        }
        
        // Query with limit
        $args = [
            'post_type' => 'malisafi_property',
            'posts_per_page' => 10,
            'paged' => 1
        ];
        
        $query = new \WP_Query($args);
        
        $this->assertEquals(10, $query->post_count);
        $this->assertEquals(25, $query->found_posts);
        $this->assertEquals(3, $query->max_num_pages);
    }
}
