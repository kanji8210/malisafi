# Guide d'Intégration - Malisafi MLS Roles

## Résumé des Modifications

Le gestionnaire de rôles `Malisafi_Roles_Manager` a été intégré dans le plugin. Voici ce qui a été modifié :

### Fichiers Modifiés

1. **includes/class-role-manager.php**
   - Structure refactorisée pour utiliser `Malisafi_Roles_Manager`
   - 6 rôles personnalisés : Client, Agent Basic, Agent Premium, Owner, Developer, Moderator
   - Capabilities personnalisées pour la gestion des propriétés

2. **includes/class-activator.php**
   - Intégration de `Malisafi_Roles_Manager::create_roles()`
   - Intégration de `Malisafi_Roles_Manager::init()`
   - Appel lors de l'activation du plugin

3. **includes/class-deactivator.php**
   - Ajout de la méthode `remove_custom_roles()` (commentée par défaut)
   - Liste des 6 rôles à supprimer si activé

4. **includes/class-core.php**
   - Chargement de class-role-manager.php
   - Initialisation de `Malisafi_Roles_Manager::init()` dans le constructeur

5. **includes/class-post-types.php**
   - `capability_type` changé de `'post'` à `array('property', 'properties')`
   - `map_meta_cap` activé pour mapper automatiquement les capabilities

6. **admin/class-admin.php**
   - Menus admin utilisent `manage_malisafi_settings` au lieu de `manage_options`
   - Nouveau menu Dashboard avec capability `access_malisafi_dashboard`
   - Nouvelle méthode `display_dashboard_page()`

7. **admin/partials/dashboard-display.php** (NOUVEAU)
   - Page de dashboard pour tous les rôles Malisafi
   - Affichage des statistiques de propriétés
   - Actions rapides basées sur les capabilities
   - Sections conditionnelles pour les fonctionnalités premium

8. **ROLES.md** (NOUVEAU)
   - Documentation complète des rôles et capabilities
   - Exemples d'utilisation
   - Workflow de modération

---

## Comment Tester

### 1. Activation du Plugin

```bash
# Via WP-CLI
wp plugin activate malisafi-mls

# Ou via l'interface WordPress
# Allez dans Plugins > Plugins installés > Activer Malisafi MLS
```

**Vérifications après activation :**
- [ ] 10 tables créées dans la base de données (préfixe `mf_`)
- [ ] 6 rôles créés (Client, Agent Basic, Agent Premium, Owner, Developer, Moderator)
- [ ] Custom post type `malisafi_property` enregistré
- [ ] Menu "MLS Settings" visible pour les administrateurs

### 2. Vérifier les Rôles

```php
// Ajouter ce code dans un fichier test ou dans functions.php temporairement
add_action('init', function() {
    $roles = wp_roles()->get_names();
    echo '<pre>';
    print_r($roles);
    echo '</pre>';
});
```

**Rôles attendus :**
- malisafi_client
- malisafi_agent_basic
- malisafi_agent_premium
- malisafi_owner
- malisafi_developer
- malisafi_moderator

### 3. Vérifier les Capabilities

```php
// Tester les capabilities d'un rôle
$role = get_role('malisafi_agent_premium');
if ($role) {
    echo '<pre>';
    print_r($role->capabilities);
    echo '</pre>';
}
```

**Capabilities attendues pour Agent Premium :**
- edit_properties: true
- publish_properties: false (mais passera true via add_custom_capabilities)
- feature_properties: true
- boost_listings: true
- advanced_analytics: true
- access_malisafi_dashboard: true

### 4. Créer des Utilisateurs de Test

```bash
# Via WP-CLI
wp user create agent_test agent@test.com --role=malisafi_agent_basic
wp user create agent_premium premium@test.com --role=malisafi_agent_premium
wp user create moderator mod@test.com --role=malisafi_moderator
```

**Ou via l'interface :**
1. Utilisateurs > Ajouter
2. Sélectionner le rôle Malisafi approprié
3. Remplir les informations

### 5. Tester le Dashboard

1. Se connecter avec un utilisateur Malisafi (non admin)
2. Aller dans le menu "MLS Settings" > "Dashboard"
3. Vérifier :
   - [ ] Statistiques des propriétés affichées
   - [ ] Actions rapides visibles selon le rôle
   - [ ] Sections premium pour Agent Premium
   - [ ] Section modération pour Moderator

