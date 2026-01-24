# Status Badges System - Guide d'utilisation

## Vue d'ensemble

Le système de badges de statut affiche visuellement le statut de chaque propriété sur toutes les miniatures et cartes de propriété à travers le plugin.

## Couleurs des badges par statut

### Statuts disponibles avec couleurs distinctives

| Statut | Couleur | Code couleur (gradient) |
|--------|---------|------------------------|
| **For Sale** | Vert | `#16a34a` → `#15803d` |
| **For Rent** | Bleu | `#2563eb` → `#1d4ed8` |
| **Sold** | Rouge | `#dc2626` → `#b91c1c` |
| **Rented** | Violet | `#9333ea` → `#7e22ce` |
| **Pending** | Orange | `#f59e0b` → `#d97706` |
| **Under Offer** | Jaune | `#eab308` → `#ca8a04` |
| **Status Not Recorded** | Gris | `#6b7280` → `#4b5563` |

**Note importante** : Si une propriété n'a pas de statut défini, le badge "Status Not Recorded" (gris) sera affiché automatiquement.

## Fichiers modifiés

### Fichiers CSS
1. **`assets/css/property-grids-unified.css`**
   - Ajout des classes de statut avec couleurs distinctives
   - Styles pour tous les badges (status, setting, listing-type)
   - Positionnement responsive

2. **`assets/css/agent-dashboard-clean.css`**
   - Styles pour badges dans les miniatures du dashboard
   - Support des badges sur `property-thumbnail`

3. **`assets/css/agent-profile-public.css`**
   - Positionnement des badges sur les images de propriétés
   - Support pour le profil public des agents

### Templates PHP

1. **`templates/property-card-modern.php`**
   - Ajout de la classe CSS dynamique selon le statut
   - Badge affiché sur l'image wrapper

2. **`templates/properties-grid.php`**
   - Badge de statut avec classe CSS spécifique
   
3. **`templates/featured-properties.php`**
   - Badge de statut avec classe CSS spécifique

4. **`templates/agent-profile-public.php`**
   - Badge de statut sur chaque carte de propriété
   - Récupération du statut depuis les métadonnées

5. **`templates/agent-dashboard-home.php`**
   - Badge de statut sur les miniatures du dashboard
   - Affichage dans la section "Recent Properties"

6. **`admin/templates/properties-list-modern.php`**
   - Badge de statut dans la vue admin
   - Affichage avec Featured et Pending badges

## Utilisation dans les templates

### Code PHP pour afficher le badge

```php
<?php 
// Get status from taxonomy (NOT from meta field)
$status_terms = wp_get_post_terms($property_id, 'malisafi_property_status');
$property_status = (!empty($status_terms) && !is_wp_error($status_terms)) ? $status_terms[0]->name : '';

// Always show status badge - default to 'Status Not Recorded' if empty
if (!empty($property_status)) {
    $status_display = ucwords(str_replace('-', ' ', $property_status));
    $status_class = 'status-' . sanitize_html_class(strtolower(str_replace(' ', '-', $property_status)));
} else {
    $status_display = 'Status Not Recorded';
    $status_class = 'status-not-recorded';
}
echo '<span class="status-badge ' . esc_attr($status_class) . '">' . esc_html($status_display) . '</span>';
?>
```

**IMPORTANT** : Le statut est stocké comme une **taxonomie** (`malisafi_property_status`), PAS comme un meta field. Utilisez toujours `wp_get_post_terms()` pour récupérer le statut.

### 2. **Property_Manager::get_property_data()**

La méthode `get_property_data()` dans `includes/class-property-manager.php` récupère automatiquement le statut depuis la taxonomie et l'ajoute au tableau retourné :

```php
// Dans includes/class-property-manager.php
public static function get_property_data($property_id) {
    // Get status from taxonomy
    $status_terms = wp_get_post_terms($property_id, 'malisafi_property_status');
    $status = (!empty($status_terms) && !is_wp_error($status_terms)) ? $status_terms[0]->name : '';
    
    return array(
        // ... autres données
        'status' => $status,
        // ...
    );
}
```

Si vous utilisez `Property_Manager::get_property_data()`, vous pouvez accéder au statut via `$property_data['status']`.

