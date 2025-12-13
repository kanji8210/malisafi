# Guide d'installation Stripe pour Malisafi MLS

## Étape 1: Installation de la bibliothèque Stripe PHP

Vous avez deux options pour installer la bibliothèque Stripe :

### Option A: Installation avec Composer (Recommandé)

1. Ouvrez PowerShell dans le dossier du plugin :
```powershell
cd c:\xampp\htdocs\wordpress\wp-content\plugins\malisafi_mls
```

2. Si Composer n'est pas installé, téléchargez-le depuis https://getcomposer.org/download/

3. Installez la bibliothèque Stripe :
```powershell
composer require stripe/stripe-php
```

### Option B: Installation manuelle

1. Téléchargez la bibliothèque Stripe PHP depuis : https://github.com/stripe/stripe-php/releases

2. Extrayez l'archive et placez le dossier `stripe-php` dans :
```
c:\xampp\htdocs\wordpress\wp-content\plugins\malisafi_mls\vendor\stripe\
```

3. La structure finale devrait être :
```
malisafi_mls/
└── vendor/
    └── stripe/
        └── stripe-php/
            └── init.php
```

## Étape 2: Configuration du compte Stripe

### Créer un compte Stripe (si nécessaire)

1. Allez sur https://dashboard.stripe.com/register
2. Créez votre compte Stripe
3. Complétez la vérification de votre entreprise

### Récupérer vos clés API

1. Connectez-vous au tableau de bord Stripe
2. En mode **Test** :
   - Allez sur : https://dashboard.stripe.com/test/apikeys
   - Notez votre **Publishable key** (commence par `pk_test_`)
   - Notez votre **Secret key** (commence par `sk_test_`)

3. Pour le mode **Live** (production) :
   - Allez sur : https://dashboard.stripe.com/apikeys
   - Notez votre **Publishable key** (commence par `pk_live_`)
   - Notez votre **Secret key** (commence par `sk_live_`)

## Étape 3: Créer les produits et prix dans Stripe

### Créer les 4 plans d'abonnement

1. Allez sur : https://dashboard.stripe.com/products

2. **Plan Agent Basic** ($29.99/mois)
   - Cliquez sur "Nouveau produit"
   - Nom : "Agent Basic Plan"
   - Description : "5 annonces, analytics de base, support email"
   - Type de tarification : Récurrent
   - Prix : $29.99
   - Période : Mensuel
   - Cliquez sur "Enregistrer"
   - **Copiez le Price ID** (commence par `price_...`)

3. **Plan Agent Premium** ($99.99/mois)
   - Nom : "Agent Premium Plan"
   - Description : "Annonces illimitées, annonces en vedette, boost, analytics avancées"
   - Prix : $99.99
   - Période : Mensuel
   - **Copiez le Price ID**

4. **Plan Property Owner** ($19.99/mois)
   - Nom : "Property Owner Plan"
   - Description : "3 annonces, analytics de base"
   - Prix : $19.99
   - Période : Mensuel
   - **Copiez le Price ID**

5. **Plan Developer** ($199.99/mois)
   - Nom : "Developer Plan"
   - Description : "Projets illimités, import en masse, accès API"
   - Prix : $199.99
   - Période : Mensuel
   - **Copiez le Price ID**

## Étape 4: Configurer le Webhook

1. Allez sur : https://dashboard.stripe.com/webhooks

2. Cliquez sur "Ajouter un endpoint"

3. URL de l'endpoint :
```
https://votre-site.com/wp-json/malisafi/v1/stripe-webhook
```
(Remplacez `votre-site.com` par votre domaine)

4. Description : "Malisafi MLS Webhook"

5. Version de l'API : Utilisez la version par défaut

6. Événements à écouter - Sélectionnez :
   - ✅ `checkout.session.completed`
   - ✅ `customer.subscription.created`
   - ✅ `customer.subscription.updated`
   - ✅ `customer.subscription.deleted`
   - ✅ `invoice.payment_succeeded`
   - ✅ `invoice.payment_failed`

7. Cliquez sur "Ajouter endpoint"

8. **Copiez le Signing Secret** (commence par `whsec_...`)

## Étape 5: Configurer le plugin WordPress

1. Connectez-vous à votre admin WordPress

2. Allez dans **Malisafi > Subscriptions**

3. Cliquez sur l'onglet **Stripe Settings**

