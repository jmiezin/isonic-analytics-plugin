# 🔌 SPÉCIFICATION TECHNIQUE : Plugin WordPress Analytics

**Nom :** iSonic Analytics Enrichment for Salesforce  
**Version :** 1.0.0  
**Date :** 23 novembre 2025

---

## 🎯 OBJECTIF

Plugin WordPress qui enrichit automatiquement les soumissions Gravity Forms avec les données analytics Matomo avant envoi à Salesforce.

---

## 🏗️ ARCHITECTURE

```
┌─────────────────────────────────────────────────────────┐
│               VISITEUR WEB                              │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│            MATOMO TRACKING (JavaScript)                 │
│  - Track page views                                     │
│  - Track session duration                               │
│  - Track referrer/source                                │
│  - Store in Matomo cookie (_pk_id, _pk_ses)           │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│          GRAVITY FORMS (Formulaire soumis)              │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│     PLUGIN iSonic Analytics (Hook Gravity Forms)        │
│                                                          │
│  1. Récupère Matomo Visitor ID (cookie)                │
│  2. Appelle Matomo API (get visitor info)              │
│  3. Parse données analytics                             │
│  4. Enrichit entry Gravity Forms                        │
└─────────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────────┐
│        SALESFORCE API (Lead Creation)                   │
│  - Données formulaire (standard)                        │
│  - Données analytics (enrichies)                        │
└─────────────────────────────────────────────────────────┘
```

---

## 📦 STACK TECHNIQUE

| Composant | Technologie |
|-----------|-------------|
| **Backend** | PHP 8.0+ (WordPress) |
| **Analytics** | Matomo API (HTTP API + Reporting API) |
| **Formulaires** | Gravity Forms (hooks PHP) |
| **CRM** | Salesforce REST API |
| **Storage** | WordPress Options API (settings) |
| **Logging** | WordPress Debug Log |

---

## 🔧 FONCTIONNALITÉS

### 1. Configuration Admin

**Page Settings dans WordPress Admin :**

```php
// Menu : Settings → iSonic Analytics

Matomo Configuration:
[x] Enable Matomo Integration
Matomo URL: [https://matomo.isonic.fr]
Matomo Site ID: [1]
Matomo Auth Token: [••••••••••••••]

Salesforce Configuration:
[x] Enable Salesforce Enrichment
Salesforce Instance: [https://isonic-ai.my.salesforce.com]
API Version: [v62.0]
Connected App Consumer Key: [••••••••••••••]
Connected App Consumer Secret: [••••••••••••••]
Username: [integration@isonic.fr]
Password + Security Token: [••••••••••••••]

Form Mapping:
Gravity Form ID → Form Type
[1] Contact Demo → [Demo ▼]
[2] Support → [Support ▼]
[3] Contact → [Contact General ▼]

[Test Connection] [Save Settings]
```

---

### 2. Matomo Data Collection

**Hook :** `gform_after_submission`

**Fonction :** `isonic_collect_matomo_data($entry, $form)`

**Étapes :**

1. **Récupérer Matomo Visitor ID**
   ```php
   $visitor_id = $_COOKIE['_pk_id.1.xxxx']; // Matomo cookie
   ```

2. **Appeler Matomo Reporting API**
   ```php
   GET https://matomo.isonic.fr/index.php?
       module=API
       &method=Live.getLastVisitsDetails
       &idSite=1
       &visitorId={$visitor_id}
       &format=JSON
       &token_auth={$auth_token}
   ```

3. **Parser Réponse**
   ```json
   {
     "visits": [{
       "visitorId": "abc123",
       "firstActionTimestamp": 1700000000,
       "lastActionTimestamp": 1700003600,
       "visitDuration": 3600,
       "actions": 12,
       "referrerType": "search",
       "referrerName": "Google",
       "referrerKeyword": "logiciel ehpad",
       "deviceType": "desktop",
       "browserName": "Chrome",
       "actionDetails": [
         {"url": "/blog/article", "timeSpent": 180},
         {"url": "/solutions/ehpad", "timeSpent": 300},
         {"url": "/pricing", "timeSpent": 120},
         {"url": "/contact", "type": "action"}
       ],
       "campaignName": "rentree-2024",
       "campaignKeyword": "utm_term_value"
     }]
   }
   ```

4. **Extraire Données**
   ```php
   $analytics_data = [
     'time_spent' => format_duration($visit['visitDuration']),
     'entry_page' => $visit['actionDetails'][0]['url'],
     'journey' => format_journey($visit['actionDetails']),
     'source' => get_source($visit),
     'campaign' => $visit['campaignName'],
     'keyword' => $visit['referrerKeyword'],
     'visit_count' => count_visits($visitor_id),
     'first_visit' => date('Y-m-d', $visit['firstActionTimestamp']),
     'pages_viewed' => $visit['actions'],
     'device' => $visit['deviceType'],
     'browser' => $visit['browserName']
   ];
   ```

