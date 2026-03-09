<?php
/**
 * Integration tests for Malisafi_Property_Submit
 *
 * @package MalisafiMLS\Tests
 */

namespace MalisafiMLS\Tests\Integration;

use WP_UnitTestCase;
use Malisafi_Property_Submit;

/**
 * Property submit manager test case
 */
class PropertySubmitManagerTest extends WP_UnitTestCase {

    /**
     * Set up test
     */
    public function set_up() {
        parent::set_up();
        require_once MALISAFI_MLS_PATH . 'admin/class-property-submit.php';
        
        // Register post type if not registered
        if (!post_type_exists('malisafi_property')) {
            register_post_type('malisafi_property', ['public' => true]);
        }
        
        // Register taxonomies if not registered
        $taxonomies = [
            'malisafi_property_type',
            'malisafi_property_status',
            'malisafi_property_location',
            'malisafi_property_features'
        ];
        foreach ($taxonomies as $tax) {
            if (!taxonomy_exists($tax)) {
                register_taxonomy($tax, 'malisafi_property', ['hierarchical' => true]);
            }
        }
    }

    /**
     * Test successful property submission
     */
    public function test_handle_property_submission_success() {
        $admin_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        // Mock $_POST data
        $_POST = [
            'malisafi_property_nonce' => wp_create_nonce('malisafi_submit_property'),
            '_wp_http_referer' => '/wp-admin/admin.php?page=malisafi-properties&action=add',
            'property_title' => 'Test Modern Villa',
            'property_description' => 'A beautiful villa with modern amenities.',
            'property_excerpt' => 'Short summary of the villa.',
            'property_price' => '15000000',
            'property_currency' => 'KES',
            'listing_type' => 'sale',
            'county' => 'Nairobi',
            'subcounty' => 'Westlands',
            'property_type' => $this->factory->term->create(['taxonomy' => 'malisafi_property_type']),
            'property_status' => $this->factory->term->create(['taxonomy' => 'malisafi_property_status']),
            'property_location' => $this->factory->term->create(['taxonomy' => 'malisafi_property_location']),
            'property_features' => [
                $this->factory->term->create(['taxonomy' => 'malisafi_property_features']),
                $this->factory->term->create(['taxonomy' => 'malisafi_property_features'])
            ]
        ];

        // We need to bypass the redirect because it calls exit
        // In a real WP unit test environment, we'd use a mock or check the database after the call
        // For this demonstration, we will manually call the underlying methods to verify logic
        
        $submit_manager = new Malisafi_Property_Submit();
        
        // Use reflection to access private methods for deep testing
        $reflection = new \ReflectionClass($submit_manager);
        $sanitize_method = $reflection->getMethod('sanitize_property_data');
        $sanitize_method->setAccessible(true);
        
        $sanitized_data = $sanitize_method->invoke(null, $_POST);
        
        // Verify sanitization
        $this->assertEquals('Test Modern Villa', $sanitized_data['title']);
        $this->assertEquals('Short summary of the villa.', $sanitized_data['excerpt']);
        $this->assertEquals(15000000, $sanitized_data['price']);
        $this->assertNotEmpty($sanitized_data['property_status']);
        $this->assertNotEmpty($sanitized_data['property_location']);
        $this->assertIsArray($sanitized_data['property_features']);

        // Test property creation
        $create_method = $reflection->getMethod('create_property');
        $create_method->setAccessible(true);
        $property_id = $create_method->invoke(null, $sanitized_data, 'publish');

        $this->assertIsInt($property_id);
        $post = get_post($property_id);
        $this->assertEquals('Short summary of the villa.', $post->post_excerpt);

        // Test taxonomy saving
        $tax_method = $reflection->getMethod('save_property_taxonomies');
        $tax_method->setAccessible(true);
        $tax_method->invoke(null, $property_id, $sanitized_data);

        $types = wp_get_object_terms($property_id, 'malisafi_property_type');
        $status = wp_get_object_terms($property_id, 'malisafi_property_status');
        $location = wp_get_object_terms($property_id, 'malisafi_property_location');
        $features = wp_get_object_terms($property_id, 'malisafi_property_features');

        $this->assertNotEmpty($types);
        $this->assertNotEmpty($status);
        $this->assertNotEmpty($location);
        $this->assertCount(2, $features);
    }
}
