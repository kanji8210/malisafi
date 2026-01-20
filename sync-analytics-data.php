<?php
/**
 * SYNC ANALYTICS DATA - Synchronise les données WordPress existantes vers les tables analytics
 * 
 * Ce script remplit les tables analytics avec les données historiques
 * Pour que les statistiques reflètent la réalité
 * 
 * Accès: /wp-content/plugins/malisafi/sync-analytics-data.php
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Accès refusé. Vous devez être administrateur.');
}

global $wpdb;

// Handle form submission
$sync_done = false;
$sync_log = '';
$properties_synced = 0;
$users_synced = 0;

if (isset($_POST['do_sync']) && wp_verify_nonce($_POST['_wpnonce'], 'malisafi_sync_nonce')) {
    // ==================================================
    // SYNC PROPERTIES
    // ==================================================
    $sync_log .= "🔄 SYNCHRONISATION ANALYTICS\n";
    $sync_log .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    $sync_log .= "=" . str_repeat("=", 50) . "\n";
    $sync_log .= "1️⃣ SYNCHRONISATION DES PROPRIÉTÉS\n";
    $sync_log .= "=" . str_repeat("=", 50) . "\n";
    
    $properties = get_posts(array(
        'post_type' => 'malisafi_property',
        'post_status' => array('publish', 'pending'),
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $properties_skipped = 0;
    $sync_log .= "Propriétés WordPress trouvées: " . count($properties) . "\n\n";
    
    foreach ($properties as $property_id) {
        // Check if already exists
        $exists = $wpdb->get_var($wpdb->prepare("
            SELECT property_id FROM {$wpdb->prefix}mf_properties 
            WHERE property_id = %d
            LIMIT 1
        ", $property_id));
        
        if ($exists) {
            $properties_skipped++;
            continue;
        }
        
        // Get property data
        $post = get_post($property_id);
        $price = get_post_meta($property_id, '_malisafi_price', true);
        $county = get_post_meta($property_id, '_malisafi_county', true);
        $neighbourhood = get_post_meta($property_id, '_malisafi_neighbourhood', true);
        
        // Insert into analytics table - WITHOUT title and id columns
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'mf_properties',
            array(
                'property_id' => $property_id,
                'author_id' => $post->post_author,
                'price' => floatval($price ?: 0),
                'location' => $county . ($neighbourhood ? ', ' . $neighbourhood : ''),
                'status' => $post->post_status,
                'created_at' => $post->post_date,
                'updated_at' => $post->post_modified
            ),
            array('%d', '%d', '%f', '%s', '%s', '%s', '%s')
        );
        
        if ($inserted) {
            $properties_synced++;
        }
    }
    
    $sync_log .= "✅ Propriétés synchronisées: " . $properties_synced . "\n";
    $sync_log .= "⏭️ Déjà existantes: " . $properties_skipped . "\n\n";
    
    // ==================================================
    // SYNC USER ACTIVITY
    // ==================================================
    $sync_log .= "=" . str_repeat("=", 50) . "\n";
    $sync_log .= "2️⃣ SYNCHRONISATION ACTIVITÉ UTILISATEURS\n";
    $sync_log .= "=" . str_repeat("=", 50) . "\n";
    
    $malisafi_roles = array('malisafi_client', 'malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_moderator');
    $user_query = new WP_User_Query(array(
        'role__in' => $malisafi_roles,
        'fields' => 'all'
    ));
    $users = $user_query->get_results();
    
    $users_skipped = 0;
    $sync_log .= "Utilisateurs Malisafi trouvés: " . count($users) . "\n\n";
    
    foreach ($users as $user) {
        // Check if user has any activity logged
        $has_activity = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}mf_user_activity 
            WHERE user_id = %d
        ", $user->ID));
        
        if ($has_activity > 0) {
            $users_skipped++;
            continue;
        }
        
        // Create a registration activity entry
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'mf_user_activity',
            array(
                'user_id' => $user->ID,
                'activity_type' => 'registration',
                'activity_data' => json_encode(array(
                    'role' => $user->roles[0] ?? 'unknown',
                    'synced' => true
                )),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Analytics Sync',
                'created_at' => $user->user_registered
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($inserted) {
            $users_synced++;
        }
    }
    
    $sync_log .= "✅ Utilisateurs avec activité créée: " . $users_synced . "\n";
    $sync_log .= "⏭️ Déjà actifs: " . $users_skipped . "\n\n";
    
    // ==================================================
    // VERIFY RESULTS
    // ==================================================
    $sync_log .= "=" . str_repeat("=", 50) . "\n";
    $sync_log .= "3️⃣ VÉRIFICATION POST-SYNC\n";
    $sync_log .= "=" . str_repeat("=", 50) . "\n";
    
    $final_properties = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mf_properties");
    $final_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}mf_user_activity");
    
    // Calculate avg properties per user
    $avg_props = $wpdb->get_var("
        SELECT ROUND(AVG(property_count), 2)
        FROM (
            SELECT COUNT(*) as property_count
            FROM {$wpdb->prefix}mf_properties
            GROUP BY author_id
        ) as subquery
    ");
    
    $sync_log .= "📊 Tables Analytics (après sync):\n";
    $sync_log .= "  - Total propriétés: " . $final_properties . "\n";
    $sync_log .= "  - Total utilisateurs actifs: " . $final_users . "\n";
    $sync_log .= "  - Moyenne props/utilisateur: " . ($avg_props ?: 0) . "\n\n";
    
    $sync_log .= "=" . str_repeat("=", 50) . "\n";
    $sync_log .= "✅ SYNCHRONISATION TERMINÉE!\n";
    $sync_log .= "=" . str_repeat("=", 50) . "\n";
    
    error_log($sync_log);
    $sync_done = true;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sync Analytics Data</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px;
            background: #f0f0f1;
            margin: 0;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 3px solid #737d5d;
            padding-bottom: 15px;
        }
        .step {
            background: #f6f7f7;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #737d5d;
        }
        .success {
            background: #dfffdf;
            border-left-color: #00a32a;
            color: #00a32a;
        }
        .error {
            background: #ffd7d7;
            border-left-color: #d63638;
            color: #d63638;
        }
        .info {
            background: #e7f3ff;
            border-left-color: #0073aa;
            color: #0073aa;
        }
        .warning {
            background: #fff8e5;
            border-left-color: #dba617;
            color: #dba617;
        }
        button {
            background: #737d5d;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 5px;
        }
        button:hover {
            background: #5f6a4e;
        }
        button:disabled {
            background: #c3c4c7;
            cursor: not-allowed;
        }
        .results {
            background: #2c3338;
            color: #a0ff00;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            font-size: 13px;
            line-height: 1.6;
            max-height: 600px;
            overflow-y: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #c3c4c7;
        }
        th {
            background: #737d5d;
            color: white;
        }
        tr:nth-child(even) {
            background: #f6f7f7;
        }
        .form-group {
            margin: 20px 0;
        }
        input[type="checkbox"] {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Synchronisation Analytics - Données Historiques</h1>
        
        <div class="step info">
            <h3>📋 Objectif</h3>
            <p>Ce script va copier vos données WordPress existantes dans les tables analytics :</p>
            <ul>
                <li><strong>Propriétés</strong> : De <code>wp_posts</code> → <code>wp_mf_properties</code></li>
                <li><strong>Utilisateurs actifs</strong> : Créer des entrées d'activité pour tous les agents</li>
                <li><strong>Analytics calculés</strong> : Permettre le calcul correct des moyennes</li>
            </ul>
        </div>

        <?php
        // Check current state
        $wp_properties_count = wp_count_posts('malisafi_property');
        $wp_total_properties = ($wp_properties_count->publish ?? 0) + ($wp_properties_count->pending ?? 0);
        
        $analytics_properties_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mf_properties");
        
        $malisafi_roles = array('malisafi_client', 'malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_moderator');
        $user_query = new WP_User_Query(array('role__in' => $malisafi_roles, 'fields' => 'ID'));
        $wp_users_count = $user_query->get_total();
        
        $analytics_activity_count = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}mf_user_activity");
        
        if ($sync_done):
        ?>
        <div class="step success">
            <h3>✅ Synchronisation Réussie!</h3>
            <p><strong>Résumé:</strong></p>
            <ul>
                <li>✅ Propriétés synchronisées: <?php echo number_format($properties_synced); ?></li>
                <li>✅ Utilisateurs avec activité: <?php echo number_format($users_synced); ?></li>
            </ul>
        </div>
        
        <div class="step">
            <h3>📊 Résultats Détaillés</h3>
            <div class="results"><?php echo esc_html($sync_log); ?></div>
        </div>
        
        <div class="step info">
            <h3>🎯 Prochaines Étapes</h3>
            <ol>
                <li><a href="<?php echo admin_url('admin.php?page=malisafi-analytics'); ?>" target="_blank"><strong>Ouvrir Analytics Overview</strong></a> - Les stats devraient être correctes maintenant</li>
                <li>Vérifiez que Avg Properties/User affiche la vraie moyenne</li>
                <li>Vérifiez que Top Contributors liste les vrais agents</li>
                <li>Faites Ctrl+F5 pour rafraîchir le cache navigateur</li>
            </ol>
        </div>
        
        <?php else: ?>
        
        <div class="step">
            <h3>📊 État Actuel</h3>
            <table>
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>WordPress</th>
                        <th>Tables Analytics</th>
                        <th>Action Requise</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Propriétés</strong></td>
                        <td><?php echo number_format($wp_total_properties); ?></td>
                        <td><?php echo number_format($analytics_properties_count); ?></td>
                        <td>
                            <?php if ($analytics_properties_count < $wp_total_properties): ?>
                                <strong style="color: #d63638;">Sync requis (<?php echo ($wp_total_properties - $analytics_properties_count); ?> manquantes)</strong>
                            <?php else: ?>
                                <strong style="color: #00a32a;">✓ Synchronisé</strong>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Utilisateurs</strong></td>
                        <td><?php echo number_format($wp_users_count); ?></td>
                        <td><?php echo number_format($analytics_activity_count); ?></td>
                        <td>
                            <?php if ($analytics_activity_count < $wp_users_count): ?>
                                <strong style="color: #d63638;">Sync requis (<?php echo ($wp_users_count - $analytics_activity_count); ?> sans activité)</strong>
                            <?php else: ?>
                                <strong style="color: #00a32a;">✓ Synchronisé</strong>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="step warning">
            <h3>⚠️ Important</h3>
            <p><strong>Cette opération va :</strong></p>
            <ul>
                <li>Synchroniser les propriétés existantes (sans dupliquer)</li>
                <li>Créer des entrées d'activité pour les utilisateurs Malisafi</li>
                <li>Permettre le calcul correct des statistiques</li>
            </ul>
            <p><strong>Sécurité :</strong> Le script vérifie les doublons avant d'insérer.</p>
        </div>

        <form method="POST" style="text-align: center; margin: 30px 0;">
            <?php wp_nonce_field('malisafi_sync_nonce'); ?>
            <input type="hidden" name="do_sync" value="1">
            <button type="submit" name="submit">🔄 Lancer la Synchronisation</button>
        </form>
        
        <?php endif; ?>
    </div>
</body>
</html>
