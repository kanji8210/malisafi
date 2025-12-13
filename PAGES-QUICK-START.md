# ✅ PAGES SYSTEM - QUICK START CHECKLIST

**MalisafiMLS Plugin - Pages Management System**

---

## 🚀 Installation Rapide (5 minutes)

### Étape 1: Activer le Plugin
- [ ] Aller dans **Plugins → Installed Plugins**
- [ ] Activer **Malisafi MLS**

### Étape 2: Créer les Pages
- [ ] Aller dans **MalisafiMLS → Pages Management**
- [ ] Cliquer sur **"Create All Missing Pages"**
- [ ] Attendre que toutes les pages montrent ✅ (vert)

### Étape 3: Réinitialiser les Permaliens
- [ ] Aller dans **Settings → Permalinks**
- [ ] Cliquer sur **"Save Changes"** (sans rien modifier)

### Étape 4: Vérifier les Pages
- [ ] Visiter votre site en frontend
- [ ] Tester: `/client-dashboard` (devrait demander connexion)
- [ ] Tester: `/properties` (devrait afficher les propriétés)

---

## 🎯 Configuration Recommandée

### Menus WordPress

#### Menu Principal
- [ ] Ajouter: **Properties** (`/properties`)
- [ ] Ajouter: **Property Search** (`/property-search`)
- [ ] Ajouter: **Our Agents** (`/agents`)
- [ ] Ajouter: **Pricing** (`/pricing`)

#### Menu Compte (Si utilisateur connecté)
- [ ] Ajouter: **My Account** (`/my-account`)
- [ ] Ajouter: **Dashboard** (selon le rôle)
- [ ] Ajouter: **Logout** (lien personnalisé)

### Pages d'Accueil Suggérées

- [ ] **HomePage:** Afficher propriétés en vedette
- [ ] Ajouter shortcode: `[malisafi_featured_properties]`

---

## 👥 Créer des Utilisateurs Test

### Client
- [ ] Créer utilisateur avec rôle **Client**
- [ ] Username: `testclient`
- [ ] Tester l'accès à `/client-dashboard`

### Agent
- [ ] Créer utilisateur avec rôle **Agent Basic**
- [ ] Username: `testagent`
- [ ] Tester l'accès à `/agent-dashboard` (redirige vers admin)

### Owner
- [ ] Créer utilisateur avec rôle **Owner**
- [ ] Username: `testowner`
- [ ] Tester l'accès à `/owner-dashboard`

### Developer
- [ ] Créer utilisateur avec rôle **Developer**
- [ ] Username: `testdev`
- [ ] Tester l'accès à `/developer-dashboard`

---

## 🧪 Tests de Base

### Test 1: Pages Créées
```
✅ Total: 28 pages
✅ Public: 5 pages
✅ Client Dashboard: 4 pages
✅ Agent Dashboard: 5 pages
✅ Owner Dashboard: 4 pages
✅ Developer Dashboard: 4 pages
✅ Account: 3 pages
✅ Common: 3 pages
```

### Test 2: Shortcodes Fonctionnels
- [ ] `[malisafi_client_dashboard]` - Affiche dashboard client
- [ ] `[malisafi_owner_dashboard]` - Affiche dashboard owner
- [ ] `[malisafi_login]` - Affiche formulaire connexion
- [ ] `[malisafi_properties]` - Liste des propriétés

### Test 3: Contrôle d'Accès
- [ ] Visiteur non-connecté → Message "Login Required"
- [ ] Client → Accès à client dashboard ✅
- [ ] Owner → Accès à owner dashboard ✅
- [ ] Agent → Redirection vers backend ✅

### Test 4: Responsive Design
- [ ] Desktop (1920x1080) - OK
- [ ] Tablet (768x1024) - OK
- [ ] Mobile (375x667) - OK

---

## 📱 Pages Par Rôle

### 👤 Client (Utilisateur Standard)
Pages accessibles:
- ✅ `/client-dashboard` - Tableau de bord
- ✅ `/client-favorites` - Favoris
- ✅ `/client-searches` - Recherches sauvegardées
- ✅ `/client-inquiries` - Mes demandes
- ✅ `/my-account` - Mon compte
- ✅ `/properties` - Liste propriétés
- ✅ `/property-search` - Recherche

### 🏘️ Agent
Pages accessibles:
- ✅ Backend: `admin.php?page=malisafi-agent-dashboard`
- ✅ Frontend: Redirection automatique vers backend
- ✅ Profile public: Via Custom Post Type Agent

### 🏠 Owner (Propriétaire)
Pages accessibles:
- ✅ `/owner-dashboard` - Tableau de bord
- ✅ `/owner-properties` - Mes propriétés
- ✅ `/owner-add-property` - Ajouter propriété
- ✅ `/owner-inquiries` - Demandes reçues
- ✅ `/my-account` - Mon compte

### 🏗️ Developer (Développeur)
Pages accessibles:
- ✅ `/developer-dashboard` - Tableau de bord
- ✅ `/developer-projects` - Mes projets
- ✅ `/developer-add-project` - Ajouter projet
- ✅ `/developer-analytics` - Analytics
- ✅ `/my-account` - Mon compte

---

## 🎨 Personnalisation CSS

### Changer les Couleurs Primaires

Fichier: `assets/css/dashboards.css`

