# 🎯 MAPPING : Formulaires → Campagnes Salesforce

**Date :** 23 novembre 2025  
**Source :** Clarifications utilisateur + Screenshots Matomo/Gravity Forms

---

## 📊 DONNÉES MATOMO DISPONIBLES

### Profil Visiteur Matomo Fournit :

```yaml
Visite #2 (exemple):
  Visitor ID: 68e35f6d3805284f
  
  Session actuelle:
    Date: Dimanche 23 novembre 2025 12:20:35
    Durée: 30 min 54s
    Pages vues: 14 actions
    Point d'entrée: /formation-echographie-urgences-partie-1-sonoschool/
    
  Historique complet:
    Première visite: Mardi 28 octobre 2025 (25 jours avant)
    Dernière visite: Dimanche 23 novembre 2025 (0 jours)
    Total visites: 2
    Total temps: 42 min 3s
    Total pages: 22 pages
    
  Localisation:
    Ville: Sillars, France
    Appareil: Windows 10, Firefox 145.0
    
  Parcours détaillé (visite #2):
    1. Formation Échographie Urgence | Échographie Point of Care
    2. Formation échographie - cours gratuits Sonoschool | iSonic
    3. Formation Échographie Pulmonaire | Sonoschool
    4. Utilisation de l'échographe en écho pulmonaire | Sonoschool
    5. Maniement des sondes en échographie pulmonaire | Sonoschool
    6. Écho-anatomie pulmonaire | Sonoschool
    7. Quizz formation échographie pulmonaire module 2
    8. Pathologies pulmonaires en échographie | Sonoschool
    9. Anatomie et Écho-anatomie Pneumothorax | Sonoschool
    10. Formation Échographie Pneumothorax | Sonoschool
    ... (14 pages au total)
```

---

## 🔄 MAPPING AUTOMATIQUE

### 1. Lead Source (Picklist Standard Salesforce)

**Source des données :** `referrerType` de la **PREMIÈRE VISITE** Matomo

**Mapping Matomo → Salesforce :**

| Matomo `referrerType` | Salesforce `LeadSource` | Notes |
|----------------------|-------------------------|-------|
| `direct` | "Site web" | Entrée directe (URL tapée, favori) |
| `search` | "Site web" | Moteur de recherche (Google, Bing, etc.) |
| `social` | "Site web" | Réseaux sociaux (Facebook, LinkedIn) |
| `website` | "Site web" | Autre site web (referral) |
| `campaign` | "Site web" | Campagne marketing (UTM) |

**⚠️ Important :** Toujours utiliser la **première visite** (pas la dernière) pour déterminer la source originale du lead.

---

### 2. Campagne Salesforce (Lookup Campaign)

**Logique de mapping basée sur le formulaire Gravity Forms :**

#### Règle A : Formulaire "Inscription Isonic"

```php
if ($form['title'] === 'Inscription Isonic' || $form_id === 1) {
    $campaign_name = 'Contenu pédagogique';
}
```

**Campaign à créer dans Salesforce :**
- **Name :** Contenu pédagogique
- **Type :** Content Download (ou Web Form)
- **Status :** Active
- **Description :** Leads issus du formulaire d'inscription aux contenus pédagogiques iSonic (formations, cours gratuits)

---

#### Règle B : Tous les Autres Formulaires

```php
else {
    $campaign_name = 'Site web isonic.fr';
}
```

**Campaign à créer dans Salesforce :**
- **Name :** Site web isonic.fr
- **Type :** Website
- **Status :** Active
- **Description :** Leads issus des formulaires génériques du site web iSonic (contact, demo, support, etc.)

**Formulaires concernés :**
- "À propos de vous" (Form ID 2)
- Futurs formulaires de contact
- Formulaires de demo
- Formulaires de support
- Etc.

---

## 📋 GRAVITY FORMS IDENTIFIÉS

| Form ID | Titre | Entrées | Vues | Conversion | Campagne Salesforce |
|---------|-------|---------|------|------------|---------------------|
| **1** | **Inscription Isonic** | 21,093 | 263,118 | 8% | **Contenu pédagogique** |
| **2** | À propos de vous | 180 | 2,072 | 8.7% | Site web isonic.fr |

---

## 🔧 IMPLÉMENTATION PLUGIN

### Fonction de Mapping

