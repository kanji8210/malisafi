# Système de Subscriptions et Billing - Malisafi MLS

## 📋 Vue d'ensemble

Le système de subscriptions Stripe a été intégré avec succès dans le plugin Malisafi MLS. Ce système permet aux utilisateurs de s'abonner à différents plans, de gérer leurs abonnements et d'accéder à des fonctionnalités basées sur leur niveau d'abonnement.

## 🎯 Fonctionnalités implémentées

### 1. Backend Stripe Integration (`includes/class-stripe.php`)
- ✅ Gestion complète des clients Stripe
- ✅ Création de sessions Checkout
- ✅ Customer Portal pour la gestion d'abonnement
- ✅ Traitement des webhooks (6 événements)
- ✅ Mise à jour automatique des rôles utilisateurs
- ✅ Gestion des limites d'annonces
- ✅ Tâche cron pour vérification des abonnements
- ✅ Support modes Test et Live

### 2. Interface Admin (`admin/templates/subscriptions.php`)
- ✅ Configuration des clés API Stripe
- ✅ Gestion des Price IDs pour chaque plan
- ✅ Configuration du webhook
- ✅ Vue d'ensemble des plans d'abonnement
- ✅ Liste des abonnements actifs
- ✅ Statistiques et revenus
- ✅ Interface à 4 onglets :
  - Stripe Settings
  - Subscription Plans
  - Active Subscriptions
  - Statistics

### 3. Page de Pricing Frontend (`templates/pricing-page.php`)
- ✅ Affichage des 4 plans avec leurs caractéristiques
- ✅ Design responsive et moderne
- ✅ Badge "Most Popular" sur le plan recommandé
- ✅ Boutons d'abonnement interactifs
- ✅ Redirection vers Stripe Checkout
- ✅ Section FAQ
- ✅ Détection du plan actuel de l'utilisateur

### 4. Shortcodes (`includes/class-shortcodes.php`)
- ✅ `[malisafi_pricing]` - Affiche la page de pricing
- ✅ `[malisafi_subscription_status]` - Affiche le statut d'abonnement
- ✅ `[malisafi_submit_property]` - Formulaire de soumission (avec vérification des limites)

### 5. Gestion des Webhooks
Événements traités automatiquement :
- ✅ `checkout.session.completed` - Nouvel abonnement
- ✅ `customer.subscription.updated` - Mise à jour d'abonnement
- ✅ `customer.subscription.deleted` - Annulation
- ✅ `invoice.payment_succeeded` - Paiement réussi
- ✅ `invoice.payment_failed` - Paiement échoué

## 💰 Plans d'abonnement

| Plan | Prix | Annonces | Fonctionnalités |
|------|------|----------|-----------------|
| **Agent Basic** | $29.99/mois | 5 | Analytics de base, Support email |
| **Agent Premium** | $99.99/mois | Illimité | Annonces en vedette, Boost, Analytics avancées |
| **Property Owner** | $19.99/mois | 3 | Analytics de base |
| **Developer** | $199.99/mois | Illimité | Import en masse, Accès API |

## 🔄 Flux de paiement

```
1. Utilisateur → Page Pricing
2. Clic sur "Get Started"
3. AJAX → Création Checkout Session
4. Redirection → Stripe Checkout
5. Paiement → Webhook reçu
6. Mise à jour automatique :
   - Enregistrement dans mf_subscriptions
   - Changement du rôle WordPress
   - Définition des limites (mf_user_limits)
   - Email de confirmation
```

## 🗄️ Tables de base de données

### `mf_subscriptions`
```sql
- id (INT)
- user_id (BIGINT)
- plan_type (VARCHAR)
- status (VARCHAR)
- stripe_customer_id (VARCHAR)
- stripe_subscription_id (VARCHAR)
- current_period_start (DATETIME)
- current_period_end (DATETIME)
- created_at (DATETIME)
- updated_at (DATETIME)
```

### `mf_user_limits`
```sql
- id (INT)
- user_id (BIGINT)
- max_listings (INT) -- -1 = illimité
- featured_listings (INT)
- can_boost (TINYINT)
- analytics_access (VARCHAR)
- created_at (DATETIME)
```

