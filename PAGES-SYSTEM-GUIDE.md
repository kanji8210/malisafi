# 📄 PAGES MANAGEMENT SYSTEM - GUIDE COMPLET

**Dernière mise à jour:** 3 décembre 2025  
**Version:** 1.0.0

## 📋 Vue d'Ensemble

Le système de gestion des pages crée automatiquement 28 pages essentielles pour votre plateforme immobilière MalisafiMLS, chacune avec son shortcode pré-configuré.

---

## 🎯 Les 28 Pages Gérées

### 📌 Pages Publiques (5)

| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Properties** | `properties` | `[malisafi_properties]` | Liste complète des propriétés |
| **Property Search** | `property-search` | `[malisafi_property_search]` | Formulaire de recherche avancée |
| **Featured Properties** | `featured-properties` | `[malisafi_featured_properties]` | Propriétés en vedette |
| **Our Agents** | `agents` | `[malisafi_agents]` | Liste des agents immobiliers |
| **Pricing** | `pricing` | `[malisafi_pricing]` | Plans et tarifications |

### 👤 Client Dashboard (4)

| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Client Dashboard** | `client-dashboard` | `[malisafi_client_dashboard]` | Tableau de bord client |
| **My Favorites** | `client-favorites` | `[malisafi_favorites]` | Propriétés favorites |
| **Saved Searches** | `client-searches` | `[malisafi_saved_searches]` | Recherches sauvegardées |
| **My Inquiries** | `client-inquiries` | `[malisafi_client_inquiries]` | Demandes envoyées |

### 🏘️ Agent Dashboard (5)

| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Agent Dashboard** | `agent-dashboard` | `[malisafi_agent_dashboard]` | Tableau de bord agent (redirige vers backend) |
| **My Properties** | `agent-properties` | `[malisafi_agent_properties]` | Propriétés de l'agent |
| **Add Property** | `agent-add-property` | `[malisafi_property_submit]` | Ajouter une propriété |
| **My Leads** | `agent-leads` | `[malisafi_agent_leads]` | Prospects et contacts |
| **My Profile** | `agent-profile` | `[malisafi_agent_profile]` | Profil agent public |

### 🏠 Owner Dashboard (4)

| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Owner Dashboard** | `owner-dashboard` | `[malisafi_owner_dashboard]` | Tableau de bord propriétaire |
| **My Properties** | `owner-properties` | `[malisafi_owner_properties]` | Propriétés du propriétaire |
| **Add Property** | `owner-add-property` | `[malisafi_property_submit]` | Ajouter une propriété |
| **Inquiries** | `owner-inquiries` | `[malisafi_owner_inquiries]` | Demandes reçues |

### 🏗️ Developer Dashboard (4)

| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Developer Dashboard** | `developer-dashboard` | `[malisafi_developer_dashboard]` | Tableau de bord développeur |
| **My Projects** | `developer-projects` | `[malisafi_developer_projects]` | Projets de développement |
| **Add Project** | `developer-add-project` | `[malisafi_property_submit]` | Ajouter un projet |
| **Analytics** | `developer-analytics` | `[malisafi_developer_analytics]` | Statistiques et analyses |

### 🔐 Account Pages (3)

| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Login** | `login` | `[malisafi_login]` | Page de connexion |
| **Register** | `register` | `[malisafi_register]` | Page d'inscription |
| **My Account** | `my-account` | `[malisafi_account]` | Gestion du compte |

---

## 🚀 Comment Utiliser

### Accéder à la Gestion des Pages

1. Allez dans **WordPress Admin**
2. Menu **MalisafiMLS**
3. Cliquez sur **Pages Management**

### Créer Toutes les Pages

Cliquez sur le bouton **"Create All Missing Pages"** dans la section Quick Actions.

Le système va :
- ✅ Créer les 28 pages automatiquement
- ✅ Assigner les shortcodes correspondants
- ✅ Créer la hiérarchie parent-enfant
- ✅ Stocker les IDs dans les options WordPress

### Actions Individuelles

Pour chaque page, vous pouvez :
- **Edit** - Modifier le contenu de la page
- **View** - Voir la page en frontend
- **Recreate** - Supprimer et recréer la page
- **Create** - Créer une page manquante

---

## 🎨 Shortcodes Disponibles

### Client Dashboard Shortcodes

