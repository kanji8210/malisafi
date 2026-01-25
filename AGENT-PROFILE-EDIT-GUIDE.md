# Guide d'Édition du Profil Agent

## Vue d'ensemble

Le système d'édition de profil permet aux agents de créer et gérer leur profil public avec photo, informations professionnelles et liens sociaux.

## Accès au Profil

**Chemin :** Dashboard Agent → Profil (onglet "Profile")

**URL directe :** `/agent-dashboard/?section=profile`

## Fonctionnalités

### 1. Photo de Profil

#### Téléchargement
- **Bouton :** "Choose Photo" (bouton primaire)
- **Méthode :** Utilise la médiathèque WordPress native
- **Formats acceptés :** JPG, PNG, WebP
- **Taille recommandée :** 400x400px (carré)
- **Taille maximale :** 2MB

#### Processus
1. Cliquer sur "Choose Photo"
2. Sélectionner une image depuis :
   - Bibliothèque médias existante
   - Upload d'un nouveau fichier
3. Cliquer "Use this photo"
4. L'aperçu se met à jour instantanément
5. Sauvegarder le formulaire pour persister

#### Suppression
- **Bouton :** "Remove Photo" (bouton secondaire)
- Affiche le placeholder par défaut (icône businessman)
- Conserve l'image dans la bibliothèque médias

### 2. Informations de Base

#### Champs obligatoires (*)
- **Email** : Email professionnel de contact
- **Phone Number** : Numéro principal (format international recommandé)

#### Champs optionnels
- **WhatsApp Number** : Pour contact direct WhatsApp
- **License Number** : Numéro de licence immobilière
- **Years of Experience** : Nombre d'années dans l'immobilier
- **Languages** : Langues parlées (séparées par virgules)
  - Exemple : `English, Swahili, French`

### 3. Informations Professionnelles

#### Bio / About Me
- **Type :** Zone de texte multiligne
- **Usage :** Présentation professionnelle
- **Conseils :**
  - Mentionner l'expérience
  - Décrire les points forts
  - Expliquer l'approche client
  - Rester concis (150-300 mots)

#### Specialties
- **Type :** Texte simple
- **Format :** Valeurs séparées par virgules
- **Exemples :**
  - `Luxury Homes, Commercial Properties`
  - `First-time Buyers, Investment Properties`
  - `Residential, Land Sales, Rentals`

### 4. Réseaux Sociaux

Tous les champs sont optionnels :
- **Facebook** : URL complète du profil
- **Twitter** : URL complète du profil
- **LinkedIn** : URL complète du profil professionnel
- **Instagram** : URL complète du compte

**Format attendu :** `https://www.facebook.com/username`

## Workflow de Sauvegarde

### Process Technique
```
1. Remplir le formulaire
2. Cliquer "Save Profile"
3. AJAX envoie les données → save_agent_profile
4. Backend valide et sanitize
5. Création/mise à jour du post malisafi_agent
6. Mise à jour des meta fields _agent_*
7. Retour JSON success/error
8. Affichage du message à l'utilisateur
```

### États du Bouton
- **Normal :** "Save Profile" avec icône checkmark
- **Saving :** "Saving..." avec spinner animé
- **Success :** Retour à l'état normal + message vert
- **Error :** Retour à l'état normal + message rouge

### Messages
- **Success :** "Profile updated successfully!"
- **Error :** Message spécifique de l'erreur
- **Durée affichage :** 5 secondes (fadeOut automatique)

## Structure des Données

### Post Type
```php
Post Type: malisafi_agent
Post Status: publish
Post Author: {user_id}
```

### Meta Fields
```php
_agent_user_id       => Current user ID
_agent_photo         => Attachment ID
_agent_email         => Sanitized email
_agent_phone         => Sanitized phone
_agent_whatsapp      => Sanitized phone
_agent_license       => Sanitized text
_agent_experience    => Integer
_agent_languages     => Sanitized text
_agent_bio           => Sanitized textarea
_agent_specialties   => Sanitized text
_agent_facebook      => Sanitized URL
_agent_twitter       => Sanitized URL
_agent_linkedin      => Sanitized URL
_agent_instagram     => Sanitized URL
```

## Sécurité

### Nonces
- **Photo upload :** `upload_agent_photo`
- **Profile save :** `save_agent_profile`
- **Vérification :** `check_ajax_referer()` côté serveur

