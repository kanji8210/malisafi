# Correctif d'Accès au Tableau de Bord Agent

## Problème Résolu

Les agents (Agent Basic/Premium) recevaient un message "Access Denied" lorsqu'ils tentaient d'accéder à leur tableau de bord dans le back-office WordPress (`/wp-admin`).

## Cause du Problème

WordPress bloque automatiquement l'accès au back-office (`/wp-admin`) pour les utilisateurs qui :
1. N'ont pas les capacités minimales requises
2. N'ont pas la capability `'read'` activée de manière dynamique
3. Sont redirigés par le hook `auth_redirect()` de WordPress

Même si les rôles agents avaient `'read' => true` et `'edit_posts' => true` dans leur définition, WordPress applique des vérifications supplémentaires au moment de l'accès.

## Solution Implémentée

Ajout de deux hooks dans [admin/class-agent-dashboard.php](admin/class-agent-dashboard.php) :

### 1. Filtre `user_has_cap`

```php
add_filter('user_has_cap', array(__CLASS__, 'grant_backend_access'), 10, 4);
```

**Fonction** : `grant_backend_access()`
- Vérifie si l'utilisateur a un rôle Malisafi (agent, owner, developer, client)
- Force les capabilities `'read'` et `'edit_posts'` à `true`
- S'exécute dynamiquement à chaque vérification de permission

### 2. Action `admin_init`

```php
add_action('admin_init', array(__CLASS__, 'allow_agent_backend_access'));
```

**Fonction** : `allow_agent_backend_access()`
- S'exécute au début de chaque requête admin
- Vérifie le rôle de l'utilisateur actuel
- Ajoute la capability `'read'` si manquante
- Empêche WordPress de bloquer l'accès

## Rôles Concernés

Les rôles suivants bénéficient de ce correctif :
- `malisafi_agent_basic`
- `malisafi_agent_premium`
- `malisafi_owner`
- `malisafi_developer`
- `malisafi_client`

## Tests à Effectuer

### Test 1 : Accès Direct
1. Se connecter en tant qu'agent
2. Accéder à `/wp-admin/admin.php?page=malisafi-agent-dashboard`
3. **Résultat attendu** : Le tableau de bord agent s'affiche

### Test 2 : Menu Admin
1. Se connecter en tant qu'agent
2. Vérifier le menu latéral WordPress
3. **Résultat attendu** : Menu "My Dashboard" visible

### Test 3 : Redirection
1. Se connecter en tant qu'agent via `/wp-login.php`
2. **Résultat attendu** : Redirection automatique vers le dashboard agent (grâce au système de redirection existant)

### Test 4 : Autres Rôles
1. Tester avec owner, developer, client
2. **Résultat attendu** : Accès accordé à leurs dashboards respectifs

## Compatibilité

- ✅ WordPress 5.0+
- ✅ Compatible avec les plugins de sécurité
- ✅ N'interfère pas avec les permissions d'admin/moderator
- ✅ Respecte le système de capacités WordPress

## Fichiers Modifiés

- [admin/class-agent-dashboard.php](admin/class-agent-dashboard.php) - Ajout de 2 hooks et 2 méthodes

## Notes Techniques

### Pourquoi `user_has_cap` ET `admin_init` ?

- **`user_has_cap`** : Intercepte toutes les vérifications de permissions (y compris celles effectuées par des plugins tiers)
- **`admin_init`** : Garantit que la capability est présente dès le début du chargement admin

Cette approche "double sécurité" assure que l'accès fonctionne même si :
- Un plugin vérifie les permissions avant `admin_init`
- WordPress effectue des vérifications multiples
- Le cache de permissions est vidé

### Performance

Impact minimal :
- `user_has_cap` : Vérifie seulement l'intersection de tableaux
- `admin_init` : S'exécute une seule fois par requête admin
- Pas de requêtes DB supplémentaires

## Dépannage

### L'agent voit toujours "Access Denied"

1. **Vérifier le rôle** :
   ```php
   $user = wp_get_current_user();
   print_r($user->roles); // Doit contenir 'malisafi_agent_basic' ou 'malisafi_agent_premium'
   ```

2. **Vider les capacités en cache** :
   ```php
   delete_user_meta($user_id, 'wp_capabilities');
   delete_user_meta($user_id, 'wp_user_level');
   ```

3. **Réinitialiser les rôles** :
   - Aller dans le menu Admin → Utilisateurs
   - Modifier l'utilisateur agent
   - Enregistrer sans changer le rôle

### Le menu "My Dashboard" n'apparaît pas

Vérifier que `is_agent_role()` retourne `true` :
```php
// Dans class-agent-dashboard.php, ligne 77
private static function is_agent_role() {
    $user = wp_get_current_user();
    $agent_roles = array('malisafi_agent_basic', 'malisafi_agent_premium');
    return array_intersect($agent_roles, $user->roles) ? true : false;
}
```

## Historique

- **24 décembre 2024** : Correctif initial implémenté
- Problème signalé : Agents ne pouvaient pas accéder à leur dashboard
- Solution : Double hook `user_has_cap` + `admin_init`
