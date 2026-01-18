# Diagnostic des Statistiques Affichant Zéro

## Problème
Les statistiques du tableau de bord affichent zéro pour les utilisateurs, propriétés et autres éléments malgré la présence de données.

## Actions de Diagnostic Effectuées

### 1. Fichier de Debug Créé
**Fichier:** `debug-agent-stats.php`  
**URL:** `http://localhost/wp-content/plugins/malisafi/debug-agent-stats.php`

**À faire:**
1. Connectez-vous en tant qu'agent
2. Visitez l'URL ci-dessus
3. Copiez tous les résultats et partagez-les

### 2. Logs de Débogage Ajoutés
**Fichier modifié:** `templates/agent-dashboard-home.php`

**À faire:**
1. Visitez votre tableau de bord d'agent
2. Vérifiez le fichier `wp-content/debug.log`
3. Cherchez les lignes contenant "MALISAFI DEBUG"
4. Partagez ces lignes

### 3. Corrections Appliquées

**Fichier: `admin/class-dashboard-widgets.php`**
- ❌ Avant: Requêtes vers table inexistante `wp_mf_properties`
- ✅ Après: Requêtes vers `wp_posts` avec `post_type = 'malisafi_property'`

## Causes Possibles

### A. Problème de Table de Données
**Symptôme:** Les statistiques utilisent la mauvaise table.

**Tables Correctes:**
- **Properties:** `wp_posts` (post_type = 'malisafi_property')
- **Subscriptions:** `wp_mf_subscriptions`
- **Inquiries:** `wp_mf_inquiries`
- **User Limits:** `wp_mf_user_limits`

**Requête Correcte:**
```sql
SELECT COUNT(*) FROM wp_posts 
WHERE post_type = 'malisafi_property' 
AND post_status IN ('publish', 'pending', 'draft')
```

**Requête Incorrecte:**
```sql
SELECT COUNT(*) FROM wp_mf_properties  -- Cette table n'existe pas!
```

### B. Problème d'Auteur/Attribution
**Symptôme:** Les propriétés ne sont pas liées au bon utilisateur.

**Vérification:**
1. Dans admin WordPress, allez dans Properties
2. Vérifiez la colonne "Author"
3. Les propriétés doivent être attribuées aux agents

**Requête pour vérifier:**
```sql
SELECT ID, post_title, post_author, post_status 
FROM wp_posts 
WHERE post_type = 'malisafi_property'
```

### C. Variables Non Passées au Template
**Symptôme:** Les variables sont undefined dans le template.

**Variables Requises dans `agent-dashboard-home.php`:**
- `$total_properties`
- `$published`
- `$pending`
- `$recent_properties`
- `$current_user`

**Vérification dans `agent-dashboard-modern.php` (lignes 21-48):**
```php
$total_properties = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} 
     WHERE post_type = 'malisafi_property' 
     AND post_author = %d",
    $linked_user_id
));
```

### D. Post Type Pas Enregistré
**Symptôme:** WordPress ne reconnaît pas `malisafi_property`.

**Vérification:**
1. Dans admin, allez dans Properties menu
2. Si le menu n'existe pas, le post type n'est pas enregistré
3. Vérifiez `includes/class-post-types.php` ligne 131

**Code d'enregistrement:**
```php
register_post_type('malisafi_property', $args);
```

## Solutions par Priorité

### Solution 1: Vérifier les Données (PRIORITÉ HAUTE)
```sql
-- Dans phpMyAdmin ou adminer.php

-- 1. Combien de propriétés totales ?
SELECT COUNT(*) as total FROM wp_posts WHERE post_type = 'malisafi_property';

-- 2. Combien par statut ?
SELECT post_status, COUNT(*) as count 
FROM wp_posts 
WHERE post_type = 'malisafi_property' 
GROUP BY post_status;

-- 3. Combien par auteur ?
SELECT post_author, COUNT(*) as count 
FROM wp_posts 
WHERE post_type = 'malisafi_property' 
GROUP BY post_author;

-- 4. Détails complets
SELECT ID, post_title, post_author, post_status, post_date 
FROM wp_posts 
WHERE post_type = 'malisafi_property'
ORDER BY post_date DESC
LIMIT 20;
```

### Solution 2: Vérifier l'Utilisateur Connecté
**Dans `debug-agent-stats.php`, vérifiez:**
- User ID
- User roles (doit contenir `malisafi_agent_basic` ou `malisafi_agent_premium`)
- Liaison avec agent post (table `_agent_user_id` meta)

