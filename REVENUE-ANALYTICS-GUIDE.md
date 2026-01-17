# Malisafi Analytics - Revenue & Subscription Tracking

## Overview

Le système **Revenue Analytics** suit tous les revenus générés via Stripe, incluant les abonnements, les mises à niveau, les annonces en vedette, et les remboursements.

## Components

### 1. **Revenue Tracking** (`Analytics_Advanced`)
- Suit toutes les transactions Stripe automatiquement
- Enregistre les abonnements, paiements uniques, remboursements
- Intégration webhook Stripe pour données en temps réel
- Calcul automatique des métriques clés

### 2. **Database Table** (`wp_mf_revenue_tracking`)

**Colonnes:**
```sql
- id (BIGINT) - ID unique
- user_id (BIGINT) - Utilisateur qui a payé
- transaction_type (ENUM) - Type de transaction
  * subscription - Abonnement mensuel/annuel
  * featured_listing - Annonce en vedette
  * boost - Boost de visibilité
  * premium_upgrade - Mise à niveau premium
  * additional_listings - Propriétés supplémentaires
  * refund - Remboursement
- plan_type (VARCHAR) - Type de plan (agent_basic, agent_premium, etc.)
- amount (DECIMAL) - Montant en KES
- currency (VARCHAR) - Devise (KES par défaut)
- stripe_payment_id (VARCHAR) - ID Stripe du paiement
- stripe_invoice_id (VARCHAR) - ID Stripe de la facture
- status (ENUM) - Statut de la transaction
  * pending - En attente
  * completed - Complétée avec succès
  * failed - Échouée
  * refunded - Remboursée
- metadata (JSON) - Données supplémentaires
- created_at (TIMESTAMP) - Date de création
- completed_at (TIMESTAMP) - Date de complétion
```

**Indexes:**
- `idx_user` - Transactions par utilisateur
- `idx_type` - Transactions par type
- `idx_status` - Transactions par statut
- `idx_amount` - Tri par montant
- `idx_date` - Tri chronologique

### 3. **Revenue Dashboard** (`/wp-admin/admin.php?page=malisafi-analytics-revenue`)

## Key Metrics (KPIs)

### 1. **Total Revenue**
- Somme de tous les paiements réussis (status = 'completed')
- Exclut les remboursements et paiements échoués
- Calculé pour la période sélectionnée (7/30/90/365 jours)

**SQL:**
```sql
SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue
```

### 2. **Total Refunds**
- Somme de tous les remboursements
- Pourcentage du revenu total remboursé
- Indicateur de satisfaction client

**Formule:** `Total Refunds / Total Revenue × 100`

### 3. **Average Transaction Value**
- Montant moyen par transaction réussie
- Aide à identifier les plans les plus populaires
- Indique la santé financière moyenne

**SQL:**
```sql
AVG(amount) WHERE status = 'completed'
```

### 4. **Success Rate**
- Pourcentage de paiements réussis vs échecs
- Indicateur de la qualité de l'expérience de paiement
- Target: > 95%

**Formule:** `(Completed Transactions / Total Transactions) × 100`

## Dashboard Sections

### 1. **Revenue by Transaction Type** (Revenus par type)

**Graphique:** Doughnut Chart  
**Affiche:**
- Subscription - Revenus des abonnements récurrents
- Featured Listing - Revenus des annonces en vedette
- Boost - Revenus des boosts de visibilité
- Premium Upgrade - Revenus des mises à niveau
- Additional Listings - Revenus des propriétés supplémentaires

**Table:**
| Type | Count | Revenue | Avg |
|------|-------|---------|-----|
| Subscription | 45 | KES 135,000 | KES 3,000 |
| Featured Listing | 23 | KES 34,500 | KES 1,500 |
| Boost | 12 | KES 6,000 | KES 500 |

**Utilité:**
- Identifier les sources de revenus principales
- Optimiser les prix selon la demande
- Focus marketing sur les types rentables

### 2. **Subscription Analytics** (Analyse des abonnements)

