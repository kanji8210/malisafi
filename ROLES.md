# Malisafi MLS - Rôles et Capabilities

## Rôles Personnalisés

Le plugin Malisafi MLS crée 7 rôles personnalisés pour gérer l'accès et les permissions des utilisateurs.

### 1. Client (malisafi_client)
**Description**: Utilisateur de base qui peut rechercher et consulter des propriétés.

**Capabilities**:
- `read` - Lire le contenu public

**Usage**: Pour les visiteurs enregistrés qui souhaitent sauvegarder des recherches, ajouter des favoris, etc.

---

### 2. Agent Basic (malisafi_agent_basic)
**Description**: Agent immobilier avec accès limité.

**Capabilities de base**:
- `read` - Lire le contenu
- `upload_files` - Télécharger des médias
- `edit_posts` - Éditer les publications de base

**Capabilities propriétés**:
- `edit_properties` - Éditer ses propres propriétés
- `edit_published_properties` - Éditer ses propriétés publiées
- `delete_properties` - Supprimer ses propriétés
- `access_malisafi_dashboard` - Accès au tableau de bord Malisafi
- `view_property_analytics` - Voir les statistiques de ses propriétés

**Limitations**:
- ❌ Ne peut PAS publier directement (`publish_properties` = false)
- ❌ Ne peut PAS éditer les propriétés d'autres utilisateurs
- Les propriétés doivent passer par la modération

---

### 3. Agent Premium (malisafi_agent_premium)
**Description**: Agent immobilier avec accès premium et fonctionnalités avancées.

**Capabilities de base**:
- `read` - Lire le contenu
- `upload_files` - Télécharger des médias
- `edit_posts` - Éditer les publications

**Capabilities propriétés**:
- `edit_properties` - Éditer ses propres propriétés
- `edit_published_properties` - Éditer ses propriétés publiées
- `delete_properties` - Supprimer ses propriétés
- `access_malisafi_dashboard` - Accès au tableau de bord
- `view_property_analytics` - Voir les statistiques

**Capabilities Premium**:
- ✅ `feature_properties` - Mettre en vedette des propriétés
- ✅ `boost_listings` - Booster la visibilité des annonces
- ✅ `advanced_analytics` - Accès aux statistiques avancées

**Avantages**:
- Publication directe sans modération
- Mise en vedette des propriétés
- Statistiques avancées

---

### 4. Real Estate Agency (malisafi_agency)
**Description**: Agence immobilière qui gère plusieurs agents et propriétés sous une marque commune.

**Capabilities de base**:
- `read` - Lire le contenu
- `upload_files` - Télécharger des médias
- `edit_posts` - Éditer les publications
- `edit_users` - Gérer les utilisateurs de l'agence

**Capabilities propriétés**:
- `edit_properties` - Éditer les propriétés de l'agence
- `edit_published_properties` - Éditer les propriétés publiées
- `delete_properties` - Supprimer les propriétés
- `access_malisafi_dashboard` - Accès au tableau de bord
- `view_property_analytics` - Voir les statistiques

**Capabilities d'agence**:
- ✅ `manage_agency_agents` - Gérer les agents de l'agence
- ✅ `bulk_manage_properties` - Gestion en masse des propriétés
- ✅ `agency_analytics` - Statistiques au niveau de l'agence
- ✅ `feature_properties` - Mettre en vedette des propriétés
- ✅ `boost_listings` - Booster la visibilité des annonces
- ✅ `advanced_analytics` - Accès aux statistiques avancées
- ✅ `create_agency_profile` - Créer un profil d'agence
- ✅ `manage_agency_listings` - Gérer les annonces de l'agence

**Fonctionnalités spéciales**:
- Gestion d'équipe d'agents
- Profil d'agence personnalisé
- Statistiques consolidées
- Gestion de marque commune
- Attribution des propriétés aux agents

---

### 5. Property Owner (malisafi_owner)
**Description**: Propriétaire de bien immobilier qui souhaite lister ses propriétés.

**Capabilities de base**:
- `read` - Lire le contenu
- `upload_files` - Télécharger des médias
- `edit_posts` - Éditer les publications

**Capabilities propriétés**:
- `edit_properties` - Éditer ses propres propriétés
- `edit_published_properties` - Éditer ses propriétés publiées
- `delete_properties` - Supprimer ses propriétés
- `access_malisafi_dashboard` - Accès au tableau de bord
- `view_property_analytics` - Voir les statistiques de ses propriétés

**Limitations**:
- ❌ Pas de publication directe
- ❌ Pas de fonctionnalités premium
- Propriétés soumises à modération

---

### 6. Developer (malisafi_developer)
**Description**: Promoteur immobilier avec capacité de gérer plusieurs propriétés et projets.

**Capabilities de base**:
- `read` - Lire le contenu
- `upload_files` - Télécharger des médias
- `edit_posts` - Éditer les publications

**Capabilities propriétés**:
- `edit_properties` - Éditer ses propres propriétés
- `edit_published_properties` - Éditer ses propriétés publiées
- `delete_properties` - Supprimer ses propriétés
- `access_malisafi_dashboard` - Accès au tableau de bord
- `view_property_analytics` - Voir les statistiques

**Fonctionnalités spéciales**:
- Capacité de gérer plusieurs projets
- Accès à l'import en masse de propriétés
- Gestion de projets de développement

