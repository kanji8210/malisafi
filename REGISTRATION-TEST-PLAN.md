# 🧪 Plan de Tests - Système d'Inscription

## 📋 Tests Fonctionnels

### Test 1 : Inscription Client
**Objectif** : Vérifier l'inscription d'un client standard

**Données de test** :
- Type : Client 🏠
- Prénom : Jean
- Nom : Dupont
- Téléphone : 712345678
- Email : jean.dupont@test.com
- Username : jeandupont
- Mot de passe : Test1234!

**Résultat attendu** :
- ✅ Compte créé avec rôle `malisafi_client`
- ✅ Email de bienvenue reçu
- ✅ Redirection vers `/dashboard`
- ✅ Connexion automatique effective

---

### Test 2 : Inscription Agent
**Objectif** : Vérifier l'inscription d'un agent avec champs supplémentaires

**Données de test** :
- Type : Agent 💼
- Prénom : Marie
- Nom : Martin
- Téléphone : 723456789
- **Agence : ABC Immobilier**
- **Licence : LIC-2025-001**
- Email : marie.martin@test.com
- Username : mariemartin
- Mot de passe : Agent2025!

**Résultat attendu** :
- ✅ Compte créé avec rôle `malisafi_agent_basic`
- ✅ Métadonnées agence et licence sauvegardées
- ✅ Email de bienvenue (mention "Agent")
- ✅ Redirection vers `/agent-dashboard`
- ✅ Champs supplémentaires visibles à l'étape 2

---

### Test 3 : Inscription Propriétaire
**Objectif** : Vérifier l'inscription d'un propriétaire

**Données de test** :
- Type : Propriétaire 🔑
- Prénom : Pierre
- Nom : Dubois
- Téléphone : 734567890
- Email : pierre.dubois@test.com
- Username : pierredubois
- Mot de passe : Owner2025!

**Résultat attendu** :
- ✅ Compte créé avec rôle `malisafi_owner`
- ✅ Email de bienvenue personnalisé
- ✅ Redirection vers `/agent-dashboard`

---

### Test 4 : Inscription Développeur
**Objectif** : Vérifier l'inscription d'un développeur

**Données de test** :
- Type : Développeur 🏗️
- Prénom : Sophie
- Nom : Moreau
- Téléphone : 745678901
- Email : sophie.moreau@test.com
- Username : sophiemoreau
- Mot de passe : Dev2025!

**Résultat attendu** :
- ✅ Compte créé avec rôle `malisafi_developer`
- ✅ Email de bienvenue approprié
- ✅ Redirection vers `/agent-dashboard`

---

## 🔍 Tests de Validation

### Test 5 : Email Déjà Existant
**Données** : Email utilisé au Test 1
**Résultat attendu** :
- ❌ Erreur : "Email address is already registered"
- ✅ Bordure rouge sur le champ email
- ✅ Message d'erreur visible

### Test 6 : Username Déjà Existant
**Données** : Username utilisé au Test 1
**Résultat attendu** :
- ❌ Erreur : "Username already exists"
- ✅ Bordure rouge sur le champ username
- ✅ Message d'erreur visible

### Test 7 : Mot de Passe Faible
**Données** : Mot de passe = "123"
**Résultat attendu** :
- ⚠️ Indicateur "Weak password" (rouge)
- ❌ Impossible de soumettre (< 8 caractères)
- ✅ Message sous le champ

### Test 8 : Mot de Passe Moyen
**Données** : Mot de passe = "password123"
**Résultat attendu** :
- ⚠️ Indicateur "Medium strength" (orange)
- ✅ Soumission possible mais déconseillée

### Test 9 : Mot de Passe Fort
**Données** : Mot de passe = "MyP@ssw0rd2025!"
**Résultat attendu** :
- ✅ Indicateur "Strong password" (vert)
- ✅ Validation immédiate

### Test 10 : Mots de Passe Non Correspondants
**Données** : 
- Mot de passe : Test1234!
- Confirmation : Test1235!
**Résultat attendu** :
- ❌ Erreur : "Passwords do not match"
- ✅ Bordure rouge sur confirmation
- ✅ Impossible de soumettre

### Test 11 : Email Invalide
**Données** : Email = "pas-un-email"
**Résultat attendu** :
- ❌ Bordure rouge
- ✅ Validation HTML5 (type="email")
- ❌ Impossible de continuer

