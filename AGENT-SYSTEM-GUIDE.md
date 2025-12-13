# Agent Profile & Dashboard System

## Vue d'ensemble

Le système de profil et tableau de bord des agents MalisafiMLS offre une interface complète pour la gestion des agents immobiliers, avec des fonctionnalités de tableau de bord personnalisé, de gestion de profil, et un système de visualisation "Vue en tant qu'agent" pour les administrateurs.

---

## Table des matières

1. [Type de Post Personnalisé Agent](#type-de-post-personnalisé-agent)
2. [Tableau de Bord Agent](#tableau-de-bord-agent)
3. [Profil Agent](#profil-agent)
4. [Leads & Enquêtes](#leads--enquêtes)
5. [Liaison Propriété-Agent](#liaison-propriété-agent)
6. [Fonctionnalité Admin "Vue en tant qu'agent"](#fonctionnalité-admin-vue-en-tant-quagent)
7. [Guide Développeur](#guide-développeur)

---

## Type de Post Personnalisé Agent

### Enregistrement

Le CPT `malisafi_agent` est enregistré avec :
- **Slug** : `agent`
- **Hiérarchique** : Non
- **Public** : Oui
- **Menu** : Sous "Malisafi Dashboard"
- **Supports** : title, editor, thumbnail, excerpt
- **Taxonomie** : `malisafi_agent_specialty` (non-hiérarchique)

### Métaboxes

#### 1. Contact Information
- Email (requis) *
- Office Phone
- Mobile Phone (requis) *
- WhatsApp Number
- Office Address
- Website

#### 2. Professional Information
- License Number
- Agency Name
- Years of Experience
- Languages Spoken
- Service Areas
- Commission Rate (%)

#### 3. Social Media
- Facebook
- Twitter
- LinkedIn
- Instagram
- YouTube

#### 4. Agent Settings
- **Agent Status** : active, inactive, on_vacation, suspended
- **Featured Agent** : Checkbox
- **Linked WordPress User** : Dropdown des utilisateurs avec rôles agent

#### 5. Statistics (Read-only)
- Total Properties
- Active Listings
- Total Views
- Total Leads

---

## Tableau de Bord Agent

### Accès

Le tableau de bord agent est accessible pour :
- **Agents Basic** (`malisafi_agent_basic`)
- **Agents Premium** (`malisafi_agent_premium`)
- **Administrateurs** (avec possibilité de voir en tant qu'agent)

### Menu Principal

```
My Dashboard (malisafi-agent-dashboard)
├── My Properties (edit.php?post_type=malisafi_property&agent_filter=mine)
├── Add Property (post-new.php?post_type=malisafi_property)
├── My Profile (malisafi-agent-profile)
└── Leads & Inquiries (malisafi-agent-leads)
```

### Statistiques Affichées

1. **Total Properties** - Toutes les propriétés assignées
2. **Active Listings** - Propriétés publiées
3. **Pending Approval** - Propriétés en attente de modération
4. **Total Views** - Vues cumulées sur toutes les propriétés

### Sections du Dashboard

#### 1. Contact Information
Affiche email, mobile, WhatsApp avec liens cliquables

#### 2. Recent Properties
Tableau des 5 dernières propriétés avec :
- Titre
- Statut (Published, Pending, Draft)
- Date
- Actions (Edit, View)

#### 3. Quick Actions
Boutons d'accès rapide :
- Add Property
- View Leads
- Edit Profile
- Pending Properties

---

## Profil Agent

### Vue d'ensemble

Page de profil complète affichant :

#### Header du Profil
- Photo de profil (featured image)
- Nom de l'agent
- Nom de l'agence
- Numéro de licence
- Badge de statut (Active/Inactive/On Vacation/Suspended)
- Badge "Featured Agent" si applicable

#### À propos de moi
Contenu de l'éditeur principal du post

#### Informations de Contact
Tableau avec tous les détails de contact

#### Informations Professionnelles
- Années d'expérience
- Langues parlées
- Zones de service
- Spécialités (termes de taxonomie)

#### Réseaux Sociaux
Liens stylisés vers tous les profils sociaux

---

## Leads & Enquêtes

### Affichage

Tableau des leads avec :
- Date & Heure
- Nom du prospect
- Contact (Email, Téléphone)
- Propriété concernée (lien vers l'édition)
- Message (tronqué)
- Statut (New, Contacted, Closed)

### Statistiques des Leads
- **Total Leads**
- **New** - Leads non contactés
- **Contacted** - Leads contactés
- **Closed** - Leads fermés (convertis ou perdus)

### Source des Données

Les leads proviennent de la table `wp_mf_leads` et sont filtrés par :
```sql
WHERE pm.meta_key = '_property_agent_id' 
AND pm.meta_value = {agent_id}
```

---

## Liaison Propriété-Agent

### Métabox "Agent Information"

Ajoutée à toutes les propriétés avec 2 options :

#### Option 1 : Agent Profile Link (Recommandé)
Dropdown des agents disponibles :
```php
<select name="property_agent_id">
    <option value="">Select Agent Profile...</option>
    <?php foreach ($agents as $agent): ?>
        <option value="<?php echo $agent->ID; ?>">
            <?php echo $agent->post_title; ?>
        </option>
    <?php endforeach; ?>
</select>
```

Stocké comme : `_property_agent_id` (post meta)

#### Option 2 : Manual Entry (Legacy)
Champs texte pour :
- Agent Name (`_malisafi_agent_name`)
- Agent Email (`_malisafi_agent_email`)
- Agent Phone (`_malisafi_agent_phone`)

### Requêtes

Récupérer les propriétés d'un agent :
```php
global $wpdb;
$properties = $wpdb->get_results($wpdb->prepare(
    "SELECT p.* FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE pm.meta_key = '_property_agent_id'
    AND pm.meta_value = %d
    AND p.post_type = 'malisafi_property'
    AND p.post_status = 'publish'",
    $agent_id
));
```

---

## Fonctionnalité Admin "Vue en tant qu'agent"

### Description

Permet aux administrateurs de voir le dashboard depuis la perspective de n'importe quel agent pour :
- Support technique
- Vérification de données
- Formation
- Monitoring

### Utilisation

1. **Accéder au Dashboard Agent** (même pour admin)
2. **Voir la section "Admin Tools"** en bas de page
3. **Sélectionner un agent** dans le dropdown
4. **Cliquer "Switch View"**
5. Le dashboard se recharge avec les données de l'agent sélectionné

### Indicateur Visuel

Quand un admin est en mode "vue agent", une notice bleue apparaît :
```
Admin View: You are viewing the dashboard as [Agent Name] | Exit Agent View
```

### Stockage

L'agent actuellement visualisé est stocké dans :
```php
update_user_meta(get_current_user_id(), '_viewing_as_agent_id', $agent_id);
```

### Sortie du Mode

- Cliquer sur "Exit Agent View"
- Ou sélectionner un autre agent
- La meta est supprimée :
```php
delete_user_meta(get_current_user_id(), '_viewing_as_agent_id');
```

### Sécurité

Vérifications :
1. `current_user_can('manage_options')` - Seuls les admins
2. Nonce verification pour toutes les actions AJAX
3. Les agents normaux ne voient jamais cette fonctionnalité

---

## Guide Développeur

### Fichiers Créés/Modifiés

```
includes/
├── class-agent-post-type.php         (NOUVEAU)
└── class-post-types.php               (MODIFIÉ - Agent metabox)

admin/
├── class-agent-dashboard.php          (NOUVEAU)
└── templates/
    ├── agent-dashboard.php            (NOUVEAU)
    ├── agent-profile.php              (NOUVEAU)
    └── agent-leads.php                (NOUVEAU)

assets/
├── css/
│   └── agent-dashboard.css            (NOUVEAU)
└── js/
    └── agent-dashboard.js             (NOUVEAU)

malisafi-mls.php                       (MODIFIÉ - Agent system init)
```

### Hooks Disponibles

#### Actions
```php
// Après l'enregistrement du CPT agent
do_action('malisafi_agent_registered');

// Avant l'affichage du dashboard agent
do_action('malisafi_before_agent_dashboard', $agent_id);

// Après l'affichage du dashboard agent
do_action('malisafi_after_agent_dashboard', $agent_id);

// Quand un agent est assigné à une propriété
do_action('malisafi_property_agent_assigned', $property_id, $agent_id);
```

#### Filtres
```php
// Modifier les statistiques affichées
$stats = apply_filters('malisafi_agent_stats', $stats, $agent_id);

// Modifier les colonnes du tableau des agents
$columns = apply_filters('malisafi_agent_columns', $columns);

// Modifier la requête des propriétés d'un agent
$properties = apply_filters('malisafi_agent_properties', $properties, $agent_id);
```

### Fonctions Utiles

#### Obtenir l'ID de l'agent actuel
```php
$agent_id = Malisafi_Agent_Dashboard::get_current_agent_id();
```

#### Vérifier si l'utilisateur est un agent
```php
$user = wp_get_current_user();
$is_agent = in_array('malisafi_agent_basic', $user->roles) || 
            in_array('malisafi_agent_premium', $user->roles);
```

#### Obtenir toutes les propriétés d'un agent
```php
$args = array(
    'post_type' => 'malisafi_property',
    'meta_query' => array(
        array(
            'key' => '_property_agent_id',
            'value' => $agent_id,
            'compare' => '='
        )
    ),
    'posts_per_page' => -1
);
$properties = get_posts($args);
```

#### Obtenir les infos d'un agent par User ID
```php
$args = array(
    'post_type' => 'malisafi_agent',
    'meta_query' => array(
        array(
            'key' => '_agent_user_id',
            'value' => $user_id,
            'compare' => '='
        )
    ),
    'posts_per_page' => 1
);
$agent = get_posts($args);
```

### Colonnes Personnalisées

Tableau des agents dans l'admin :

| Colonne | Description |
|---------|-------------|
| Photo | Thumbnail 50x50 ou icône par défaut |
| Title | Nom de l'agent |
| Contact | Email + Mobile |
| Properties | Nombre de propriétés assignées |
| Status | Statut avec couleur + Badge Featured |
| Date | Date de création |

### Statuts d'Agent

```php
$statuses = array(
    'active'       => '● Active' (vert),
    'inactive'     => '● Inactive' (gris),
    'on_vacation'  => '● On Vacation' (orange),
    'suspended'    => '● Suspended' (rouge)
);
```

### Classes CSS Principales

```css
.malisafi-agent-dashboard      /* Container principal */
.malisafi-stats-grid           /* Grille des statistiques */
.stat-card                     /* Carte de statistique */
.agent-status-badge            /* Badge de statut */
.malisafi-card                 /* Carte de contenu */
.quick-actions-grid            /* Grille d'actions rapides */
.quick-action-btn              /* Bouton d'action */
.profile-header                /* Header du profil */
.profile-photo                 /* Photo de profil */
.profile-table                 /* Tableau d'informations */
.social-media-links            /* Liens sociaux */
.admin-tools-card              /* Carte outils admin */
```

### AJAX Endpoints

#### switch_agent_view
```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'switch_agent_view',
        nonce: nonce,
        agent_id: agentId
    }
});
```

#### update_lead_status (À implémenter)
```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'update_lead_status',
        nonce: nonce,
        lead_id: leadId,
        status: newStatus
    }
});
```

---

## Améliorations Futures

### Phase 1 (Court terme)
- [ ] Filtrage avancé des propriétés dans le dashboard
- [ ] Graphiques de performance (Chart.js)
- [ ] Export des données en CSV/PDF
- [ ] Notifications en temps réel pour les nouveaux leads
- [ ] Calendrier de rendez-vous intégré

### Phase 2 (Moyen terme)
- [ ] Système de commission automatique
- [ ] Territoires géographiques assignés
- [ ] Leaderboard des agents
- [ ] Intégration CRM (Salesforce, HubSpot)
- [ ] Application mobile pour agents

### Phase 3 (Long terme)
- [ ] IA pour scoring de leads
- [ ] Recommandations automatiques de prix
- [ ] Chatbot pour agents
- [ ] Analyses prédictives
- [ ] Intégration VR/360° tours

---

## Support & Contact

Pour toute question ou assistance :
- **Documentation** : [docs.malisafi.com](https://docs.malisafi.com)
- **Support** : support@malisafi.com
- **GitHub** : [github.com/malisafi/malisafi-mls](https://github.com/malisafi/malisafi-mls)

---

## Changelog

### Version 1.0.0 (2 décembre 2025)
- ✅ Type de post personnalisé Agent
- ✅ Dashboard agent complet
- ✅ Profil agent détaillé
- ✅ Gestion des leads
- ✅ Liaison propriété-agent
- ✅ Fonctionnalité admin "Vue en tant qu'agent"
- ✅ Responsive design
- ✅ CSS & JS optimisés

---

**Développé avec ❤️ par l'équipe Malisafi**
