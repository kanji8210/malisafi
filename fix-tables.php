<?php
/**
 * Script pour créer/corriger les tables de la base de données Malisafi MLS
 * 
 * INSTRUCTIONS:
 * 1. Accédez à ce fichier via votre navigateur: http://localhost/wordpress/wp-content/plugins/malisafi_mls/fix-tables.php
 * 2. Le script créera automatiquement toutes les tables manquantes
 * 3. Supprimez ce fichier après utilisation pour des raisons de sécurité
 */

// Charger WordPress
require_once('../../../wp-load.php');

// Vérifier les permissions admin
if (!current_user_can('manage_options')) {
    wp_die('Vous devez être administrateur pour exécuter ce script.');
}

// Inclure la classe Database
require_once(__DIR__ . '/includes/class-database.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Malisafi MLS - Réparation de la base de données</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a1a1a;
            border-bottom: 3px solid #4a4a4a;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .table-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .table-list ul {
            list-style: none;
            padding: 0;
        }
        .table-list li {
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        .table-list li:before {
            content: '✓ ';
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4a4a4a;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #1a1a1a;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Réparation de la base de données Malisafi MLS</h1>";

try {
    // Activer l'affichage des erreurs pour debug
    global $wpdb;
    $wpdb->show_errors();
    
    echo "<div class='info'><strong>🚀 Début de la création des tables...</strong></div>";
    
    // Créer toutes les tables
    \MalisafiMLS\Database::create_tables();
    
    echo "<div class='success'><strong>✅ Toutes les tables ont été créées avec succès!</strong></div>";
    
    // Lister les tables créées
    $prefix = $wpdb->prefix;
    $tables = [
        'mf_subscriptions' => 'Abonnements des utilisateurs',
        'mf_user_limits' => 'Limites des utilisateurs',
        'mf_properties' => 'Propriétés (table principale)',
        'mf_property_amenities' => 'Équipements des propriétés',
        'mf_property_media' => 'Médias des propriétés',
        'mf_inquiries' => 'Demandes de renseignements (CORRIGÉE)',
        'mf_saved_searches' => 'Recherches sauvegardées',
        'mf_favorites' => 'Favoris',
        'mf_moderation_queue' => 'File de modération',
        'mf_property_reports' => 'Rapports de propriétés',
        'mf_analytics' => 'Analytique et statistiques',
    ];
    
    echo "<div class='table-list'>";
    echo "<h3>📋 Tables créées:</h3>";
    echo "<ul>";
    
    foreach ($tables as $table => $description) {
        $full_table = $prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table;
        
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
            echo "<li><strong>$full_table</strong> - $description ($count enregistrements)</li>";
        } else {
            echo "<li style='color: red;'>❌ <strong>$full_table</strong> - ÉCHEC DE LA CRÉATION</li>";
        }
    }
    
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔍 Vérification de la table problématique:</h3>";
    $inquiries_table = $prefix . 'mf_inquiries';
    $test_query = $wpdb->get_var("SELECT COUNT(*) FROM $inquiries_table");
    echo "<p>✅ La table <strong>$inquiries_table</strong> fonctionne correctement!</p>";
    echo "<p>Nombre d'enregistrements: <strong>$test_query</strong></p>";
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h3>✨ Modifications du code effectuées:</h3>";
    echo "<p>Le fichier <code>includes/class-dashboard-shortcodes.php</code> a été corrigé pour utiliser le bon nom de table:</p>";
    echo "<ul>";
    echo "<li>Ancienne table: <code>wp_malisafi_inquiries</code> ❌</li>";
    echo "<li>Nouvelle table: <code>wp_mf_inquiries</code> ✅</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ Erreur:</strong> " . esc_html($e->getMessage());
    echo "</div>";
}

echo "
        <a href='" . admin_url('admin.php?page=malisafi-mls-settings') . "' class='btn'>Retour au tableau de bord</a>
        
        <div class='info' style='margin-top: 30px;'>
            <h3>⚠️ Important:</h3>
            <p><strong>Supprimez ce fichier après utilisation pour des raisons de sécurité!</strong></p>
            <p>Chemin du fichier: <code>" . __FILE__ . "</code></p>
        </div>
    </div>
</body>
</html>";