4. Remplissez les champs :
   - **Stripe Mode** : Sélectionnez "Test Mode" pour commencer
   - **Test Publishable Key** : Collez `pk_test_...`
   - **Test Secret Key** : Collez `sk_test_...`
   - **Webhook Signing Secret** : Collez `whsec_...`
   
5. Remplissez les **Price IDs** :
   - Agent Basic Price ID : `price_...` (du plan à $29.99)
   - Agent Premium Price ID : `price_...` (du plan à $99.99)
   - Property Owner Price ID : `price_...` (du plan à $19.99)
   - Developer Price ID : `price_...` (du plan à $199.99)

6. Cliquez sur **Enregistrer les paramètres**

## Étape 6: Créer les pages frontend

### Page de Pricing

1. Allez dans **Pages > Ajouter**
2. Titre : "Pricing" ou "Tarifs"
3. Dans l'éditeur, ajoutez le shortcode :
```
[malisafi_pricing]
```
4. Publiez la page

### Page de Dashboard Utilisateur (optionnel)

1. Créez une nouvelle page "Dashboard"
2. Ajoutez le shortcode :
```
[malisafi_subscription_status]
```
3. Publiez la page

## Étape 7: Tests

### Tester avec des cartes de test Stripe

Utilisez ces numéros de carte pour tester :

- **Paiement réussi** : `4242 4242 4242 4242`
- **Carte refusée** : `4000 0000 0000 0002`
- **Authentification requise** : `4000 0025 0000 3155`

Date d'expiration : N'importe quelle date future (ex: 12/25)
CVC : N'importe quel code à 3 chiffres (ex: 123)

### Processus de test complet

1. Sur votre site, allez sur la page **Pricing**
2. Connectez-vous avec un compte utilisateur
3. Cliquez sur **Get Started** sur un plan
4. Vous serez redirigé vers Stripe Checkout
5. Utilisez une carte de test : `4242 4242 4242 4242`
6. Complétez le paiement
7. Vérifiez que :
   - Vous êtes redirigé vers votre site
   - Votre rôle WordPress a changé
   - Votre abonnement apparaît dans **Malisafi > Subscriptions**
   - Vous recevez un email de confirmation

## Étape 8: Passer en mode Live (Production)

⚠️ **Ne passez en mode Live qu'après avoir testé complètement !**

1. Complétez la vérification de votre compte Stripe
2. Configurez votre compte bancaire dans Stripe
3. Créez les mêmes produits/prix dans le mode Live de Stripe
4. Créez un nouveau webhook pour le mode Live
5. Dans WordPress, allez dans **Malisafi > Subscriptions**
6. Changez **Stripe Mode** à "Live Mode"
7. Remplissez les clés Live :
   - Live Publishable Key : `pk_live_...`
   - Live Secret Key : `sk_live_...`
   - Webhook Secret : Le nouveau `whsec_...` du webhook Live
   - Price IDs Live : Les nouveaux Price IDs du mode Live
8. Enregistrez

## Dépannage

### Problème : "Stripe is not configured"
- Vérifiez que toutes les clés API sont remplies
- Vérifiez qu'il n'y a pas d'espaces avant/après les clés

### Problème : "Invalid price ID"
- Vérifiez que vous utilisez bien les Price IDs (commence par `price_`)
- Pas les Product IDs (commence par `prod_`)
- Vérifiez que vous utilisez les bons IDs (test vs live)

### Problème : Webhook ne fonctionne pas
- Testez l'URL du webhook dans Stripe Dashboard
- Vérifiez que l'URL est accessible publiquement
- Vérifiez les logs dans Stripe Dashboard > Webhooks > [votre webhook] > Logs

### Problème : La bibliothèque Stripe n'est pas trouvée
- Vérifiez le chemin : `vendor/stripe/stripe-php/init.php`
- Réinstallez avec Composer
- Vérifiez les permissions du dossier

## Support

Pour toute question ou problème :
1. Vérifiez les logs WordPress : `wp-content/debug.log`
2. Vérifiez les logs Stripe : Dashboard > Developers > Logs
3. Consultez la documentation Stripe : https://stripe.com/docs
4. Documentation Stripe PHP : https://github.com/stripe/stripe-php

## Ressources utiles

- Dashboard Stripe Test : https://dashboard.stripe.com/test/dashboard
- Dashboard Stripe Live : https://dashboard.stripe.com/dashboard
- Documentation Stripe Checkout : https://stripe.com/docs/checkout
- Documentation Webhooks : https://stripe.com/docs/webhooks
- Documentation Subscriptions : https://stripe.com/docs/billing/subscriptions/overview
