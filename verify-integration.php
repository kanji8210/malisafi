#!/usr/bin/env php
<?php
/**
 * Script de vérification de l'intégration Malisafi MLS
 * 
 * Usage: php verify-integration.php
 * 
 * @package MalisafiMLS
 */

// Couleurs pour le terminal
define('COLOR_GREEN', "\033[0;32m");
define('COLOR_RED', "\033[0;31m");
define('COLOR_YELLOW', "\033[1;33m");
define('COLOR_BLUE', "\033[0;34m");
define('COLOR_RESET', "\033[0m");

// Chemin vers WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (!file_exists($wp_load_path)) {
    echo COLOR_RED . "❌ WordPress non trouvé. Assurez-vous que le plugin est dans wp-content/plugins/" . COLOR_RESET . "\n";
    exit(1);
}

// Charger WordPress
require_once $wp_load_path;

echo COLOR_BLUE . "=== Vérification de l'intégration Malisafi MLS ===" . COLOR_RESET . "\n\n";

// Test 1: Vérifier si le plugin est activé
echo "1. Vérification de l'activation du plugin...\n";
if (is_plugin_active('malisafi_mls/malisafi-mls.php')) {
    echo COLOR_GREEN . "   ✓ Plugin activé" . COLOR_RESET . "\n";
} else {
    echo COLOR_RED . "   ✗ Plugin non activé" . COLOR_RESET . "\n";
}

// Test 2: Vérifier les rôles
echo "\n2. Vérification des rôles personnalisés...\n";
$expected_roles = [
    'malisafi_client' => 'Client',
    'malisafi_agent_basic' => 'Basic Agent',
    'malisafi_agent_premium' => 'Premium Agent',
    'malisafi_owner' => 'Property Owner',
    'malisafi_developer' => 'Developer',
    'malisafi_moderator' => 'Moderator'
];

$role_check = true;
foreach ($expected_roles as $role_slug => $role_name) {
    $role = get_role($role_slug);
    if ($role) {
        echo COLOR_GREEN . "   ✓ Rôle '$role_name' trouvé" . COLOR_RESET . "\n";
    } else {
        echo COLOR_RED . "   ✗ Rôle '$role_name' manquant" . COLOR_RESET . "\n";
        $role_check = false;
    }
}

// Test 3: Vérifier les capabilities
echo "\n3. Vérification des capabilities personnalisées...\n";
$test_role = get_role('malisafi_agent_premium');
if ($test_role) {
    $expected_caps = [
        'edit_properties',
        'feature_properties',
        'boost_listings',
        'advanced_analytics',
        'access_malisafi_dashboard'
    ];
    
    foreach ($expected_caps as $cap) {
        if (isset($test_role->capabilities[$cap]) && $test_role->capabilities[$cap]) {
            echo COLOR_GREEN . "   ✓ Capability '$cap' présente" . COLOR_RESET . "\n";
        } else {
            echo COLOR_RED . "   ✗ Capability '$cap' manquante" . COLOR_RESET . "\n";
        }
    }
} else {
    echo COLOR_RED . "   ✗ Impossible de vérifier les capabilities (rôle Agent Premium manquant)" . COLOR_RESET . "\n";
}

// Test 4: Vérifier les tables de base de données
echo "\n4. Vérification des tables de base de données...\n";
global $wpdb;
$prefix = $wpdb->prefix;
$expected_tables = [
    'mf_subscriptions',
    'mf_user_limits',
    'mf_properties',
    'mf_property_amenities',
    'mf_property_media',
    'mf_inquiries',
    'mf_saved_searches',
    'mf_favorites',
    'mf_moderation_queue',
    'mf_analytics'
];

foreach ($expected_tables as $table) {
    $table_name = $prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        echo COLOR_GREEN . "   ✓ Table '$table' existe" . COLOR_RESET . "\n";
    } else {
        echo COLOR_RED . "   ✗ Table '$table' manquante" . COLOR_RESET . "\n";
    }
}