## 📂 Fichiers créés/modifiés

### Nouveaux fichiers
```
includes/
├── class-stripe.php              (612 lignes) ✨ NEW
└── class-shortcodes.php          (236 lignes) ✨ NEW

admin/templates/
└── subscriptions.php             (537 lignes) ✨ NEW

templates/
└── pricing-page.php              (342 lignes) ✨ NEW

Documentation/
├── STRIPE_SETUP_GUIDE.md         ✨ NEW
└── install-stripe.ps1            ✨ NEW
```

### Fichiers modifiés
```
includes/
├── class-core.php                ✏️ MODIFIED
└── class-database.php            (tables créées)

public/
└── class-public.php              ✏️ MODIFIED (ajout malisafiAjax)
```

## 🚀 Installation

### 1. Installer la bibliothèque Stripe

**Option A: Avec Composer (Recommandé)**
```powershell
cd c:\xampp\htdocs\wordpress\wp-content\plugins\malisafi_mls
composer require stripe/stripe-php
```

**Option B: Script automatique**
```powershell
.\install-stripe.ps1
```

**Option C: Manuel**
1. Téléchargez depuis https://github.com/stripe/stripe-php/releases
2. Extrayez dans `vendor/stripe/stripe-php/`

### 2. Configurer Stripe

1. Créez un compte sur https://dashboard.stripe.com
2. Récupérez vos clés API (test)
3. Allez dans **WordPress Admin > Malisafi > Subscriptions**
4. Remplissez les clés API
5. Créez 4 produits dans Stripe Dashboard
6. Copiez les Price IDs dans WordPress
7. Configurez le webhook

📖 **Guide complet** : Voir `STRIPE_SETUP_GUIDE.md`

## 🔧 Configuration WordPress

### 1. Paramètres Stripe

Dans **Malisafi > Subscriptions > Stripe Settings** :

```
Mode: Test Mode
Test Publishable Key: pk_test_...
Test Secret Key: sk_test_...
Webhook Secret: whsec_...

Price IDs:
- Agent Basic: price_...
- Agent Premium: price_...
- Property Owner: price_...
- Developer: price_...
```

### 2. Créer les pages

**Page Pricing**
```
Titre: Pricing
Contenu: [malisafi_pricing]
```

**Page Dashboard (optionnel)**
```
Titre: My Subscription
Contenu: [malisafi_subscription_status]
```

### 3. Configurer le Webhook

URL du webhook :
```
https://votre-site.com/wp-json/malisafi/v1/stripe-webhook
```

Événements à sélectionner :
- ✅ checkout.session.completed
- ✅ customer.subscription.updated
- ✅ customer.subscription.deleted
- ✅ invoice.payment_succeeded
- ✅ invoice.payment_failed

## 🧪 Tests

### Cartes de test Stripe

```
Paiement réussi : 4242 4242 4242 4242
Carte refusée   : 4000 0000 0000 0002
Authentification: 4000 0025 0000 3155

Date expiration: 12/25
CVC: 123
```

### Scénario de test complet

1. ✅ Aller sur la page Pricing
2. ✅ Se connecter avec un compte utilisateur
3. ✅ Cliquer sur "Get Started"
4. ✅ Remplir avec la carte de test
5. ✅ Vérifier la redirection
6. ✅ Vérifier le rôle WordPress
7. ✅ Vérifier l'abonnement dans l'admin
8. ✅ Vérifier les limites d'annonces

## 🔐 Sécurité

- ✅ Vérification nonce sur tous les AJAX
- ✅ Validation des signatures webhook
- ✅ Vérification des permissions utilisateur
- ✅ Sanitization de toutes les données
- ✅ Clés API stockées en options WordPress
- ✅ Aucune donnée de carte stockée localement

## 📊 Capacités administrateur

### Gestion des abonnements

L'administrateur peut :
- Voir tous les abonnements actifs
- Filtrer par plan
- Voir le revenu mensuel total
- Voir les statistiques par plan
- Accéder directement à Stripe Dashboard
- Gérer les paramètres Stripe

