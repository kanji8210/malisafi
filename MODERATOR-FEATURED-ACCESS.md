## Featured Properties - Moderator Access Update

### Changements Appliqués

✅ **Permissions mises à jour** :
- Les **Administrateurs** peuvent gérer les propriétés featured (comme avant)
- Les **Modérateurs** peuvent maintenant gérer les propriétés featured
- Nouvelle capacité : `manage_featured_properties`

### Fonctionnalités pour Modérateurs

Les modérateurs peuvent maintenant :
1. ✨ **Marquer des propriétés comme featured** sans paiement
2. 📅 **Définir une date d'expiration personnalisée**
3. 🔄 **Activer/désactiver** le statut featured depuis la liste des propriétés
4. 👀 **Voir toutes les informations** de paiement et d'expiration

### Méthode de Vérification

Le système utilise maintenant la fonction `can_manage_featured()` qui vérifie :
```php
return current_user_can('manage_options') || current_user_can('moderate_properties');
```

### Mise à Jour des Rôles Existants

Pour appliquer les nouvelles permissions aux modérateurs existants :

**Option 1: Script WordPress (Recommandé)**

Créer un fichier temporaire `update-moderator-caps.php` à la racine de WordPress :

```php
<?php
require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

// Get moderator role
$moderator = get_role('malisafi_moderator');

if ($moderator) {
    $moderator->add_cap('moderate_properties', true);
    $moderator->add_cap('manage_featured_properties', true);
    echo "Moderator capabilities updated successfully!<br>";
    
    // List updated capabilities
    echo "<h3>Moderator Capabilities:</h3>";
    echo "<pre>";
    print_r($moderator->capabilities);
    echo "</pre>";
} else {
    echo "Moderator role not found.";
}

echo "<p><strong>Done! Delete this file for security.</strong></p>";
```

Visiter : `http://localhost/wordpress/update-moderator-caps.php`
**⚠️ Supprimer le fichier après utilisation**

**Option 2: Via WP-CLI**

```bash
wp role update malisafi_moderator moderate_properties manage_featured_properties
```

**Option 3: Désactiver/Réactiver le Plugin**

La réactivation du plugin recréera les rôles avec les nouvelles capacités.

### Vérification

Pour vérifier que les permissions sont appliquées :

1. Connectez-vous en tant que modérateur
2. Allez dans Propriétés → Toutes les propriétés
3. Vous devriez voir les liens "Make Featured" / "Remove"
4. En éditant une propriété, vous devriez voir la meta box "Featured Property"

### Tests

Testez avec un compte modérateur :
- [ ] Peut voir la colonne "Featured" dans la liste des propriétés
- [ ] Peut cliquer sur "Make Featured" / "Remove"
- [ ] Peut cocher "Mark as Featured" dans la meta box
- [ ] Peut définir une date d'expiration personnalisée
- [ ] Le statut featured change sans demander de paiement
- [ ] Voit le message "As admin/moderator, you can feature properties without payment"

### Différences Admin vs Modérateur

**Similaire** :
- Gestion du statut featured
- Définition de dates d'expiration
- Voir les informations de paiement

**Différent** (Admin seulement) :
- Accès aux réglages Stripe
- Gestion des webhooks
- Suppression du plugin

### Logs

Toutes les actions de gestion featured sont enregistrées avec :
- Payment ID: `admin-{timestamp}` pour admin
- Payment ID: `moderator-{timestamp}` pour modérateur (à implémenter si besoin)

---
**Dernière mise à jour** : 15 décembre 2025
