# Malisafi MLS - Property Submission System

**Date**: 26 novembre 2025  
**Version**: 1.0.0

## Vue d'Ensemble

Le système de soumission de propriétés permet aux administrateurs, modérateurs, agents, propriétaires et développeurs d'ajouter, modifier et gérer des propriétés immobilières directement depuis le tableau de bord WordPress avec workflow de modération automatique.

## Fichiers Créés

### 1. admin/class-property-submit.php (406 lignes)
**Classe**: `Malisafi_Property_Submit`

**Responsabilités**:
- Traitement des soumissions de propriétés
- Validation et sanitization des données
- Gestion du statut de publication selon le rôle
- Upload et gestion d'images
- Sauvegarde des métadonnées et taxonomies

**Méthodes Publiques**:
```php
init()                          // Initialisation et hooks
handle_property_submission()    // Traitement soumission POST
ajax_upload_image()            // Upload AJAX d'images
ajax_delete_image()            // Suppression AJAX d'images
```

**Méthodes Privées**:
```php
can_submit_property($user)                    // Vérification permissions
get_post_status_for_user($user)              // Statut selon rôle
sanitize_property_data($data)                 // Nettoyage données
validate_property_data($data)                 // Validation
create_property($data, $status)              // Création propriété
update_property($id, $data, $status)         // Mise à jour
save_property_meta($id, $data)               // Sauvegarde meta
save_property_taxonomies($id, $data)         // Sauvegarde taxonomies
attach_images($id, $image_ids)               // Attachement images
redirect_with_error($message)                 // Redirection erreur
redirect_with_success($id, $status)          // Redirection succès
```

### 2. admin/templates/properties-list.php (540+ lignes)
**Vues**: Liste + Formulaire Add/Edit

**Sections Liste**:
- Filtres par statut (Published/Pending/Draft)
- Tableau avec 10 colonnes
- Pagination (20 propriétés/page)
- Actions Edit/View

**Sections Formulaire**:
- Informations de base (titre, description, excerpt)
- Détails propriété (type, statut, prix, chambres, etc.)
- Localisation (adresse complète + coordonnées)
- Médias (images multiples, vidéo, visite virtuelle)
- Informations agent/contact

### 3. Templates Partiels (property-form-parts/)

#### location.php
- Adresse complète (rue, ville, état, ZIP, pays)
- Catégorie de localisation (taxonomie)
- Coordonnées GPS (latitude/longitude)

#### media.php
- Upload multiple d'images avec preview
- Gestion des features (checkboxes)
- URL vidéo (YouTube/Vimeo)
- URL visite virtuelle (360°/Matterport)
- Interface drag-and-drop

#### agent.php
- Nom de l'agent/contact
- Email de contact
- Téléphone
- Pré-rempli avec données utilisateur

## Workflow de Soumission

### 1. Permissions par Rôle

| Rôle | Peut Soumettre | Statut Initial | Peut Modifier Tout |
|------|----------------|----------------|-------------------|
| Administrator | ✅ | `publish` | ✅ |
| Malisafi Moderator | ✅ | `publish` | ✅ |
| Agent Premium | ✅ | `publish` | ❌ (seulement ses propriétés) |
| Agent Basic | ✅ | `pending` | ❌ (seulement ses propriétés) |
| Property Owner | ✅ | `pending` | ❌ (seulement ses propriétés) |
| Developer | ✅ | `pending` | ❌ (seulement ses propriétés) |
| Client | ❌ | N/A | ❌ |

### 2. Statuts de Publication

**`publish`** (Publié)
- Visible publiquement immédiatement
- Attribué automatiquement: Admin, Moderator, Agent Premium
- Apparaît dans recherches et listings

**`pending`** (En attente)
- Nécessite approbation modérateur
- Attribué automatiquement: Agent Basic, Owner, Developer
- Visible seulement dans backend admin

**`draft`** (Brouillon)
- Enregistré mais incomplet
- Non visible publiquement
- Peut être édité et soumis plus tard

### 3. Flux de Travail

```
┌─────────────────────────────────────────┐
│ Utilisateur remplit formulaire         │
│ admin.php?page=malisafi-properties      │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ Validation côté serveur                │
│ - Champs obligatoires                  │
│ - Format des données                   │
│ - Permissions utilisateur              │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ Détermination statut selon rôle        │
│ Admin/Moderator/Premium → publish      │
│ Autres → pending                       │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ Création post type malisafi_property   │
│ + Sauvegarde metadata                  │
│ + Assignation taxonomies               │
│ + Attachement images                   │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ Redirection avec message succès        │
│ - "Published" ou "Pending Review"      │
└─────────────────────────────────────────┘
```