### Statistiques disponibles

- Nombre total d'abonnements actifs
- Revenu mensuel récurrent (MRR)
- Répartition par plan
- Dates de renouvellement

## 🛠️ API et Hooks

### Actions WordPress disponibles

```php
// Après création d'un abonnement
do_action('malisafi_subscription_created', $user_id, $subscription_data);

// Après annulation
do_action('malisafi_subscription_cancelled', $user_id);

// Après mise à jour
do_action('malisafi_subscription_updated', $user_id, $new_status);
```

### Filtres disponibles

```php
// Modifier les plans avant affichage
$plans = apply_filters('malisafi_stripe_plans', $plans);

// Modifier les limites utilisateur
$limits = apply_filters('malisafi_user_limits', $limits, $plan_type);
```

## 🔄 Tâches automatiques

### Cron job quotidien

La tâche `malisafi_check_subscriptions` s'exécute quotidiennement pour :
- Vérifier le statut des abonnements dans Stripe
- Mettre à jour les statuts locaux
- Synchroniser les données
- Détecter les abonnements expirés

## 🚨 Dépannage

### "Stripe is not configured"
- Vérifiez que toutes les clés API sont remplies
- Pas d'espaces avant/après les clés

### "Invalid price ID"
- Utilisez les Price IDs (`price_...`), pas les Product IDs (`prod_...`)
- Vérifiez le bon mode (test vs live)

### Webhook ne fonctionne pas
- Testez l'URL dans Stripe Dashboard
- Vérifiez que l'URL est accessible publiquement
- Consultez les logs Stripe

### Bibliothèque Stripe introuvable
- Vérifiez : `vendor/stripe/stripe-php/init.php`
- Réinstallez avec Composer
- Vérifiez les permissions

## 📝 Logs

Les erreurs sont enregistrées dans :
```
wp-content/debug.log
```

Activer le mode debug dans `wp-config.php` :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🌐 URLs importantes

- Admin Subscriptions : `/wp-admin/admin.php?page=malisafi-subscriptions`
- Page Pricing : `/pricing` (à créer avec le shortcode)
- Endpoint Webhook : `/wp-json/malisafi/v1/stripe-webhook`
- Stripe Dashboard Test : https://dashboard.stripe.com/test
- Stripe Dashboard Live : https://dashboard.stripe.com

## 📚 Ressources

- [Documentation Stripe](https://stripe.com/docs)
- [Stripe PHP Library](https://github.com/stripe/stripe-php)
- [Stripe Checkout](https://stripe.com/docs/checkout)
- [Webhooks Guide](https://stripe.com/docs/webhooks)
- [Subscriptions](https://stripe.com/docs/billing/subscriptions)

## ✅ Checklist de déploiement

Avant de passer en production :

- [ ] Bibliothèque Stripe installée
- [ ] Compte Stripe vérifié
- [ ] Produits créés en mode Live
- [ ] Webhook configuré en Live
- [ ] Clés Live ajoutées dans WordPress
- [ ] Page Pricing créée et publiée
- [ ] Tests effectués en mode Test
- [ ] Emails de confirmation testés
- [ ] Webhook testé et fonctionnel
- [ ] Mode Live activé
- [ ] Test avec vraie carte
- [ ] Documentation lue par l'équipe

## 🎉 Prochaines étapes possibles

- [ ] Ajouter des coupons de réduction
- [ ] Implémenter des essais gratuits
- [ ] Ajouter des plans annuels
- [ ] Créer un système de parrainage
- [ ] Ajouter des rapports avancés
- [ ] Intégrer des emails marketing
- [ ] Créer des landing pages personnalisées
- [ ] Ajouter des upgrades mid-cycle

## 📞 Support

Pour toute question :
1. Consultez `STRIPE_SETUP_GUIDE.md`
2. Vérifiez les logs WordPress
3. Consultez les logs Stripe Dashboard
4. Testez le webhook dans Stripe

---

**Version** : 1.0.0  
**Date** : 2025  
**Auteur** : Malisafi MLS Development Team
