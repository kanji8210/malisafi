# Guide de Configuration des Pages - Malisafi MLS

## Date: 6 décembre 2025

## 🚀 Comment créer toutes les pages nécessaires

### Méthode Automatique (Recommandée)

1. **Accédez à la gestion des pages**
   - Dans le menu WordPress, allez à : **Malisafi → Pages**
   - URL directe : `/wp-admin/admin.php?page=malisafi-pages`

2. **Créez toutes les pages en un clic**
   - Si des pages sont manquantes, vous verrez une notification bleue
   - Cliquez sur le bouton **"Create All Missing Pages"**
   - Toutes les pages seront créées automatiquement avec les bons shortcodes

3. **Vérification**
   - Après création, toutes les cartes devraient avoir une coche verte ✓
   - Le compteur "Missing" devrait afficher 0

---

## 📋 Liste complète des pages créées

### Pages Publiques (6 pages)
| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Properties** | `properties` | `[malisafi_properties]` | Liste principale des propriétés |
| **Property Search** | `property-search` | `[malisafi_property_search]` | Recherche avancée |
| **Featured Properties** | `featured-properties` | `[malisafi_featured_properties]` | Propriétés mises en avant |
| **Our Agents** | `agents` | `[malisafi_agents]` | Liste de tous les agents |
| **Pricing & Plans** | `pricing` | `[malisafi_pricing]` | Plans d'abonnement |

### Tableau de bord Client (4 pages)
| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **My Dashboard** | `client-dashboard` | `[malisafi_client_dashboard]` | Tableau de bord principal |
| **My Favorites** | `my-favorites` | `[malisafi_favorites]` | Propriétés favorites |
| **Saved Searches** | `saved-searches` | `[malisafi_saved_searches]` | Recherches sauvegardées |
| **My Inquiries** | `my-inquiries` | `[malisafi_client_inquiries]` | Historique des demandes |

### Tableau de bord Agent (5 pages)
| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Agent Dashboard** | `agent-dashboard` | `[malisafi_agent_dashboard]` | Tableau de bord agent |
| **My Properties** | `agent-properties` | `[malisafi_agent_properties]` | Propriétés de l'agent |
| **Add Property** | `add-property` | `[malisafi_property_submit]` | Ajouter une propriété |
| **My Leads** | `agent-leads` | `[malisafi_agent_leads]` | Gestion des leads |
| **My Profile** | `agent-profile` | `[malisafi_agent_profile]` | Profil agent |

### Tableau de bord Propriétaire (4 pages)
| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Owner Dashboard** | `owner-dashboard` | `[malisafi_owner_dashboard]` | Tableau de bord propriétaire |
| **My Properties** | `owner-properties` | `[malisafi_owner_properties]` | Propriétés du propriétaire |
| **List Property** | `list-property` | `[malisafi_property_submit role="owner"]` | Mettre en vente |
| **Inquiries** | `owner-inquiries` | `[malisafi_owner_inquiries]` | Demandes reçues |

### Tableau de bord Développeur (4 pages)
| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Developer Dashboard** | `developer-dashboard` | `[malisafi_developer_dashboard]` | Tableau de bord développeur |
| **My Projects** | `developer-projects` | `[malisafi_developer_projects]` | Projets du développeur |
| **Add Project** | `add-project` | `[malisafi_property_submit role="developer"]` | Nouveau projet |
| **Analytics** | `developer-analytics` | `[malisafi_developer_analytics]` | Statistiques et rapports |

### Pages de Compte (3 pages)
| Page | Slug | Shortcode | Description |
|------|------|-----------|-------------|
| **Login** | `login` | `[malisafi_login]` | Connexion utilisateur |
| **Register** | `register` | `[malisafi_register]` | Inscription |
| **My Account** | `my-account` | `[malisafi_account]` | Paramètres du compte |

---

## 🎨 Personnalisation des pages

### Modifier le contenu d'une page

1. Allez dans **Malisafi → Pages**
2. Trouvez la page à modifier
3. Cliquez sur **"Edit"**
4. Vous pouvez :
   - ✅ Ajouter du texte au-dessus ou en-dessous du shortcode
   - ✅ Modifier le titre de la page
   - ✅ Changer le slug (URL)
   - ⚠️ **NE PAS supprimer le shortcode** (la page ne fonctionnera plus)

### Exemple de personnalisation

**Avant :**
```
[malisafi_client_dashboard]
```

**Après personnalisation :**
```
<h1>Welcome to your personal dashboard!</h1>
<p>Manage your favorite properties and saved searches.</p>

[malisafi_client_dashboard]

<div class="need-help">
<h3>Need Help?</h3>
<p>Contact us at support@malisafi.com</p>
</div>
```

---

## 🔧 Actions disponibles dans la gestion des pages

### Pour chaque page

| Bouton | Action | Quand l'utiliser |
|--------|--------|------------------|
| **View** 👁️ | Voir la page publiée | Vérifier l'affichage public |
| **Edit** ✏️ | Modifier la page | Personnaliser le contenu |
| **Recreate** 🔄 | Supprimer et recréer | Réinitialiser une page modifiée |
| **Create** ➕ | Créer la page | Si la page est manquante |

### Actions globales

| Bouton | Action | Confirmation |
|--------|--------|--------------|
| **Create All Missing Pages** | Crée toutes les pages manquantes | Non |
| **Delete All Plugin Pages** | ⚠️ SUPPRIME toutes les pages | OUI - Danger ! |