#### `[malisafi_client_dashboard]`
Affiche le tableau de bord complet du client avec :
- Statistiques (favoris, recherches, demandes)
- Actions rapides
- Activité récente
- **Requis:** Utilisateur connecté

#### `[malisafi_favorites]`
Liste toutes les propriétés favorites du client.
- Grille de propriétés
- Bouton pour parcourir si vide
- **Requis:** Utilisateur connecté

#### `[malisafi_saved_searches]`
Affiche les recherches sauvegardées.
- Critères de recherche
- Bouton "Run Search"
- Option de suppression
- **Requis:** Utilisateur connecté

#### `[malisafi_client_inquiries]`
Tableau des demandes envoyées.
- Statut des demandes
- Détails de chaque demande
- **Requis:** Utilisateur connecté

### Owner Dashboard Shortcodes

#### `[malisafi_owner_dashboard]`
Tableau de bord propriétaire avec :
- Nombre de propriétés
- Demandes reçues
- Vues totales
- Actions rapides
- **Requis:** Rôle `owner`

#### `[malisafi_owner_properties]`
Liste des propriétés du propriétaire.
- Tableau avec statut, prix, vues
- Boutons Edit/View
- **Requis:** Rôle `owner`

#### `[malisafi_owner_inquiries]`
Demandes reçues pour les propriétés.
- Tableau des demandes
- Informations contact
- **Requis:** Rôle `owner`

### Developer Dashboard Shortcodes

#### `[malisafi_developer_dashboard]`
Tableau de bord développeur avec :
- Projets actifs
- Propriétés totales
- Statistiques
- **Requis:** Rôle `developer`

#### `[malisafi_developer_projects]`
Liste des projets de développement.
- **Requis:** Rôle `developer`
- **Note:** À développer

#### `[malisafi_developer_analytics]`
Statistiques et analyses.
- **Requis:** Rôle `developer`
- **Note:** À développer

### Account Shortcodes

#### `[malisafi_login]`
Formulaire de connexion WordPress.
- Redirection après connexion
- Lien vers inscription

#### `[malisafi_register]`
Formulaire d'inscription.
- Username, Email, Password
- Validation côté serveur
- Lien vers connexion

#### `[malisafi_account]`
Page de compte utilisateur.
- Informations du compte
- Liens vers dashboards
- Option de déconnexion

### Property Submission

#### `[malisafi_property_submit]`
Formulaire de soumission de propriété.
- **Requis:** Utilisateur connecté
- **Note:** À développer complètement

---

## 🔧 Fonctions Helper

### Dans votre thème ou plugin

```php
// Obtenir l'URL d'une page
$url = MalisafiMLS\Page_Manager::get_page_url('client_dashboard');

// Vérifier si une page existe
$exists = MalisafiMLS\Page_Manager::page_exists('owner_properties');

// Obtenir l'ID d'une page
$page_id = get_option('malisafi_page_client_dashboard');
```

---

## 📊 Hiérarchie des Pages

### Structure Parent-Enfant

```
Client Dashboard (parent)
├── My Favorites
├── Saved Searches
└── My Inquiries

Agent Dashboard (parent)
├── My Properties
├── Add Property
├── My Leads
└── My Profile

Owner Dashboard (parent)
├── My Properties
├── Add Property
└── Inquiries

Developer Dashboard (parent)
├── My Projects
├── Add Project
└── Analytics
```

Cette hiérarchie améliore :
- **Navigation** - Breadcrumbs automatiques
- **Organisation** - Structure logique
- **SEO** - URLs structurées

---

## 🎨 Personnalisation CSS

Les styles sont dans `assets/css/dashboards.css` :

```css
/* Personnaliser les cartes de stats */
.stat-card {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
}

/* Personnaliser les boutons d'action */
.action-button {
    padding: 20px;
    background: #fff;
}

/* Personnaliser la grille de propriétés */
.properties-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}
```

---

## 🔒 Contrôle d'Accès

### Vérification Automatique

Tous les shortcodes de dashboard vérifient :
1. ✅ Utilisateur connecté
2. ✅ Rôle approprié (si requis)
3. ✅ Affiche message si refusé

### Capacités Requises

| Dashboard | Capacité |
|-----------|----------|
| Client | Aucune (juste connecté) |
| Agent | `agent_basic` |
| Owner | `owner` |
| Developer | `developer` |

---

## 🗄️ Stockage des Données