## Champs de Formulaire

### Champs Obligatoires (*)

| Champ | Type | Validation | Meta Key |
|-------|------|------------|----------|
| Property Title * | text | Non vide | post_title |
| Description * | wysiwyg | Non vide | post_content |
| Price * | number | > 0 | _malisafi_price |
| Address * | text | Non vide | _malisafi_address |
| Property Type * | select | Taxonomy | malisafi_property_type |
| Property Status * | select | Taxonomy | malisafi_property_status |

### Détails Propriété (Optionnels)

| Champ | Type | Meta Key |
|-------|------|----------|
| Price Suffix | text | _malisafi_price_suffix |
| Bedrooms | number | _malisafi_bedrooms |
| Bathrooms | number (0.5 step) | _malisafi_bathrooms |
| Area (sq ft) | number | _malisafi_area |
| Lot Size (sq ft) | number | _malisafi_lot_size |
| Year Built | number (1800-current+5) | _malisafi_year_built |
| Garage Spaces | number | _malisafi_garage |

### Localisation

| Champ | Type | Meta Key |
|-------|------|----------|
| City | text | _malisafi_city |
| State/Province | text | _malisafi_state |
| ZIP/Postal Code | text | _malisafi_zip |
| Country | text | _malisafi_country |
| Location Category | select | malisafi_property_location (taxonomy) |
| Latitude | text | _malisafi_latitude |
| Longitude | text | _malisafi_longitude |

### Médias

| Champ | Type | Storage |
|-------|------|---------|
| Property Images | multiple | Attachments (post_parent) |
| Features | checkboxes | malisafi_property_features (taxonomy) |
| Video URL | url | _malisafi_video_url |
| Virtual Tour URL | url | _malisafi_virtual_tour |

### Agent/Contact

| Champ | Type | Meta Key | Défaut |
|-------|------|----------|--------|
| Agent Name | text | _malisafi_agent_name | current_user->display_name |
| Agent Email | email | _malisafi_agent_email | current_user->user_email |
| Agent Phone | tel | _malisafi_agent_phone | user_meta 'phone' |

## Validation

### Règles Serveur

```php
// Champs obligatoires
- title: Non vide
- description: Non vide
- price: > 0 (numérique)
- address: Non vide
- property_type: Au moins 1 sélectionné
- property_status: Au moins 1 sélectionné

// Format
- email: Validé via is_email()
- url: Validé via esc_url_raw()
- numbers: Convertis avec intval()/floatval()
```

### Messages d'Erreur

```php
'Property title is required.'
'Property description is required.'
'Valid property price is required.'
'Property address is required.'
'Property type is required.'
'Property status is required.'
```

## Gestion des Images

### Upload Multiple

**Mécanisme**:
1. Utilise WordPress Media Uploader (wp.media)
2. Sélection multiple d'images
3. Preview immédiat en grille
4. IDs stockés dans champ hidden

**Code JavaScript**:
```javascript
var mediaUploader = wp.media({
    title: 'Select Property Images',
    button: { text: 'Add Images' },
    multiple: true
});

mediaUploader.on('select', function() {
    var attachments = mediaUploader.state().get('selection').toJSON();
    // Ajouter à la grille
});
```

### Attachement au Post

```php
// Mise à jour post_parent pour chaque image
wp_update_post(array(
    'ID' => $image_id,
    'post_parent' => $property_id
));

// Première image = featured image
set_post_thumbnail($property_id, $image_ids[0]);
```

### Suppression

- Bouton "×" sur chaque thumbnail
- Suppression immédiate du DOM
- Suppression de la liste d'IDs
- Peut être supprimée définitivement via AJAX

## Interface Utilisateur

### Page Liste des Propriétés

**URL**: `admin.php?page=malisafi-properties`

**Éléments**:
- Bouton "Add New Property" (en-tête)
- Filtre par statut (Published/Pending/Draft)
- Tableau responsive 10 colonnes
- Pagination avec navigation
- Actions par ligne: Edit | View

**Colonnes**:
1. Checkbox (sélection multiple)
2. Image (thumbnail 60x60px)
3. Titre (lien vers édition)
4. Prix (formaté $X,XXX)
5. Type (première taxonomie)
6. Localisation (ville)
7. Statut (badge coloré)
8. Auteur
9. Date (M j, Y)
10. Actions (boutons)

