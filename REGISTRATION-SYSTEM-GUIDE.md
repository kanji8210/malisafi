# 📝 Système d'Inscription Conversationnel - Guide Complet

## 🎯 Vue d'ensemble

Le nouveau système d'inscription de Malisafi offre une expérience utilisateur moderne et conversationnelle avec :

- **Interface en plusieurs étapes** : Navigation fluide avec barre de progression
- **Choix de types de compte** : Client, Agent, Propriétaire, ou Développeur
- **Validation en temps réel** : Vérification instantanée des données
- **Design responsive** : Fonctionne parfaitement sur mobile, tablette et desktop
- **Expérience interactive** : Animations, transitions et feedback visuel

---

## 🚀 Installation et Configuration

### 1. Fichiers créés

Les fichiers suivants ont été ajoutés au plugin :

```
templates/
  └── registration-form.php          # Template du formulaire HTML

assets/
  ├── css/
  │   └── registration-form.css      # Styles du formulaire
  └── js/
      └── registration-form.js       # Logique interactive JavaScript

includes/
  └── class-registration-handler.php # Gestionnaire backend PHP
```

### 2. Utilisation du shortcode

Pour afficher le formulaire d'inscription sur n'importe quelle page :

```
[malisafi_registration]
```

ou

```
[malisafi_register]
```

### 3. Créer une page d'inscription

1. **Allez dans WordPress Admin** → Pages → Ajouter
2. **Titre** : "Inscription" ou "Register"
3. **Contenu** : Ajoutez simplement le shortcode `[malisafi_registration]`
4. **Publiez** la page

---

## 🎨 Fonctionnalités du Formulaire

### Étape 1 : Choix du Type de Compte

L'utilisateur choisit parmi 4 options :

#### 🏠 **Client** (`malisafi_client`)
- **Objectif** : Trouver une propriété
- **Description** : "Je recherche à acheter, louer ou investir dans des propriétés"
- **Accès** : Dashboard client standard

#### 💼 **Agent** (`malisafi_agent_basic`)
- **Objectif** : Travailler comme agent immobilier
- **Description** : "Je suis un professionnel de l'immobilier qui aide les clients"
- **Champs supplémentaires** :
  - Nom de l'agence (optionnel)
  - Numéro de licence (optionnel)
- **Accès** : Dashboard agent avec fonctionnalités avancées

#### 🔑 **Propriétaire** (`malisafi_owner`)
- **Objectif** : Lister une propriété
- **Description** : "Je possède une propriété que je veux vendre ou louer"
- **Accès** : Dashboard propriétaire

#### 🏗️ **Développeur** (`malisafi_developer`)
- **Objectif** : Promouvoir des projets
- **Description** : "Je développe et commercialise de nouveaux projets immobiliers"
- **Accès** : Dashboard développeur

### Étape 2 : Informations Personnelles

**Champs requis :**
- Prénom *
- Nom *
- Numéro de téléphone * (format Kenya : +254)

**Champs conditionnels (pour Agents uniquement) :**
- Nom de l'agence
- Numéro de licence professionnelle

### Étape 3 : Identifiants du Compte

**Champs requis :**
- Adresse email * (avec vérification en temps réel)
- Nom d'utilisateur * (minimum 4 caractères, vérification de disponibilité)
- Mot de passe * (minimum 8 caractères)
- Confirmation du mot de passe *
- Acceptation des conditions d'utilisation *

**Fonctionnalités :**
- 👁️ Afficher/masquer le mot de passe
- 📊 Indicateur de force du mot de passe (Faible/Moyen/Fort)
- ✅ Validation en temps réel
- 🔍 Vérification AJAX de l'email et du nom d'utilisateur

---

## ⚙️ Fonctionnement Technique

### AJAX Endpoints

Le système utilise 3 endpoints AJAX :

#### 1. `malisafi_register_user`
**Fonction** : Enregistre un nouvel utilisateur

**Données envoyées** :
```javascript
{
  action: 'malisafi_register_user',
  nonce: 'security_token',
  account_type: 'client|agent|owner|developer',
  user_role: 'malisafi_client|malisafi_agent_basic|...',
  first_name: 'John',
  last_name: 'Doe',
  phone: '+254712345678',
  email: 'john@example.com',
  username: 'johndoe',
  password: '********',
  agency_name: 'ABC Realty', // Optionnel
  license_number: 'LIC123'    // Optionnel
}
```

**Réponse en cas de succès** :
```javascript
{
  success: true,
  data: {
    message: 'Registration successful!',
    redirect: '/dashboard',
    user_id: 123
  }
}
```

#### 2. `malisafi_check_email`
**Fonction** : Vérifie si l'email existe déjà

#### 3. `malisafi_check_username`
**Fonction** : Vérifie si le nom d'utilisateur existe déjà

### Validation et Sécurité

