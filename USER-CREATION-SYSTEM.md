# Système Unifié de Création d'Utilisateurs - Malisafi MLS

## Vue d'ensemble

Le système de création d'utilisateurs a été complètement refactorisé pour garantir la cohérence entre le frontend (inscription publique) et le backend (admin).

## Architecture

### Classe Centrale: `User_Creation_Helper`

**Fichier**: `includes/class-user-creation-helper.php`

Cette classe centralise toute la logique de création d'utilisateur pour éviter la duplication de code et garantir que **tous** les enregistrements de base de données sont créés.

#### Responsabilités

1. **Création utilisateur WordPress** (`wp_users`, `wp_usermeta`)
2. **Création enregistrement subscription** (`wp_mf_subscriptions`)
3. **Création enregistrement limites** (`wp_mf_user_limits`)
4. **Création profil agent** (si applicable)
   - User meta pour tous les champs agent
   - Post type `malisafi_agent`
   - Notification admin

## Tables de Base de Données Gérées

### 1. `wp_mf_subscriptions`

Créée automatiquement pour les rôles payants:
- `malisafi_agent_basic` → plan_type: `agent_basic`
- `malisafi_agent_premium` → plan_type: `agent_premium`
- `malisafi_owner` → plan_type: `owner_basic`
- `malisafi_developer` → plan_type: `developer`

**Champs créés**:
```php
array(
    'user_id' => $user_id,
    'plan_type' => $plan_type,
    'status' => 'active',
    'current_period_start' => current_time('mysql'),
    'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 year')),
    'created_at' => current_time('mysql'),
    'updated_at' => current_time('mysql')
)
```

### 2. `wp_mf_user_limits`

Créée pour **tous** les utilisateurs avec des limites basées sur le rôle:

| Rôle | max_listings | featured_listings | can_boost | analytics_access |
|------|--------------|-------------------|-----------|------------------|
| Client | 0 | 0 | false | false |
| Agent Basic | 5 | 1 | false | false |
| Agent Premium | -1 (illimité) | 5 | true | true |
| Owner | 3 | 0 | false | false |
| Developer | -1 (illimité) | 10 | true | true |
| Moderator | 0 | 0 | false | true |

### 3. User Meta (wp_usermeta)

**Champs standard** (tous utilisateurs):
- `phone` - Numéro de téléphone
- `account_type` - Type de compte (client, agent, owner, developer)

**Champs agents** (si account_type = 'agent'):
- `agency_name` - Nom de l'agence
- `license_number` - Numéro de licence
- `years_experience` - Années d'expérience
- `agent_county` - Comté d'opération (Kenya)
- `business_address` - Adresse professionnelle
- `city` - Ville
- `specializations` - Spécialisations (array)
- `agent_bio` - Biographie (min 100 caractères)
- `national_id` - Numéro d'identité nationale
- `website` - Site web (optionnel)
- `whatsapp` - WhatsApp (optionnel)
- `office_phone` - Téléphone bureau (optionnel)
- `languages` - Langues parlées (optionnel)
- `service_areas` - Zones de service (optionnel)
- `commission_rate` - Taux de commission (optionnel)
- `facebook` - URL Facebook (optionnel)
- `twitter` - URL Twitter (optionnel)
- `linkedin` - URL LinkedIn (optionnel)
- `instagram` - URL Instagram (optionnel)
- `youtube` - URL YouTube (optionnel)
- `agent_status` - Statut d'approbation ('pending', 'approved', 'rejected')
- `agent_registered_date` - Date d'inscription
- `agent_post_id` - ID du post type agent

### 4. Post Type `malisafi_agent`

Créé automatiquement avec tous les meta fields:
- `_agent_user_id` - ID utilisateur lié
- `_agent_email` - Email
- `_agent_phone` - Téléphone
- `_agent_mobile` - Mobile (même que phone)
- `_agent_agency_name` - Nom agence
- `_agent_license_number` - Licence
- `_agent_experience_years` - Années d'expérience
- `_agent_county` - Comté
- `_agent_office_address` - Adresse bureau
- `_agent_city` - Ville
- `_agent_specializations` - Spécialisations (string)
- `_agent_bio` - Biographie
- `_agent_national_id` - ID national
- `_agent_website` - Site web
- `_agent_whatsapp` - WhatsApp
- `_agent_languages` - Langues
- `_agent_service_areas` - Zones de service
- `_agent_commission_rate` - Taux commission
- `_agent_facebook` - Facebook
- `_agent_twitter` - Twitter
- `_agent_linkedin` - LinkedIn
- `_agent_instagram` - Instagram
- `_agent_youtube` - YouTube
- `_agent_rating` - Note moyenne (défaut: 0)
- `_agent_total_reviews` - Total avis (défaut: 0)
- `_agent_properties_count` - Nombre de propriétés (défaut: 0)
- `_agent_status` - Statut (défaut: 'active')

## Utilisation

### Frontend (Inscription Publique)

**Fichier**: `includes/class-registration-handler.php`

```php
// Préparer les données utilisateur
$user_data = array(
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'first_name' => $first_name,
    'last_name' => $last_name,
    'role' => $user_role
);

// Préparer les métadonnées
$meta_data = array(
    'phone' => $phone,
    'account_type' => $account_type,
    // Tous les champs agent...
);

// Créer l'utilisateur avec auto-login
$user_id = User_Creation_Helper::create_user($user_data, $meta_data, true);

if (is_wp_error($user_id)) {
    // Gérer l'erreur
}
```