### Formulaire Add/Edit

**URL Add**: `admin.php?page=malisafi-properties&action=add`  
**URL Edit**: `admin.php?page=malisafi-properties&action=edit&property_id=X`

**Sections** (postbox):
1. Basic Information
2. Property Details
3. Location
4. Property Images & Media
5. Agent/Contact Information

**Boutons**:
- **Submit Property** / **Update Property** (primary)
- **Cancel** (retour liste)

### Badges de Statut

```css
Published → Vert (#00a32a)
Pending → Jaune (#dba617)
Draft → Gris (#8c8f94)
```

## Sécurité

### Vérifications

**Nonce**:
```php
wp_nonce_field('malisafi_submit_property', 'malisafi_property_nonce');
check_admin_referer('malisafi_submit_property', 'malisafi_property_nonce');
```

**Capacités**:
```php
// Soumission
can_submit_property() // Vérifie rôles autorisés

// Édition
- Moderators: peuvent tout éditer
- Autres: seulement leurs propriétés (post_author check)
```

**Sanitization**:
```php
sanitize_text_field()    // Texte général
sanitize_email()         // Emails
sanitize_textarea_field() // Textarea
wp_kses_post()           // HTML description
esc_url_raw()            // URLs
floatval() / intval()    // Nombres
array_map('intval')      // Tableaux d'IDs
```

### Protection CSRF

- Tous les formulaires incluent nonce WordPress
- Actions POST vérifiées avant traitement
- Redirections après traitement (PRG pattern)

## Intégration Base de Données

### Post Type: malisafi_property

**Table**: `wp_posts`
```sql
post_type = 'malisafi_property'
post_status IN ('publish', 'pending', 'draft')
post_author = {user_id}
```

### Meta Data

**Table**: `wp_postmeta`
```sql
_malisafi_price           DECIMAL(10,2)
_malisafi_price_suffix    VARCHAR(50)
_malisafi_bedrooms        INT
_malisafi_bathrooms       DECIMAL(3,1)
_malisafi_area            DECIMAL(10,2)
_malisafi_lot_size        DECIMAL(10,2)
_malisafi_year_built      INT
_malisafi_garage          INT
_malisafi_address         VARCHAR(255)
_malisafi_city            VARCHAR(100)
_malisafi_state           VARCHAR(100)
_malisafi_zip             VARCHAR(20)
_malisafi_country         VARCHAR(100)
_malisafi_latitude        VARCHAR(20)
_malisafi_longitude       VARCHAR(20)
_malisafi_agent_name      VARCHAR(100)
_malisafi_agent_email     VARCHAR(100)
_malisafi_agent_phone     VARCHAR(20)
_malisafi_video_url       VARCHAR(255)
_malisafi_virtual_tour    VARCHAR(255)
```

### Taxonomies

**Tables**: `wp_terms`, `wp_term_taxonomy`, `wp_term_relationships`

```
malisafi_property_type      // Hierarchical (Maison, Appartement, etc.)
malisafi_property_status    // Hierarchical (À vendre, À louer, etc.)
malisafi_property_location  // Hierarchical (Centre-ville, Banlieue, etc.)
malisafi_property_features  // Non-hierarchical (Piscine, Jardin, etc.)
```

## Messages et Notifications

### Messages de Succès

```php
'property_published' → "Property published successfully."
'property_pending'   → "Property submitted and pending review."
'property_updated'   → "Property updated successfully."
'property_deleted'   → "Property deleted successfully."
```

### Affichage

```php
// Succès (vert)
<div class="notice notice-success is-dismissible">
    <p>{message}</p>
</div>

// Erreur (rouge)
<div class="notice notice-error is-dismissible">
    <p>{error_message}</p>
</div>
```

## Hooks et Actions

### Actions WordPress

```php
// POST handlers
add_action('admin_post_malisafi_submit_property', 
    ['Malisafi_Property_Submit', 'handle_property_submission']);

// AJAX
add_action('wp_ajax_malisafi_upload_property_image', 
    ['Malisafi_Property_Submit', 'ajax_upload_image']);
    
add_action('wp_ajax_malisafi_delete_property_image', 
    ['Malisafi_Property_Submit', 'ajax_delete_image']);
```

### Filtres Personnalisables (Future)