### Test 12 : Téléphone Invalide
**Données** : Téléphone = "abc"
**Résultat attendu** :
- ✅ Caractères non numériques supprimés automatiquement
- ✅ Formatage au fur et à mesure

### Test 13 : Champs Requis Vides
**Action** : Essayer de passer à l'étape 2 sans sélectionner de type
**Résultat attendu** :
- ✅ Bouton "Continue" désactivé (disabled)
- ✅ Impossible de continuer

### Test 14 : CGU Non Acceptées
**Action** : Essayer de soumettre sans cocher CGU
**Résultat attendu** :
- ✅ Bouton "Create Account" désactivé
- ❌ Validation HTML5 empêche soumission

---

## 🎨 Tests Interface

### Test 15 : Navigation Entre Étapes
**Actions** :
1. Étape 1 → Étape 2 (bouton Suivant)
2. Étape 2 → Étape 3 (bouton Suivant)
3. Étape 3 → Étape 2 (bouton Retour)
4. Étape 2 → Étape 1 (bouton Retour)

**Résultat attendu** :
- ✅ Transitions fluides (animation fadeIn)
- ✅ Barre de progression mise à jour
- ✅ Données conservées entre les étapes
- ✅ Étapes marquées "completed" en vert

### Test 16 : Bouton Afficher/Masquer Mot de Passe
**Actions** :
1. Taper mot de passe
2. Cliquer sur l'icône œil

**Résultat attendu** :
- ✅ Type passe de "password" à "text"
- ✅ Icône change (👁️ → 🙈)
- ✅ Mot de passe visible/caché

### Test 17 : Sélection Type de Compte
**Actions** :
1. Cliquer sur carte "Client"
2. Cliquer sur carte "Agent"

**Résultat attendu** :
- ✅ Carte Client désélectionnée
- ✅ Carte Agent sélectionnée (fond violet)
- ✅ Champs agence apparaissent (slideDown)
- ✅ Bouton "Continue" activé

### Test 18 : Indicateur Force Mot de Passe
**Actions** : Taper progressivement un mot de passe

**Résultats attendus** :
- "test" → Faible (rouge)
- "test1234" → Moyen (orange)
- "Test1234!" → Fort (vert)
- Barre de progression se remplit

---

## 📱 Tests Responsive

### Test 19 : Mobile (< 480px)
**Appareil** : iPhone SE, Samsung Galaxy
**Points à vérifier** :
- ✅ Cartes de type empilées (1 colonne)
- ✅ Champs pleine largeur
- ✅ Boutons tactiles (taille adaptée)
- ✅ Pas de scroll horizontal
- ✅ Texte lisible sans zoom

### Test 20 : Tablette (768px)
**Appareil** : iPad, Android Tablet
**Points à vérifier** :
- ✅ Grille adaptée (2 colonnes max)
- ✅ Espacement confortable
- ✅ Navigation tactile fluide

### Test 21 : Desktop (> 1024px)
**Appareil** : Laptop, Desktop
**Points à vérifier** :
- ✅ Grille 4 colonnes pour cartes
- ✅ Champs prénom/nom côte à côte
- ✅ Tous les détails visibles
- ✅ Centré avec max-width

---

## 🌐 Tests Navigateurs

### Test 22 : Chrome
- [ ] Inscription complète
- [ ] Validation temps réel
- [ ] Animations fluides

### Test 23 : Firefox
- [ ] Inscription complète
- [ ] Styles corrects
- [ ] AJAX fonctionnel

### Test 24 : Safari
- [ ] Inscription complète
- [ ] Compatibilité iOS
- [ ] Pas de bugs webkit

### Test 25 : Edge
- [ ] Inscription complète
- [ ] Compatibilité Windows
- [ ] Rendu identique

---

## 🔒 Tests Sécurité

### Test 26 : Injection SQL
**Action** : Taper `'; DROP TABLE users; --` dans username
**Résultat attendu** :
- ✅ Requête préparée empêche injection
- ✅ Caractères échappés correctement

### Test 27 : XSS (Cross-Site Scripting)
**Action** : Taper `<script>alert('XSS')</script>` dans prénom
**Résultat attendu** :
- ✅ Texte échappé dans l'affichage
- ✅ Pas d'exécution de script

### Test 28 : CSRF (Cross-Site Request Forgery)
**Action** : Soumettre formulaire sans nonce valide
**Résultat attendu** :
- ❌ Erreur : "Security check failed"
- ✅ Inscription refusée

