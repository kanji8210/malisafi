# 🚀 Démarrage Rapide - Inscription Conversationnelle

## ⚡ Mise en Place en 3 Minutes

### Étape 1 : Créer la Page d'Inscription

1. **WordPress Admin** → **Pages** → **Ajouter**
2. **Titre** : `Inscription` ou `Register`
3. **Contenu** : Copiez-collez ce shortcode :
   ```
   [malisafi_registration]
   ```
4. **Publier** la page
5. Notez l'URL de la page (ex: `https://votresite.com/inscription`)

---

### Étape 2 : Tester le Formulaire

Visitez votre page d'inscription et testez chaque type de compte :

#### ✅ Test Client
1. Sélectionnez "Find a Property" 🏠
2. Remplissez les informations
3. Créez le compte
4. Vérifiez la redirection vers le dashboard client

#### ✅ Test Agent
1. Sélectionnez "Work as an Agent" 💼
2. Remplissez les champs obligatoires + agence (optionnel)
3. Créez le compte
4. Vérifiez la redirection vers le dashboard agent

---

### Étape 3 : Configuration (Optionnel)

#### Personnaliser les Redirections

Modifiez les URLs de redirection dans `class-registration-handler.php` :

```php
// Ligne ~210
if ($account_type === 'agent' || $account_type === 'owner' || $account_type === 'developer') {
    $redirect_url = home_url('/agent-dashboard'); // Modifiez ici
}
```

#### Ajouter un Lien dans le Menu

1. **Apparence** → **Menus**
2. Ajoutez votre page d'inscription
3. Texte du lien : "S'inscrire" ou "Register"

---

## 🎯 Utilisation du Shortcode

### Shortcode Principal
```
[malisafi_registration]
```

### Shortcode Alternatif (même résultat)
```
[malisafi_register]
```

### Dans un Fichier Template PHP
```php
<?php echo do_shortcode('[malisafi_registration]'); ?>
```

---

## 📋 Types de Comptes Disponibles

| Type | Rôle WordPress | Dashboard | Fonctionnalités |
|------|---------------|-----------|-----------------|
| 🏠 **Client** | `malisafi_client` | `/dashboard` | Recherche de propriétés, favoris |
| 💼 **Agent** | `malisafi_agent_basic` | `/agent-dashboard` | Gestion de listings, clients |
| 🔑 **Propriétaire** | `malisafi_owner` | `/agent-dashboard` | Publication de propriétés |
| 🏗️ **Développeur** | `malisafi_developer` | `/agent-dashboard` | Projets immobiliers |

---

## 🔧 Configuration Email (Important !)

Pour que les emails de bienvenue fonctionnent correctement :

### Option 1 : Plugin SMTP (Recommandé)
1. Installez **WP Mail SMTP** ou **Easy WP SMTP**
2. Configurez avec vos identifiants SMTP
3. Testez l'envoi d'email

### Option 2 : Configuration Manuelle
Ajoutez dans `wp-config.php` :
```php
define('SMTP_HOST', 'smtp.votreserveur.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'votre@email.com');
define('SMTP_PASS', 'votre_mot_de_passe');
define('SMTP_FROM', 'noreply@votresite.com');
define('SMTP_NAME', 'Malisafi');
```

---

## ✅ Checklist de Vérification

Avant de lancer :

- [ ] La page d'inscription est créée et publiée
- [ ] Le shortcode affiche le formulaire correctement
- [ ] Les 4 types de comptes sont cliquables
- [ ] La progression à 3 étapes fonctionne
- [ ] La validation en temps réel marche
- [ ] Les emails de bienvenue sont envoyés
- [ ] Les redirections fonctionnent après inscription
- [ ] Le formulaire est responsive (testez sur mobile)
- [ ] Les pages CGU et Politique de confidentialité existent
- [ ] Le site utilise HTTPS (sécurité des mots de passe)

---

## 🎨 Personnalisation Rapide

### Changer les Couleurs
Éditez `assets/css/registration-form.css`, lignes 20-22 :

```css
background: linear-gradient(135deg, #VotreCouleur1 0%, #VotreCouleur2 100%);
```

### Modifier les Textes
Tous les textes sont dans `templates/registration-form.php`. Exemple :

```php
<h2><?php _e('Welcome to Malisafi! 👋', 'malisafi-mls'); ?></h2>
```

Changez en :
```php
<h2><?php _e('Bienvenue chez Malisafi! 👋', 'malisafi-mls'); ?></h2>
```

---

## 🐛 Dépannage Express

### Le formulaire ne s'affiche pas
```
1. Vérifiez que le shortcode est correct : [malisafi_registration]
2. Videz le cache (Ctrl+F5 ou Cmd+Shift+R)
3. Vérifiez que le plugin Malisafi est activé
```

### Les styles sont cassés
```
1. Allez dans Admin → Réglages → Permaliens
2. Cliquez sur "Enregistrer" (sans rien changer)
3. Videz le cache de votre site
```

### L'inscription ne fonctionne pas
```
1. Ouvrez la console du navigateur (F12)
2. Vérifiez s'il y a des erreurs JavaScript
3. Testez avec un autre navigateur
4. Vérifiez que JavaScript est activé
```

### Pas d'email de bienvenue
```
1. Vérifiez vos spams/courrier indésirable
2. Testez l'envoi d'email WordPress (Admin → Outils → Santé du site)
3. Installez un plugin SMTP (voir section Configuration Email)
```

---

## 📞 Besoin d'Aide ?

### Logs de Debug
Activez le mode debug WordPress dans `wp-config.php` :

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Les logs seront dans `wp-content/debug.log`

### Console JavaScript
Ouvrez la console (F12) et tapez :
```javascript
console.log(malisafiRegistration);
```

Vous devriez voir :
```javascript
{
  ajaxUrl: "https://votresite.com/wp-admin/admin-ajax.php",
  nonce: "abc123...",
  dashboardUrl: "https://votresite.com/dashboard"
}
```

---

## 🎉 C'est Prêt !

Votre système d'inscription conversationnel est maintenant opérationnel !

**URL de test** : Remplacez avec votre domaine
```
https://votresite.com/inscription
```

**Prochaines étapes** :
1. Tester les 4 types de comptes
2. Personnaliser les couleurs/textes
3. Configurer les emails SMTP
4. Promouvoir votre page d'inscription

---

**Astuce Pro** : Ajoutez un bouton "S'inscrire" bien visible sur votre page d'accueil !

```html
<a href="/inscription" class="btn-cta">
  Créer mon compte gratuitement →
</a>
```