```php
/**
 * Détermine la campagne Salesforce selon le formulaire
 */
function isonic_get_campaign_name($form_id, $form_title) {
    // Règle A : Inscription Isonic → Contenu pédagogique
    if ($form_id === 1 || 
        stripos($form_title, 'Inscription Isonic') !== false) {
        return 'Contenu pédagogique';
    }
    
    // Règle B : Tous les autres → Site web isonic.fr
    return 'Site web isonic.fr';
}

/**
 * Récupère l'ID de la Campaign Salesforce par nom
 */
function isonic_get_campaign_id($campaign_name, $sf_api) {
    // Query Salesforce
    $query = "SELECT Id FROM Campaign WHERE Name = '" . 
             addslashes($campaign_name) . "' AND IsActive = true LIMIT 1";
    
    $result = $sf_api->query($query);
    
    if ($result && !empty($result['records'])) {
        return $result['records'][0]['Id'];
    }
    
    // Fallback : log error
    error_log("[iSonic Analytics] Campaign not found: {$campaign_name}");
    return null;
}

/**
 * Détermine le Lead Source basé sur la PREMIÈRE visite Matomo
 */
function isonic_get_lead_source($matomo_data) {
    // Toujours utiliser la PREMIÈRE visite
    $first_visit = $matomo_data['first_visit'] ?? $matomo_data['current_visit'];
    
    $referrer_type = $first_visit['referrerType'] ?? 'direct';
    
    // Mapping Matomo → Salesforce LeadSource
    $mapping = [
        'direct' => 'Site web',
        'search' => 'Site web',
        'social' => 'Site web',
        'website' => 'Site web',
        'campaign' => 'Site web'
    ];
    
    return $mapping[$referrer_type] ?? 'Site web';
}
```

---

### Hook Gravity Forms

```php
add_action('gform_after_submission', 'isonic_enrich_and_send_to_salesforce', 10, 2);

function isonic_enrich_and_send_to_salesforce($entry, $form) {
    // 1. Récupérer données Matomo
    $matomo_api = new Isonic_Matomo_API();
    $matomo_data = $matomo_api->get_visitor_history();
    
    // 2. Déterminer Campaign Salesforce
    $campaign_name = isonic_get_campaign_name($form['id'], $form['title']);
    
    // 3. Récupérer Campaign ID Salesforce
    $sf_api = new Isonic_Salesforce_API();
    $campaign_id = isonic_get_campaign_id($campaign_name, $sf_api);
    
    // 4. Déterminer Lead Source
    $lead_source = isonic_get_lead_source($matomo_data);
    
    // 5. Construire payload Salesforce
    $lead_data = [
        // Champs formulaire standard
        'FirstName' => $entry['1'],
        'LastName' => $entry['2'],
        'Email' => $entry['3'],
        // ... autres champs
        
        // Lead Source (picklist)
        'LeadSource' => $lead_source, // "Site web"
        
        // Campaign (lookup) - sera utilisé pour créer CampaignMember
        'Campaign__c' => $campaign_id, // ID de "Contenu pédagogique" ou "Site web isonic.fr"
        
        // Champs analytics (nouveaux)
        'Web_Time_Spent__c' => $matomo_data['total_time'],
        'Web_Entry_Page__c' => $matomo_data['first_visit']['entry_page'],
        'Web_Journey__c' => $matomo_data['journey_formatted'],
        'Web_Source__c' => $matomo_data['first_visit']['referrerName'], // "Google", "Direct", etc.
        'Web_First_Visit__c' => $matomo_data['first_visit_date'], // Mardi 28 octobre 2025
        'Web_Visit_Count__c' => $matomo_data['visit_count'], // 2
        'Web_Pages_Viewed__c' => $matomo_data['total_pages'], // 22
        'Form_Name__c' => $form['title'],
        'Form_Type__c' => isonic_detect_form_type($form['id'], $entry)
    ];
    
    // 6. Créer Lead dans Salesforce
    $lead_id = $sf_api->create_lead($lead_data);
    
    // 7. Créer CampaignMember si Campaign définie
    if ($campaign_id && $lead_id) {
        $sf_api->create_campaign_member($campaign_id, $lead_id);
    }
}
```

---

## 📊 DONNÉES ENRICHIES - EXEMPLE RÉEL

**Scénario :** Visiteur du screenshot Matomo soumet "Inscription Isonic"

### Lead créé dans Salesforce :

