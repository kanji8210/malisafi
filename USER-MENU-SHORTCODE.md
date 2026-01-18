# User Menu Shortcode - Guide d'Utilisation

**Shortcode:** `[malisafi_user_menu]`  
**Version:** 1.0.0  
**Fichiers:** `includes/class-shortcodes.php`, `assets/css/user-menu.css`

## Description

Affiche un menu utilisateur dans le header avec le nom de l'utilisateur connecté et une option de déconnexion. Parfait pour les thèmes personnalisés et les headers.

## Utilisation de Base

### 1. Dans le Header du Thème

**Fichier:** `header.php`

```php
<header class="site-header">
    <div class="header-container">
        <div class="logo">
            <a href="<?php echo home_url(); ?>">
                <img src="<?php echo get_template_directory_uri(); ?>/logo.png" alt="Logo">
            </a>
        </div>
        
        <nav class="main-menu">
            <!-- Votre menu principal -->
        </nav>
        
        <div class="header-user-menu">
            <?php echo do_shortcode('[malisafi_user_menu]'); ?>
        </div>
    </div>
</header>
```

### 2. Dans un Widget

Ajoutez le shortcode dans un widget "Text" ou "HTML personnalisé":
```
[malisafi_user_menu]
```

### 3. Dans une Page/Article

Simplement ajouter le shortcode dans le contenu:
```
[malisafi_user_menu]
```

### 4. Dans Gutenberg

Utilisez le bloc "Shortcode" et ajoutez:
```
[malisafi_user_menu]
```

## Paramètres

### `show_avatar`
- **Type:** `yes`/`no`
- **Défaut:** `yes`
- **Description:** Afficher ou masquer l'avatar de l'utilisateur

```php
[malisafi_user_menu show_avatar="no"]
```

### `show_dashboard`
- **Type:** `yes`/`no`
- **Défaut:** `yes`
- **Description:** Afficher ou masquer le lien vers le dashboard

```php
[malisafi_user_menu show_dashboard="no"]
```

### `login_text`
- **Type:** Texte
- **Défaut:** `"Login"`
- **Description:** Texte personnalisé pour le bouton de connexion

```php
[malisafi_user_menu login_text="Sign In"]
```

### `register_text`
- **Type:** Texte
- **Défaut:** `"Register"`
- **Description:** Texte personnalisé pour le bouton d'inscription

```php
[malisafi_user_menu register_text="Sign Up"]
```

## Exemples

### Exemple 1: Configuration Standard
```php
[malisafi_user_menu]
```
**Résultat (connecté):**
- Avatar + "Howdy, John Doe" avec menu dropdown
- Dashboard, My Properties, My Account, Logout

### Exemple 2: Sans Avatar
```php
[malisafi_user_menu show_avatar="no"]
```
**Résultat:**
- "Howdy, John Doe" (sans avatar) avec menu dropdown

### Exemple 3: Texte Personnalisé
```php
[malisafi_user_menu login_text="Se Connecter" register_text="S'inscrire"]
```
**Résultat (non connecté):**
- Bouton "Se Connecter"
- Bouton "S'inscrire"

### Exemple 4: Minimaliste
```php
[malisafi_user_menu show_avatar="no" show_dashboard="no"]
```
**Résultat:**
- Texte uniquement avec menu simple

## États du Menu

### Utilisateur Connecté

**Affichage:**
```
[Avatar] Howdy, John Doe ▼
```

**Menu Dropdown:**
- 🏠 Dashboard (URL selon le rôle)
- ➕ Add Property (agents seulement)
- 🏡 My Properties (agents seulement)
- 👤 My Account
- ─────────────────
- 🚪 Logout

### Utilisateur Non Connecté

**Affichage:**
```
[👤 Login] [➕ Register]
```

## Personnalisation CSS

### Couleurs Principales

Le menu utilise les variables CSS du design system:

```css
/* Variables utilisées */
--mls-accent: #737d5d;          /* Couleur principale */
--mls-dark: #4a5a3a;            /* Couleur foncée */
--mls-grey-green: #c5d3b6;      /* Vert grisâtre */
--mls-light-grey: #f5f5f5;      /* Gris clair */
--mls-border-light: #e0e0e0;    /* Bordure */
--mls-text-primary: #333;       /* Texte principal */
--mls-text-secondary: #666;     /* Texte secondaire */
```

### Personnaliser le Style

**Fichier:** `style.css` de votre thème

