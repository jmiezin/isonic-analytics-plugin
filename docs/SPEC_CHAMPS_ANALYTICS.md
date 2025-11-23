# 📊 SPÉCIFICATION : Champs Analytics sur Lead

**Date :** 23 novembre 2025  
**Contexte :** Enrichissement des Leads avec données Matomo + Gravity Forms

---

## 🎯 OBJECTIF

Capturer le **contexte web complet** de chaque Lead pour :
1. Améliorer le scoring (leads "chauds" = parcours long, pages stratégiques)
2. Personnaliser l'approche commerciale (connaître l'intérêt réel)
3. Mesurer le ROI des campagnes web

---

## 📋 CHAMPS À CRÉER SUR LEAD

### 1. Parcours Web (Journey)

#### `Web_Time_Spent__c`
- **Type :** Text(50)
- **Label :** "Temps Passé sur le Site"
- **Description :** Durée totale de la session avant soumission formulaire
- **Exemple :** "12 min 34 sec", "45 sec", "1h 23 min"
- **Rempli par :** Plugin WordPress (calcul Matomo)
- **Tracking :** Non (pas historique nécessaire)

#### `Web_Entry_Page__c`
- **Type :** URL(255)
- **Label :** "Page d'Entrée"
- **Description :** Première page visitée lors de la session
- **Exemple :** "https://isonic.fr/blog/erp-ehpad"
- **Rempli par :** Plugin WordPress (Matomo entry page)
- **Tracking :** Non

#### `Web_Journey__c`
- **Type :** Long Text Area(32000)
- **Label :** "Parcours Web"
- **Description :** Liste des pages consultées avant le formulaire
- **Exemple :** 
  ```
  1. /blog/article-erp (3 min)
  2. /solutions/ehpad (5 min)
  3. /demo (2 min)
  4. /pricing (1 min)
  5. /contact (formulaire)
  ```
- **Rempli par :** Plugin WordPress (Matomo page views)
- **Tracking :** Non

---

### 2. Source & Campagne

#### `Web_Source__c`
- **Type :** Text(100)
- **Label :** "Source Web"
- **Description :** Origine du trafic
- **Exemple :** "Google Ads", "Facebook", "LinkedIn", "Direct", "Organic Google", "Email Campaign"
- **Rempli par :** Plugin WordPress (Matomo referrer)
- **Tracking :** Non

#### `Web_Campaign__c`
- **Type :** Text(100)
- **Label :** "Campagne Web"
- **Description :** Nom de la campagne marketing (utm_campaign)
- **Exemple :** "rentree-2024", "black-friday", "webinar-nov"
- **Rempli par :** Plugin WordPress (UTM parameters)
- **Tracking :** Non

#### `Web_Medium__c`
- **Type :** Text(50)
- **Label :** "Medium Web"
- **Description :** Type de medium (utm_medium)
- **Exemple :** "cpc", "email", "social", "referral"
- **Rempli par :** Plugin WordPress (UTM parameters)
- **Tracking :** Non

#### `Web_Keyword__c`
- **Type :** Text(255)
- **Label :** "Mot-Clé"
- **Description :** Mot-clé de recherche si trafic organique/payant
- **Exemple :** "logiciel gestion ehpad", "erp santé"
- **Rempli par :** Plugin WordPress (Matomo keyword ou utm_term)
- **Tracking :** Non

---

### 3. Formulaire

#### `Form_Page__c`
- **Type :** URL(255)
- **Label :** "Page du Formulaire"
- **Description :** URL exacte où le formulaire a été soumis
- **Exemple :** "https://isonic.fr/contact-demo"
- **Rempli par :** Plugin WordPress (current page URL)
- **Tracking :** Non

#### `Form_Type__c`
- **Type :** Picklist
- **Label :** "Type de Formulaire"
- **Description :** Nature du formulaire selon la page
- **Values :**
  - Demo
  - Support
  - Contact General
  - Devis
  - Newsletter
  - Webinar
  - Essai Gratuit
  - Autre
- **Rempli par :** Plugin WordPress (détection automatique selon URL ou Gravity Form ID)
- **Tracking :** Non

#### `Form_Name__c`
- **Type :** Text(100)
- **Label :** "Nom du Formulaire"
- **Description :** Nom exact du formulaire Gravity Forms
- **Exemple :** "Contact Demo EHPAD", "Support Technique"
- **Rempli par :** Plugin WordPress (Gravity Forms title)
- **Tracking :** Non

