<?php
/**
 * Base Test Case with common helpers
 *
 * @package MalisafiMLS\Tests
 */

namespace MalisafiMLS\Tests\Helpers;

use WP_UnitTestCase;

/**
 * Base TestCase class
 */
class TestCase extends WP_UnitTestCase {
    
    /**
     * Set up before each test
     */
    public function setUp(): void {
        parent::setUp();
        
        // Clear caches
        if (class_exists('MalisafiMLS\Cache_Manager')) {
            \MalisafiMLS\Cache_Manager::clear_all();
        }
    }
    
    /**
     * Tear down after each test
     */
    public function tearDown(): void {
        // Clear caches
        if (class_exists('MalisafiMLS\Cache_Manager')) {
            \MalisafiMLS\Cache_Manager::clear_all();
        }
        
        parent::tearDown();
    }
    
    /**
     * Create and authenticate a user
     *
     * @param string $role User role
     * @return int User ID
     */
    protected function actingAs($role = 'administrator') {
        $user_id = $this->factory->user->create(['role' => $role]);
        wp_set_current_user($user_id);
        return $user_id;
    }
    
    /**
     * Create property using factory
     *
     * @param array $args Property arguments
     * @return int Property ID
     */
    protected function createProperty($args = []) {
        return PropertyFactory::create($args);
    }
    
    /**
     * Create agent user
     *
     * @param array $args User arguments
     * @return int User ID
     */
    protected function createAgent($args = []) {
        return UserFactory::create_agent($args);
    }
    
    /**
     * Assert that response is successful JSON
     *
     * @param string $output JSON output
     */
    protected function assertJsonSuccess($output) {
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false);
    }
    
    /**
     * Assert that response is error JSON
     *
     * @param string $output JSON output
     */
    protected function assertJsonError($output) {
        $response = json_decode($output, true);
        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
    }
    
    /**
     * Mock AJAX request
     *
     * @param string $action AJAX action
     * @param array $data POST data
     * @return string Response
     */
    protected function mockAjaxRequest($action, $data = []) {
        $_POST = array_merge($_POST, $data);
        $_REQUEST['action'] = $action;
        
        ob_start();
        
        try {
            do_action('wp_ajax_' . $action);
        } catch (\WPAjaxDieContinueException $e) {
            // Expected
        }
        
        return ob_get_clean();
    }
    
    /**
     * Mock AJAX request for non-logged-in user
     *
     * @param string $action AJAX action
     * @param array $data POST data
     * @return string Response
     */
    protected function mockNoPrivAjaxRequest($action, $data = []) {
        wp_set_current_user(0); // Logout
        
        $_POST = array_merge($_POST, $data);
        $_REQUEST['action'] = $action;
        
        ob_start();
        
        try {
            do_action('wp_ajax_nopriv_' . $action);
        } catch (\WPAjaxDieContinueException $e) {
            // Expected
        }
        
        return ob_get_clean();
    }
    
    /**
     * Assert property has metadata
     *
     * @param int $property_id Property ID
     * @param string $key Meta key
     * @param mixed $expected Expected value
     */
    protected function assertPropertyMeta($property_id, $key, $expected) {
        $actual = get_post_meta($property_id, $key, true);
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Assert user has metadata
     *
     * @param int $user_id User ID
     * @param string $key Meta key
     * @param mixed $expected Expected value
     */
    protected function assertUserMeta($user_id, $key, $expected) {
        $actual = get_user_meta($user_id, $key, true);
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Assert database has record
     *
     * @param string $table Table name
     * @param array $data Data to check
     */
    protected function assertDatabaseHas($table, $data) {
        global $wpdb;
        
        $where = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $where[] = "$key = %s";
            $values[] = $value;
        }
        
        $where_clause = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE {$where_clause}";
        
        $count = $wpdb->get_var($wpdb->prepare($query, $values));
        
        $this->assertGreaterThan(0, $count, "Database does not have matching record in {$table}");
    }
    
    /**
     * Assert database missing record
     *
     * @param string $table Table name
     * @param array $data Data to check
     */
    protected function assertDatabaseMissing($table, $data) {
        global $wpdb;
        
        $where = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $where[] = "$key = %s";
            $values[] = $value;
        }
        
        $where_clause = implode(' AND ', $where);
        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE {$where_clause}";
        
        $count = $wpdb->get_var($wpdb->prepare($query, $values));
        
        $this->assertEquals(0, $count, "Database has unexpected record in {$table}");
    }
}