// Test 5: Vérifier le Custom Post Type
echo "\n5. Vérification du Custom Post Type...\n";
if (post_type_exists('malisafi_property')) {
    echo COLOR_GREEN . "   ✓ Post type 'malisafi_property' enregistré" . COLOR_RESET . "\n";
    
    // Vérifier les capabilities du CPT
    $post_type = get_post_type_object('malisafi_property');
    if ($post_type && $post_type->capability_type === 'property') {
        echo COLOR_GREEN . "   ✓ Capability type 'property' configuré" . COLOR_RESET . "\n";
    } else {
        echo COLOR_YELLOW . "   ⚠ Capability type non configuré correctement" . COLOR_RESET . "\n";
    }
    
    if ($post_type && $post_type->map_meta_cap) {
        echo COLOR_GREEN . "   ✓ Meta capability mapping activé" . COLOR_RESET . "\n";
    } else {
        echo COLOR_YELLOW . "   ⚠ Meta capability mapping non activé" . COLOR_RESET . "\n";
    }
} else {
    echo COLOR_RED . "   ✗ Post type 'malisafi_property' non enregistré" . COLOR_RESET . "\n";
}

// Test 6: Vérifier les taxonomies
echo "\n6. Vérification des taxonomies...\n";
$expected_taxonomies = [
    'malisafi_property_type' => 'Property Types',
    'malisafi_property_status' => 'Property Status',
    'malisafi_location' => 'Locations',
    'malisafi_feature' => 'Features'
];

foreach ($expected_taxonomies as $tax_slug => $tax_name) {
    if (taxonomy_exists($tax_slug)) {
        echo COLOR_GREEN . "   ✓ Taxonomie '$tax_name' enregistrée" . COLOR_RESET . "\n";
    } else {
        echo COLOR_RED . "   ✗ Taxonomie '$tax_name' manquante" . COLOR_RESET . "\n";
    }
}

// Test 7: Vérifier les fichiers critiques
echo "\n7. Vérification des fichiers critiques...\n";
$plugin_path = WP_PLUGIN_DIR . '/malisafi_mls/';
$critical_files = [
    'includes/class-role-manager.php',
    'includes/class-database.php',
    'includes/class-activator.php',
    'includes/class-core.php',
    'admin/class-admin.php',
    'admin/partials/dashboard-display.php',
    'ROLES.md',
    'INTEGRATION.md'
];

foreach ($critical_files as $file) {
    if (file_exists($plugin_path . $file)) {
        echo COLOR_GREEN . "   ✓ Fichier '$file' présent" . COLOR_RESET . "\n";
    } else {
        echo COLOR_RED . "   ✗ Fichier '$file' manquant" . COLOR_RESET . "\n";
    }
}

// Test 8: Vérifier les options
echo "\n8. Vérification des options du plugin...\n";
$db_version = get_option('malisafi_mls_db_version');
if ($db_version) {
    echo COLOR_GREEN . "   ✓ Version de la base de données: $db_version" . COLOR_RESET . "\n";
} else {
    echo COLOR_YELLOW . "   ⚠ Version de la base de données non définie" . COLOR_RESET . "\n";
}

$activated = get_option('malisafi_mls_activated');
if ($activated) {
    echo COLOR_GREEN . "   ✓ Date d'activation: " . date('Y-m-d H:i:s', $activated) . COLOR_RESET . "\n";
} else {
    echo COLOR_YELLOW . "   ⚠ Date d'activation non enregistrée" . COLOR_RESET . "\n";
}

// Résumé
echo "\n" . COLOR_BLUE . "=== Résumé ===" . COLOR_RESET . "\n";
if ($role_check) {
    echo COLOR_GREEN . "✓ Tous les rôles sont correctement créés" . COLOR_RESET . "\n";
} else {
    echo COLOR_RED . "✗ Certains rôles sont manquants. Essayez de désactiver/réactiver le plugin." . COLOR_RESET . "\n";
}

echo "\n" . COLOR_BLUE . "Pour plus d'informations, consultez:" . COLOR_RESET . "\n";
echo "  - ROLES.md - Documentation des rôles et capabilities\n";
echo "  - INTEGRATION.md - Guide d'intégration et de test\n";
echo "  - TODO.md - État d'avancement du projet\n";

echo "\n" . COLOR_BLUE . "=== Fin de la vérification ===" . COLOR_RESET . "\n";
