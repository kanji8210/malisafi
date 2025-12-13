# Malisafi MLS - User Management System

**Date**: 25 novembre 2025  
**Version**: 1.0.0

## Vue d'Ensemble

Le système de gestion des utilisateurs Malisafi MLS permet aux administrateurs et modérateurs de créer, modifier et supprimer des utilisateurs directement depuis le tableau de bord WordPress, avec gestion automatique des abonnements et des rôles personnalisés.

## Architecture

### Fichiers Créés

#### 1. admin/class-user-manager.php (376 lignes)
**Statut**: ✅ Complété et intégré

**Classe**: `Malisafi_User_Manager`

**Responsabilités**:
- Gestion CRUD des utilisateurs
- Validation des données
- Création automatique des abonnements
- Vérification AJAX des emails
- Génération de badges de rôles

**Méthodes Publiques**:
```php
init()                          // Initialisation et hooks
get_malisafi_users($args)      // Récupération des utilisateurs avec abonnements
handle_add_user()              // Traitement ajout utilisateur
handle_edit_user()             // Traitement modification utilisateur
handle_delete_user()           // Traitement suppression utilisateur
ajax_check_email()             // Vérification AJAX email existant
get_available_roles()          // Liste des rôles disponibles
get_role_badge($role)          // Badge HTML coloré pour rôle
```

**Méthodes Privées**:
```php
create_subscription($user_id, $role)  // Création abonnement automatique
```

#### 2. admin/templates/users-management.php (461 lignes)
**Statut**: ✅ Complété avec 3 vues

**Vues Disponibles**:
1. **Liste des utilisateurs** (`action=list`)
   - Tableau avec tous les utilisateurs Malisafi
   - Filtres par rôle
   - Pagination (20 utilisateurs/page)
   - Actions rapides (Éditer/Supprimer)

2. **Ajouter un utilisateur** (`action=add`)
   - Formulaire complet de création
   - Validation côté client et serveur
   - Génération automatique de mot de passe
   - Option d'envoi d'email de notification

3. **Éditer un utilisateur** (`action=edit&user_id=X`)
   - Modification des informations
   - Changement de rôle
   - Réinitialisation du mot de passe
   - Protection contre auto-suppression

## Fonctionnalités

### 1. Ajout d'Utilisateurs

#### Champs Obligatoires
- **Username**: Unique, non modifiable après création
- **Email**: Validé, vérifié pour unicité
- **Password**: Minimum 8 caractères
- **Role**: Sélection parmi les 6 rôles Malisafi

#### Champs Optionnels
- **First Name**: Prénom de l'utilisateur
- **Last Name**: Nom de famille
- **Phone**: Numéro de téléphone (meta)
- **Send Notification**: Email de bienvenue avec identifiants

#### Validation
```php
// Côté serveur (handle_add_user)
- Username unique
- Email valide et unique
- Mot de passe >= 8 caractères
- Rôle valide dans la liste autorisée
```

#### Création Automatique d'Abonnement
Pour les rôles suivants, un abonnement est créé automatiquement:
- `malisafi_agent_basic` → plan_type: `basic_agent`
- `malisafi_agent_premium` → plan_type: `premium_agent`
- `malisafi_developer` → plan_type: `developer`

**Table**: `{prefix}mf_subscriptions`
**Statut initial**: `active`

### 2. Modification d'Utilisateurs

