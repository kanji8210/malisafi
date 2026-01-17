# Malisafi Analytics - User Activity Tracking

## Overview

Le système **User Activity** suit et analyse toutes les activités des utilisateurs sur la plateforme Malisafi. Cela inclut les connexions, les soumissions de propriétés, les recherches, et les interactions.

## Components

### 1. **Tracking System** (`Analytics_Tracker`)
- Enregistre automatiquement toutes les activités utilisateur
- Utilise WordPress hooks et AJAX
- Session management avec UUIDs
- Détection du type d'appareil (mobile, tablette, desktop)

### 2. **Analytics Queries** (`Analytics_Core`)
- `get_login_frequency()` - Fréquence des connexions par rôle
- `get_top_contributors()` - Utilisateurs les plus actifs
- `get_activity_trends()` - Tendances d'activité quotidiennes
- `get_dropoff_points()` - Points d'abandon dans les formulaires

### 3. **Dashboard Pages**

#### Overview Page (`/wp-admin/admin.php?page=malisafi-analytics`)
- 6 statistiques clés
- 4 graphiques Chart.js
- Sélecteur de plage de dates (7/30/90/365 jours)

#### User Activity Page (`/wp-admin/admin.php?page=malisafi-analytics-users`)
- **Fréquence des connexions par rôle**
  - Nombre d'utilisateurs uniques
  - Total des connexions
  - Moyenne de connexions par utilisateur
  - Durée moyenne des sessions

- **Contributeurs principaux**
  - Ranking des utilisateurs par propriétés ajoutées
  - Vues et demandes reçues
  - Score d'engagement
  - Données de contact

- **Analyse d'abandon de formulaire**
  - Taux de progression par étape
  - Points d'abandon
  - Taux de completion
  - Temps moyen par étape

- **Graphiques**
  - Tendances d'activité quotidienne (logins, propriétés, recherches)
  - Timeline des actions utilisateur

## Database Tables

### `wp_mf_user_activity`
Enregistre chaque action utilisateur:
- Logins/Logouts
- Visites de pages
- Soumission de propriétés
- Modifications de profil
- Recherches et filtres

**Colonnes clés:**
```sql
- user_id (BIGINT)
- activity_type (ENUM: login, logout, property_add_complete, etc.)
- activity_data (JSON)
- session_id (VARCHAR 255)
- device_type (mobile/tablet/desktop)
- created_at (TIMESTAMP)
```

### `wp_mf_submission_funnel`
Suivi détaillé du processus de soumission:
- Chaque étape du formulaire
- Durée à chaque étape
- Erreurs rencontrées
- Taux de completion

**Étapes suivies:**
1. `form_loaded` - Formulaire ouvert
2. `basic_info` - Informations de base remplies
3. `pricing` - Prix défini
4. `details` - Détails ajoutés
5. `location` - Localisation (Kenya)
6. `amenities` - Commodités sélectionnées
7. `images` - Images téléchargées
8. `submit_success` - Soumise avec succès

## Event Tracking

### Hooks Enregistrés

```php
// WordPress Hooks
add_action('wp_login', 'track_login');
add_action('wp_logout', 'track_logout');
add_action('template_redirect', 'track_property_view');
add_action('save_post_malisafi_property', 'track_property_submission');

// AJAX Hooks
add_action('wp_ajax_malisafi_track_funnel', 'ajax_track_funnel');
add_action('wp_ajax_malisafi_track_interaction', 'ajax_track_interaction');
add_action('wp_ajax_malisafi_track_view_duration', 'ajax_track_view_duration');
```

### Frontend Tracking (`analytics-tracking.js`)

L'appareil suit automatiquement:
- **Durée de vue** - Mise à jour toutes les 30 secondes + à la fermeture
- **Profondeur de défilement** - Pourcentage de la page vu
- **Interactions**
  - Clics sur galerie
  - Clics sur carte
  - Clics de contact (tel, email, WhatsApp)
  - Partages sociaux
  - Favoris

## Metrics Explained

### Login Frequency (Fréquence de connexion)

Affiche:
- **Unique Users** - Nombre d'utilisateurs distincts qui se sont connectés
- **Total Logins** - Nombre total de sessions de connexion
- **Avg/User** - Moyenne de connexions par utilisateur
- **Avg Session** - Durée moyenne d'une session

**Utilité:** Identifier les utilisateurs actifs vs passifs

### Top Contributors (Contributeurs principaux)

Affiche:
- **Utilisateurs** avec le plus de propriétés
- **Vues** reçues par leurs propriétés
- **Demandes** d'information reçues
- **Score d'engagement** (engagement score)

**Calcul:** `(Vues / Propriétés) = Score d'engagement`

### Form Dropoff (Abandon de formulaire)

Affiche:
- **Reached** - Sessions qui ont atteint cette étape
- **Completed** - Sessions qui l'ont complétée
- **Dropout %** - Pourcentage d'utilisateurs qui ont arrêté à cette étape

**Exemple:**
```
form_loaded:    1000 reached,  900 completed,  10% dropout
basic_info:      900 reached,  800 completed,  11% dropout
pricing:         800 reached,  600 completed,  25% dropout ⚠️
details:         600 reached,  550 completed,  8% dropout
```

Ici, l'étape **pricing** a un dropout élevé = à améliorer

### Activity Trends (Tendances d'activité)

Graphiques quotidiens:
- **Logins** - Connexions par jour
- **Properties Added** - Nouvelles propriétés par jour
- **Searches** - Recherches effectuées par jour
- **Active Users** - Utilisateurs uniques par jour

## Usage

### Accéder au Dashboard

1. **Allez à:** WordPress Admin → Malisafi Analytics
2. **Choisissez:** User Activity pour voir les activités utilisateur
3. **Filtrez par:** Plage de dates (7/30/90/365 jours)

### Interpréter les données

**Faible engagement → Actions:**
- Améliorez la présentation des propriétés
- Simplifiez le formulaire de soumission
- Envoyez des emails de re-engagement

**High dropoff sur une étape → Actions:**
- Identifiez les problèmes de convivialité
- Testez sur mobile
- Simplifiez les champs requis

**Top contributors → Actions:**
- Analysez leurs propriétés comme cas d'étude
- Identifiez les meilleures pratiques
- Envisagez des incitations pour les rôles

## Verification

Visitez: `/wp-content/plugins/malisafi/verify-analytics.php`

Vérifie:
- ✓ Tables de base de données créées
- ✓ Hooks enregistrés
- ✓ Données d'exemple
- ✓ Accès au menu Admin

## Next Steps

Phase 2 (Autres pages analytics):
- [ ] Properties Page - Analyse détaillée des propriétés
- [ ] Searches Page - Analyse des recherches et zéro résultats
- [ ] Revenue Page - Suivi des revenus Stripe
- [ ] Fraud Detection - Détection et gestion des fraudes
- [ ] System Health - Métriques de performance

## Files

- `includes/analytics/class-analytics-migration.php` - Création des tables
- `includes/analytics/class-analytics-tracker.php` - Tracking des événements
- `includes/analytics/class-analytics-core.php` - Requêtes analytiques
- `admin/analytics/user-activity.php` - Page du dashboard
- `admin/analytics/overview.php` - Page d'aperçu
- `assets/js/analytics-tracking.js` - Tracking frontend
- `assets/css/analytics.css` - Styles dashboard

---

**Status:** ✅ Phase 1 Complete - User Activity System Fully Operational