---

### 3. Form Type Detection

**Fonction :** `isonic_detect_form_type($form_id, $entry)`

**Logique :**

```php
// 1. Mapping manuel (depuis settings)
if (isset($manual_mapping[$form_id])) {
    return $manual_mapping[$form_id];
}

// 2. Détection automatique par URL
$current_url = $entry['source_url'];

if (strpos($current_url, '/demo') !== false) return 'Demo';
if (strpos($current_url, '/support') !== false) return 'Support';
if (strpos($current_url, '/devis') !== false) return 'Devis';
if (strpos($current_url, '/essai') !== false) return 'Essai Gratuit';
if (strpos($current_url, '/webinar') !== false) return 'Webinar';

// 3. Détection par titre formulaire
if (stripos($form['title'], 'demo') !== false) return 'Demo';
if (stripos($form['title'], 'support') !== false) return 'Support';

// 4. Défaut
return 'Contact General';
```

---

### 4. Journey Formatting

**Fonction :** `isonic_format_journey($action_details)`

**Format de sortie :**

```
1. /blog/logiciel-erp-ehpad (3 min)
2. /solutions/ehpad (5 min)
3. /fonctionnalites (2 min)
4. /pricing (1 min)
5. /contact-demo (formulaire)

Pages clés consultées:
✓ Pricing (intérêt commercial)
✓ Solutions (intérêt produit)
✓ Blog (recherche info)
```

---

### 5. Salesforce Field Mapping

**Fonction :** `isonic_map_to_salesforce($entry, $analytics_data)`

**Mapping :**

```php
$salesforce_lead = [
    // Champs standard Gravity Forms
    'FirstName' => $entry['1'], // Field ID 1
    'LastName' => $entry['2'],
    'Email' => $entry['3'],
    'Company' => $entry['4'],
    // ... autres champs existants
    
    // Nouveaux champs analytics
    'Web_Time_Spent__c' => $analytics_data['time_spent'],
    'Web_Entry_Page__c' => $analytics_data['entry_page'],
    'Web_Journey__c' => $analytics_data['journey'],
    'Web_Source__c' => $analytics_data['source'],
    'Web_Campaign__c' => $analytics_data['campaign'],
    'Web_Medium__c' => $analytics_data['medium'],
    'Web_Keyword__c' => $analytics_data['keyword'],
    'Form_Page__c' => $entry['source_url'],
    'Form_Type__c' => $analytics_data['form_type'],
    'Form_Name__c' => $form['title'],
    'Web_Visit_Count__c' => $analytics_data['visit_count'],
    'Web_First_Visit__c' => $analytics_data['first_visit'],
    'Web_Pages_Viewed__c' => $analytics_data['pages_viewed'],
    'Web_Device__c' => $analytics_data['device'],
    'Web_Browser__c' => $analytics_data['browser']
];
```

---

### 6. Salesforce API Integration

**Fonction :** `isonic_send_to_salesforce($lead_data)`

**Flow :**

1. **OAuth2 Authentication**
   ```php
   POST https://login.salesforce.com/services/oauth2/token
   grant_type=password
   client_id={consumer_key}
   client_secret={consumer_secret}
   username={username}
   password={password}{security_token}
   
   → Retourne access_token
   ```

2. **Create Lead**
   ```php
   POST https://isonic-ai.my.salesforce.com/services/data/v62.0/sobjects/Lead
   Authorization: Bearer {access_token}
   Content-Type: application/json
   
   Body: {lead_data}
   ```

3. **Error Handling**
   ```php
   if ($response['success'] === false) {
       // Log error
       error_log('[iSonic Analytics] Salesforce error: ' . $response['errors']);
       
       // Store in WordPress (fallback)
       update_option('isonic_failed_leads', $lead_data);
       
       // Send admin notification
       wp_mail(get_option('admin_email'), 
               'Salesforce Lead Failed', 
               $response['errors']);
   }
   ```

---

## 📂 STRUCTURE DU PLUGIN

```
is-analytics-plugin/
│
├── is-analytics-plugin.php          # Main plugin file
│   └── Plugin Header
│   └── Load includes
│   └── Register hooks
│
├── includes/
│   ├── class-matomo-api.php         # Matomo API wrapper
│   │   └── get_visitor_data()
│   │   └── get_visit_history()
│   │   └── format_journey()
│   │
│   ├── class-salesforce-api.php     # Salesforce API wrapper
│   │   └── authenticate()
│   │   └── create_lead()
│   │   └── handle_errors()
│   │
│   ├── class-form-enricher.php      # Main enrichment logic
│   │   └── enrich_entry()
│   │   └── detect_form_type()
│   │   └── map_fields()
│   │
│   └── class-logger.php             # Logging utility
│       └── log_info()
│       └── log_error()
│
├── admin/
│   ├── settings-page.php            # Admin UI
│   ├── css/admin-styles.css
│   └── js/admin-scripts.js
│
├── assets/
│   └── icon-128x128.png
│
├── languages/
│   └── is-analytics-fr_FR.po
│
├── tests/
│   ├── test-matomo-api.php
│   ├── test-salesforce-api.php
│   └── test-enrichment.php
│
├── README.md
├── CHANGELOG.md
└── composer.json                    # Dependencies
```