### 6. Tester la Création de Propriété

**En tant qu'Agent Basic :**
1. Aller dans Properties > Add New
2. Créer une propriété
3. Vérifier : Statut = "En attente de révision" (Pending)

**En tant qu'Agent Premium :**
1. Créer une propriété
2. Vérifier : Statut = "Publié" (Published) directement

**En tant que Moderator :**
1. Aller dans Properties > Tous
2. Voir les propriétés en attente
3. Pouvoir modifier et publier

### 7. Tester les Permissions

```php
// Dans un template ou functions.php
if (current_user_can('edit_properties')) {
    echo "✅ Peut éditer des propriétés";
}

if (current_user_can('moderate_properties')) {
    echo "✅ Peut modérer";
}

if (current_user_can('feature_properties')) {
    echo "✅ Peut mettre en vedette";
}
```

---

## Commandes Utiles

### WP-CLI

```bash
# Lister tous les rôles
wp role list

# Voir les capabilities d'un rôle
wp cap list 'malisafi_agent_premium'

# Ajouter une capability à un rôle
wp cap add 'malisafi_agent_basic' 'custom_capability'

# Supprimer une capability
wp cap remove 'malisafi_agent_basic' 'custom_capability'

# Réinitialiser les rôles (désactiver/activer le plugin)
wp plugin deactivate malisafi-mls
wp plugin activate malisafi-mls
```

### Base de Données

```sql
-- Vérifier les tables créées
SHOW TABLES LIKE 'wp_mf_%';

-- Voir les rôles enregistrés
SELECT option_value FROM wp_options WHERE option_name = 'wp_user_roles';

-- Compter les utilisateurs par rôle
SELECT meta_value as role, COUNT(*) as count 
FROM wp_usermeta 
WHERE meta_key = 'wp_capabilities' 
AND meta_value LIKE '%malisafi%'
GROUP BY meta_value;
```

---

## Dépannage

### Problème : Les rôles ne sont pas créés

**Solution :**
1. Désactiver et réactiver le plugin
2. Vider le cache si vous utilisez un plugin de cache
3. Vérifier les erreurs PHP dans le log

### Problème : Les capabilities ne fonctionnent pas

**Solution :**
1. Vérifier que `Malisafi_Roles_Manager::init()` est appelé
2. Vérifier que le hook `init` est bien déclenché
3. Essayer de forcer la mise à jour :

```php
Malisafi_Roles_Manager::add_custom_capabilities();
```

### Problème : Le menu Dashboard n'apparaît pas

**Solution :**
1. Vérifier que l'utilisateur a la capability `access_malisafi_dashboard`
2. Vérifier que le fichier `dashboard-display.php` existe
3. Vider le cache du navigateur

### Problème : Les propriétés ne passent pas en "Publié"

**Solution :**
1. Vérifier la capability `publish_properties`
2. Pour Agent Premium, vérifier que `add_custom_capabilities()` s'exécute
3. Tester avec un administrateur pour confirmer

---

## Prochaines Étapes

### Recommandations

1. **Tester en environnement de staging** avant la production
2. **Créer une sauvegarde** de la base de données
3. **Documenter** les rôles personnalisés pour les clients
4. **Former** les modérateurs sur le workflow d'approbation

### Fonctionnalités à Ajouter

- [ ] Interface de modération améliorée
- [ ] Notifications pour les modérateurs (nouvelles propriétés en attente)
- [ ] Historique des modifications
- [ ] Logs d'activité par rôle
- [ ] Limites de propriétés par rôle (intégration avec la table `mf_user_limits`)
- [ ] Dashboard analytics avancé pour Premium
- [ ] Système de boost pour les annonces

### Intégrations Suggérées

- Plugin de membership (pour gérer les abonnements)
- Plugin de paiement (Stripe, PayPal)
- Plugin de notifications (email, push)
- Plugin d'analytics (Google Analytics, Matomo)

---

## Support

Pour toute question ou problème :
1. Consultez `ROLES.md` pour la documentation complète
2. Vérifiez `TODO.md` pour l'état du projet
3. Consultez les logs WordPress : `wp-content/debug.log`

---

**Version :** 1.0.0  
**Dernière mise à jour :** 25 novembre 2025