```yaml
Lead Fields:
  # Standard
  FirstName: [Prénom du formulaire]
  LastName: [Nom du formulaire]
  Email: [Email du formulaire]
  Company: [Entreprise du formulaire]
  
  # Lead Source
  LeadSource: "Site web"  # Basé sur 1ère visite (28 oct)
  
  # Analytics
  Web_Time_Spent__c: "42 min 3 sec"  # Total 2 visites
  Web_Entry_Page__c: "/formation-echographie-urgences-partie-1-sonoschool/"
  Web_Journey__c: |
    Visite #1 (28 octobre 2025):
    [Parcours première visite]
    
    Visite #2 (23 novembre 2025 - 30 min 54s):
    1. Formation Échographie Urgence (entrée)
    2. Formation échographie - cours gratuits
    3. Formation Échographie Pulmonaire
    4. Utilisation de l'échographe en écho pulmonaire
    5. Maniement des sondes en échographie
    6. Écho-anatomie pulmonaire
    7. Quizz formation échographie module 2
    8. Pathologies pulmonaires en échographie
    9. Anatomie Pneumothorax
    10. Formation Pneumothorax
    ... (14 pages)
    
  Web_Source__c: "Direct" (ou "Google" selon 1ère visite)
  Web_First_Visit__c: 2025-10-28
  Web_Visit_Count__c: 2
  Web_Pages_Viewed__c: 22
  Web_Device__c: "Desktop"
  Web_Browser__c: "Firefox 145.0"
  
  Form_Page__c: "https://isonic.fr/inscription/"
  Form_Name__c: "Inscription Isonic"
  Form_Type__c: "Formation"

CampaignMember créé:
  CampaignId: [ID de "Contenu pédagogique"]
  LeadId: [ID du Lead créé]
  Status: "Sent" (ou autre status selon config Campaign)
```

---

## 🎯 CAMPAIGNS SALESFORCE (EXISTANTES)

### ✅ Campaigns Déjà Créées

**Campaign #1 : Contenu pédagogique**
```
Name: Contenu pédagogique
ID: 701Jv00000oEgv7IAC
Type: [Existant]
Status: Active
Description: Leads issus du formulaire "Inscription Isonic" pour accès aux contenus de formation échographie (Sonoschool)
```

**Campaign #2 : Site web isonic.fr**
```
Name: Site web isonic.fr
ID: 701Jv00000oEi1EIAS
Type: [Existant]
Status: Active
Description: Leads issus des formulaires génériques du site iSonic (contact, demo, support)
```

**⚠️ IMPORTANT :** Le plugin utilisera ces IDs pour créer automatiquement les CampaignMembers.

---

### 2. Modifier Lead_Trigger_New pour CampaignMember

**Actuellement :**
```
SI Campagne_Source__c rempli
  → Créer CampaignMember
```

**Nouveau (avec plugin) :**
```
SI Campaign__c rempli (par plugin)
  → CampaignMember déjà créé par le plugin
  → SKIP la création (éviter doublon)

OU

SI Campagne_Source__c rempli (ancien champ, legacy)
  → Créer CampaignMember (backward compatibility)
```

**Alternative simplifiée :** Supprimer la logique CampaignMember du Flow Salesforce, laisser le plugin gérer à 100%.

---

## 📝 RÉSUMÉ

| Élément | Source | Destination Salesforce | Logique |
|---------|--------|------------------------|---------|
| **Lead Source** | Matomo `referrerType` (1ère visite) | `LeadSource` = "Site web" | Toujours "Site web" pour formulaires web |
| **Campagne** | Gravity Forms (titre/ID) | `Campaign` (lookup) | "Inscription Isonic" → "Contenu pédagogique"<br>Autres → "Site web isonic.fr" |
| **1ère visite** | Matomo `firstActionTimestamp` | `Web_First_Visit__c` | Date de découverte du site |
| **Parcours** | Matomo `actionDetails` (toutes visites) | `Web_Journey__c` | Historique complet multi-visites |

---

## ✅ NEXT STEPS

1. **Créer les 2 Campaigns dans Salesforce** (5 min)
2. **Tester le mapping manuellement** (créer Lead avec données fictives)
3. **Implémenter la logique dans le plugin** (30 min)
4. **Tester avec vrai formulaire** (5 min)
5. **Vérifier CampaignMember créé automatiquement** (2 min)

---

**🎯 Avec ces données, votre scoring va devenir ULTRA-PRÉCIS !**

Exemple : Lead qui a consulté 22 pages de formation sur 2 visites = **TRÈS ENGAGÉ** = Score élevé = Priorité commerciale !