---

### 4. Engagement

#### `Web_Visit_Count__c`
- **Type :** Number(3,0)
- **Label :** "Nombre de Visites"
- **Description :** Nombre total de visites avant soumission formulaire
- **Exemple :** 1 (première visite), 5 (lead "chaud" revenu plusieurs fois)
- **Rempli par :** Plugin WordPress (Matomo visitor count)
- **Tracking :** Non

#### `Web_First_Visit__c`
- **Type :** Date
- **Label :** "Première Visite Web"
- **Description :** Date de la toute première visite du visiteur
- **Exemple :** 2024-11-01
- **Rempli par :** Plugin WordPress (Matomo first visit)
- **Tracking :** Non

#### `Web_Pages_Viewed__c`
- **Type :** Number(4,0)
- **Label :** "Pages Consultées"
- **Description :** Nombre total de pages vues lors de la session
- **Exemple :** 12
- **Rempli par :** Plugin WordPress (Matomo page count)
- **Tracking :** Non

---

### 5. Technique (Optionnel)

#### `Web_Device__c`
- **Type :** Picklist
- **Label :** "Appareil"
- **Description :** Type d'appareil utilisé
- **Values :**
  - Desktop
  - Mobile
  - Tablet
- **Rempli par :** Plugin WordPress (Matomo device type)
- **Tracking :** Non

#### `Web_Browser__c`
- **Type :** Text(50)
- **Label :** "Navigateur"
- **Description :** Navigateur utilisé
- **Exemple :** "Chrome 119", "Safari 17", "Firefox 120"
- **Rempli par :** Plugin WordPress (User Agent parsing)
- **Tracking :** Non

---

## 📊 TOTAL : 13 NOUVEAUX CHAMPS

| Catégorie | Nombre |
|-----------|--------|
| Parcours Web | 3 |
| Source & Campagne | 4 |
| Formulaire | 3 |
| Engagement | 3 |
| Technique (optionnel) | 2 |

---

## 🎯 IMPACT SUR LE SCORING

### Nouveau Scoring Proposé

```
Score Lead = 
  
  // ENGAGEMENT (max 50 points)
  + 10 si Web_Visit_Count >= 3 (lead récurrent)
  + 20 si Web_Time_Spent >= 10 min (très engagé)
  + 10 si Web_Pages_Viewed >= 5 (exploration profonde)
  + 10 si Web_First_Visit < 30 jours (lead récent)
  
  // INTENTION (max 30 points)
  + 30 si Form_Type = "Demo" (intention forte)
  + 20 si Form_Type = "Devis" (intention forte)
  + 15 si Form_Type = "Essai Gratuit"
  + 10 si Form_Type = "Webinar"
  + 5 si Form_Type = "Contact General"
  + 0 si Form_Type = "Newsletter"
  
  // PARCOURS QUALIFIÉ (max 20 points)
  + 10 si Web_Journey contient "/pricing" (consulté les prix)
  + 10 si Web_Journey contient "/solutions" (intérêt produit)
  + 5 si Web_Journey contient "/blog" (recherche d'info)
  
  // MÉTIER (existant, max 50 points)
  + 50 si Timing = "Immédiat"
  + 30 si Timing = "3 mois"
  + 10 si Timing = "6 mois"
  + 20 si Adresse complète
  + 10 si Budget renseigné
  
  // TOTAL MAX = 150 points
```

---

## 🚀 DÉPLOIEMENT

### Ordre de Création

1. **Créer les 13 champs** (API Metadata ou Setup UI)
2. **Ajouter au Layout Lead** (section "Contexte Web")
3. **Mettre à jour Permission Sets** (FLS en read pour Users, write pour API)
4. **Tester avec données fictives**
5. **Modifier formule Lead Score** (intégrer nouveaux critères)
6. **Déployer le plugin WordPress**

---

## 📝 NOTES IMPORTANTES

1. **Pas de tracking FeedHistory** : Données statiques, pas besoin d'historique
2. **Champs en read-only pour Users** : Seul le plugin peut écrire (via API)
3. **Long Text Area limité à 32K** : Si parcours très long, tronquer
4. **Privacy/RGPD** : Vérifier conformité (anonymisation IP Matomo ?)

---

## 🔗 FICHIERS LIÉS

- `SPEC_PLUGIN_ANALYTICS.md` : Spécifications techniques du plugin WordPress
- `API_SALESFORCE_ENRICHMENT.md` : Documentation API Salesforce pour le plugin