**Graphique:** Pie Chart  
**Affiche:**
- Répartition des abonnés par plan
- MRR (Monthly Recurring Revenue) par plan
- Nombre d'abonnés actifs

**Plans Suivis:**
- **Agent Basic** - KES 1,000/mois (3 propriétés)
- **Agent Premium** - KES 3,000/mois (illimité + auto-publish)
- **Owner** - KES 500/mois (1 propriété)
- **Developer** - KES 5,000/mois (100+ propriétés)

**Métriques:**
- **MRR** (Monthly Recurring Revenue) - Revenu mensuel récurrent
- **ARR** (Annual Recurring Revenue) - MRR × 12
- **Churn Rate** - Taux d'annulation (calculé séparément)

### 3. **Recent Transactions** (Transactions récentes)

**Affiche les 20 dernières transactions avec:**
- Date et heure
- Nom et email de l'utilisateur
- Type de transaction
- Plan associé
- Montant (KES)
- Statut avec couleurs:
  - ✅ **Completed** - Vert
  - ⏳ **Pending** - Jaune
  - ❌ **Failed** - Rouge
  - ↩️ **Refunded** - Gris
- Lien direct vers Stripe Dashboard

**Fonctionnalités:**
- Clique sur Stripe ID → Ouvre la transaction dans Stripe
- Tri chronologique (plus récent en premier)
- Filtrable par période (7/30/90/365 jours)

### 4. **Revenue Timeline** (Chronologie des revenus)

**Graphique:** Line Chart (2 lignes)  
**Affiche:**
- **Ligne verte** - Revenus quotidiens
- **Ligne rouge** - Remboursements quotidiens
- Permet d'identifier les tendances et anomalies

**Utilité:**
- Détecter les pics de revenus (campagnes marketing)
- Identifier les jours/semaines à faible activité
- Prévoir les revenus futurs (tendances)
- Alertes sur augmentation des remboursements

## Webhook Integration

### Stripe Events Suivis

```php
// Dans includes/class-stripe.php
public function handle_checkout_completed($event) {
    global $wpdb;
    
    $session = $event->data->object;
    $customer_email = $session->customer_email;
    $amount_total = $session->amount_total / 100; // Convert from cents
    $plan_type = $session->metadata->plan_type;
    
    // Insert into revenue tracking
    $wpdb->insert(
        $wpdb->prefix . 'mf_revenue_tracking',
        [
            'user_id' => $user_id,
            'transaction_type' => 'subscription',
            'plan_type' => $plan_type,
            'amount' => $amount_total,
            'currency' => 'KES',
            'stripe_payment_id' => $session->payment_intent,
            'stripe_invoice_id' => $session->invoice,
            'status' => 'completed',
            'metadata' => json_encode($session->metadata),
            'created_at' => current_time('mysql'),
            'completed_at' => current_time('mysql')
        ]
    );
}
```

**Events Écoutés:**
- `checkout.session.completed` - Paiement réussi
- `invoice.payment_succeeded` - Abonnement renouvelé
- `charge.refunded` - Remboursement émis
- `invoice.payment_failed` - Paiement échoué

## Analytics Queries

### Get Revenue Summary

```php
use MalisafiMLS\Analytics\Analytics_Advanced;

$summary = Analytics_Advanced::get_revenue_summary(30);

// Returns:
// - total_revenue (DECIMAL)
// - total_refunds (DECIMAL)
// - avg_transaction_value (DECIMAL)
// - failed_transactions (INT)
```

### Get Revenue by Type

```php
$revenue_by_type = Analytics_Advanced::get_revenue_metrics(30);

// Returns array:
// - transaction_type (STRING)
// - transaction_count (INT)
// - total_revenue (DECIMAL)
// - avg_amount (DECIMAL)
```

### Get Subscription Analytics

```php
$subscriptions = Analytics_Advanced::get_subscription_analytics();

// Returns array by plan:
// - plan_type (STRING)
// - subscriber_count (INT)
// - total_revenue (DECIMAL)
// - avg_revenue (DECIMAL)
```