Le badge doit être placé à l'intérieur d'un conteneur d'image avec `position: relative` :

```html
<div class="property-image-wrapper">
    <img src="..." alt="...">
    <span class="status-badge status-for-sale">For Sale</span>
</div>
```

## Classes CSS utilisées

### Classes de base
- `.status-badge` - Classe de base pour tous les badges de statut

### Classes de statut spécifiques
- `.status-badge.status-for-sale` - Vert
- `.status-badge.status-for-rent` - Bleu
- `.status-badge.status-sold` - Rouge
- `.status-badge.status-rented` - Violet
- `.status-badge.status-pending` - Orange
- `.status-badge.status-under-offer` - Jaune
- `.status-badge.status-not-recorded` - Gris (par défaut si statut manquant)

## Positionnement

### Par défaut
- **Position** : Absolute, top-left de l'image
- **Coordonnées** : `top: 12px; left: 12px;`
- **z-index** : 3
- **Box-shadow** : `0 3px 8px rgba(0, 0, 0, 0.25)`

### Mobile (≤768px)
- Badges réduits : `padding: 5px 12px; font-size: 10px;`
- Position ajustée : `top: 10px; left: 10px;`

### Stacking des badges

Si plusieurs badges sont présents :
1. **Listing Type Badge** : Top-left (`top: 12px; left: 12px;`)
2. **Status Badge** : En dessous (`top: 46px; left: 12px;`)
3. **Setting Badge** : Top-right (`top: 12px; right: 12px;`)

## Responsive Design

Le système est entièrement responsive avec des ajustements pour :
- **Desktop Wide** (≥1201px) : 3 colonnes
- **Desktop Medium** (1025px-1200px) : 3 colonnes
- **Tablet** (769px-1024px) : 2 colonnes
- **Mobile** (≤768px) : 1 colonne avec badges réduits
- **Small Mobile** (≤480px) : Espacement réduit

## Variables CSS utilisées

Conformément au design system :
- `--mls-text-inverse` : Couleur du texte (blanc)
- Gradients personnalisés pour chaque statut

## Notes importantes

1. **CRITIQUE** : Le statut est une **taxonomie** (`malisafi_property_status`), pas un meta field
   - Utilisez `wp_get_post_terms($property_id, 'malisafi_property_status')`
   - NE PAS utiliser `get_post_meta($property_id, '_malisafi_status')`
   
2. **Toujours utiliser** `sanitize_html_class()` pour les classes dynamiques

3. **Échapper la sortie** avec `esc_html()` et `esc_attr()`

4. **Vérifier l'existence** du statut avant d'afficher le badge

5. **Respecter la hiérarchie** : status badge ne doit jamais chevaucher d'autres éléments importants

## Compatibilité

- **WordPress** : 5.0+
- **PHP** : 7.4+
- **Navigateurs** : Tous les navigateurs modernes (Chrome, Firefox, Safari, Edge)
- **Mobile** : Optimisé pour tous les appareils

## Maintenance

Pour ajouter un nouveau statut :

1. Définir la couleur dans `property-grids-unified.css` :
```css
.status-badge.status-nouveau-statut {
    background: linear-gradient(135deg, #color1 0%, #color2 100%);
}
```

2. Le template PHP générera automatiquement la classe appropriée

## Testing

Testez avec différents statuts :
- For Sale
- For Rent  
- Sold
- Rented
- Pending
- Under Offer
- Propriétés sans statut (affichera "Status Not Recorded")

Vérifiez :
- Affichage correct des couleurs
- Positionnement sur tous les écrans
- Responsive sur mobile
- Pas de chevauchement avec d'autres badges
- Les icônes dashicons s'affichent correctement

## Corrections des problèmes d'icônes

Si les dashicons ne s'affichent pas correctement :

1. **Vérifier que dashicons est chargé** : Le fichier `public/class-public.php` doit contenir `wp_enqueue_style('dashicons');`

2. **Styles CSS pour forcer l'affichage** :
```css
.dashicons,
i.dashicons,
span.dashicons {
    font-family: dashicons !important;
    display: inline-block;
}
```

Ces styles sont déjà ajoutés dans `property-grids-unified.css` pour assurer une compatibilité maximale.