### Solution 3: Forcer le Recalcul
**Option A - Via Admin:**
1. Allez dans Properties
2. Bulk edit quelques propriétés
3. Changez le statut (puis rechangez-le)
4. Cela force WordPress à recalculer

**Option B - Via Code (temporaire):**
Ajoutez dans `agent-dashboard-modern.php` après ligne 48:
```php
// DEBUG: Afficher les valeurs brutes
echo '<!-- DEBUG Stats: Total=' . $total_properties . ', Published=' . $published . ', Pending=' . $pending . ' -->';
```

Visitez votre dashboard et faites "Afficher le code source" pour voir ces valeurs.

### Solution 4: Vider les Caches
```bash
# Si vous utilisez un plugin de cache
wp cache flush

# Ou manuellement:
# 1. Désactivez tous les plugins de cache
# 2. Videz le cache de votre navigateur
# 3. Rechargez le dashboard
```

### Solution 5: Vérifier les Permissions
**Dans `wp_posts`:**
```sql
-- Les propriétés d'un agent spécifique
SELECT COUNT(*) FROM wp_posts 
WHERE post_type = 'malisafi_property' 
AND post_author = 123  -- Remplacez par l'ID de votre agent
AND post_status IN ('publish', 'pending', 'draft');
```

## Tests à Effectuer (Dans l'Ordre)

### Test 1: Accès au Dashboard Agent
1. Connectez-vous en tant qu'agent
2. Visitez: `/agent-dashboard/` ou la page configurée
3. Prenez une capture d'écran des statistiques
4. Notez les valeurs affichées

### Test 2: Exécuter le Script de Debug
1. Visitez: `http://localhost/wp-content/plugins/malisafi/debug-agent-stats.php`
2. Copiez TOUS les résultats
3. Partagez-les pour analyse

### Test 3: Vérifier les Logs
1. Ouvrez: `wp-content/debug.log`
2. Cherchez: "MALISAFI DEBUG"
3. Copiez les 20 dernières lignes

### Test 4: Inspection Base de Données
```sql
-- 1. Propriétés totales
SELECT COUNT(*) FROM wp_posts WHERE post_type = 'malisafi_property';

-- 2. Utilisateurs agents
SELECT u.ID, u.user_login, u.display_name 
FROM wp_users u
JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'wp_capabilities'
AND um.meta_value LIKE '%malisafi_agent%';

-- 3. Propriétés par agent
SELECT p.post_author, u.user_login, COUNT(*) as property_count
FROM wp_posts p
LEFT JOIN wp_users u ON p.post_author = u.ID
WHERE p.post_type = 'malisafi_property'
GROUP BY p.post_author;
```

### Test 5: Créer une Propriété de Test
1. Dans admin, allez dans Properties → Add New
2. Remplissez le formulaire minimal
3. Publiez
4. Retournez au dashboard agent
5. Les statistiques ont-elles changé ?

## Résultats Attendus

### Si les stats fonctionnent:
- `$total_properties` > 0
- `$published` >= 0
- `$pending` >= 0
- `$recent_properties` contient des objets

### Si les stats ne fonctionnent pas:
- Toutes les valeurs = 0
- `$recent_properties` = array vide
- Logs montrent "not defined"

## Prochaines Étapes

**Après avoir exécuté les tests:**
1. Partagez les résultats de `debug-agent-stats.php`
2. Partagez les logs WordPress
3. Partagez les résultats SQL
4. Indiquez combien de propriétés vous avez créées et pour quel utilisateur

**Je pourrai alors:**
- Identifier la cause exacte
- Fournir un correctif ciblé
- Mettre à jour les fichiers nécessaires

## Fichiers Modifiés

1. ✅ `templates/agent-dashboard-home.php` - Ajout logs debug
2. ✅ `admin/class-dashboard-widgets.php` - Correction requêtes SQL
3. ✅ `debug-agent-stats.php` - Script de diagnostic (NOUVEAU)
4. ✅ `DIAGNOSTIC-STATS-ZERO.md` - Ce document (NOUVEAU)

## Contact/Support

Si le problème persiste après ces tests, partagez:
1. Résultats du script debug
2. Logs WordPress
3. Capture d'écran du dashboard
4. Nombre de propriétés créées (visible dans admin)
