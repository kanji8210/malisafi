# Malisafi MLS - WordPress Real Estate Plugin

Un plugin WordPress robuste et complet pour la gestion de propriétés immobilières (Multiple Listing Service - MLS).

## Caractéristiques

### Fonctionnalités Principales

- **Gestion complète des propriétés** : Ajoutez et gérez des propriétés immobilières avec tous les détails nécessaires
- **Custom Post Type** : Type de contenu personnalisé pour les propriétés avec capabilities personnalisées
- **Système de rôles avancé** : 6 rôles personnalisés avec permissions granulaires
- **Workflow de modération** : Approbation des propriétés avant publication
- **Taxonomies multiples** : Types de propriété, statuts, localisations et caractéristiques
- **Métadonnées avancées** : Prix, chambres, salles de bain, superficie, année de construction, etc.
- **Recherche avancée** : Filtrage par type, statut, localisation, prix, chambres, etc.
- **Propriétés en vedette** : Mettez en avant vos meilleures propriétés
- **Galerie d'images** : Support pour plusieurs photos par propriété
- **Informations d'agent** : Associez des agents immobiliers aux propriétés
- **Géolocalisation** : Intégration Google Maps avec latitude/longitude
- **Import/Export** : Importez et exportez des propriétés via CSV
- **Dashboard personnalisé** : Interface utilisateur basée sur les rôles

### Shortcodes Disponibles

```
[malisafi_properties] - Affiche une grille de propriétés
[malisafi_property_search] - Affiche un formulaire de recherche
[malisafi_featured_properties] - Affiche les propriétés en vedette
```

#### Paramètres des Shortcodes

**malisafi_properties :**
- `type` : Filtrer par type de propriété
- `status` : Filtrer par statut
- `location` : Filtrer par localisation
- `featured` : Afficher uniquement les propriétés en vedette
- `count` : Nombre de propriétés à afficher (défaut: 12)
- `orderby` : Trier par (date, price, title, etc.)
- `order` : Ordre (ASC ou DESC)

**malisafi_property_search :**
- `style` : Style du formulaire (horizontal ou vertical)

**malisafi_featured_properties :**
- `count` : Nombre de propriétés à afficher (défaut: 6)

## Installation

1. Téléchargez le plugin dans le dossier `/wp-content/plugins/malisafi_mls/`
2. Activez le plugin via le menu 'Extensions' dans WordPress
3. Configurez le plugin via 'MLS Settings' dans le menu WordPress
4. Les rôles personnalisés et les tables de base de données seront créés automatiquement lors de l'activation

### Vérification de l'Installation

Exécutez le script de vérification pour confirmer que tout est correctement installé :

```bash
php wp-content/plugins/malisafi_mls/verify-integration.php
```

Ou consultez les fichiers de documentation :
- **STATUS.md** : État actuel de l'intégration et checklist
- **ROLES.md** : Documentation complète des rôles et permissions
- **INTEGRATION.md** : Guide d'intégration et de test
- **CHANGELOG-ROLES.md** : Résumé des modifications apportées
- **FILES-CHANGED.md** : Liste des fichiers modifiés et créés

## Configuration

### Paramètres Généraux

- **Devise** : USD, EUR, GBP, etc.
- **Symbole de devise** : $, €, £, etc.
- **Position de la devise** : Avant ou après le montant
- **Séparateurs** : Milliers et décimales
- **Unité de surface** : Pieds carrés ou mètres carrés
- **Propriétés par page** : Nombre de propriétés affichées

### Paramètres des Fonctionnalités

- **Soumission front-end** : Permettre aux utilisateurs de soumettre des propriétés
- **Clé API Google Maps** : Pour l'intégration des cartes
- **Propriétés favorites** : Activer la liste de souhaits
- **Comparaison de propriétés** : Comparer jusqu'à 4 propriétés
- **Profils d'agents** : Pages de profil pour les agents

## Rôles Utilisateurs

Le plugin crée 6 rôles personnalisés avec des permissions spécifiques :

### 1. Client (malisafi_client)
- Consultation de propriétés
- Sauvegarde de favoris

### 2. Agent Basic (malisafi_agent_basic)
- Création de propriétés (soumises à modération)
- Édition de ses propres propriétés
- Accès au dashboard

### 3. Agent Premium (malisafi_agent_premium)
- Publication directe sans modération
- Mise en vedette de propriétés
- Boost des annonces
- Analytics avancées

### 4. Property Owner (malisafi_owner)
- Création de propriétés limitées
- Gestion de ses biens
- Accès au dashboard

### 5. Developer (malisafi_developer)
- Gestion de projets multiples
- Import en masse de propriétés
- Analytics avancées

### 6. Moderator (malisafi_moderator)
- Approbation des propriétés en attente
- Édition de toutes les propriétés
- Accès aux paramètres du plugin
- Modération complète

Pour plus de détails, consultez **ROLES.md**.

