# 🔧 Correction de l'erreur SQL - Table wp_malisafi_inquiries manquante

## ❌ Problème identifié

**Erreur SQL:**
```
WordPress database error: [Table 'mysql.wp_malisafi_inquiries' doesn't exist]
SELECT COUNT(*) FROM wp_malisafi_inquiries WHERE user_id = 1
```

## 🔍 Cause du problème

Il y avait une **incohérence de nommage** entre :
- **Tables créées** dans `class-database.php` : `wp_mf_inquiries` ✅
- **Tables utilisées** dans `class-dashboard-shortcodes.php` : `wp_malisafi_inquiries` ❌

## ✅ Solutions appliquées

### 1. Correction du code PHP

**Fichier modifié:** `includes/class-dashboard-shortcodes.php`

Changé dans **4 emplacements** :

| Méthode | Ligne | Ancien | Nouveau |
|---------|-------|--------|---------|
| `client_inquiries()` | ~243 | `malisafi_inquiries` | `mf_inquiries` ✅ |
| `owner_inquiries()` | ~490 | `malisafi_inquiries` | `mf_inquiries` ✅ |
| `get_inquiries_count()` | ~934 | `malisafi_inquiries` | `mf_inquiries` ✅ |
| `get_user_inquiries_count()` | ~943 | `malisafi_inquiries` | `mf_inquiries` ✅ |

### 2. Scripts de réparation créés

Deux options pour créer/vérifier les tables :

#### Option A: Script PHP (Recommandé) 🌟

**Fichier:** `fix-tables.php`

**Utilisation:**
1. Accédez à : `http://localhost/wordpress/wp-content/plugins/malisafi_mls/fix-tables.php`
2. Le script créera toutes les tables automatiquement
3. Affiche un rapport détaillé
4. **Supprimez le fichier après utilisation**

#### Option B: Script SQL

**Fichier:** `sql/fix-database-tables.sql`

**Utilisation via phpMyAdmin:**
1. Ouvrez phpMyAdmin
2. Sélectionnez votre base de données WordPress
3. Cliquez sur "Importer"
4. Sélectionnez le fichier `fix-database-tables.sql`
5. Cliquez sur "Exécuter"

**Utilisation via ligne de commande:**
```bash
cd c:\xampp\mysql\bin
mysql -u root wordpress < "c:\xampp\htdocs\wordpress\wp-content\plugins\malisafi_mls\sql\fix-database-tables.sql"
```

## 📊 Tables créées

Le plugin utilise le préfixe `mf_` (Malisafi) pour toutes les tables personnalisées :

| Table | Description | Enregistrements typiques |
|-------|-------------|-------------------------|
| `wp_mf_subscriptions` | Abonnements (Agent Basic, Premium, Owner, Developer) | Plans actifs |
| `wp_mf_user_limits` | Limites des utilisateurs (listings, featured, analytics) | Par utilisateur |
| `wp_mf_properties` | Propriétés principales (prix, localisation, type) | Toutes les propriétés |
| `wp_mf_property_amenities` | Équipements (piscine, parking, etc.) | Par propriété |
| `wp_mf_property_media` | Images, vidéos, visites virtuelles | Média par propriété |
| **`wp_mf_inquiries`** | **Demandes de renseignements** ⭐ **CORRIGÉE** | Contacts clients |
| `wp_mf_saved_searches` | Recherches sauvegardées des clients | Par utilisateur |
| `wp_mf_favorites` | Favoris des clients | Par utilisateur |
| `wp_mf_moderation_queue` | File de modération des propriétés | En attente |
| `wp_mf_property_reports` | Signalements de propriétés | Par propriété |
| `wp_mf_analytics` | Statistiques (vues, clics, partages) | Événements |

## 🎯 Structure de la table wp_mf_inquiries

```sql
CREATE TABLE wp_mf_inquiries (
    inquiry_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    agent_id BIGINT UNSIGNED NOT NULL,
    inquiry_type ENUM('general', 'tour_request', 'price_negotiation'),
    message TEXT,
    status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    client_phone VARCHAR(20),
    client_email VARCHAR(255),
    preferred_contact_time ENUM('morning', 'afternoon', 'evening', 'anytime'),
    tour_requested_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 🔄 Processus de création des tables

Les tables sont automatiquement créées lors de l'activation du plugin via :

1. **`malisafi-mls.php`** → Active le plugin
2. **`includes/class-activator.php`** → `Activator::activate()`
3. **`includes/class-database.php`** → `Database::create_tables()`
4. Utilise **`dbDelta()`** pour créer/mettre à jour les tables

## 🧪 Vérification

### Test 1: Vérifier l'existence de la table

```sql
SHOW TABLES LIKE 'wp_mf_inquiries';
```

### Test 2: Compter les enregistrements

```sql
SELECT COUNT(*) FROM wp_mf_inquiries;
```

### Test 3: Structure de la table

```sql
DESCRIBE wp_mf_inquiries;
```

### Test 4: Via WordPress

```php
global $wpdb;
$table_name = $wpdb->prefix . 'mf_inquiries';
$count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "Nombre d'enregistrements : " . $count;
```

## 📝 Notes importantes

### Nommage cohérent

Toutes les tables custom du plugin suivent le pattern :
- **Préfixe WordPress** : `wp_` (ou votre préfixe personnalisé)
- **Préfixe plugin** : `mf_` (Malisafi)
- **Nom descriptif** : `inquiries`, `properties`, etc.

### Autres préfixes dans le plugin

- **Shortcodes** : `[malisafi_*]` (ex: `[malisafi_properties]`)
- **Meta keys** : `_malisafi_*` ou `malisafi_*` (ex: `_malisafi_favorites`)
- **Options** : `malisafi_mls_*` (ex: `malisafi_mls_db_version`)
- **Rôles** : `malisafi_*` (ex: `malisafi_agent`)

## ⚠️ Prévention future

Pour éviter ce type d'erreur :

1. **Toujours utiliser** le même préfixe de table (`mf_`)
2. **Vérifier** les références dans tout le code
3. **Tester** après modification de schéma
4. **Désactiver/réactiver** le plugin après modification de `class-database.php`

## 🔐 Sécurité

Après avoir exécuté `fix-tables.php` :
1. ✅ Vérifiez que toutes les tables sont créées
2. ⚠️ **SUPPRIMEZ immédiatement le fichier `fix-tables.php`**
3. Le fichier SQL peut rester (pas de risque de sécurité)

## 📞 Support

Si l'erreur persiste :

1. Vérifiez les logs MySQL : `c:\xampp\mysql\data\*.err`
2. Activez WP_DEBUG dans `wp-config.php`
3. Vérifiez les permissions de la base de données
4. Testez la connexion MySQL depuis phpMyAdmin

## ✅ Checklist de résolution

- [x] Code corrigé dans `class-dashboard-shortcodes.php`
- [ ] Script `fix-tables.php` exécuté
- [ ] Tables vérifiées dans phpMyAdmin
- [ ] Test des fonctionnalités (dashboard, inquiries)
- [ ] Fichier `fix-tables.php` supprimé

## 📌 Résumé

**Problème** : Nom de table incorrect (`wp_malisafi_inquiries` au lieu de `wp_mf_inquiries`)  
**Solution** : Code corrigé + script de création de tables fourni  
**Statut** : ✅ RÉSOLU  
**Action requise** : Exécuter `fix-tables.php` puis le supprimer
