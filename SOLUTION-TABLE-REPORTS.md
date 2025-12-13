# Solution pour l'erreur "Table 'wp_mf_property_reports' doesn't exist"

## Problème
La table `wp_mf_property_reports` n'a pas été créée lors de l'activation du plugin car elle a été ajoutée après l'activation initiale.

## Solutions (Choisir l'une des 3 options)

### Option 1: Via l'interface WordPress (RECOMMANDÉ)
1. Connectez-vous à l'admin WordPress
2. Allez dans **Malisafi > Database Tools**
3. Cliquez sur le bouton **"Create/Repair Missing Tables"**
4. La table sera créée automatiquement

### Option 2: Recharger la page admin
1. Allez dans l'admin WordPress (n'importe quelle page)
2. La table sera créée automatiquement au prochain chargement
3. Vous verrez un message de succès

### Option 3: Créer manuellement via phpMyAdmin
1. Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
2. Sélectionnez votre base de données WordPress
3. Cliquez sur l'onglet "SQL"
4. Copiez et exécutez le script SQL suivant:

```sql
CREATE TABLE IF NOT EXISTS `wp_mf_property_reports` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` BIGINT UNSIGNED NOT NULL,
    `reporter_id` BIGINT UNSIGNED NOT NULL,
    `reason` VARCHAR(50) NOT NULL,
    `details` TEXT,
    `status` ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `property_id` (`property_id`),
    KEY `reporter_id` (`reporter_id`),
    KEY `status` (`status`),
    KEY `idx_property_reports` (`property_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

5. Cliquez sur "Exécuter"

## Vérification
Après avoir appliqué l'une des solutions:
1. Allez dans **Malisafi > Database Tools**
2. Vérifiez que toutes les tables affichent "Exists" en vert
3. La table `wp_mf_property_reports` devrait maintenant apparaître

## Note
- Le système vérifie automatiquement les tables manquantes à chaque chargement de page admin
- Toutes les autres tables seront également vérifiées et créées si nécessaire
- Cette solution ne supprime aucune donnée existante