## Structure des Fichiers

```
malisafi_mls/
├── malisafi-mls.php          # Fichier principal du plugin
├── includes/                  # Classes principales
│   ├── class-core.php        # Classe core
│   ├── class-activator.php   # Activation
│   ├── class-deactivator.php # Désactivation
│   ├── class-loader.php      # Gestionnaire de hooks
│   ├── class-i18n.php        # Internationalisation
│   ├── class-post-types.php  # Custom post types
│   ├── class-property-manager.php # Gestion des propriétés
│   ├── class-database.php    # Gestion de la base de données
│   └── class-role-manager.php # Gestion des rôles et permissions
├── admin/                     # Zone d'administration
│   ├── class-admin.php       # Classe admin
│   └── partials/             # Templates admin
│       ├── settings-display.php
│       ├── import-export-display.php
│       └── dashboard-display.php
├── public/                    # Zone publique
│   └── class-public.php      # Classe publique
├── templates/                 # Templates front-end
│   ├── properties-grid.php   # Grille de propriétés
│   ├── search-form.php       # Formulaire de recherche
│   └── featured-properties.php # Propriétés vedettes
├── assets/                    # Ressources
│   ├── css/                  # Styles
│   └── js/                   # Scripts
├── README.md                 # Documentation
├── ROLES.md                  # Documentation des rôles
├── INTEGRATION.md            # Guide d'intégration
├── TODO.md                   # État du projet
└── verify-integration.php    # Script de vérification
```

## Utilisation

### Ajouter une Propriété

1. Allez dans 'Properties' > 'Add New'
2. Remplissez les informations de la propriété
3. Ajoutez les détails (chambres, salles de bain, superficie, etc.)
4. Définissez le prix et marquez comme vedette si nécessaire
5. Ajoutez l'adresse et les coordonnées GPS
6. Associez un agent immobilier
7. Publiez la propriété

### Créer des Taxonomies

- **Types de propriété** : Maison, Appartement, Villa, etc.
- **Statuts** : À vendre, À louer, Vendu, Loué, etc.
- **Localisations** : Villes, quartiers, etc.
- **Caractéristiques** : Piscine, Garage, Jardin, etc.

### Afficher les Propriétés

Utilisez les shortcodes dans vos pages ou articles :

```
[malisafi_property_search]
[malisafi_featured_properties count="6"]
[malisafi_properties count="12" orderby="date"]
```

## API et Fonctions

### Obtenir des Propriétés

```php
$properties = \MalisafiMLS\Property_Manager::get_properties(array(
    'property_type' => 'house',
    'min_price' => 100000,
    'max_price' => 500000,
    'min_bedrooms' => 3,
    'location' => 'new-york',
));
```

### Obtenir les Données d'une Propriété

```php
$data = \MalisafiMLS\Property_Manager::get_property_data($property_id);
```

### Formater un Prix

```php
$formatted_price = \MalisafiMLS\Property_Manager::format_price(250000);
```

## Base de Données

Le plugin crée 10 tables personnalisées (préfixe `wp_mf_`) :

- `mf_subscriptions` : Abonnements des utilisateurs (Stripe integration)
- `mf_user_limits` : Limites de propriétés par utilisateur
- `mf_properties` : Données étendues des propriétés
- `mf_property_amenities` : Équipements des propriétés
- `mf_property_media` : Médias des propriétés (photos, vidéos, tours virtuels)
- `mf_inquiries` : Demandes de renseignements
- `mf_saved_searches` : Recherches sauvegardées
- `mf_favorites` : Propriétés favorites
- `mf_moderation_queue` : File d'attente de modération
- `mf_analytics` : Statistiques et analyses

## Hooks et Filtres

### Actions Disponibles

- `malisafi_property_before_save` : Avant la sauvegarde d'une propriété
- `malisafi_property_after_save` : Après la sauvegarde d'une propriété
- `malisafi_property_deleted` : Quand une propriété est supprimée

### Filtres Disponibles

- `malisafi_property_query_args` : Modifier les arguments de requête
- `malisafi_property_data` : Modifier les données d'une propriété
- `malisafi_formatted_price` : Modifier le formatage du prix

## Internationalisation

Le plugin est prêt pour la traduction. Les fichiers de langue doivent être placés dans le dossier `/languages/`.

Domaine de texte : `malisafi-mls`

## Compatibilité

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.6+

## Support

Pour le support et les questions, veuillez contacter [support@malisafi.com](mailto:support@malisafi.com)

## Licence

Ce plugin est sous licence GPL v2 ou ultérieure.

## Crédits

Développé par Malisafi
Site web : https://malisafi.com

## Changelog

### Version 1.0.0
- Version initiale
- Gestion complète des propriétés
- Recherche avancée
- Propriétés en vedette
- Import/Export CSV
- Intégration Google Maps
- Shortcodes multiples
- Zone d'administration complète


