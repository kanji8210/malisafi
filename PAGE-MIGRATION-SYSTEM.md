# Page Manager Migration System

## Vue d'ensemble

Le système de migration automatique du Page Manager permet de mettre à jour automatiquement les pages WordPress lorsque les configurations de shortcode changent dans le plugin.

## Comment ça fonctionne

### 1. Migration automatique au chargement

Lors de chaque chargement de l'admin WordPress, le Page Manager exécute `migrate_pages()` qui :

1. ✅ Vérifie la version de migration actuelle
2. ✅ Compare avec la dernière version de migration disponible
3. ✅ Exécute les migrations manquantes
4. ✅ Marque la migration comme complète

### 2. Mise à jour lors de la création

Lorsque `create_all_pages()` ou `create_page()` est appelé, le système :

1. ✅ Vérifie si la page existe déjà
2. ✅ Compare le shortcode actuel avec le shortcode configuré
3. ✅ Met à jour automatiquement si différent
4. ✅ Retourne l'ID de la page

## Migrations actuelles

### Migration 1.0 - Agent Profile Page Fix

**Date** : 17 janvier 2026

**Problème résolu** :
- La page `agent-profile` utilisait `[malisafi_agent_profile]` (dashboard privé)
- Devait utiliser `[malisafi_agent_profile_view]` (profil public)

**Actions** :
1. Détecte les pages avec l'ancien shortcode `[malisafi_agent_profile]`
2. Met à jour vers `[malisafi_agent_profile_view]`
3. Change le titre de "My Profile" vers "Agent Profile"

**Code** :
```php
// Migration 1.0: Fix agent-profile page shortcode
$agent_profile_id = get_option('malisafi_page_agent_profile');
if ($agent_profile_id && ($page = get_post($agent_profile_id))) {
    if ($page->post_content === '[malisafi_agent_profile]') {
        wp_update_post(array(
            'ID' => $agent_profile_id,
            'post_content' => '[malisafi_agent_profile_view]',
            'post_title' => 'Agent Profile'
        ));
    }
}
```

## Ajouter une nouvelle migration

### Étape 1 : Incrémenter la version

Dans [class-page-manager.php](includes/class-page-manager.php) :

```php
public static function migrate_pages() {
    $migration_version = get_option('malisafi_pages_migration_version', '0');
    $current_migration = '1.1'; // ← Incrémenter ici
```

### Étape 2 : Ajouter le code de migration

```php
// Migration 1.1: Votre description
$page_id = get_option('malisafi_page_YOUR_PAGE_KEY');
if ($page_id && ($page = get_post($page_id))) {
    // Votre logique de migration
    if (/* condition */) {
        wp_update_post(array(
            'ID' => $page_id,
            'post_content' => '[nouveau_shortcode]',
            'post_title' => 'Nouveau titre'
        ));
    }
}
```

### Étape 3 : Documenter

Ajoutez une section dans ce fichier avec :
- Date
- Problème résolu
- Actions effectuées
- Code de la migration

## Forcer une re-migration

Si vous devez forcer la ré-exécution des migrations :

**Option 1 : Via WP-CLI**
```bash
wp option delete malisafi_pages_migration_version
```

**Option 2 : Via SQL**
```sql
DELETE FROM wp_options WHERE option_name = 'malisafi_pages_migration_version';
```

**Option 3 : Via PHP**
```php
delete_option('malisafi_pages_migration_version');
```

## Vérifier l'état des migrations

### Via Options

```php
$version = get_option('malisafi_pages_migration_version', '0');
echo "Version de migration actuelle : " . $version;
```

### Via Admin

Allez dans **Malisafi MLS → Pages** pour voir l'état de toutes les pages.

## Bonnes pratiques

### ✅ À faire

1. **Toujours incrémenter la version** lors de l'ajout d'une migration
2. **Tester en local** avant de déployer
3. **Documenter** chaque migration avec date et raison
4. **Vérifier l'existence** de la page avant modification
5. **Utiliser des conditions** pour éviter les mises à jour inutiles

### ❌ À éviter

1. **Ne jamais supprimer** d'anciennes migrations du code
2. **Ne pas modifier** des migrations déjà déployées
3. **Ne pas oublier** d'incrémenter la version
4. **Ne pas forcer** wp_update_post() sans vérifier si nécessaire

## Exemples de migrations futures

### Exemple 1 : Changer un shortcode

```php
// Migration 1.2: Update property search shortcode
$search_id = get_option('malisafi_page_property_search');
if ($search_id && ($page = get_post($search_id))) {
    if ($page->post_content === '[malisafi_properties]') {
        wp_update_post(array(
            'ID' => $search_id,
            'post_content' => '[malisafi_properties_modern]'
        ));
    }
}
```

### Exemple 2 : Ajouter du contenu

```php
// Migration 1.3: Add intro text to pricing page
$pricing_id = get_option('malisafi_page_pricing');
if ($pricing_id && ($page = get_post($pricing_id))) {
    $new_content = '<p>Choose your plan below.</p>' . "\n" . $page->post_content;
    wp_update_post(array(
        'ID' => $pricing_id,
        'post_content' => $new_content
    ));
}
```

### Exemple 3 : Mettre à jour plusieurs pages

```php
// Migration 1.4: Update all dashboard pages with new wrapper
$dashboard_pages = array('agent_dashboard', 'owner_dashboard', 'developer_dashboard');
foreach ($dashboard_pages as $page_key) {
    $page_id = get_option('malisafi_page_' . $page_key);
    if ($page_id && ($page = get_post($page_id))) {
        // Wrap existing shortcode
        $old_shortcode = $page->post_content;
        $new_content = '[malisafi_dashboard_wrapper]' . "\n" . $old_shortcode;
        
        wp_update_post(array(
            'ID' => $page_id,
            'post_content' => $new_content
        ));
    }
}
```

## Dépannage

### Migration ne s'exécute pas

**Vérifier** :
1. Hook `admin_init` est bien actif
2. Option `malisafi_pages_migration_version` dans la base de données
3. Version actuelle vs version du code

**Solution** :
```php
// Activer les logs
error_log('Migration version: ' . get_option('malisafi_pages_migration_version', '0'));
```

### Migration s'exécute en boucle

**Cause** : Version non sauvegardée après migration

**Solution** :
```php
// À la fin de migrate_pages()
update_option('malisafi_pages_migration_version', $current_migration);
```

### Page non mise à jour

**Vérifier** :
1. Page existe dans la base de données
2. Option `malisafi_page_XXXXX` contient le bon ID
3. Condition de la migration est vraie

## Fichiers concernés

- [includes/class-page-manager.php](includes/class-page-manager.php) - Logique de migration
- Options WordPress :
  - `malisafi_pages_migration_version` - Version actuelle
  - `malisafi_page_*` - IDs des pages (ex: `malisafi_page_agent_profile`)

## Historique des versions

| Version | Date | Description |
|---------|------|-------------|
| 1.0 | 17 jan 2026 | Fix agent-profile shortcode `[malisafi_agent_profile]` → `[malisafi_agent_profile_view]` |

---

**Note** : Ce système garantit que toutes les installations du plugin (nouvelles et existantes) auront toujours les bonnes configurations de pages, sans intervention manuelle.