```php
// Modifier statut par défaut
apply_filters('malisafi_default_property_status', $status, $user);

// Modifier champs obligatoires
apply_filters('malisafi_required_property_fields', $fields);

// Notification email
do_action('malisafi_property_submitted', $property_id, $status);
```

## Tests Recommandés

### Tests Fonctionnels

- [ ] **Admin**: Créer propriété → statut `publish` immédiat
- [ ] **Moderator**: Créer propriété → statut `publish` immédiat
- [ ] **Agent Premium**: Créer propriété → statut `publish` immédiat
- [ ] **Agent Basic**: Créer propriété → statut `pending`
- [ ] **Owner**: Créer propriété → statut `pending`
- [ ] **Developer**: Créer propriété → statut `pending`
- [ ] **Client**: Pas d'accès au formulaire

### Tests d'Édition

- [ ] Admin peut éditer toutes propriétés
- [ ] Moderator peut éditer toutes propriétés
- [ ] Agent ne peut éditer que ses propres propriétés
- [ ] Impossible d'éditer propriété d'un autre utilisateur

### Tests de Validation

- [ ] Soumission sans titre → erreur
- [ ] Soumission sans description → erreur
- [ ] Soumission sans prix → erreur
- [ ] Soumission sans adresse → erreur
- [ ] Soumission sans type → erreur
- [ ] Soumission sans statut → erreur
- [ ] Prix négatif → erreur

### Tests d'Images

- [ ] Upload image unique → succès
- [ ] Upload multiples images → succès
- [ ] Première image devient featured image
- [ ] Suppression d'image depuis grille
- [ ] Images attachées au bon post_parent

### Tests d'Interface

- [ ] Liste affiche propriétés avec pagination
- [ ] Filtres par statut fonctionnent
- [ ] Messages de succès/erreur s'affichent
- [ ] Formulaire pré-rempli en mode édition
- [ ] Bouton Cancel redirige vers liste

## Prochaines Améliorations

### Phase 1: UX (Priorité Haute)

- [ ] **Drag & Drop images**: Réorganiser ordre des photos
- [ ] **Preview en temps réel**: Aperçu propriété avant soumission
- [ ] **Autosave**: Sauvegarde automatique brouillon
- [ ] **Validation AJAX**: Vérification champs en temps réel
- [ ] **Progress indicator**: Barre de progression formulaire

### Phase 2: Fonctionnalités (Priorité Moyenne)

- [ ] **Duplicate property**: Cloner propriété existante
- [ ] **Bulk actions**: Actions groupées (supprimer, changer statut)
- [ ] **Import CSV**: Import en masse depuis fichier
- [ ] **Export**: Export propriétés en CSV/PDF
- [ ] **Templates**: Modèles prédéfinis par type

### Phase 3: Modération (Priorité Haute)

- [ ] **Queue modération**: Interface dédiée pending review
- [ ] **Quick edit**: Édition rapide depuis liste
- [ ] **Commentaires internes**: Notes modérateurs
- [ ] **Historique modifications**: Log des changements
- [ ] **Notifications email**: Alertes modérateurs

### Phase 4: Avancé (Priorité Basse)

- [ ] **Geocoding automatique**: Coordonnées GPS depuis adresse
- [ ] **Cartes interactives**: Sélection localisation sur carte
- [ ] **Galerie avancée**: Lightbox, zoom, captions
- [ ] **Comparaison propriétés**: Feature comparison
- [ ] **Analytics propriété**: Vues, favoris, demandes

## Documentation Associée

- **ROLES.md** - Système de rôles et permissions
- **USER-MANAGEMENT.md** - Gestion des utilisateurs
- **DASHBOARD-SEPARATION.md** - Architecture admin
- **STATUS.md** - État général du plugin

## Notes Techniques

### Performance

- Pas de cache implémenté (considérer pour >10k propriétés)
- Requêtes optimisées avec `posts_per_page` limité
- Images uploadées via WordPress media library (optimisation native)

### Compatibilité

- WordPress 5.0+ (required for wp.media API)
- PHP 7.2+ (null coalescing, type hints)
- Gutenberg compatible (show_in_rest => true)

### Multisite

Non testé en mode multisite. Adaptations possibles:
- Partage propriétés entre sites
- Modération centralisée
- Media library par site

---

**Dernière mise à jour**: 26 novembre 2025  
**Auteur**: GitHub Copilot  
**Version Plugin**: 1.0.0