### Sanitization
- **Email :** `sanitize_email()`
- **Text :** `sanitize_text_field()`
- **Textarea :** `sanitize_textarea_field()`
- **URL :** `esc_url_raw()`
- **Number :** `absint()`

### Validation
- Utilisateur connecté requis
- Nonce valide requis
- Types de fichiers contrôlés
- Taille de fichier limitée

## Intégration Media Uploader

### Enqueue
```php
wp_enqueue_media(); // Dans le template
```

### Configuration
```javascript
wp.media({
    title: 'Choose Profile Photo',
    button: { text: 'Use this photo' },
    library: { type: 'image' },
    multiple: false
})
```

### Événements
- `select` : Quand une image est choisie
- Récupère `attachment.id` et `attachment.url`
- Met à jour l'input hidden `agent_photo_id`
- Affiche l'aperçu en temps réel

## CSS et Design

### Variables utilisées
```css
--mls-accent: #737d5d       /* Boutons primaires */
--mls-dark: #2c2c2c         /* Hover states */
--mls-text-primary          /* Texte principal */
--mls-border-light          /* Bordures */
```

### Classes principales
- `.dashboard-profile-editor` : Container principal
- `.agent-profile-form` : Formulaire
- `.form-section` : Section groupée
- `.profile-photo-upload` : Zone photo
- `.photo-preview` : Aperçu 200x200px
- `.photo-controls` : Boutons et descriptions
- `.form-grid` : Grille responsive 2 colonnes
- `.form-group` : Groupe champ individuel

### Responsive
```css
@media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
    .profile-photo-upload { flex-direction: column; }
}
```

## Profil Public

### Visualisation
- **Bouton :** "View Public Profile" (en haut du formulaire)
- **URL :** `/agent-profile/?agent_id={id}`
- **Ouverture :** Nouvel onglet (target="_blank")

### Affichage Public
Fichier : `templates/agent-profile-public.php`
- Photo de profil
- Nom et statut
- Informations de contact
- Bio
- Spécialités
- Liens sociaux
- Propriétés de l'agent
- Système de reviews

## Bonnes Pratiques

### Pour les Agents
1. **Photo :** Utilisez un portrait professionnel de qualité
2. **Bio :** Soyez authentique et précis
3. **Contacts :** Vérifiez tous les numéros/emails
4. **Spécialités :** Soyez spécifique (max 3-4)
5. **Réseaux :** Ajoutez seulement les comptes actifs

### Pour les Développeurs
1. Toujours vérifier les nonces
2. Sanitize toutes les entrées utilisateur
3. Utiliser wp_send_json_* pour les réponses
4. Logger les erreurs en mode debug
5. Tester avec différents rôles utilisateurs

## Dépannage

### Photo ne s'affiche pas
1. Vérifier permissions dossier uploads
2. Vérifier taille fichier < 2MB
3. Vérifier format (JPG/PNG/WebP)
4. Console browser pour erreurs JS

### Formulaire ne sauvegarde pas
1. Vérifier console pour erreurs AJAX
2. Vérifier nonce dans Network tab
3. Vérifier user capabilities
4. Activer WP_DEBUG pour logs

### Media uploader ne s'ouvre pas
1. Vérifier `wp_enqueue_media()` chargé
2. Vérifier pas de conflits JS
3. Vérifier version WordPress >= 3.5

## Fichiers Concernés

```
templates/
  └── agent-dashboard-profile.php   (411 lignes) - Template principal

includes/
  └── class-agent-profile-ajax.php  (360 lignes) - AJAX handlers

assets/css/
  └── agent-dashboard-clean.css     - Styles dashboard
```

## Actions AJAX

### upload_agent_photo
- **Hook :** `wp_ajax_upload_agent_photo`
- **Method :** POST
- **Nonce :** `upload_agent_photo`
- **Response :** `{ attachment_id, url }`

### save_agent_profile
- **Hook :** `wp_ajax_save_agent_profile`
- **Method :** POST (FormData)
- **Nonce :** `agent_profile_nonce`
- **Response :** `{ message, agent_id }`

## Compatibilité

- **WordPress :** 5.0+
- **PHP :** 7.4+
- **Navigateurs :** Chrome, Firefox, Safari, Edge (dernières versions)
- **Mobile :** Responsive design adapté
