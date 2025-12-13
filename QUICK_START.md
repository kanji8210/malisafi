# 🚀 Guide de Démarrage Rapide - Stripe Subscriptions

## Installation en 5 minutes

### Étape 1 : Installer Stripe PHP (2 min)

**Option simple avec Composer :**
```powershell
cd c:\xampp\htdocs\wordpress\wp-content\plugins\malisafi_mls
composer install
```

OU utilisez le script automatique :
```powershell
.\install-stripe.ps1
```

### Étape 2 : Créer un compte Stripe (1 min)
1. Allez sur https://dashboard.stripe.com/register
2. Inscrivez-vous (gratuit)
3. Activez le mode Test

### Étape 3 : Configurer WordPress (2 min)

1. **Récupérez vos clés Stripe** :
   - https://dashboard.stripe.com/test/apikeys
   - Copiez `pk_test_...` et `sk_test_...`

2. **Dans WordPress** :
   - Allez dans **Malisafi > Subscriptions**
   - Mode : **Test Mode**
   - Collez les 2 clés
   - Cliquez **Enregistrer**

### Étape 4 : Créer les produits Stripe (5 min)

Allez sur https://dashboard.stripe.com/products et créez :

1. **Agent Basic** - $29.99/mois
2. **Agent Premium** - $99.99/mois  
3. **Property Owner** - $19.99/mois
4. **Developer** - $199.99/mois

Pour chaque produit, **copiez le Price ID** (commence par `price_...`)

### Étape 5 : Ajouter les Price IDs (1 min)

Retournez dans **Malisafi > Subscriptions** et collez les 4 Price IDs.

### Étape 6 : Configurer le Webhook (2 min)

1. Allez sur https://dashboard.stripe.com/webhooks
2. Cliquez **Ajouter endpoint**
3. URL : `https://votre-site.com/wp-json/malisafi/v1/stripe-webhook`
4. Sélectionnez ces événements :
   - ✅ checkout.session.completed
   - ✅ customer.subscription.updated
   - ✅ customer.subscription.deleted
   - ✅ invoice.payment_succeeded
   - ✅ invoice.payment_failed
5. Copiez le **Signing Secret** (`whsec_...`)
6. Collez-le dans WordPress

### Étape 7 : Créer la page Pricing (30 secondes)

1. **Pages > Ajouter**
2. Titre : **Pricing**
3. Contenu : `[malisafi_pricing]`
4. **Publier**

---

## ✅ C'est fait ! Testez maintenant

### Test rapide (2 min)

1. Allez sur votre page **/pricing**
2. Connectez-vous avec un compte utilisateur
3. Cliquez **Get Started** sur un plan
4. Utilisez la carte de test : **4242 4242 4242 4242**
5. Date : **12/25**, CVC : **123**
6. Complétez le paiement

### Vérifications

✅ Redirection vers votre site  
✅ Rôle utilisateur changé  
✅ Abonnement visible dans **Malisafi > Subscriptions**  
✅ Email de confirmation reçu

---

## 🎯 Utilisation quotidienne

### Pour les utilisateurs

**S'abonner** : Page Pricing > Get Started  
**Gérer** : Dashboard > Manage Subscription  
**Annuler** : Customer Portal Stripe

### Pour l'admin

**Voir les abonnements** : Malisafi > Subscriptions  
**Statistiques** : Onglet Statistics  
**Paramètres** : Onglet Stripe Settings

---

## 🔄 Passer en Production

Quand vous êtes prêt :

1. ✅ Vérifiez votre compte Stripe
2. ✅ Configurez votre compte bancaire
3. ✅ Créez les produits en mode **Live**
4. ✅ Créez un webhook en mode **Live**
5. ✅ Dans WordPress : Mode → **Live Mode**
6. ✅ Ajoutez les clés Live (`pk_live_...` et `sk_live_...`)
7. ✅ Testez avec une vraie carte
8. ✅ C'est en ligne ! 🎉

---

## 🆘 Aide rapide

### Problème : "Stripe is not configured"
→ Vérifiez que les clés API sont bien remplies (aucun espace)

### Problème : Checkout ne s'ouvre pas
→ Vérifiez les Price IDs (doivent commencer par `price_`)

### Problème : Webhook ne fonctionne pas
→ Testez l'URL dans Stripe Dashboard > Webhooks > [votre webhook] > Send test

### Problème : Bibliothèque Stripe introuvable
→ Exécutez : `composer install` OU `.\install-stripe.ps1`

---

## 📚 Documentation complète

- **Guide complet** : `STRIPE_SETUP_GUIDE.md`
- **Détails système** : `SUBSCRIPTIONS_README.md`
- **Support Stripe** : https://stripe.com/docs

---

## 🎁 Cartes de test utiles

```
✅ Paiement réussi    : 4242 4242 4242 4242
❌ Carte refusée      : 4000 0000 0000 0002
🔐 Authentification   : 4000 0025 0000 3155
```

Date : N'importe quelle date future (ex: 12/25)  
CVC : N'importe quel code (ex: 123)

---

**Temps total d'installation : ~15 minutes**  
**Prêt à accepter des paiements !** 💳✨
