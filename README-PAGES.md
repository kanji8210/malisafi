# 📄 MalisafiMLS - Pages Management System

**Système de gestion automatique de 28 pages pour votre plateforme immobilière**

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/malisafi/malisafi-mls)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)](LICENSE)

---

## 🎯 Qu'est-ce que c'est ?

Le **Pages Management System** est un système complet qui crée et gère automatiquement **28 pages essentielles** pour votre plateforme immobilière MalisafiMLS, chacune avec son shortcode pré-configuré.

### ✨ Fonctionnalités Principales

- ✅ **28 pages automatiques** avec shortcodes pré-assignés
- ✅ **Interface admin intuitive** pour gérer toutes les pages
- ✅ **Hiérarchie parent-enfant** pour une meilleure organisation
- ✅ **Dashboards par rôle** (Client, Agent, Owner, Developer)
- ✅ **Contrôle d'accès automatique** par rôle utilisateur
- ✅ **Design responsive** pour tous les appareils
- ✅ **Suivi d'état visuel** (✅ existant / ⚠️ manquant)
- ✅ **Actions en masse** (créer tout / supprimer tout)

---

## 🚀 Installation Rapide

### 1. Activer le Plugin
```
WordPress Admin → Plugins → Activate MalisafiMLS
```

### 2. Créer les Pages
```
MalisafiMLS → Pages Management → "Create All Missing Pages"
```

### 3. Réinitialiser les Permaliens
```
Settings → Permalinks → Save Changes
```

### 4. C'est prêt ! 🎉
Visitez `/client-dashboard` pour tester

---

## 📋 Les 28 Pages

### 📌 Pages Publiques (5)
| Page | URL | Shortcode |
|------|-----|-----------|
| Properties | `/properties` | `[malisafi_properties]` |
| Property Search | `/property-search` | `[malisafi_property_search]` |
| Featured Properties | `/featured-properties` | `[malisafi_featured_properties]` |
| Our Agents | `/agents` | `[malisafi_agents]` |
| Pricing | `/pricing` | `[malisafi_pricing]` |

### 👤 Client Dashboard (4)
| Page | URL | Shortcode |
|------|-----|-----------|
| Dashboard | `/client-dashboard` | `[malisafi_client_dashboard]` |
| Favorites | `/client-favorites` | `[malisafi_favorites]` |
| Saved Searches | `/client-searches` | `[malisafi_saved_searches]` |
| My Inquiries | `/client-inquiries` | `[malisafi_client_inquiries]` |

### 🏘️ Agent Dashboard (5)
| Page | URL | Shortcode |
|------|-----|-----------|
| Dashboard | `/agent-dashboard` | `[malisafi_agent_dashboard]` |
| My Properties | `/agent-properties` | `[malisafi_agent_properties]` |
| Add Property | `/agent-add-property` | `[malisafi_property_submit]` |
| My Leads | `/agent-leads` | `[malisafi_agent_leads]` |
| My Profile | `/agent-profile` | `[malisafi_agent_profile]` |

### 🏠 Owner Dashboard (4)
| Page | URL | Shortcode |
|------|-----|-----------|
| Dashboard | `/owner-dashboard` | `[malisafi_owner_dashboard]` |
| My Properties | `/owner-properties` | `[malisafi_owner_properties]` |
| Add Property | `/owner-add-property` | `[malisafi_property_submit]` |
| Inquiries | `/owner-inquiries` | `[malisafi_owner_inquiries]` |

### 🏗️ Developer Dashboard (4)
| Page | URL | Shortcode |
|------|-----|-----------|
| Dashboard | `/developer-dashboard` | `[malisafi_developer_dashboard]` |
| My Projects | `/developer-projects` | `[malisafi_developer_projects]` |
| Add Project | `/developer-add-project` | `[malisafi_property_submit]` |
| Analytics | `/developer-analytics` | `[malisafi_developer_analytics]` |

### 🔐 Account Pages (3)
| Page | URL | Shortcode |
|------|-----|-----------|
| Login | `/login` | `[malisafi_login]` |
| Register | `/register` | `[malisafi_register]` |
| My Account | `/my-account` | `[malisafi_account]` |

---

## 💻 Utilisation

### Interface Admin

Accédez à l'interface de gestion :
```
MalisafiMLS → Pages Management
```

### Actions Disponibles

**Créer toutes les pages manquantes :**
```
Cliquer sur "Create All Missing Pages"
```

**Recréer une page individuelle :**
```
Cliquer sur "Recreate" à côté de la page
```

