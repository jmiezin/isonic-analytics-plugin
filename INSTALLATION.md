# 🚀 Guide d'Installation Rapide

## Prérequis

- ✅ WordPress 6.0+
- ✅ PHP 8.0+
- ✅ Gravity Forms 2.5+
- ✅ Matomo installé et configuré
- ✅ Salesforce Connected App créée

---

## Étape 1 : Installation du Plugin

### Option A : Upload Manuel

1. **Télécharger** le plugin
2. **WordPress Admin** → Plugins → Add New → Upload Plugin
3. **Activer** le plugin

### Option B : FTP

```bash
cd wp-content/plugins/
git clone https://github.com/jmiezin/isonic-analytics-plugin.git
```

Puis activer dans WordPress Admin → Plugins

---

## Étape 2 : Créer Connected App Salesforce

1. **Salesforce Setup** → App Manager → New Connected App

### Basic Information
```
External Client App Name: iSonic WordPress Integration
API Name: iSonic_WordPress_Integration
Contact Email: votre@email.com
Distribution State: Local
```

### API (Enable OAuth Settings)
```
☑ Enable OAuth Settings

Callback URL: https://isonic.fr/oauth/callback

Selected OAuth Scopes:
- Full access (full)
- Perform requests at any time (refresh_token, offline_access)
- Manage user data via APIs (api)

Security:
☑ Require secret for Web Server Flow
☐ Require Proof Key for Code Exchange (PKCE)
```

2. **Save** → Copier **Consumer Key** et **Consumer Secret**

3. **Reset Security Token**
   - Profil → Settings → Reset My Security Token
   - Un email sera envoyé avec le token

---

## Étape 3 : Récupérer Auth Token Matomo

1. **Matomo** → Personal → Security
2. **Create New Token**
3. Description : `WordPress Integration`
4. **Copier le token**

---

## Étape 4 : Configurer le Plugin

### WordPress Admin → Settings → iSonic Analytics

#### Configuration Matomo

```
☑ Activer Matomo
URL Matomo: https://matomo.isonic.fr
Site ID: 1
Auth Token: [token copié à l'étape 3]
```

**Cliquer sur "🔍 Tester Matomo"** → Devrait afficher "Connection successful!"

#### Configuration Salesforce

```
☑ Activer Salesforce
Instance URL: https://isonic-ai.my.salesforce.com
Consumer Key: [Consumer Key de l'étape 2]
Consumer Secret: [Consumer Secret de l'étape 2]
Username: j.miezin@isonic.fr
Password: [votre mot de passe Salesforce]
Security Token: [token reçu par email à l'étape 2]
```

**Cliquer sur "🔍 Tester Salesforce"** → Devrait afficher "Connection successful!"

### Enregistrer

Cliquer sur **"Enregistrer la configuration"**

---

## Étape 5 : Validation

### Test Complet

Accédez à :
```
https://isonic.fr/wp-content/plugins/isonic-analytics-plugin/test-plugin.php
```

Vous devriez voir :
- ✅ Toutes les classes OK
- ✅ Gravity Forms détecté
- ✅ Matomo configuré et connecté
- ✅ Salesforce configuré et connecté
- ✅ **Plugin prêt !**

---

## Étape 6 : Test en Production

### Tester avec un formulaire

1. **Ouvrir** un formulaire Gravity Forms sur votre site
2. **Remplir** et soumettre
3. **Vérifier** dans Salesforce :
   - Un nouveau Lead doit être créé
   - Les champs analytics doivent être remplis :
     - `Web_Time_Spent__c`
     - `Web_Source__c`
     - `Web_Journey__c`
     - etc.
   - Le Lead doit être ajouté à la bonne Campaign :
     - Form "Inscription Isonic" → Campaign "Contenu pédagogique"
     - Autres forms → Campaign "Site web isonic.fr"

### Vérifier les Logs

```bash
tail -f wp-content/debug.log
```

Vous devriez voir :
```
[iSonic Analytics INFO] Plugin initialized
[iSonic Analytics INFO] Processing form submission: "Contact" (ID: 2)
[iSonic Analytics INFO] Salesforce authentication successful
[iSonic Analytics INFO] Lead created: 00Q...
[iSonic Analytics INFO] CampaignMember created for Lead 00Q...
[iSonic Analytics INFO] Form "Contact" (ID: 2) → Lead 00Q... → Campaign Site web isonic.fr
```

---

## 🎯 Résumé des Champs Enrichis

Chaque Lead créé contiendra **13 champs analytics** :

### Parcours Web
- `Web_Time_Spent__c` : Durée totale de la session
- `Web_Entry_Page__c` : Première page visitée
- `Web_Journey__c` : Liste des pages consultées

### Source & Campagne
- `Web_Source__c` : Google, Facebook, Direct, etc.
- `Web_Medium__c` : cpc, email, social, etc.
- `Web_Keyword__c` : Mot-clé de recherche

### Formulaire
- `Form_Page__c` : URL exacte du formulaire
- `Form_Type__c` : Demo, Support, Formation, etc.
- `Form_Name__c` : Nom Gravity Forms

### Engagement
- `Web_Visit_Count__c` : Nombre de visites avant soumission
- `Web_First_Visit__c` : Date de découverte du site
- `Web_Pages_Viewed__c` : Nombre total de pages vues

---

## 🔧 Troubleshooting

### Erreur "Matomo API failed"
- Vérifier que l'URL Matomo est correcte (sans `/index.php`)
- Vérifier que le Auth Token est valide
- Vérifier que le Site ID est correct

### Erreur "Salesforce OAuth failed"
- Vérifier Consumer Key et Consumer Secret
- Vérifier Username et Password
- **Important** : Le Security Token doit être ajouté au Password lors de l'authentification (fait automatiquement par le plugin)
- Vérifier que la Connected App est bien activée

### Lead créé mais pas de CampaignMember
- Vérifier que les Campaign IDs sont corrects dans `isonic-analytics.php`
- Vérifier que les Campaigns existent dans Salesforce

### Pas de données Matomo
- Vérifier que le cookie Matomo `_pk_id` est présent
- Tester sur une session avec historique (pas la première visite)
- Vérifier que le Visitor ID est bien récupéré

---

## 📞 Support

Pour toute question ou problème :
- **Email** : j.miezin@isonic.fr
- **Documentation** : Voir dossier `/docs`
- **GitHub Issues** : https://github.com/jmiezin/isonic-analytics-plugin/issues

---

## ✅ Checklist Installation

- [ ] Plugin installé et activé
- [ ] Connected App Salesforce créée
- [ ] Auth Token Matomo récupéré
- [ ] Credentials configurés dans Settings
- [ ] Test Matomo ✅ Connection successful
- [ ] Test Salesforce ✅ Connection successful
- [ ] Test complet (test-plugin.php) ✅ Plugin prêt
- [ ] Test formulaire → Lead créé dans Salesforce
- [ ] Champs analytics remplis
- [ ] CampaignMember créé

**Félicitations ! Votre plugin est opérationnel ! 🎉**

