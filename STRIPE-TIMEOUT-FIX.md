# Instructions pour corriger l'erreur "Maximum execution time exceeded"

## Problème
L'erreur "Maximum execution time of 120 seconds exceeded" se produit lorsque les requêtes Stripe prennent trop de temps.

## Solutions Appliquées

### 1. Configuration Plugin (✅ Déjà fait)
- Timeout API Stripe configuré à 80 secondes
- Timeout de connexion à 30 secondes
- Maximum 2 tentatives de reconnexion
- Gestion d'erreur améliorée avec types d'exceptions spécifiques

### 2. Configuration PHP dans le plugin (✅ Déjà fait)
- `max_execution_time` augmenté à 180 secondes pour les opérations Stripe
- Limite de mémoire augmentée à 256M si nécessaire
- `.htaccess` créé avec configuration PHP optimisée

## Configuration Manuelle XAMPP (À faire)

### Option A: Via php.ini (Recommandé)

1. **Ouvrir le fichier php.ini**
   - Chemin: `C:\xampp\php\php.ini`
   - Ou via XAMPP Control Panel → Apache → Config → php.ini

2. **Modifier les valeurs**
   ```ini
   max_execution_time = 180
   max_input_time = 180
   memory_limit = 256M
   ```

3. **Redémarrer Apache**
   - XAMPP Control Panel → Apache → Stop → Start

### Option B: Via .htaccess (Déjà créé)
Le fichier `.htaccess` a été créé dans le dossier du plugin avec les bonnes configurations.

### Option C: Via wp-config.php

Ajouter dans `wp-config.php` (avant `/* That's all, stop editing! */`):
```php
// Increase PHP limits for Stripe operations
@ini_set('max_execution_time', '180');
@ini_set('memory_limit', '256M');
```

## Vérification de la Configuration

Créer un fichier `info.php` à la racine de WordPress:
```php
<?php phpinfo(); ?>
```

Puis visiter: `http://localhost/wordpress/info.php`
Vérifier:
- `max_execution_time` = 180 ou plus
- `memory_limit` = 256M ou plus

**⚠️ Supprimer info.php après vérification pour des raisons de sécurité**

## Tests Stripe

Si l'erreur persiste:

1. **Tester la connexion Stripe**
   - Vérifier que les clés API sont correctes
   - Tester en mode test d'abord

2. **Vérifier les logs**
   - Logs WordPress: `wp-content/debug.log` (si WP_DEBUG activé)
   - Logs Apache: `C:\xampp\apache\logs\error.log`

3. **Activer le mode debug WordPress**
   Dans `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

## Erreurs Stripe Spécifiques Gérées

Le plugin gère maintenant ces erreurs:
- **ApiConnectionException**: Problème réseau/timeout
- **RateLimitException**: Trop de requêtes
- **InvalidRequestException**: Paramètres invalides
- **AuthenticationException**: Clé API incorrecte
- **Exception générique**: Autres erreurs

Tous les détails sont enregistrés dans les logs pour debugging.

## Support

Si le problème persiste après ces modifications:
1. Vérifier la console du navigateur pour erreurs JavaScript
2. Vérifier le log d'erreur PHP
3. Tester avec une autre propriété
4. Contacter le support Stripe si le problème est spécifique à leur API

---
**Dernière mise à jour**: 15 décembre 2025