#### Modifications Autorisées
- Email (avec vérification d'unicité)
- Prénom et nom
- Numéro de téléphone
- Rôle
- Mot de passe (optionnel)

#### Restrictions
- Username **non modifiable**
- Impossible de modifier son propre compte (pour éviter auto-blocage)

#### Gestion des Rôles
Lors du changement de rôle:
1. Ancien rôle supprimé
2. Nouveau rôle assigné avec `set_role()`
3. Capacités automatiquement mises à jour via `add_custom_capabilities()`

### 3. Suppression d'Utilisateurs

#### Sécurité
- Vérification nonce: `malisafi_delete_user_{user_id}`
- Vérification capacité: `manage_malisafi_settings`
- **Protection**: Impossible de supprimer son propre compte
- Confirmation JavaScript avant suppression

#### Données Supprimées
WordPress supprime automatiquement:
- Compte utilisateur
- Meta données associées
- Posts attribués (reassignation possible)

**Note**: Les abonnements dans `mf_subscriptions` ne sont pas supprimés automatiquement (tracer les historiques).

### 4. Liste et Filtrage

#### Colonnes Affichées
| Colonne | Source | Description |
|---------|--------|-------------|
| Username | `user_login` | Identifiant unique |
| Name | `first_name + last_name` | Nom complet |
| Email | `user_email` | Email avec lien mailto |
| Role | `roles[0]` | Badge coloré du rôle |
| Subscription | `mf_subscriptions` | Statut abonnement ou N/A |
| Registered | `user_registered` | Date d'inscription formatée |
| Actions | - | Boutons Éditer/Supprimer |

#### Filtres Disponibles
- **Par rôle**: Dropdown avec tous les rôles Malisafi
- **Pagination**: 20 utilisateurs par page

#### Requête de Récupération
```php
$args = array(
    'role__in' => array(...),  // Seulement rôles Malisafi
    'orderby' => 'registered',
    'order' => 'DESC',
    'number' => 20,
    'paged' => $current_page
);

$user_query = new WP_User_Query($args);
```

Enrichissement avec abonnements via JOIN SQL manuel sur `mf_subscriptions`.

## Interface Utilisateur

### Accès au Menu
**Menu WordPress**: `Malisafi > Users`
**URL**: `admin.php?page=malisafi-users`
**Capacité requise**: `manage_malisafi_settings`

### Actions Disponibles

#### 1. Liste (Vue par Défaut)
- **Bouton**: "Add New User" (en-tête)
- **Actions par ligne**: 
  - "Edit" → Formulaire d'édition
  - "Delete" → Confirmation + suppression (sauf soi-même)

#### 2. Formulaire d'Ajout
- **Bouton**: "Add User" (soumettre)
- **Bouton**: "Cancel" (retour liste)
- **Bouton**: "Generate" (génération mot de passe)

#### 3. Formulaire d'Édition
- **Bouton**: "Update User" (soumettre)
- **Bouton**: "Cancel" (retour liste)
- **Bouton**: "Generate" (génération mot de passe)

### Badges de Rôles

Code couleur par rôle:
```php
'malisafi_client'         => '#8c8f94' (Gris)
'malisafi_agent_basic'    => '#2271b1' (Bleu)
'malisafi_agent_premium'  => '#d63638' (Rouge)
'malisafi_owner'          => '#00a32a' (Vert)
'malisafi_developer'      => '#9b51e0' (Violet)
'malisafi_moderator'      => '#dba617' (Jaune)
```

HTML généré:
```html
<span class="malisafi-role-badge" style="background-color: #2271b1; color: white; ...">
    Agent Basic
</span>
```

### Badges de Statut d'Abonnement

Statuts avec couleurs:
- **Active**: Vert (#00a32a)
- **Pending**: Jaune (#dba617)
- **Expired**: Rouge (#d63638)
- **Cancelled**: Rouge (#d63638)
- **N/A**: Gris (pas d'abonnement)

## Sécurité

### Vérifications Implémentées

#### 1. WordPress Nonces
```php
// Ajout utilisateur
wp_nonce_field('malisafi_add_user', 'malisafi_user_nonce');
check_admin_referer('malisafi_add_user', 'malisafi_user_nonce');

// Modification utilisateur
wp_nonce_field('malisafi_edit_user', 'malisafi_user_nonce');
check_admin_referer('malisafi_edit_user', 'malisafi_user_nonce');

// Suppression utilisateur (URL)
wp_nonce_url(..., 'malisafi_delete_user_' . $user_id);
```

#### 2. Capacités WordPress
Toutes les actions vérifient:
```php
current_user_can('manage_malisafi_settings')
```

**Rôles avec accès**:
- Administrator (WordPress natif)
- Malisafi Moderator
- Malisafi Developer (si capacité ajoutée)

#### 3. Sanitization des Données
```php
sanitize_user()        // Username
sanitize_email()       // Email
sanitize_text_field()  // Texte général
sanitize_tel()         // Téléphone
intval()               // IDs numériques
```

#### 4. Validation Email Existant
```php
is_email($email)       // Format valide
email_exists($email)   // Unicité
```

#### 5. Protection Auto-Suppression
```php
if ($user_id === get_current_user_id()) {
    // Erreur: impossble de supprimer son propre compte
}
```

## Intégration

### 1. Core Plugin (includes/class-core.php)
```php
// Ligne 43 - Chargement
require_once MALISAFI_MLS_PATH . 'admin/class-user-manager.php';

// Ligne 57 - Initialisation
\Malisafi_User_Manager::init();
```

### 2. Hooks WordPress
```php
// POST handlers
add_action('admin_post_malisafi_add_user', [__CLASS__, 'handle_add_user']);
add_action('admin_post_malisafi_edit_user', [__CLASS__, 'handle_edit_user']);
add_action('admin_post_malisafi_delete_user', [__CLASS__, 'handle_delete_user']);

// AJAX
add_action('wp_ajax_malisafi_check_email', [__CLASS__, 'ajax_check_email']);
```

### 3. Dashboard Menu (class-admin-dashboard.php)
Menu déjà configuré:
```php
add_submenu_page(
    'malisafi-dashboard',
    __('User Management', 'malisafi-mls'),
    __('Users', 'malisafi-mls'),
    'manage_malisafi_settings',
    'malisafi-users',
    array(__CLASS__, 'render_users_management')
);
```

## Base de Données

### Tables Utilisées

#### 1. wp_users (WordPress Core)
Colonnes principales:
- `ID` - Clé primaire
- `user_login` - Username
- `user_pass` - Mot de passe hashé
- `user_email` - Email
- `user_registered` - Date d'inscription
- `display_name` - Nom affiché

#### 2. wp_usermeta (WordPress Core)
Meta données personnalisées:
- `first_name` - Prénom
- `last_name` - Nom
- `phone` - Téléphone (custom)
- `wp_capabilities` - Rôles et capacités

#### 3. {prefix}mf_subscriptions (Custom)
Abonnements Malisafi:
```sql
CREATE TABLE {prefix}mf_subscriptions (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    plan_type VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL,
    start_date DATETIME,
    end_date DATETIME,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);
```

**Statuts possibles**: `active`, `pending`, `expired`, `cancelled`

### Requêtes SQL Principales

#### Récupération Utilisateurs avec Abonnements
```php
$subscription = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$table_subscriptions} 
     WHERE user_id = %d 
     ORDER BY created_at DESC 
     LIMIT 1",
    $user->ID
));
```

#### Création Abonnement
```php
$wpdb->insert(
    $table,
    array(
        'user_id' => $user_id,
        'plan_type' => $plan_type,
        'status' => 'active',
        'start_date' => current_time('mysql'),
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ),
    array('%d', '%s', '%s', '%s', '%s', '%s')
);
```

## Messages et Notifications

### Messages de Succès
- `user_added` - "User successfully added."
- `user_updated` - "User successfully updated."
- `user_deleted` - "User successfully deleted."

### Messages d'Erreur
- Email invalide
- Username/Email existant
- Mot de passe trop court (< 8 caractères)
- Utilisateur non trouvé
- Impossible de supprimer son propre compte
- Échec de suppression

### Redirections
```php
// Après succès
admin_url('admin.php?page=malisafi-users&message=user_added&user_id=' . $id)

// Après erreur
admin_url('admin.php?page=malisafi-users&error=' . urlencode($message))
```

## JavaScript Intégré

### Fonctionnalités

#### 1. Génération de Mot de Passe
```javascript
$('#generate-password').on('click', function(e) {
    // Génère mot de passe aléatoire 12 caractères
    // Charset: a-z, A-Z, 0-9, symboles spéciaux
    // Affiche en clair après génération
});
```

#### 2. Sélection Globale
```javascript
$('#cb-select-all').on('change', function() {
    // Coche/décoche toutes les checkboxes utilisateurs
});
```

## Styles CSS Intégrés

### Classes Principales
```css
.malisafi-users-page              // Container principal
.malisafi-role-badge              // Badge de rôle coloré
.subscription-status              // Statut abonnement
.malisafi-user-form               // Formulaires add/edit
.button-link-delete               // Bouton supprimer rouge
```

### Responsive
- Largeurs de colonnes optimisées
- Formulaires max-width: 800px
- Table full-width avec colonnes fixes

## Tests Recommandés

### Tests Fonctionnels
- [ ] Création utilisateur avec tous les rôles
- [ ] Modification email, nom, rôle
- [ ] Génération automatique de mot de passe
- [ ] Suppression utilisateur (sauf soi-même)
- [ ] Filtrage par rôle
- [ ] Pagination (>20 utilisateurs)
- [ ] Envoi email de notification

### Tests de Validation
- [ ] Username déjà existant (erreur)
- [ ] Email déjà existant (erreur)
- [ ] Email invalide (erreur)
- [ ] Mot de passe < 8 caractères (erreur)
- [ ] Champs obligatoires vides (erreur)

### Tests de Sécurité
- [ ] Nonces vérifiés pour toutes les actions
- [ ] Capacités vérifiées (`manage_malisafi_settings`)
- [ ] Impossible de supprimer son propre compte
- [ ] Sanitization de toutes les entrées
- [ ] Protection CSRF

### Tests d'Intégration
- [ ] Abonnement créé pour agents/développeurs
- [ ] Rôle assigné correctement avec capacités
- [ ] Badges de rôles affichés avec bonnes couleurs
- [ ] Statut abonnement affiché depuis mf_subscriptions

## Prochaines Améliorations

### Phase 1: UX (Priorité Moyenne)
- [ ] AJAX pour validation email en temps réel
- [ ] Indicateur de force du mot de passe
- [ ] Actions groupées (suppression multiple)
- [ ] Export CSV de la liste d'utilisateurs
- [ ] Recherche par nom/email/username

### Phase 2: Fonctionnalités (Priorité Moyenne)
- [ ] Gestion avancée des abonnements (dates, tarifs)
- [ ] Historique des modifications utilisateur
- [ ] Système de permissions granulaires
- [ ] Import CSV d'utilisateurs en masse
- [ ] Réinitialisation mot de passe par email

### Phase 3: Notifications (Priorité Basse)
- [ ] Templates d'emails personnalisés
- [ ] Notifications admin lors création utilisateur
- [ ] Emails de bienvenue par rôle
- [ ] Rappels d'expiration d'abonnement

### Phase 4: Analytique (Priorité Basse)
- [ ] Statistiques d'activité utilisateur
- [ ] Graphiques de croissance par rôle
- [ ] Taux de conversion client → agent
- [ ] Tableau de bord utilisateur individuel

## Documentation Associée

- **ROLES.md** - Système de rôles et capacités détaillé
- **INTEGRATION.md** - Guide d'intégration du role manager
- **DASHBOARD-SEPARATION.md** - Architecture modulaire admin
- **WIDGETS-STATUS.md** - Widgets dashboard WordPress
- **STATUS.md** - État général du plugin

## Notes Techniques

### Performance
- Requêtes paginées (20 utilisateurs max par page)
- Jointure manuelle pour abonnements (optimiser avec JOIN SQL)
- Pas de cache implémenté (à considérer pour >1000 utilisateurs)

### Compatibilité
- WordPress 5.0+ (WP_User_Query)
- PHP 7.2+ (null coalescing operator ??)
- MySQL 5.6+ (table mf_subscriptions)

### Multisite
Non testé en mode multisite WordPress. Adaptations possibles:
- Utiliser `get_blog_prefix()` pour les tables
- Vérifier les capacités par site
- Gérer les rôles réseau vs site

---

**Dernière mise à jour**: 25 novembre 2025  
**Auteur**: GitHub Copilot  
**Version Plugin**: 1.0.0
