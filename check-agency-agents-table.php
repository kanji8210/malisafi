<?php
require_once '../../../wp-load.php';
global $wpdb;
$table = $wpdb->prefix . 'mf_agency_agents';
$result = $wpdb->get_results("DESCRIBE $table");
echo "Table structure for $table:\n";
foreach ($result as $column) {
    echo "  {$column->Field}: {$column->Type}\n";
}
?>