---

## 🔐 SÉCURITÉ

### 1. Credentials Storage
```php
// Utiliser WordPress Options API avec encryption
define('ISONIC_ENCRYPTION_KEY', wp_salt('auth'));

function isonic_store_credential($key, $value) {
    $encrypted = openssl_encrypt($value, 'AES-256-CBC', ISONIC_ENCRYPTION_KEY);
    update_option('isonic_' . $key, $encrypted);
}

function isonic_get_credential($key) {
    $encrypted = get_option('isonic_' . $key);
    return openssl_decrypt($encrypted, 'AES-256-CBC', ISONIC_ENCRYPTION_KEY);
}
```

### 2. API Rate Limiting
```php
function isonic_check_rate_limit() {
    $count = get_transient('isonic_api_calls');
    if ($count >= 100) { // Max 100 calls/hour
        return false;
    }
    set_transient('isonic_api_calls', $count + 1, HOUR_IN_SECONDS);
    return true;
}
```

### 3. Data Sanitization
```php
function isonic_sanitize_analytics($data) {
    return [
        'time_spent' => sanitize_text_field($data['time_spent']),
        'entry_page' => esc_url_raw($data['entry_page']),
        'journey' => wp_kses_post($data['journey']),
        // ... etc
    ];
}
```

---

## 🧪 TESTS

### Test Cases

1. **Matomo API Connection**
   - ✅ Auth token valide
   - ❌ Auth token invalide
   - ❌ Visitor ID introuvable

2. **Form Enrichment**
   - ✅ Visitor avec historique complet
   - ✅ Visitor première visite (pas d'historique)
   - ❌ Cookie Matomo absent

3. **Salesforce Integration**
   - ✅ Lead créé avec succès
   - ❌ Duplicate lead (email existant)
   - ❌ Required field missing
   - ❌ API timeout

4. **Form Type Detection**
   - ✅ Mapping manuel
   - ✅ Détection par URL
   - ✅ Détection par titre
   - ✅ Fallback défaut

---

## 🚀 DÉPLOIEMENT

### Prérequis

1. **WordPress :** 6.0+
2. **PHP :** 8.0+
3. **Gravity Forms :** 2.5+
4. **Matomo :** Installé et configuré
5. **Salesforce :** Connected App créée

### Installation

```bash
# 1. Créer Connected App Salesforce
# 2. Installer Matomo
# 3. Uploader plugin WordPress
wp plugin install is-analytics-plugin.zip --activate

# 4. Configurer
# WP Admin → Settings → iSonic Analytics

# 5. Tester
# Soumettre formulaire de test
# Vérifier logs: wp-content/debug.log
# Vérifier Lead créé dans Salesforce
```

---

## 📊 MONITORING

### Logs à Tracker

```php
// Success log
isonic_log_info("Lead created: {$email} | Form: {$form_type} | Source: {$source}");

// Error log
isonic_log_error("Matomo API failed for visitor {$visitor_id}: {$error}");

// Performance log
isonic_log_info("Enrichment took {$duration}ms for form {$form_id}");
```

### Métriques à Mesurer

- Taux de succès enrichissement Matomo
- Taux de succès création Lead Salesforce
- Temps moyen d'enrichissement
- Nombre de fallback (données manquantes)

---

## 🎯 ROADMAP

### v1.0 (MVP)
- ✅ Matomo API integration
- ✅ Gravity Forms hook
- ✅ Salesforce Lead creation
- ✅ Admin settings page

### v1.1 (Amélioration)
- 🔄 Retry logic pour API failures
- 🔄 Queue système pour traitement asynchrone
- 🔄 Dashboard analytics dans WP Admin

### v2.0 (Avancé)
- 🔄 Support multi-sites WordPress
- 🔄 A/B testing form variants
- 🔄 Predictive scoring (ML)

---

## 📝 DOCUMENTATION UTILISATEUR

**Fichier séparé :** `USER_GUIDE.md`

Contenu :
1. Installation step-by-step
2. Configuration Matomo
3. Configuration Salesforce Connected App
4. Form Type mapping
5. Troubleshooting
6. FAQ

---

## 🔗 RESSOURCES

- [Matomo HTTP API](https://developer.matomo.org/api-reference/reporting-api)
- [Gravity Forms Hooks](https://docs.gravityforms.com/category/developers/hooks/)
- [Salesforce REST API](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)

