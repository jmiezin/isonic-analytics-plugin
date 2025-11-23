# ⚙️ CONFIGURATION PLUGIN : Campaign IDs

**Date :** 23 novembre 2025  
**Org :** Production (j.miezin2@isonic.fr)

---

## 📋 CAMPAIGN IDS SALESFORCE

### Campaigns Existantes

| Campaign Name | Campaign ID | Usage |
|---------------|-------------|-------|
| **Site web isonic.fr** | `701Jv00000oEi1EIAS` | Formulaires génériques (contact, demo, support, etc.) |
| **Contenu pédagogique** | `701Jv00000oEgv7IAC` | Formulaire "Inscription Isonic" uniquement |

---

## 🔧 CONFIGURATION PLUGIN WORDPRESS

### Fichier: `includes/class-campaign-mapper.php`

```php
<?php
/**
 * Campaign Mapper - Gère le mapping Formulaire → Campaign Salesforce
 *
 * @package iSonic_Analytics
 */

class Isonic_Campaign_Mapper {
    
    /**
     * Campaign IDs Salesforce (hardcodés car stables)
     */
    const CAMPAIGN_SITE_WEB = '701Jv00000oEi1EIAS';
    const CAMPAIGN_CONTENU_PEDAGOGIQUE = '701Jv00000oEgv7IAC';
    
    /**
     * Form ID Gravity Forms pour "Inscription Isonic"
     */
    const FORM_ID_INSCRIPTION_ISONIC = 1;
    
    /**
     * Détermine la Campaign Salesforce selon le formulaire
     *
     * @param int $form_id Gravity Forms ID
     * @param string $form_title Titre du formulaire
     * @return string Campaign ID Salesforce
     */
    public static function get_campaign_id( $form_id, $form_title ) {
        
        // Règle A : Formulaire "Inscription Isonic" → Contenu pédagogique
        if ( $form_id === self::FORM_ID_INSCRIPTION_ISONIC ) {
            return self::CAMPAIGN_CONTENU_PEDAGOGIQUE;
        }
        
        // Détection par titre (fallback si Form ID change)
        if ( stripos( $form_title, 'Inscription Isonic' ) !== false ||
             stripos( $form_title, 'Inscription iSonic' ) !== false ) {
            return self::CAMPAIGN_CONTENU_PEDAGOGIQUE;
        }
        
        // Règle B : Tous les autres formulaires → Site web isonic.fr
        return self::CAMPAIGN_SITE_WEB;
    }
    
    /**
     * Récupère le nom de la Campaign pour logging
     *
     * @param string $campaign_id Salesforce Campaign ID
     * @return string Nom de la campagne
     */
    public static function get_campaign_name( $campaign_id ) {
        switch ( $campaign_id ) {
            case self::CAMPAIGN_CONTENU_PEDAGOGIQUE:
                return 'Contenu pédagogique';
            
            case self::CAMPAIGN_SITE_WEB:
                return 'Site web isonic.fr';
            
            default:
                return 'Unknown Campaign';
        }
    }
    
    /**
     * Valide qu'une Campaign ID est valide
     *
     * @param string $campaign_id
     * @return bool
     */
    public static function is_valid_campaign_id( $campaign_id ) {
        return in_array( $campaign_id, [
            self::CAMPAIGN_SITE_WEB,
            self::CAMPAIGN_CONTENU_PEDAGOGIQUE
        ], true );
    }
}
```

---

## 📝 UTILISATION DANS LE HOOK GRAVITY FORMS

### Fichier: `includes/class-form-enricher.php`