**Supprimer toutes les pages :**
```
Danger Zone → "Delete All Pages" (avec confirmation)
```

---

## 🎨 Shortcodes

### Client Dashboard

#### Dashboard Principal
```php
[malisafi_client_dashboard]
```
Affiche :
- Statistiques (favoris, recherches, demandes)
- Actions rapides
- Activité récente

#### Favoris
```php
[malisafi_favorites]
```
Liste toutes les propriétés favorites du client.

#### Recherches Sauvegardées
```php
[malisafi_saved_searches]
```
Affiche et gère les recherches sauvegardées.

#### Mes Demandes
```php
[malisafi_client_inquiries]
```
Liste toutes les demandes envoyées.

### Owner Dashboard

#### Dashboard Propriétaire
```php
[malisafi_owner_dashboard]
```
Affiche :
- Nombre de propriétés
- Demandes reçues
- Vues totales

#### Mes Propriétés
```php
[malisafi_owner_properties]
```
Tableau de toutes les propriétés du propriétaire.

#### Demandes Reçues
```php
[malisafi_owner_inquiries]
```
Liste des demandes pour les propriétés du propriétaire.

### Developer Dashboard

#### Dashboard Développeur
```php
[malisafi_developer_dashboard]
```
Tableau de bord avec projets et statistiques.

### Account Pages

#### Connexion
```php
[malisafi_login]
```
Formulaire de connexion WordPress.

#### Inscription
```php
[malisafi_register]
```
Formulaire d'inscription avec validation.

#### Mon Compte
```php
[malisafi_account]
```
Informations de compte et liens rapides.

---

## 🔒 Contrôle d'Accès

### Par Rôle

| Rôle | Pages Accessibles |
|------|-------------------|
| **Visiteur** | Pages publiques uniquement |
| **Client** | Dashboard client + pages publiques |
| **Agent** | Dashboard agent (backend) + pages publiques |
| **Owner** | Dashboard owner + pages publiques |
| **Developer** | Dashboard developer + pages publiques |

### Messages d'Erreur

**Non connecté :**
```
"You must be logged in to view this page."
+ Bouton "Login"
```

**Rôle insuffisant :**
```
"You do not have permission to access this page."
```

---

## 🎨 Personnalisation

### CSS

Fichier : `assets/css/dashboards.css`

#### Changer les Couleurs
```css
/* Couleur primaire */
.stat-card h3,
.action-button.primary {
    background: #3498db; /* Votre couleur */
}

/* Couleur de survol */
.action-button:hover {
    border-color: #3498db; /* Votre couleur */
}
```

#### Modifier la Grille
```css
/* Nombre de colonnes */
.dashboard-stats {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}
```

---

## 🔧 API pour Développeurs

### Obtenir l'URL d'une Page
```php
$url = MalisafiMLS\Page_Manager::get_page_url('client_dashboard');
echo $url; // https://yoursite.com/client-dashboard
```

### Vérifier si une Page Existe
```php
if (MalisafiMLS\Page_Manager::page_exists('owner_properties')) {
    echo "La page existe!";
}
```

### Créer une Page Personnalisée
```php
MalisafiMLS\Page_Manager::create_page('my_custom_page', [
    'title' => 'My Custom Page',
    'slug' => 'my-custom-page',
    'shortcode' => '[my_custom_shortcode]',
    'description' => 'Description...',
    'parent' => null // ou ID de la page parent
]);
```

### Créer un Shortcode Personnalisé
```php
add_shortcode('my_custom_dashboard', function($atts) {
    if (!is_user_logged_in()) {
        return '<p>Please login</p>';
    }
    
    ob_start();
    ?>
    <div class="my-dashboard">
        <h1>Mon Dashboard</h1>
        <!-- Votre contenu -->
    </div>
    <?php
    return ob_get_clean();
});
```

---

## 📊 Statistiques

### Code Ajouté

| Type | Lignes | Fichiers |
|------|--------|----------|
| PHP | 1,826 | 3 |
| CSS | 543 | 1 |
| Documentation | 1,900 | 4 |
| **Total** | **4,269** | **8** |

### Fonctionnalités

- ✅ 28 pages gérées
- ✅ 15+ shortcodes
- ✅ 6 catégories
- ✅ 4 rôles supportés
- ✅ Responsive design
- ✅ Documentation complète

---

## 🐛 Dépannage

### Les pages ne s'affichent pas

**Solution 1 : Réinitialiser les permaliens**
```
Settings → Permalinks → Save Changes
```

**Solution 2 : Vider le cache**
```
Si vous utilisez un plugin de cache, videz-le
```

