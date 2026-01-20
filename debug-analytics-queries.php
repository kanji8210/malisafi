<?php
/**
 * Debug Analytics Queries - Affiche EXACTEMENT ce que les queries retournent
 * 
 * Accès: /wp-content/plugins/malisafi/debug-analytics-queries.php
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Accès refusé. Vous devez être administrateur.');
}

global $wpdb;

echo '<html><head><meta charset="UTF-8">';
echo '<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f0f0f1; }
    .container { max-width: 1200px; margin: 0 auto; }
    h1 { color: #1d2327; border-bottom: 3px solid #737d5d; padding-bottom: 10px; }
    h2 { color: #737d5d; margin-top: 30px; background: white; padding: 15px; border-left: 4px solid #737d5d; }
    .query-box { background: #2c3338; color: #f0f0f1; padding: 15px; margin: 10px 0; border-radius: 4px; overflow-x: auto; }
    .result-box { background: white; padding: 20px; margin: 10px 0; border: 1px solid #c3c4c7; border-radius: 4px; }
    .success { color: #00a32a; font-weight: bold; font-size: 24px; }
    .error { color: #d63638; font-weight: bold; }
    .warning { color: #dba617; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; background: white; margin: 10px 0; }
    th, td { padding: 12px; text-align: left; border: 1px solid #c3c4c7; }
    th { background: #737d5d; color: white; font-weight: 600; }
    tr:nth-child(even) { background: #f6f7f7; }
    .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; }
    .badge-success { background: #00a32a; color: white; }
    .badge-warning { background: #dba617; color: white; }
    .badge-error { background: #d63638; color: white; }
</style></head><body>';

echo '<div class="container">';
echo '<h1>🔍 Analytics Queries Debug - Diagnostic Complet</h1>';
echo '<p style="color: #666; font-size: 14px;">Date: ' . date('Y-m-d H:i:s') . ' | Prefix: <code>' . $wpdb->prefix . '</code></p>';

$days = 30;

// ===========================================
// 1. ACTIVE USERS
// ===========================================
echo '<h2>1️⃣ ACTIVE USERS (Derniers ' . $days . ' jours)</h2>';

// Query 1a: Depuis table analytics
$query_1a = $wpdb->prepare("
    SELECT COUNT(DISTINCT user_id)
    FROM {$wpdb->prefix}mf_user_activity
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
", $days);

echo '<div class="query-box"><strong>Query Analytics Table:</strong><br>' . esc_html($query_1a) . '</div>';
$result_1a = $wpdb->get_var($query_1a);
echo '<div class="result-box">';
echo '<span class="' . ($result_1a > 0 ? 'success' : 'warning') . '">Résultat: ' . ($result_1a ?? 'NULL') . ' utilisateurs</span>';
if ($wpdb->last_error) {
    echo '<br><span class="error">❌ Erreur SQL: ' . $wpdb->last_error . '</span>';
}
echo '</div>';

// Query 1b: Fallback - Total Malisafi users
echo '<div class="query-box"><strong>Fallback - Total Malisafi Users (WP_User_Query):</strong></div>';
$malisafi_roles = array('malisafi_client', 'malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_moderator');
$user_query = new WP_User_Query(array(
    'role__in' => $malisafi_roles,
    'fields' => 'ID'
));
$result_1b = $user_query->get_total();
echo '<div class="result-box">';
echo '<span class="success">Résultat: ' . $result_1b . ' utilisateurs Malisafi</span>';
echo '<br><small style="color: #666;">Rôles: ' . implode(', ', $malisafi_roles) . '</small>';
echo '</div>';

echo '<div class="result-box" style="background: #e7f3ff; border-left: 4px solid #0073aa;">';
echo '<strong>✅ VALEUR FINALE (active_users):</strong> ';
$final_active_users = ($result_1a > 0) ? $result_1a : $result_1b;
echo '<span class="success">' . $final_active_users . '</span>';
echo '</div>';

// ===========================================
// 2. PROPERTIES ADDED
// ===========================================
echo '<h2>2️⃣ PROPERTIES ADDED (Derniers ' . $days . ' jours)</h2>';

// Query 2a: Depuis table analytics
$query_2a = $wpdb->prepare("
    SELECT COUNT(*)
    FROM {$wpdb->prefix}mf_properties
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
", $days);

echo '<div class="query-box"><strong>Query Analytics Table:</strong><br>' . esc_html($query_2a) . '</div>';
$result_2a = $wpdb->get_var($query_2a);
echo '<div class="result-box">';
echo '<span class="' . ($result_2a > 0 ? 'success' : 'warning') . '">Résultat: ' . ($result_2a ?? 'NULL') . ' propriétés</span>';
if ($wpdb->last_error) {
    echo '<br><span class="error">❌ Erreur SQL: ' . $wpdb->last_error . '</span>';
}
echo '</div>';

// Query 2b: Fallback - WordPress posts
echo '<div class="query-box"><strong>Fallback - WordPress Posts (wp_count_posts):</strong></div>';
$wp_count = wp_count_posts('malisafi_property');
$result_2b = ($wp_count->publish ?? 0) + ($wp_count->pending ?? 0);
echo '<div class="result-box">';
echo '<span class="success">Résultat: ' . $result_2b . ' propriétés WordPress</span>';
echo '<br><small style="color: #666;">Publish: ' . ($wp_count->publish ?? 0) . ', Pending: ' . ($wp_count->pending ?? 0) . '</small>';
echo '</div>';

echo '<div class="result-box" style="background: #e7f3ff; border-left: 4px solid #0073aa;">';
echo '<strong>✅ VALEUR FINALE (properties_added):</strong> ';
$final_properties = ($result_2a > 0) ? $result_2a : $result_2b;
echo '<span class="success">' . $final_properties . '</span>';
echo '</div>';

// ===========================================
// 3. TOTAL VIEWS
// ===========================================
echo '<h2>3️⃣ TOTAL PROPERTY VIEWS (Derniers ' . $days . ' jours)</h2>';

$query_3 = $wpdb->prepare("
    SELECT COUNT(*)
    FROM {$wpdb->prefix}mf_property_views
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
", $days);

echo '<div class="query-box"><strong>Query:</strong><br>' . esc_html($query_3) . '</div>';
$result_3 = $wpdb->get_var($query_3);
echo '<div class="result-box">';
echo '<span class="' . ($result_3 > 0 ? 'success' : 'warning') . '">Résultat: ' . ($result_3 ?? '0') . ' vues</span>';
if ($wpdb->last_error) {
    echo '<br><span class="error">❌ Erreur SQL: ' . $wpdb->last_error . '</span>';
}
echo '<br><small style="color: #666;">Note: Tracking en temps réel, 0 est normal si aucune vue trackée</small>';
echo '</div>';

// ===========================================
// 4. TOTAL INQUIRIES
// ===========================================
echo '<h2>4️⃣ TOTAL INQUIRIES (Derniers ' . $days . ' jours)</h2>';

$query_4 = $wpdb->prepare("
    SELECT COUNT(*)
    FROM {$wpdb->prefix}mf_property_interactions
    WHERE interaction_type = 'inquiry'
    AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
", $days);

echo '<div class="query-box"><strong>Query:</strong><br>' . esc_html($query_4) . '</div>';
$result_4 = $wpdb->get_var($query_4);
echo '<div class="result-box">';
echo '<span class="' . ($result_4 > 0 ? 'success' : 'warning') . '">Résultat: ' . ($result_4 ?? '0') . ' demandes</span>';
if ($wpdb->last_error) {
    echo '<br><span class="error">❌ Erreur SQL: ' . $wpdb->last_error . '</span>';
}
echo '<br><small style="color: #666;">Note: 0 est normal si aucun formulaire de contact soumis</small>';
echo '</div>';

// ===========================================
// 5. AVG PROPERTIES PER USER
// ===========================================
echo '<h2>5️⃣ AVERAGE PROPERTIES PER USER (Derniers ' . $days . ' jours)</h2>';

$query_5 = $wpdb->prepare("
    SELECT ROUND(AVG(property_count), 2)
    FROM (
        SELECT COUNT(*) as property_count
        FROM {$wpdb->prefix}mf_properties
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        GROUP BY author_id
    ) as subquery
", $days);

echo '<div class="query-box"><strong>Query:</strong><br>' . esc_html($query_5) . '</div>';
$result_5 = $wpdb->get_var($query_5);
echo '<div class="result-box">';
echo '<span class="' . ($result_5 > 0 ? 'success' : 'warning') . '">Résultat: ' . ($result_5 ?? '0') . ' propriétés/utilisateur</span>';
if ($wpdb->last_error) {
    echo '<br><span class="error">❌ Erreur SQL: ' . $wpdb->last_error . '</span>';
}
echo '</div>';

// ===========================================
// 6. FUNNEL COMPLETION RATE
// ===========================================
echo '<h2>6️⃣ FUNNEL COMPLETION RATE (Derniers ' . $days . ' jours)</h2>';

$query_6 = $wpdb->prepare("
    SELECT 
        COUNT(DISTINCT session_id) as total_sessions,
        COUNT(DISTINCT CASE WHEN completed = 1 THEN session_id END) as completed_sessions
    FROM {$wpdb->prefix}mf_submission_funnel
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
", $days);

echo '<div class="query-box"><strong>Query:</strong><br>' . esc_html($query_6) . '</div>';
$result_6 = $wpdb->get_row($query_6);
echo '<div class="result-box">';
if ($result_6) {
    $completion_rate = $result_6->total_sessions > 0 
        ? round(($result_6->completed_sessions / $result_6->total_sessions) * 100, 2)
        : 0;
    echo '<span class="success">Total sessions: ' . $result_6->total_sessions . '</span><br>';
    echo '<span class="success">Sessions complétées: ' . $result_6->completed_sessions . '</span><br>';
    echo '<strong>Taux de complétion: ' . $completion_rate . '%</strong>';
} else {
    echo '<span class="warning">Aucune donnée</span>';
}
if ($wpdb->last_error) {
    echo '<br><span class="error">❌ Erreur SQL: ' . $wpdb->last_error . '</span>';
}
echo '</div>';

// ===========================================
// SUMMARY
// ===========================================
echo '<h2>📊 RÉSUMÉ DES VALEURS FINALES</h2>';
echo '<table>';
echo '<thead><tr><th>Métrique</th><th>Valeur</th><th>Source</th></tr></thead>';
echo '<tbody>';

echo '<tr>';
echo '<td><strong>Active Users</strong></td>';
echo '<td class="success">' . number_format($final_active_users) . '</td>';
echo '<td>' . ($result_1a > 0 ? 'Table analytics' : '<span class="badge badge-warning">Fallback WordPress</span>') . '</td>';
echo '</tr>';

echo '<tr>';
echo '<td><strong>Properties Added</strong></td>';
echo '<td class="success">' . number_format($final_properties) . '</td>';
echo '<td>' . ($result_2a > 0 ? 'Table analytics' : '<span class="badge badge-warning">Fallback WordPress</span>') . '</td>';
echo '</tr>';

echo '<tr>';
echo '<td><strong>Total Views</strong></td>';
echo '<td>' . number_format($result_3 ?? 0) . '</td>';
echo '<td>Table analytics</td>';
echo '</tr>';

echo '<tr>';
echo '<td><strong>Total Inquiries</strong></td>';
echo '<td>' . number_format($result_4 ?? 0) . '</td>';
echo '<td>Table analytics</td>';
echo '</tr>';

echo '<tr>';
echo '<td><strong>Avg Properties/User</strong></td>';
echo '<td>' . number_format($result_5 ?? 0, 1) . '</td>';
echo '<td>Table analytics</td>';
echo '</tr>';

echo '<tr>';
echo '<td><strong>Funnel Completion</strong></td>';
echo '<td>' . ($completion_rate ?? 0) . '%</td>';
echo '<td>Table analytics</td>';
echo '</tr>';

echo '</tbody>';
echo '</table>';

// ===========================================
// TABLE CHECKS
// ===========================================
echo '<h2>🗄️ VÉRIFICATION DES TABLES</h2>';
$tables_to_check = array(
    'mf_user_activity',
    'mf_properties',
    'mf_property_views',
    'mf_property_interactions',
    'mf_submission_funnel'
);

echo '<table>';
echo '<thead><tr><th>Table</th><th>Existe</th><th>Nombre de lignes</th><th>Dernière entrée</th></tr></thead>';
echo '<tbody>';

foreach ($tables_to_check as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");
    
    echo '<tr>';
    echo '<td><code>' . $full_table . '</code></td>';
    
    if ($exists) {
        echo '<td><span class="badge badge-success">✓ Oui</span></td>';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
        echo '<td>' . number_format($count) . '</td>';
        
        $last_entry = $wpdb->get_var("SELECT created_at FROM $full_table ORDER BY created_at DESC LIMIT 1");
        echo '<td>' . ($last_entry ?? '<em>Aucune</em>') . '</td>';
    } else {
        echo '<td><span class="badge badge-error">✗ Non</span></td>';
        echo '<td colspan="2"><em>Table n\'existe pas</em></td>';
    }
    
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

echo '<div style="margin-top: 40px; padding: 20px; background: white; border-left: 4px solid #737d5d;">';
echo '<h3>🎯 Ce que vous devriez voir dans Analytics Overview</h3>';
echo '<ul style="line-height: 2;">';
echo '<li><strong>Active Users:</strong> ' . $final_active_users . '</li>';
echo '<li><strong>Properties Added:</strong> ' . $final_properties . '</li>';
echo '<li><strong>Property Views:</strong> ' . ($result_3 ?? 0) . ' (normal si 0, tracking en temps réel)</li>';
echo '<li><strong>Inquiries Received:</strong> ' . ($result_4 ?? 0) . ' (normal si 0, aucun formulaire soumis)</li>';
echo '<li><strong>Avg Properties/User:</strong> ' . number_format($result_5 ?? 0, 1) . '</li>';
echo '<li><strong>Funnel Completion:</strong> ' . ($completion_rate ?? 0) . '%</li>';
echo '</ul>';
echo '</div>';

echo '</div></body></html>';
