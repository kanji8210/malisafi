<?php
/**
 * Test case for Database class security
 *
 * @package MalisafiMLS\Tests
 */

namespace MalisafiMLS\Tests\Unit;

use MalisafiMLS\Database;
use WP_UnitTestCase;
use WP_Error;

/**
 * Database security test case
 */
class DatabaseTest extends WP_UnitTestCase {

    /**
     * Test drop_tables requires confirmation
     */
    public function test_drop_tables_requires_confirmation() {
        $result = Database::drop_tables(false);
        
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('drop_tables_forbidden', $result->get_error_code());
    }

    /**
     * Test drop_tables requires uninstall context
     */
    public function test_drop_tables_requires_uninstall_context() {
        // Even with confirmation, should fail without WP_UNINSTALL_PLUGIN
        $result = Database::drop_tables(true);
        
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * Test SQL injection prevention in prepared statements
     */
    public function test_sql_injection_prevention() {
        global $wpdb;
        
        // Attempt SQL injection
        $malicious_input = "' OR '1'='1"; 
        
        // This should be safely escaped
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_title = %s",
            $malicious_input
        ));
        
        // Should return 0, not all posts
        $this->assertEquals(0, $result);
    }

    /**
     * Test table creation
     */
    public function test_create_tables() {
        global $wpdb;
        
        // This would normally be called during activation
        Database::create_tables();
        
        // Check if main tables exist
        $tables = [
            'mf_properties',
            'mf_subscriptions',
            'mf_inquiries',
            'mf_favorites'
        ];
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            $this->assertEquals($table_name, $exists, "Table $table should exist");
        }
    }
}