---

### 7. Moderator (malisafi_moderator)
**Description**: Modérateur avec accès étendu pour gérer et approuver les propriétés.

**Capabilities de base**:
- `read` - Lire le contenu
- `moderate_comments` - Modérer les commentaires
- `upload_files` - Télécharger des médias
- `edit_posts` - Éditer les publications
- `edit_others_posts` - Éditer les publications d'autres utilisateurs
- `edit_published_posts` - Éditer les publications publiées

**Capabilities propriétés**:
- `edit_properties` - Éditer toutes les propriétés
- ✅ `edit_others_properties` - Éditer les propriétés d'autres utilisateurs
- ✅ `edit_published_properties` - Éditer les propriétés publiées
- ✅ `publish_properties` - Publier des propriétés
- `delete_properties` - Supprimer des propriétés
- `access_malisafi_dashboard` - Accès au tableau de bord
- `view_property_analytics` - Voir toutes les statistiques

**Capabilities de modération**:
- ✅ `moderate_properties` - Modérer et approuver les propriétés
- ✅ `manage_malisafi_settings` - Accès aux paramètres du plugin

**Responsabilités**:
- Approuver ou rejeter les propriétés en attente
- Gérer la qualité des annonces
- Modérer le contenu du site

---

### 8. Administrator (Rôle WordPress existant)
**Description**: Administrateur WordPress avec capacités Malisafi étendues.

**Capabilities héritées de WordPress**:
- Toutes les capabilities WordPress par défaut

**Capabilities Malisafi ajoutées**:
- ✅ `edit_others_properties` - Éditer toutes les propriétés
- ✅ `publish_properties` - Publier n'importe quelle propriété
- ✅ `moderate_properties` - Modération complète
- ✅ `manage_malisafi_settings` - Gestion complète des paramètres
- Accès complet à toutes les fonctionnalités

---

## Capabilities Personnalisées

### Capabilities de gestion des propriétés
- `edit_properties` - Éditer ses propres propriétés
- `edit_others_properties` - Éditer les propriétés d'autres utilisateurs
- `edit_published_properties` - Éditer les propriétés publiées
- `publish_properties` - Publier des propriétés (bypasser la modération)
- `delete_properties` - Supprimer des propriétés

### Capabilities de modération
- `moderate_properties` - Modérer et approuver les propriétés en attente

### Capabilities d'accès
- `access_malisafi_dashboard` - Accéder au tableau de bord Malisafi
- `manage_malisafi_settings` - Gérer les paramètres du plugin

### Capabilities d'analyse
- `view_property_analytics` - Voir les statistiques des propriétés
- `advanced_analytics` - Accès aux statistiques avancées (Premium)

### Capabilities Premium
- `feature_properties` - Mettre en vedette des propriétés
- `boost_listings` - Booster la visibilité des annonces

---

## Intégration avec WordPress

### Custom Post Type: malisafi_property
Le custom post type `malisafi_property` utilise les capabilities personnalisées:

```php
'capability_type' => array('property', 'properties'),
'map_meta_cap' => true,
```

Cela permet à WordPress de mapper automatiquement:
- `edit_post` → `edit_property`
- `delete_post` → `delete_property`
- `publish_posts` → `publish_properties`
- etc.

### Vérification des permissions

Dans le code, utilisez:

```php
// Vérifier si l'utilisateur peut éditer des propriétés
if (current_user_can('edit_properties')) {
    // Autoriser l'action
}

// Vérifier si l'utilisateur peut modérer
if (current_user_can('moderate_properties')) {
    // Afficher l'interface de modération
}

// Vérifier si l'utilisateur peut voir les analytics avancées
if (current_user_can('advanced_analytics')) {
    // Afficher les statistiques avancées
}
```

---

## Workflow de modération

1. **Agent Basic / Owner / Developer** crée une propriété
   - Statut: `pending` (En attente de modération)
   - Visible uniquement pour l'auteur et les modérateurs

2. **Moderator / Administrator** révise la propriété
   - Peut approuver (changer le statut à `publish`)
   - Peut rejeter ou demander des modifications

3. **Agent Premium** peut publier directement
   - Statut: `publish` immédiatement
   - Bypass la file de modération

---

## Gestion des rôles

### Création des rôles
Les rôles sont créés lors de l'activation du plugin via:
```php
Malisafi_Roles_Manager::create_roles();
```

### Ajout des capabilities
Les capabilities sont ajoutées via le hook `init`:
```php
Malisafi_Roles_Manager::init();
```

### Suppression des rôles
Les rôles peuvent être supprimés lors de la désactivation (optionnel, commenté par défaut pour préserver les données utilisateur).

---

## Notes importantes

1. **Préservation des données**: Par défaut, les rôles ne sont PAS supprimés lors de la désactivation du plugin pour préserver les utilisateurs existants.

2. **Upgrade safety**: Si vous ajoutez de nouvelles capabilities, assurez-vous de:
   - Mettre à jour la méthode `add_custom_capabilities()`
   - Tester sur un environnement de staging
   - Documenter les changements

3. **Compatibilité**: Les capabilities personnalisées sont compatibles avec les plugins de membership et de restriction de contenu.

4. **Sécurité**: Toujours vérifier les capabilities avant d'afficher du contenu sensible ou de permettre des actions.