```css
/* Couleur primaire (bleu par défaut) */
.stat-card h3,
.action-button:hover {
    color: #3498db; /* Changer ici */
}

.stat-card a,
.action-button.primary {
    background: #3498db; /* Changer ici */
}

/* Couleur de succès (vert) */
.status-badge.status-publish {
    background: #d4edda; /* Changer ici */
    color: #155724; /* Changer ici */
}
```

### Changer la Taille des Cartes

```css
.dashboard-stats {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Modifier minmax */
}
```

---

## 🔧 Dépannage Rapide

### Problème: Pages ne s'affichent pas
**Solution:**
1. Vérifier que les pages sont créées (Pages Management)
2. Réinitialiser les permaliens
3. Vider le cache (si plugin de cache actif)

### Problème: Shortcode ne fonctionne pas
**Solution:**
1. Vérifier que vous êtes connecté
2. Vérifier votre rôle utilisateur
3. Désactiver/réactiver le plugin

### Problème: CSS ne s'applique pas
**Solution:**
1. Vérifier que `dashboards.css` existe
2. Forcer le rechargement (Ctrl+F5)
3. Vider le cache du navigateur

### Problème: Accès refusé
**Solution:**
1. Vérifier le rôle de l'utilisateur
2. Se connecter si nécessaire
3. Vérifier les capacités requises

---

## 📊 Tableau des Shortcodes

| Shortcode | Page | Rôle Requis | Status |
|-----------|------|-------------|--------|
| `[malisafi_client_dashboard]` | Client Dashboard | Client | ✅ |
| `[malisafi_favorites]` | My Favorites | Client | ✅ |
| `[malisafi_saved_searches]` | Saved Searches | Client | ✅ |
| `[malisafi_client_inquiries]` | My Inquiries | Client | ✅ |
| `[malisafi_agent_dashboard]` | Agent Dashboard | Agent | ✅ (Redirect) |
| `[malisafi_owner_dashboard]` | Owner Dashboard | Owner | ✅ |
| `[malisafi_owner_properties]` | Owner Properties | Owner | ✅ |
| `[malisafi_owner_inquiries]` | Owner Inquiries | Owner | ✅ |
| `[malisafi_developer_dashboard]` | Developer Dashboard | Developer | ✅ |
| `[malisafi_developer_projects]` | Developer Projects | Developer | 🔄 (Placeholder) |
| `[malisafi_developer_analytics]` | Developer Analytics | Developer | 🔄 (Placeholder) |
| `[malisafi_login]` | Login | None | ✅ |
| `[malisafi_register]` | Register | None | ✅ |
| `[malisafi_account]` | My Account | Logged In | ✅ |
| `[malisafi_property_submit]` | Submit Property | Logged In | 🔄 (Placeholder) |

**Légende:**
- ✅ = Fonctionnel
- 🔄 = À développer
- ⚠️ = En test

---

## 💾 Backup Recommandé

Avant toute modification:
- [ ] Backup de la base de données
- [ ] Backup des fichiers du plugin
- [ ] Noter les IDs des pages créées
- [ ] Export des paramètres

---

## 📞 Support & Documentation

### Fichiers de Documentation
- `PAGES-SYSTEM-GUIDE.md` - Guide complet
- `PAGES-SYSTEM-CHANGES.md` - Résumé des changements
- `README.md` - Documentation générale
- `TODO.md` - Progression du projet

### Liens Utiles
- WordPress Codex: https://codex.wordpress.org/
- Plugin Handbook: https://developer.wordpress.org/plugins/
- Shortcode API: https://codex.wordpress.org/Shortcode_API

---

## ✨ Fonctionnalités Avancées (Optionnel)

### Ajouter un Widget de Dashboard
```php
// Dans functions.php du thème
add_action('wp_dashboard_setup', 'malisafi_add_dashboard_widgets');
function malisafi_add_dashboard_widgets() {
    wp_add_dashboard_widget(
        'malisafi_dashboard_widget',
        'MalisafiMLS Stats',
        'malisafi_dashboard_widget_function'
    );
}
```

### Personnaliser les Redirections
```php
// Rediriger après connexion
add_filter('login_redirect', 'malisafi_login_redirect', 10, 3);
function malisafi_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('owner', $user->roles)) {
            return home_url('/owner-dashboard');
        }
    }
    return $redirect_to;
}
```

---

## 🎯 Prochaines Actions

### Court Terme (Cette Semaine)
- [ ] Tester tous les dashboards
- [ ] Ajouter des propriétés test
- [ ] Créer des utilisateurs test
- [ ] Configurer les menus

### Moyen Terme (Ce Mois)
- [ ] Compléter les placeholders
- [ ] Ajouter AJAX pour favoris
- [ ] Implémenter notifications
- [ ] Ajouter analytics

### Long Terme (3 Mois)
- [ ] Application mobile
- [ ] API REST
- [ ] Webhooks
- [ ] Intégrations tierces

---

**Dernière mise à jour:** 3 décembre 2025  
**Version:** 1.0.0  
**Développé par:** Malisafi Development Team

---

## ✅ Checklist Finale

- [ ] Plugin activé
- [ ] 28 pages créées
- [ ] Permaliens réinitialisés
- [ ] Menus configurés
- [ ] Utilisateurs test créés
- [ ] Tests de base effectués
- [ ] CSS personnalisé (optionnel)
- [ ] Documentation lue
- [ ] Backup effectué
- [ ] Prêt pour production! 🚀