### Backend (Admin)

**Fichier**: `admin/class-user-manager.php`

```php
// Préparer les données utilisateur
$user_data = array(
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'first_name' => $first_name,
    'last_name' => $last_name,
    'role' => $role
);

// Préparer les métadonnées minimales
$meta_data = array(
    'phone' => $phone,
    'account_type' => $account_type // Déduit du rôle
);

// Créer l'utilisateur sans auto-login
$user_id = User_Creation_Helper::create_user($user_data, $meta_data, false);

if (is_wp_error($user_id)) {
    // Gérer l'erreur
}
```

## Méthodes Utilitaires du Helper

### 1. `get_user_limits($user_id)`

Récupère les limites d'un utilisateur depuis `wp_mf_user_limits`.

```php
$limits = User_Creation_Helper::get_user_limits($user_id);
// Retourne: object { max_listings, used_listings, featured_listings, can_boost, analytics_access }
```

### 2. `get_user_subscription($user_id)`

Récupère la souscription active d'un utilisateur.

```php
$subscription = User_Creation_Helper::get_user_subscription($user_id);
// Retourne: object { plan_type, status, current_period_start, current_period_end, ... }
```

### 3. `update_user_limits($user_id, $limits)`

Met à jour les limites d'un utilisateur.

```php
User_Creation_Helper::update_user_limits($user_id, array(
    'max_listings' => 10,
    'featured_listings' => 2
));
```

### 4. `has_reached_listing_limit($user_id)`

Vérifie si l'utilisateur a atteint sa limite de listings.

```php
if (User_Creation_Helper::has_reached_listing_limit($user_id)) {
    // Afficher message upgrade
}
```

## Hooks WordPress Disponibles

### Action: `malisafi_user_created`

Déclenché après la création complète d'un utilisateur.

```php
add_action('malisafi_user_created', function($user_id, $role, $account_type) {
    // Log, analytics, webhooks, etc.
}, 10, 3);
```

## Validation des Données

### Champs Obligatoires (tous utilisateurs)

- `username` - Non vide, unique
- `email` - Format email valide, unique
- `password` - Minimum 8 caractères
- `first_name` - Non vide
- `last_name` - Non vide
- `phone` - Non vide

### Champs Obligatoires (agents uniquement)

- `agency_name`
- `license_number`
- `years_experience`
- `agent_county` (doit être un comté du Kenya)
- `business_address`
- `city`
- `specializations` (au moins une)
- `agent_bio` (minimum 100 caractères)
- `national_id`

## Notifications

### Email de Bienvenue

Envoyé automatiquement après création (géré par `class-registration-handler.php`).

### Notification Admin (Nouveaux Agents)

Email envoyé à l'admin quand un agent s'inscrit:
- Nom complet
- Email
- User ID
- Lien vers page de gestion des agents

## Améliorations vs. Ancien Système

### ✅ Ce qui a été ajouté

1. **Création automatique subscription** - Plus de données manquantes
2. **Création automatique user_limits** - Limites définies dès l'inscription
3. **Cohérence frontend/backend** - Même logique partout
4. **Validation centralisée** - Pas de duplication
5. **Post type agent automatique** - Profile complet créé
6. **Gestion erreurs améliorée** - WP_Error retourné

### 🔄 Migrations Nécessaires

**Utilisateurs existants sans enregistrement limits/subscription**:

```php
// Script à exécuter une fois
function malisafi_migrate_existing_users() {
    $users = get_users(array('role__in' => array(
        'malisafi_client',
        'malisafi_agent_basic',
        'malisafi_agent_premium',
        'malisafi_owner',
        'malisafi_developer'
    )));
    
    foreach ($users as $user) {
        // Créer subscription si manquante
        $subscription = User_Creation_Helper::get_user_subscription($user->ID);
        if (!$subscription) {
            // Créer manuellement via helper
        }
        
        // Créer limits si manquantes
        $limits = User_Creation_Helper::get_user_limits($user->ID);
        if (!$limits) {
            // Créer manuellement via helper
        }
    }
}
```

## Dépannage

### Problème: "Username already exists"

**Cause**: Validation côté serveur
**Solution**: Vérifier l'unicité avant soumission (AJAX)

### Problème: "Email already exists"

**Cause**: Email déjà utilisé
**Solution**: Utiliser AJAX `malisafi_check_email` avant soumission

### Problème: Subscription non créée

**Cause**: Rôle non payant OU erreur SQL
**Solution**: Vérifier que les tables existent (`fix-tables.php`)

### Problème: User limits = null

**Cause**: Table `wp_mf_user_limits` manquante
**Solution**: Exécuter `MalisafiMLS\Database::create_tables()`

## Tests Recommandés

1. **Inscription client frontend** → Vérifier wp_mf_user_limits créé
2. **Inscription agent frontend** → Vérifier subscription + agent post créé
3. **Création utilisateur admin** → Vérifier tables créées
4. **Upgrade de plan** → Vérifier mise à jour subscription + limits

---

**Dernière mise à jour**: 24 décembre 2025  
**Version**: 1.0.0  
**Fichiers modifiés**: 4
- `includes/class-user-creation-helper.php` (nouveau)
- `includes/class-registration-handler.php`
- `admin/class-user-manager.php`
- `includes/class-core.php`