### Options WordPress

Chaque page est stockée comme :
```
Option: malisafi_page_{key}
Value: {page_id}
```

Exemples :
- `malisafi_page_client_dashboard` → 123
- `malisafi_page_owner_properties` → 456

### Récupération

```php
$page_id = get_option('malisafi_page_client_dashboard');
$page_url = get_permalink($page_id);
```

---

## ⚙️ Actions Disponibles

### Dans l'Admin

1. **Create All Missing Pages**
   - Crée toutes les pages manquantes
   - Deux passes (parents puis enfants)
   - Shortcodes auto-assignés

2. **Recreate Individual Page**
   - Supprime l'ancienne page
   - Crée une nouvelle
   - Préserve le slug

3. **Delete All Pages**
   - ⚠️ DANGER ZONE
   - Supprime les 28 pages
   - Supprime les options
   - Requiert confirmation

---

## 🔍 Dépannage

### Les pages n'apparaissent pas ?

1. Vérifiez que les pages sont créées :
   - Admin → MalisafiMLS → Pages Management
   - Vérifiez les statuts (✅ ou ⚠️)

2. Recréez les pages manquantes :
   - Cliquez sur "Create All Missing Pages"
   - Ou recréez individuellement

3. Vérifiez les permaliens :
   - Admin → Settings → Permalinks
   - Cliquez "Save Changes"

### Les shortcodes ne fonctionnent pas ?

1. Vérifiez que vous êtes connecté
2. Vérifiez votre rôle utilisateur
3. Désactivez/réactivez le plugin
4. Videz le cache

### CSS ne s'affiche pas ?

1. Vérifiez que `dashboards.css` existe
2. Forcez le rechargement (Ctrl+F5)
3. Videz le cache WordPress

---

## 📝 Notes Techniques

### Classe Principal : `Page_Manager`

**Fichier:** `includes/class-page-manager.php`

**Méthodes principales:**
```php
Page_Manager::init()                    // Initialisation
Page_Manager::create_all_pages()        // Créer toutes
Page_Manager::create_page($key, $page)  // Créer une page
Page_Manager::get_page_url($key)        // Obtenir URL
Page_Manager::recreate_page($key)       // Recréer
Page_Manager::delete_all_pages()        // Tout supprimer
```

### Classe Shortcodes : `Dashboard_Shortcodes`

**Fichier:** `includes/class-dashboard-shortcodes.php`

**Méthodes principales:**
```php
Dashboard_Shortcodes::init()            // Enregistrer tous
Dashboard_Shortcodes::client_dashboard() // Client dashboard
Dashboard_Shortcodes::owner_dashboard()  // Owner dashboard
// ... etc
```

---

## 🚧 Développement Futur

### Pages à Compléter

1. **Property Submit Form** - Formulaire complet de soumission
2. **Developer Projects** - Gestion des projets
3. **Developer Analytics** - Statistiques avancées
4. **Agent Properties** - Dashboard frontend agent

### Améliorations Prévues

- [ ] AJAX pour les actions rapides
- [ ] Filtres avancés dans les tableaux
- [ ] Export PDF des demandes
- [ ] Notifications en temps réel
- [ ] Statistiques graphiques

---

## 📚 Ressources

- **Documentation WordPress:** https://developer.wordpress.org/
- **Shortcode API:** https://developer.wordpress.org/plugins/shortcodes/
- **Page API:** https://developer.wordpress.org/reference/functions/wp_insert_post/

---

## ✅ Checklist d'Activation

Après installation du plugin :

1. [ ] Aller dans **MalisafiMLS → Pages Management**
2. [ ] Cliquer sur **"Create All Missing Pages"**
3. [ ] Vérifier que les 28 pages montrent ✅
4. [ ] Aller dans **Settings → Permalinks** et sauvegarder
5. [ ] Tester une page (ex: `/client-dashboard`)
6. [ ] Vérifier les redirections de connexion
7. [ ] Ajouter des liens au menu WordPress

---

## 🎯 Recommandations Menu

Ajoutez ces pages à votre menu WordPress :

**Menu Principal:**
- Properties
- Property Search
- Our Agents
- Pricing

**Menu Compte (pour utilisateurs connectés):**
- My Account
- [Dashboard selon le rôle]
- Logout

---

**Besoin d'aide ?** Consultez le fichier `README.md` ou contactez le support Malisafi.
