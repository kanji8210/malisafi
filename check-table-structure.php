<?php
/**
 * TABLE STRUCTURE DIAGNOSTIC
 * Affiche la vraie structure de wp_mf_properties
 */

require_once(__DIR__ . '/../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Accès refusé.');
}

global $wpdb;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Table Structure Diagnostic</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; padding: 20px; background: #f0f0f1; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #1d2327; border-bottom: 3px solid #737d5d; padding-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #c3c4c7; }
        th { background: #737d5d; color: white; }
        tr:nth-child(even) { background: #f6f7f7; }
        .code { background: #2c3338; color: #a0ff00; padding: 20px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Structure de wp_mf_properties</h1>

        <?php
        $table = $wpdb->prefix . 'mf_properties';
        
        // Check if table exists
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        
        if (!$exists) {
            echo '<div style="background: #ffd7d7; padding: 20px; border-left: 4px solid #d63638;">';
            echo '<strong>❌ Erreur:</strong> Table ' . $table . ' n\'existe pas!';
            echo '</div>';
        } else {
            echo '<div style="background: #dfffdf; padding: 20px; border-left: 4px solid #00a32a; margin: 20px 0;">';
            echo '<strong>✅ Table trouvée:</strong> ' . $table;
            echo '</div>';
            
            // Get table structure
            $columns = $wpdb->get_results("DESCRIBE $table");
            
            echo '<h2>📋 Colonnes existantes</h2>';
            echo '<table>';
            echo '<thead><tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Default</th><th>Extra</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td><strong>' . $col->Field . '</strong></td>';
                echo '<td>' . $col->Type . '</td>';
                echo '<td>' . ($col->Null === 'YES' ? '✓' : '') . '</td>';
                echo '<td>' . ($col->Key ?: '-') . '</td>';
                echo '<td>' . ($col->Default !== null ? $col->Default : 'NULL') . '</td>';
                echo '<td>' . ($col->Extra ?: '-') . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            
            // Show sample data
            echo '<h2>📊 Données existantes (exemple)</h2>';
            $sample = $wpdb->get_results("SELECT * FROM $table LIMIT 5");
            
            if ($sample) {
                echo '<table>';
                echo '<thead><tr>';
                foreach ($columns as $col) {
                    echo '<th>' . $col->Field . '</th>';
                }
                echo '</tr></thead>';
                echo '<tbody>';
                
                foreach ($sample as $row) {
                    echo '<tr>';
                    foreach ($columns as $col) {
                        $value = $row->{$col->Field};
                        echo '<td>' . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : esc_html($value)) . '</td>';
                    }
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<div style="background: #fff8e5; padding: 15px; border-left: 4px solid #dba617;">';
                echo 'ℹ️ Table vide - pas de données exemple';
                echo '</div>';
            }
            
            // Show correct query
            echo '<h2>💡 Requête correcte pour insérer</h2>';
            echo '<div class="code">';
            echo "INSERT INTO $table (";
            $col_names = array();
            foreach ($columns as $col) {
                if ($col->Field !== 'id' && $col->Extra !== 'auto_increment') {
                    $col_names[] = '`' . $col->Field . '`';
                }
            }
            echo implode(', ', $col_names);
            echo ") VALUES (?, ?, ?, ...)";
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
