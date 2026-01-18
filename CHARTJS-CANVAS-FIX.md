# Chart.js Canvas Height Fix - RÉSOLU

**Date:** 18 janvier 2026  
**Problème:** Canvas exceeds max size - Hauteur infinie sur les graphiques

## Symptômes

```javascript
Uncaught DOMException: CanvasRenderingContext2D.setTransform: Canvas exceeds max size.
    ke chart.umd.min.js:19
    _resize chart.umd.min.js:19
```

- Hauteur infinie/très grande sur Device Stats
- Tableau Top Contributors avec hauteur excessive
- Problème visible sur plusieurs pages d'analytics

## Cause Racine

Chart.js calcule la hauteur du canvas basée sur son conteneur parent. Quand le canvas a un style `height` inline mais pas de conteneur avec hauteur fixe, le navigateur peut créer une boucle de redimensionnement infinie.

### ❌ Mauvaise Structure
```html
<canvas id="deviceChart" style="height: 250px;"></canvas>
```

### ✅ Bonne Structure
```html
<div style="height: 250px; position: relative;">
    <canvas id="deviceChart"></canvas>
</div>
```

## Solutions Appliquées

### 1. Properties Analytics (`admin/analytics/properties.php`)

**Corrections:**
- ✅ `deviceChart` - Enveloppé dans conteneur 250px
- ✅ `trafficChart` - Enveloppé dans conteneur 250px  
- ✅ `geoChart` - Enveloppé dans conteneur 350px
- ✅ `conversionChart` - Déjà corrigé (conteneur 300px)

**Code:**
```php
<!-- AVANT -->
<canvas id="deviceChart" style="height: 250px;"></canvas>

<!-- APRÈS -->
<div style="height: 250px; position: relative;">
    <canvas id="deviceChart"></canvas>
</div>
```

### 2. User Activity Analytics (`admin/analytics/user-activity.php`)

**Corrections:**
- ✅ Tableau Top Contributors - Ajout de `max-height: 600px` avec scroll
- ✅ `activityChart` - Déjà dans conteneur correct
- ✅ `timelineChart` - Déjà dans conteneur correct

**Code:**
```php
<!-- Limitation du tableau -->
<div style="max-height: 600px; overflow-y: auto;">
    <table style="width: 100%; border-collapse: collapse;">
        <!-- ... contenu tableau ... -->
    </table>
</div>
```

### 3. Revenue Analytics (`admin/analytics/revenue.php`)

**Corrections:**
- ✅ `revenueByTypeChart` - Enveloppé dans conteneur 300px
- ✅ `subscriptionChart` - Enveloppé dans conteneur 300px
- ✅ `revenueTimelineChart` - Vérifier si déjà correct

**Code:**
```php
<!-- AVANT -->
<div>
    <canvas id="revenueByTypeChart" style="height: 300px;"></canvas>
</div>

<!-- APRÈS -->
<div style="height: 300px; position: relative;">
    <canvas id="revenueByTypeChart"></canvas>
</div>
```

### 4. Overview Analytics (`admin/analytics/overview.php`)

**Status:** ✅ Déjà correct
- Utilise `.chart-canvas-wrapper` avec hauteurs fixes
- Tous les canvas sont dans des conteneurs appropriés

## Règles à Suivre

### ✅ TOUJOURS FAIRE

1. **Conteneur avec hauteur fixe:**
   ```html
   <div style="height: 250px; position: relative;">
       <canvas id="myChart"></canvas>
   </div>
   ```

2. **Configuration Chart.js:**
   ```javascript
   options: {
       responsive: true,
       maintainAspectRatio: false,  // IMPORTANT!
       // ... autres options
   }
   ```

3. **Pour les tableaux avec beaucoup de données:**
   ```html
   <div style="max-height: 600px; overflow-y: auto;">
       <table>...</table>
   </div>
   ```

### ❌ NE JAMAIS FAIRE

1. **Hauteur inline sur canvas:**
   ```html
   <!-- ❌ MAUVAIS -->
   <canvas style="height: 250px;"></canvas>
   ```

2. **Canvas sans conteneur:**
   ```html
   <!-- ❌ MAUVAIS -->
   <div class="some-class">
       <canvas id="chart"></canvas>
   </div>
   ```

3. **Oublier maintainAspectRatio:**
   ```javascript
   // ❌ MAUVAIS
   options: {
       responsive: true
       // maintainAspectRatio manquant
   }
   ```

## Vérification

### Test Manuel
1. Ouvrir chaque page d'analytics
2. Vérifier que les graphiques s'affichent correctement
3. Redimensionner la fenêtre - pas d'erreurs console
4. Vérifier la console navigateur (F12) - aucune erreur Canvas

### Pages à Tester
- ✅ Analytics → Overview
- ✅ Analytics → Properties
- ✅ Analytics → User Activity
- ✅ Analytics → Revenue
- ✅ Analytics → Searches
- ✅ Analytics → System Health
- ✅ Analytics → Fraud Detection

## Fichiers Modifiés

1. `admin/analytics/properties.php` (lignes 220-270)
2. `admin/analytics/user-activity.php` (lignes 94-150)
3. `admin/analytics/revenue.php` (lignes 115-170)

## Prévention Future

### Template pour Nouveau Graphique
```php
<!-- Container avec hauteur fixe -->
<div class="malisafi-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Titre du Graphique', 'malisafi-mls'); ?></h3>
    
    <!-- Conteneur avec hauteur FIXE et position relative -->
    <div style="height: 300px; position: relative;">
        <canvas id="monGraphique"></canvas>
    </div>
</div>

<script>
// Initialisation Chart.js
const ctx = document.getElementById('monGraphique');
if (ctx) {
    new Chart(ctx, {
        type: 'bar', // ou 'line', 'pie', 'doughnut', etc.
        data: {
            labels: [],
            datasets: [{
                label: 'Label',
                data: [],
                backgroundColor: '#737d5d'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,  // ⚠️ ESSENTIEL!
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
</script>
```

## Ressources

- [Chart.js Responsive Charts](https://www.chartjs.org/docs/latest/configuration/responsive.html)
- [Canvas API - MDN](https://developer.mozilla.org/en-US/docs/Web/API/Canvas_API)
- Plugin: Chart.js v3.x (version actuelle: vérifier dans package.json)

## Notes Techniques

### Pourquoi `position: relative` ?
Chart.js utilise `position: absolute` en interne pour certains éléments. Le parent doit avoir `position: relative` pour que le positionnement fonctionne correctement.

### Pourquoi `maintainAspectRatio: false` ?
Par défaut, Chart.js maintient un ratio d'aspect 2:1. Avec une hauteur fixe, on doit désactiver cela pour respecter la hauteur du conteneur.

### Hauteurs Recommandées
- **Petits graphiques (doughnut, pie):** 250-300px
- **Graphiques moyens (bar, line):** 300-400px
- **Grands graphiques (timeline):** 400-500px
- **Tableaux:** `max-height: 600px` avec `overflow-y: auto`

---

**Status:** ✅ RÉSOLU  
**Testé:** En attente de vérification utilisateur  
**Prochaine Étape:** Tester toutes les pages analytics