```css
/* Changer la couleur de fond du trigger */
.malisafi-user-menu.logged-in .user-menu-trigger {
    background: #your-color;
    border-color: #your-border;
}

/* Changer la taille de l'avatar */
.user-avatar {
    width: 40px;
    height: 40px;
}

/* Modifier le dropdown */
.user-menu-dropdown {
    min-width: 250px;
    border-radius: 12px;
}

/* Changer la couleur du logout */
.logout-link {
    color: #your-logout-color !important;
}
```

### Ajouter une Icône Personnalisée

```css
.user-greeting::before {
    content: '👋';
    margin-right: 5px;
}
```

## Intégration avec les Rôles

Le menu s'adapte automatiquement selon le rôle de l'utilisateur:

### Agent Basic / Agent Premium
- Dashboard → `/agent-dashboard/`
- Add Property → `/add-property/`
- My Properties → `/agent-dashboard/?tab=properties`
- My Account → `/my-account/`
- Logout

### Administrator / Moderator
- Dashboard → `/wp-admin/`
- My Account → `/my-account/`
- Logout

### Client / Autres
- Dashboard → `/my-account/`
- My Account → `/my-account/`
- Logout

## Responsive Design

### Desktop (> 768px)
- Avatar visible
- Texte "Howdy, [Name]" visible
- Menu dropdown aligné à droite

### Tablet (≤ 768px)
- Avatar visible
- Texte "Howdy, [Name]" masqué
- Menu dropdown centré

### Mobile (≤ 480px)
- Avatar uniquement (si show_avatar="yes")
- Boutons Login/Register avec icônes uniquement
- Menu dropdown plus compact

## JavaScript (Optionnel)

Si vous voulez ajouter des interactions personnalisées:

```javascript
jQuery(document).ready(function($) {
    // Empêcher la fermeture du menu au clic
    $('.user-menu-dropdown').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Fermer le menu au clic extérieur
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.malisafi-user-menu').length) {
            $('.user-menu-dropdown').css({
                'opacity': '0',
                'visibility': 'hidden'
            });
        }
    });
});
```

## Compatibilité

### Thèmes WordPress
- ✅ Tous les thèmes WordPress
- ✅ Thèmes FSE (Full Site Editing)
- ✅ Page Builders (Elementor, Divi, etc.)

### Plugins
- ✅ WooCommerce
- ✅ BuddyPress
- ✅ bbPress
- ✅ Tout plugin avec système de rôles WP

## URLs Utilisées

Le menu génère automatiquement les URLs suivantes:

```php
// Logout
wp_logout_url(home_url())

// Login (redirige vers page actuelle)
wp_login_url(get_permalink())

// Register
home_url('/register/')

// Dashboard Agent
home_url('/agent-dashboard/')

// Add Property
home_url('/add-property/')

// My Account
home_url('/my-account/')

// Admin Dashboard
admin_url()
```

## Accessibilité

### Fonctionnalités
- ✅ Navigation au clavier (Tab, Enter)
- ✅ Focus visible (outline)
- ✅ ARIA labels (peut être ajouté)
- ✅ Contraste de couleurs WCAG AA

### Améliorer l'Accessibilité

```php
// Ajouter dans functions.php
add_filter('malisafi_user_menu_html', function($html) {
    return str_replace(
        'class="user-menu-trigger"',
        'class="user-menu-trigger" role="button" aria-haspopup="true" aria-expanded="false"',
        $html
    );
});
```

## Dépannage

### Le menu ne s'affiche pas

**Vérifier:**
1. Le shortcode est correct: `[malisafi_user_menu]`
2. Le plugin est activé
3. Le fichier CSS est chargé (vérifier dans l'inspecteur)

### Les avatars ne s'affichent pas

**Solutions:**
1. Activer Gravatar dans WordPress
2. Vérifier que `show_discussions` est activé
3. Uploader un avatar local

### Le dropdown ne fonctionne pas

**Vérifier:**
1. CSS chargé correctement
2. Pas de conflit CSS avec le thème
3. Hover fonctionne (`:hover` actif)

### Les liens sont incorrects

**Solutions:**
1. Vérifier que les pages existent (/register/, /agent-dashboard/, etc.)
2. Recréer les pages avec Page Manager
3. Vérifier les permaliens (Settings → Permalinks)

## Support

Pour toute question ou personnalisation:
- Documentation complète: `shortcode.txt`
- Fichier CSS: `assets/css/user-menu.css`
- Fichier PHP: `includes/class-shortcodes.php` (ligne ~520)

---

**Version:** 1.0.0  
**Dernière mise à jour:** 18 janvier 2026