**Côté serveur (PHP) :**
- ✅ Vérification du nonce WordPress
- ✅ Sanitisation de toutes les entrées
- ✅ Validation des formats (email, téléphone, nom d'utilisateur)
- ✅ Vérification de la disponibilité (email, username)
- ✅ Vérification de la force du mot de passe
- ✅ Validation du rôle utilisateur

**Côté client (JavaScript) :**
- ✅ Validation en temps réel
- ✅ Feedback visuel immédiat
- ✅ Vérification AJAX avant soumission
- ✅ Protection contre la double soumission

### Email de Bienvenue

Après inscription, l'utilisateur reçoit un email contenant :
- Message de bienvenue personnalisé
- Type de compte créé
- Lien vers le dashboard
- Informations de contact

---

## 🎨 Personnalisation

### Modifier les Couleurs

Dans `assets/css/registration-form.css`, modifiez les couleurs principales :

```css
/* Couleur primaire du thème */
.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Couleur d'accent */
.progress-step.active .step-circle {
    background: #667eea;
}
```

### Ajouter des Champs Personnalisés

1. **Modifier le template HTML** (`templates/registration-form.php`)
2. **Mettre à jour le JavaScript** pour la validation
3. **Modifier le handler PHP** pour traiter les nouvelles données

### Personnaliser les Messages

Les messages sont traduisibles via WordPress. Utilisez les fonctions :
- `__('Message', 'malisafi-mls')`
- `_e('Message', 'malisafi-mls')`

---

## 🔧 Hooks et Filtres

### Actions disponibles

```php
// Après l'inscription d'un utilisateur
do_action('malisafi_user_registered', $user_id, $user_role, $account_type);
```

**Exemple d'utilisation :**
```php
add_action('malisafi_user_registered', function($user_id, $user_role, $account_type) {
    // Envoyer une notification admin
    // Créer un profil par défaut
    // Logger l'événement
}, 10, 3);
```

### Filtres disponibles

```php
// Personnaliser l'email de bienvenue
apply_filters('malisafi_welcome_email_message', $message, $user_id, $account_type);
```

**Exemple d'utilisation :**
```php
add_filter('malisafi_welcome_email_message', function($message, $user_id, $account_type) {
    // Ajouter du contenu personnalisé
    return $message . "\n\n" . "Contenu supplémentaire";
}, 10, 3);
```

---

## 📱 Responsive Design

Le formulaire est optimisé pour tous les appareils :

### Desktop (> 768px)
- Grille de 2-4 colonnes pour les cartes de type de compte
- Champs en ligne (prénom/nom côte à côte)
- Boutons alignés à droite

### Tablette (≤ 768px)
- Grille d'une colonne
- Champs empilés
- Boutons pleine largeur

### Mobile (≤ 480px)
- Interface simplifiée
- Icônes et texte plus petits
- Optimisé pour le toucher

---

## 🐛 Dépannage

### Le formulaire ne s'affiche pas

**Vérifiez :**
1. Le shortcode est bien `[malisafi_registration]` ou `[malisafi_register]`
2. Les fichiers CSS/JS sont chargés (vérifier la console du navigateur)
3. Le plugin est activé

### Les styles ne s'appliquent pas

**Solutions :**
1. Vider le cache du navigateur
2. Vider le cache WordPress (si plugin de cache installé)
3. Vérifier que le fichier CSS existe dans `assets/css/registration-form.css`

### AJAX ne fonctionne pas

**Vérifiez :**
1. La console du navigateur pour les erreurs JavaScript
2. Que l'URL AJAX est correcte (`admin-ajax.php`)
3. Que le nonce est valide

**Debug :**
```javascript
console.log(malisafiRegistration); // Devrait afficher l'objet avec ajaxUrl
```

### Erreurs de validation

**Causes courantes :**
- Email ou username déjà existant
- Mot de passe trop court (< 8 caractères)
- Champs requis manquants
- Format de téléphone invalide

---

## 🚀 Améliorations Futures

### Suggestions d'amélioration

1. **Vérification OTP par SMS** : Valider le numéro de téléphone
2. **Connexion sociale** : Google, Facebook, LinkedIn
3. **Upload de photo de profil** : Pendant l'inscription
4. **Onboarding guidé** : Tutoriel après inscription
5. **Vérification email** : Envoyer un lien de confirmation
6. **Captcha** : Protection anti-spam (reCAPTCHA)
7. **Progression sauvegardée** : Permettre de reprendre plus tard

---

## 📊 Métriques et Analytics

Pour suivre les inscriptions, vous pouvez ajouter :

```php
add_action('malisafi_user_registered', function($user_id, $user_role, $account_type) {
    // Logger dans une table personnalisée
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'mf_registration_log',
        array(
            'user_id' => $user_id,
            'account_type' => $account_type,
            'registered_at' => current_time('mysql')
        )
    );
}, 10, 3);
```

---

## 📞 Support

Pour toute question ou problème :

1. Vérifiez ce guide
2. Consultez les logs WordPress (WP_DEBUG)
3. Vérifiez la console du navigateur
4. Contactez l'équipe de développement

---

## ✅ Checklist de Déploiement

Avant de mettre en production :

- [ ] Tester tous les types de compte (Client, Agent, Owner, Developer)
- [ ] Vérifier l'envoi des emails de bienvenue
- [ ] Tester sur mobile, tablette et desktop
- [ ] Vérifier la validation en temps réel
- [ ] Tester les redirections après inscription
- [ ] Vérifier les permissions par rôle
- [ ] Activer HTTPS pour sécuriser les mots de passe
- [ ] Configurer les emails (SMTP recommandé)
- [ ] Tester les liens dans l'email de bienvenue
- [ ] Ajouter les pages CGU et Politique de confidentialité

---

## 🎉 Félicitations !

Votre système d'inscription conversationnel est maintenant prêt à accueillir vos utilisateurs avec une expérience moderne et engageante !