**Solution 3 : Recréer les pages**
```
MalisafiMLS → Pages Management → Recreate
```

### Les shortcodes ne fonctionnent pas

**Vérifier :**
1. Vous êtes connecté ?
2. Vous avez le bon rôle ?
3. Le plugin est activé ?

**Solution :**
```
Désactiver puis réactiver le plugin
```

### CSS ne s'applique pas

**Solution :**
```
1. Ctrl+F5 (forcer le rechargement)
2. Vider le cache du navigateur
3. Vérifier que dashboards.css existe
```

---

## 📚 Documentation

### Fichiers de Documentation

| Fichier | Description |
|---------|-------------|
| `PAGES-SYSTEM-GUIDE.md` | Guide complet et détaillé |
| `PAGES-QUICK-START.md` | Checklist de démarrage rapide |
| `PAGES-SYSTEM-CHANGES.md` | Résumé des changements |
| `PAGES-FILES-STRUCTURE.md` | Structure des fichiers |
| `README-PAGES.md` | Ce fichier |

### Liens Utiles

- [WordPress Codex](https://codex.wordpress.org/)
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Shortcode API](https://codex.wordpress.org/Shortcode_API)

---

## 🛠️ Technologies

- **WordPress:** 5.0+
- **PHP:** 7.4+
- **MySQL:** 5.6+
- **CSS3:** Grid, Flexbox
- **JavaScript:** Vanilla JS

---

## 📦 Structure des Fichiers

```
includes/
├── class-page-manager.php              (446 lignes)
└── class-dashboard-shortcodes.php      (1,048 lignes)

admin/
└── templates/
    └── pages-management.php            (332 lignes)

assets/
└── css/
    └── dashboards.css                  (543 lignes)

Documentation/
├── PAGES-SYSTEM-GUIDE.md              (~700 lignes)
├── PAGES-QUICK-START.md               (~400 lignes)
├── PAGES-SYSTEM-CHANGES.md            (~500 lignes)
├── PAGES-FILES-STRUCTURE.md           (~300 lignes)
└── README-PAGES.md                    (Ce fichier)
```

---

## 🔄 Mises à Jour

### Version 1.0.0 (3 décembre 2025)

**Nouveautés :**
- ✨ Système de pages automatiques
- ✨ 28 pages pré-configurées
- ✨ 15+ shortcodes de dashboard
- ✨ Interface admin complète
- ✨ Contrôle d'accès par rôle
- ✨ Design responsive
- ✨ Documentation complète

---

## 👥 Contribuer

Nous accueillons les contributions ! Voici comment :

1. **Fork** le projet
2. **Créer** une branche (`git checkout -b feature/AmazingFeature`)
3. **Commit** vos changements (`git commit -m 'Add AmazingFeature'`)
4. **Push** vers la branche (`git push origin feature/AmazingFeature`)
5. **Ouvrir** une Pull Request

---

## 📄 Licence

Ce projet est sous licence GPL-2.0. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 📞 Support

**Besoin d'aide ?**

- 📧 Email: support@malisafi.com
- 📖 Documentation: Voir fichiers `.md`
- 🐛 Bugs: [GitHub Issues](https://github.com/malisafi/malisafi-mls/issues)
- 💬 Forum: [Support Forum](https://malisafi.com/support)

---

## 🎯 Roadmap

### Court Terme (1 mois)
- [ ] Compléter les placeholders
- [ ] Ajouter AJAX pour favoris
- [ ] Améliorer les formulaires

### Moyen Terme (3 mois)
- [ ] Notifications email
- [ ] Export PDF/CSV
- [ ] Analytics avancés

### Long Terme (6 mois)
- [ ] Application mobile
- [ ] API REST complète
- [ ] Intégrations tierces

---

## 🙏 Remerciements

- WordPress Community
- Contributors
- Beta Testers
- Malisafi Team

---

## ⭐ Donnez-nous une Étoile !

Si vous trouvez ce projet utile, donnez-nous une étoile sur GitHub ! ⭐

---

**Développé avec ❤️ par Malisafi Development Team**

**Version:** 1.0.0  
**Dernière mise à jour:** 3 décembre 2025

---

## 🚀 Commencer Maintenant

```bash
# 1. Activer le plugin
WP Admin → Plugins → Activate MalisafiMLS

# 2. Créer les pages
MalisafiMLS → Pages Management → Create All Missing Pages

# 3. Réinitialiser les permaliens
Settings → Permalinks → Save Changes

# 4. C'est prêt ! 🎉
Visitez: /client-dashboard
```

---

**Happy Coding! 💻✨**