## Usage Examples

### Viewing Revenue Dashboard

1. **Accédez:** WordPress Admin → Analytics → Revenue
2. **Sélectionnez:** Plage de dates (7/30/90/365 jours)
3. **Analysez:**
   - KPIs en haut (revenus, remboursements, moyenne, taux de succès)
   - Graphique en beignet (revenus par type)
   - Graphique circulaire (abonnés par plan)
   - Tableau des transactions récentes
   - Chronologie des revenus

### Interpreting Data

**High Refund Rate (>10%)**
→ Actions:
- Vérifier la qualité du service
- Améliorer l'onboarding
- Contacter les utilisateurs qui remboursent
- Enquête de satisfaction

**Low Success Rate (<90%)**
→ Actions:
- Vérifier l'intégration Stripe
- Tester le processus de paiement
- Vérifier les cartes déclinées
- Support pour les utilisateurs bloqués

**Declining MRR**
→ Actions:
- Campagne de rétention
- Nouveaux plans attractifs
- Améliorer la valeur perçue
- Programme de fidélité

**High Growth Period**
→ Actions:
- Analyser ce qui a fonctionné
- Répliquer les campagnes réussies
- Augmenter l'infrastructure si besoin
- Préparer le support client

## Advanced Features

### Revenue Forecasting (Phase 3)

Prévoit les revenus futurs basés sur:
- Tendances historiques (croissance mensuelle)
- Taux de renouvellement des abonnements
- Saisonnalité (mois forts vs faibles)
- Nouveaux abonnés moyens par mois

**Formule:**
```
Projected_Revenue = Current_MRR × (1 + Avg_Growth_Rate) + New_Subs × Avg_Plan_Price
```

### Cohort Analysis (Phase 3)

Analyse les utilisateurs par cohorte (mois d'inscription):
- Rétention par cohorte
- LTV (Lifetime Value) par cohorte
- Churn par cohorte

### Revenue per User (RPU)

**Calcul:** `Total Revenue / Active Users`

Indicateur de monétisation:
- RPU élevé = bonne monétisation
- RPU faible = opportunités de upsell

## Testing

### Create Test Revenue Data

```php
global $wpdb;

// Insert test subscription
$wpdb->insert(
    $wpdb->prefix . 'mf_revenue_tracking',
    [
        'user_id' => get_current_user_id(),
        'transaction_type' => 'subscription',
        'plan_type' => 'agent_premium',
        'amount' => 3000.00,
        'currency' => 'KES',
        'stripe_payment_id' => 'pi_test_' . uniqid(),
        'status' => 'completed',
        'created_at' => current_time('mysql'),
        'completed_at' => current_time('mysql')
    ]
);

// Insert test refund
$wpdb->insert(
    $wpdb->prefix . 'mf_revenue_tracking',
    [
        'user_id' => get_current_user_id(),
        'transaction_type' => 'refund',
        'plan_type' => 'agent_basic',
        'amount' => 1000.00,
        'currency' => 'KES',
        'status' => 'refunded',
        'created_at' => current_time('mysql')
    ]
);
```

## Files

- `admin/analytics/revenue.php` - Page revenue dashboard
- `includes/analytics/class-analytics-advanced.php` - Requêtes revenue
- `includes/class-stripe.php` - Intégration webhook Stripe
- `assets/css/analytics.css` - Styles dashboard

## Next Steps

**Phase 2 Extensions:**
- [ ] Export de données CSV/PDF
- [ ] Email alerts pour grandes transactions
- [ ] Revenue forecasting
- [ ] Cohort analysis
- [ ] RPU tracking
- [ ] Churn rate calculation
- [ ] Tax reporting (KRA compliance)

---

**Status:** ✅ Revenue Analytics System Fully Operational  
**Stripe Integration:** ✅ Active  
**Real-time Tracking:** ✅ Webhook-based  
**Reporting:** ✅ Complete