---

## 🔍 Vérifier que tout fonctionne

### Checklist après création

- [ ] Toutes les pages ont une coche verte ✓
- [ ] Le compteur "Missing" affiche 0
- [ ] Cliquer sur "View" pour chaque page fonctionne
- [ ] Les shortcodes s'affichent correctement (pas de texte brut `[shortcode]`)

### Tester les tableaux de bord

1. **Client Dashboard** :
   - Créez un compte client
   - Allez sur `/client-dashboard`
   - Vérifiez que vous voyez vos favoris et recherches

2. **Agent Dashboard** :
   - Connectez-vous en tant qu'agent
   - Allez sur `/agent-dashboard`
   - Vérifiez les statistiques et propriétés

3. **Login/Register** :
   - Déconnectez-vous
   - Allez sur `/login`
   - Vérifiez le design Malisafi
   - Testez le lien vers `/register`

---

## 🎯 Configuration des menus WordPress

### Créer un menu avec les pages Dashboard

1. Allez dans **Apparence → Menus**
2. Créez un nouveau menu : **"User Dashboard Menu"**
3. Ajoutez les pages :
   - My Dashboard (client-dashboard)
   - My Favorites (my-favorites)
   - Saved Searches (saved-searches)
   - My Inquiries (my-inquiries)
   - My Account (my-account)
   - Logout (lien personnalisé : `/wp-login.php?action=logout`)

4. **Pour les Agents**, créez : **"Agent Menu"**
   - Agent Dashboard
   - My Properties
   - Add Property
   - My Leads
   - My Profile

5. Assignez les menus aux emplacements de votre thème

---

## 🚨 Résolution des problèmes

### Problème : Les shortcodes s'affichent en texte brut

**Solution :**
1. Vérifiez que le plugin est activé
2. Allez dans **Plugins → Extensions installées**
3. Vérifiez que "Malisafi MLS" est actif
4. Si nécessaire, désactivez puis réactivez le plugin

### Problème : Une page affiche "404 Not Found"

**Solution :**
1. Allez dans **Réglages → Permaliens**
2. Cliquez sur **"Enregistrer les modifications"** (même sans rien changer)
3. Cela régénère les règles de réécriture WordPress
4. Testez à nouveau la page

### Problème : Les pages enfants ne s'affichent pas

**Solution :**
1. Allez dans **Malisafi → Pages**
2. Vérifiez que la page parent existe (ex: "My Dashboard" pour les pages client)
3. Recréez la page parent si nécessaire
4. Recréez ensuite les pages enfants

### Problème : "You don't have permission to access this page"

**Solution :**
1. Vérifiez que vous êtes connecté
2. Vérifiez votre rôle utilisateur :
   - **Client** : accès aux pages `/client-dashboard/*`
   - **Agent** : accès aux pages `/agent-dashboard/*`
   - **Owner** : accès aux pages `/owner-dashboard/*`
   - **Developer** : accès aux pages `/developer-dashboard/*`
3. Si besoin, contactez un administrateur pour changer votre rôle

---

## 📊 Hiérarchie des pages

```
📄 Properties
📄 Property Search
📄 Featured Properties
📄 Our Agents
📄 Pricing & Plans

📂 Client Dashboard (My Dashboard)
   ├─ 📄 My Favorites
   ├─ 📄 Saved Searches
   └─ 📄 My Inquiries

📂 Agent Dashboard
   ├─ 📄 My Properties
   ├─ 📄 Add Property
   ├─ 📄 My Leads
   └─ 📄 My Profile

📂 Owner Dashboard
   ├─ 📄 My Properties
   ├─ 📄 List Property
   └─ 📄 Inquiries

📂 Developer Dashboard
   ├─ 📄 My Projects
   ├─ 📄 Add Project
   └─ 📄 Analytics

📄 Login
📄 Register
📄 My Account
```

---

## 💡 Conseils et bonnes pratiques

### 1. Ne supprimez jamais les shortcodes
- Les shortcodes `[malisafi_xxx]` sont essentiels au fonctionnement
- Vous pouvez ajouter du contenu avant/après, mais pas les supprimer

### 2. Utilisez des URLs logiques
- Gardez les slugs courts et descriptifs
- Évitez les caractères spéciaux
- Utilisez des tirets `-` au lieu d'underscores `_`

### 3. Créez une navigation cohérente
- Ajoutez les liens dashboard dans le header/footer de votre thème
- Créez des menus conditionnels selon le rôle de l'utilisateur
- Utilisez des widgets pour afficher les liens rapides

### 4. Testez avec différents rôles
- Créez des comptes test pour chaque rôle
- Vérifiez les permissions d'accès
- Assurez-vous que les redirections fonctionnent

### 5. Sauvegardez avant de supprimer
- Avant d'utiliser "Delete All Plugin Pages"
- Exportez vos pages personnalisées
- Notez vos modifications custom

---

## 🆘 Support

Si vous rencontrez des problèmes :

1. **Vérifiez cette documentation** en premier
2. **Consultez les logs WordPress** : `/wp-content/debug.log`
3. **Testez avec un thème par défaut** (Twenty Twenty-Four)
4. **Désactivez les autres plugins** pour identifier les conflits
5. **Contactez le support** : support@malisafi.com

---

**Documentation Malisafi MLS - Pages Management**  
*Dernière mise à jour : 6 décembre 2025*