```php
<?php
/**
 * Form Enricher - Enrichit les soumissions Gravity Forms
 */

add_action( 'gform_after_submission', 'isonic_enrich_and_send', 10, 2 );

function isonic_enrich_and_send( $entry, $form ) {
    
    // 1. Récupérer données Matomo
    $matomo_api = new Isonic_Matomo_API();
    $matomo_data = $matomo_api->get_visitor_history();
    
    // 2. Déterminer Campaign Salesforce
    $campaign_id = Isonic_Campaign_Mapper::get_campaign_id( 
        $form['id'], 
        $form['title'] 
    );
    
    $campaign_name = Isonic_Campaign_Mapper::get_campaign_name( $campaign_id );
    
    // Log
    error_log( sprintf(
        '[iSonic Analytics] Form "%s" (ID: %d) → Campaign "%s" (ID: %s)',
        $form['title'],
        $form['id'],
        $campaign_name,
        $campaign_id
    ));
    
    // 3. Construire payload Salesforce
    $lead_data = array(
        // Champs standard du formulaire
        'FirstName' => rgar( $entry, '1' ),
        'LastName' => rgar( $entry, '2' ),
        'Email' => rgar( $entry, '3' ),
        'Company' => rgar( $entry, '4' ),
        
        // Lead Source (toujours "Site web" pour formulaires web)
        'LeadSource' => 'Site web',
        
        // Champs analytics Matomo
        'Web_Time_Spent__c' => $matomo_data['time_spent'] ?? '',
        'Web_Entry_Page__c' => $matomo_data['entry_page'] ?? '',
        'Web_Journey__c' => $matomo_data['journey'] ?? '',
        'Web_Source__c' => $matomo_data['source'] ?? '',
        'Web_Medium__c' => $matomo_data['medium'] ?? '',
        'Web_Keyword__c' => $matomo_data['keyword'] ?? '',
        'Web_First_Visit__c' => $matomo_data['first_visit_date'] ?? '',
        'Web_Visit_Count__c' => $matomo_data['visit_count'] ?? 1,
        'Web_Pages_Viewed__c' => $matomo_data['pages_viewed'] ?? 1,
        
        // Champs formulaire
        'Form_Page__c' => $entry['source_url'] ?? '',
        'Form_Name__c' => $form['title'] ?? '',
        'Form_Type__c' => isonic_detect_form_type( $form['id'], $entry ),
    );
    
    // 4. Envoyer à Salesforce
    $sf_api = new Isonic_Salesforce_API();
    $lead_id = $sf_api->create_lead( $lead_data );
    
    // 5. Créer CampaignMember
    if ( $lead_id && $campaign_id ) {
        $member_created = $sf_api->create_campaign_member( $campaign_id, $lead_id );
        
        if ( $member_created ) {
            error_log( sprintf(
                '[iSonic Analytics] CampaignMember created: Lead %s → Campaign %s',
                $lead_id,
                $campaign_name
            ));
        }
    }
}
```

---

## 🧪 TESTS DE MAPPING

### Test Case 1 : Formulaire "Inscription Isonic"

```php
// Input
$form = [
    'id' => 1,
    'title' => 'Inscription Isonic'
];

// Expected Output
$campaign_id = Isonic_Campaign_Mapper::get_campaign_id( 1, 'Inscription Isonic' );
// → '701Jv00000oEgv7IAC' (Contenu pédagogique) ✅
```

---

### Test Case 2 : Formulaire "À propos de vous"

```php
// Input
$form = [
    'id' => 2,
    'title' => 'À propos de vous'
];

// Expected Output
$campaign_id = Isonic_Campaign_Mapper::get_campaign_id( 2, 'À propos de vous' );
// → '701Jv00000oEi1EIAS' (Site web isonic.fr) ✅
```

---

### Test Case 3 : Futur formulaire "Contact Demo"

```php
// Input
$form = [
    'id' => 3,
    'title' => 'Contact Demo'
];

// Expected Output
$campaign_id = Isonic_Campaign_Mapper::get_campaign_id( 3, 'Contact Demo' );
// → '701Jv00000oEi1EIAS' (Site web isonic.fr) ✅
```

---

## ⚠️ IMPORTANT : HARDCODED IDS

**Pourquoi hardcoder les Campaign IDs ?**

✅ **Avantages :**
- Performance (pas de query Salesforce à chaque soumission)
- Fiabilité (pas de risque d'erreur si Campaign renommée)
- Simplicité (pas de cache à gérer)

⚠️ **Inconvénients :**
- Si Campaign supprimée/changée dans Salesforce → Erreur
- Nécessite modification code si nouvelle Campaign

**Alternative (si besoin futur) :**
- Stocker IDs dans WordPress Options
- Interface admin pour configurer mapping
- Query Salesforce au démarrage + cache 24h

**Recommandation actuelle :** Garder hardcodé, les Campaigns sont stables.

---

## 📊 VÉRIFICATION DANS SALESFORCE

### Query pour vérifier les Campaigns

```bash
sf data query \
  --query "SELECT Id, Name, Type, Status, IsActive FROM Campaign WHERE Id IN ('701Jv00000oEi1EIAS', '701Jv00000oEgv7IAC')" \
  --target-org production \
  --result-format human
```

**Résultat attendu :**

```
ID                  Name                    Type       Status  IsActive
701Jv00000oEi1EIAS  Site web isonic.fr      Website    Active  true
701Jv00000oEgv7IAC  Contenu pédagogique     Content    Active  true
```

---

## ✅ CHECKLIST DÉPLOIEMENT

- [x] Campaign "Site web isonic.fr" existe (701Jv00000oEi1EIAS)
- [x] Campaign "Contenu pédagogique" existe (701Jv00000oEgv7IAC)
- [ ] 13 champs analytics créés sur Lead
- [ ] Layout Lead mis à jour (section "Contexte Web")
- [ ] Permission Sets mis à jour (FLS)
- [ ] Connected App Salesforce créée
- [ ] Plugin WordPress développé
- [ ] Tests effectués
- [ ] Déploiement production

---

**🎯 Prochaine étape : Créer les 13 champs analytics sur Lead !**