### Test 29 : Brute Force
**Action** : Soumettre 100 fois en 1 minute
**Résultat attendu** :
- ⚠️ Rate limiting recommandé (à implémenter)
- Actuellement : Pas de limite

### Test 30 : Session Hijacking
**Action** : Tester avec cookies désactivés
**Résultat attendu** :
- ✅ Inscription fonctionne (pas de session pré-requise)
- ✅ Connexion auto après inscription

---

## ✉️ Tests Email

### Test 31 : Email de Bienvenue - Client
**Vérifications** :
- ✅ Reçu dans les 2 minutes
- ✅ Prénom personnalisé
- ✅ Mention "Client"
- ✅ Lien dashboard fonctionnel
- ✅ Pas dans les spams

### Test 32 : Email de Bienvenue - Agent
**Vérifications** :
- ✅ Mention "Real Estate Agent"
- ✅ Informations agence si renseignées
- ✅ Lien agent-dashboard

### Test 33 : Email - Domaine Invalide
**Données** : Email = "test@domaine-inexistant-123.com"
**Résultat attendu** :
- ✅ Compte créé quand même
- ⚠️ Email non reçu (domaine invalide)
- Note : Validation DNS non implémentée

---

## 🚀 Tests Performance

### Test 34 : Temps de Chargement
**Mesure** : Temps d'affichage complet du formulaire
**Résultat attendu** :
- ✅ < 2 secondes sur connexion normale
- ✅ CSS/JS chargés uniquement sur page d'inscription

### Test 35 : Temps de Validation AJAX
**Mesure** : Délai vérification email/username
**Résultat attendu** :
- ✅ < 500ms par vérification
- ✅ Indicateur de chargement visible

### Test 36 : Temps d'Inscription
**Mesure** : Clic "Create Account" → Redirection
**Résultat attendu** :
- ✅ < 3 secondes sur serveur normal
- ✅ Indicateur "Creating account..." visible

---

## 📊 Tests Analytics (Optionnel)

### Test 37 : Tracking Étapes
**Vérifier que les événements sont envoyés** :
- Étape 1 complétée
- Étape 2 complétée
- Inscription finalisée

### Test 38 : Abandons
**Identifier où les utilisateurs abandonnent** :
- % à l'étape 1
- % à l'étape 2
- % à l'étape 3

---

## ✅ Checklist Finale

### Avant Production
- [ ] Tous les tests fonctionnels passent
- [ ] Validation complète testée
- [ ] Responsive vérifié (3 tailles)
- [ ] 4 navigateurs testés
- [ ] Emails reçus correctement
- [ ] HTTPS activé
- [ ] SMTP configuré
- [ ] Pages CGU créées et liées
- [ ] Logs de debug désactivés en prod
- [ ] Backup de la base de données effectué

### Après Lancement
- [ ] Monitorer les logs d'erreur (24h)
- [ ] Vérifier taux de conversion (1 semaine)
- [ ] Collecter feedback utilisateurs
- [ ] Analyser les abandons
- [ ] Optimiser selon données

---

## 🐛 Rapports de Bugs

**Format recommandé** :

```
Titre : [Type de bug] Description courte

**Étapes de reproduction** :
1. Action 1
2. Action 2
3. Action 3

**Résultat attendu** :
Ce qui devrait se passer

**Résultat actuel** :
Ce qui se passe réellement

**Environnement** :
- Navigateur : Chrome 120
- OS : Windows 11
- Appareil : Desktop

**Logs/Captures** :
[Joindre captures d'écran ou logs]
```

---

## 📝 Notes de Test

**Environnement de test recommandé** :
- URL : `https://staging.malisafi.com/inscription`
- Base de données : Copie de production
- Emails : Service de test (Mailtrap, Mailhog)

**Données de test à NE PAS utiliser en production** :
- Emails @test.com
- Usernames préfixés "test"
- Numéros de téléphone fictifs

**Nettoyage après tests** :
```sql
DELETE FROM wp_users WHERE user_email LIKE '%@test.com';
DELETE FROM wp_usermeta WHERE user_id NOT IN (SELECT ID FROM wp_users);
```

---

**Durée estimée des tests complets** : 2-3 heures
**Fréquence recommandée** : Avant chaque mise à jour majeure

✅ Tests effectués par : _______________
📅 Date : _______________
✅ Statut : [ ] Tous passés [ ] Quelques échecs [ ] À reprendre
