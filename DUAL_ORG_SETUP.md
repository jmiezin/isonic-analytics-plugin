# 🔄 Configuration Dual-Org (Primary + Secondary)

Guide pour configurer le plugin afin d'envoyer les Leads à **2 orgs Salesforce simultanément**.

---

## 🎯 Cas d'Usage

**Migration Salesforce** : Vous avez une ancienne org et une nouvelle org, et vous voulez envoyer les Leads aux **2 orgs en parallèle** pendant la période de migration.

**Résultat** : Chaque soumission de formulaire Gravity Forms créera :
- ✅ 1 Lead dans **Primary Org** (nouvelle org - production)
- ✅ 1 Lead dans **Secondary Org** (ancienne org - migration)

Une fois la migration terminée, vous désactivez simplement la Secondary Org.

---

## 📋 Prérequis

### Pour chaque org, vous aurez besoin de :

1. **Connected App** créée dans chaque org
   - Consumer Key
   - Consumer Secret
2. **Utilisateur Salesforce** avec accès
   - Username
   - Password
   - Security Token
3. **Campaign IDs** de chaque org
   - Campaign "Site web isonic.fr"
   - Campaign "Contenu pédagogique"

---

## 🔧 Configuration Step-by-Step

### Étape 1 : Configuration Primary Org (Nouvelle Org - Production)

**WordPress Admin → Réglages → iSonic Analytics**

Remplissez la section **"☁️ Salesforce PRIMARY (Nouvelle Org - Production)"** :

```
☑ Activer Primary Org

Instance URL: https://isonic-ai.my.salesforce.com
Consumer Key: 3MVG9suI4ZYS8sz4kl7tz9nOTHO2CucAx... [copié depuis Connected App]
Consumer Secret: 5918BE231AB02BC3D3363686295DD33D... [copié depuis Connected App]
Username: j.miezin@isonic.fr
Password: [votre mot de passe]
Security Token: [reçu par email]
```

**Test** : Cliquez sur **"🔍 Tester Primary Org"**

✅ Vous devriez voir : `[PRIMARY] Connection successful! Salesforce API is reachable. (https://isonic-ai.my.salesforce.com)`

---

### Étape 2 : Configuration Secondary Org (Ancienne Org - Migration)

Remplissez la section **"🔄 Salesforce SECONDARY (Ancienne Org - Migration)"** :

```
☑ Activer Secondary Org

Instance URL: https://isonic.lightning.force.com
Consumer Key: 3MVG91BJr_0ZDQ4sHW.nHUNj8TeO7Hi9w7... [copié depuis Connected App]
Consumer Secret: 096D5171D2F6D40F56EC2565CBEAD34A... [copié depuis Connected App]
Username: j.miezin@isonic.fr
Password: [votre mot de passe]
Security Token: [reçu par email]
```

**Test** : Cliquez sur **"🔍 Tester Secondary Org"**

✅ Vous devriez voir : `[SECONDARY] Connection successful! Salesforce API is reachable. (https://isonic.lightning.force.com)`

---

### Étape 3 : Enregistrer

Cliquez sur **"Enregistrer la configuration"** en bas de la page.

---

## 📊 Vérification des Campaigns

Une fois sauvegardé, vous devriez voir dans la section **"📊 Campaigns Salesforce"** :

### 🟢 PRIMARY ORG (Nouvelle - Production)
| Formulaire | Campaign | ID |
|-----------|----------|-----|
| Inscription Isonic | Contenu pédagogique | `701Jv00000oEgv7IAC` |
| Autres formulaires | Site web isonic.fr | `701Jv00000oEi1EIAS` |

### 🔄 SECONDARY ORG (Ancienne - Migration)
| Formulaire | Campaign | ID |
|-----------|----------|-----|
| Inscription Isonic | Contenu pédagogique | `701IV00000xTZBhYAO` |
| Autres formulaires | Site web isonic.fr | `7013X000001msrWQAQ` |

---

## ✅ Test en Production

### 1. Soumettre un formulaire

Remplissez et soumettez un formulaire Gravity Forms sur votre site.

### 2. Vérifier les Logs

```bash
tail -f wp-content/debug.log
```

Vous devriez voir :

```
[iSonic Analytics INFO] Processing form submission: "Contact" (ID: 2)
[iSonic Analytics INFO] [PRIMARY] Salesforce authentication successful
[iSonic Analytics INFO] [PRIMARY] Lead created: 00Q7c00000XYZ123
[iSonic Analytics INFO] [PRIMARY] CampaignMember created for Lead 00Q7c00000XYZ123
[iSonic Analytics INFO] Form "Contact" (ID: 2) → Lead 00Q7c00000XYZ123 → Campaign Site web isonic.fr (Primary)
[iSonic Analytics INFO] [SECONDARY] Salesforce authentication successful
[iSonic Analytics INFO] [SECONDARY] Lead created: 00Q1r00000ABC456
[iSonic Analytics INFO] [SECONDARY] CampaignMember created for Lead 00Q1r00000ABC456
[iSonic Analytics INFO] Form "Contact" (ID: 2) → Lead 00Q1r00000ABC456 → Campaign Site web isonic.fr (Secondary)
```

### 3. Vérifier dans Salesforce

**Primary Org (isonic-ai)** :
- Allez dans Leads
- Cherchez le Lead avec l'email du formulaire
- ✅ Le Lead doit exister avec tous les champs analytics remplis
- ✅ Le Lead doit être dans la bonne Campaign

**Secondary Org (isonic)** :
- Allez dans Leads
- Cherchez le Lead avec le même email
- ✅ Le Lead doit exister avec les mêmes données
- ✅ Le Lead doit être dans la Campaign correspondante

---

## 🔄 Workflow de Migration

### Phase 1 : Migration en cours (2 orgs actives)

```
☑ Activer Primary Org
☑ Activer Secondary Org
```

**Résultat** : Chaque formulaire crée **2 Leads** (1 dans chaque org)

**Logs** :
```
[PRIMARY] Lead created: 00Q...
[SECONDARY] Lead created: 00Q...
```

---

### Phase 2 : Migration terminée (1 seule org active)

Une fois que vous êtes sûr que la nouvelle org fonctionne bien et que vous avez migré toutes les données :

```
☑ Activer Primary Org
☐ Activer Secondary Org  ← Décocher
```

**Résultat** : Les formulaires créent uniquement des Leads dans **Primary Org**

**Logs** :
```
[PRIMARY] Lead created: 00Q...
[SECONDARY] Org disabled - skipping (normal si migration terminée)
```

---

## 🛡️ Gestion d'Erreurs

### Si Primary Org fail mais Secondary Org réussit

Le plugin continue d'exécuter les 2 orgs **indépendamment**.

**Exemple** :
```
[PRIMARY] Salesforce OAuth failed: Invalid credentials
[SECONDARY] Lead created: 00Q...
```

Le Lead sera créé dans Secondary Org même si Primary échoue (et vice versa).

### Si les 2 orgs fail

Les erreurs seront loggées pour chaque org :

```
[PRIMARY] Salesforce API error: Connection timeout
[SECONDARY] Salesforce API error: Connection timeout
```

Aucun Lead ne sera créé, et vous devrez corriger la configuration ou résoudre les problèmes réseau.

---

## ❓ FAQ

### Q1 : Les 2 orgs doivent-elles avoir les mêmes champs custom ?

**Oui.** Le plugin envoie exactement les mêmes données aux 2 orgs. Les champs suivants doivent exister dans les 2 orgs :

- `Web_Time_Spent__c`
- `Web_Entry_Page__c`
- `Web_Journey__c`
- `Web_Source__c`
- `Web_Medium__c`
- `Web_Keyword__c`
- `Form_Page__c`
- `Form_Type__c`
- `Form_Name__c`
- `Web_Visit_Count__c`
- `Web_First_Visit__c`
- `Web_Pages_Viewed__c`

### Q2 : Puis-je utiliser des usernames différents pour chaque org ?

**Oui.** Chaque org a ses propres credentials (Username, Password, Security Token).

### Q3 : Les Campaign IDs sont-ils toujours différents entre les 2 orgs ?

**Oui.** Les IDs Salesforce sont uniques par org. C'est pourquoi le plugin gère des Campaign IDs différents pour chaque org.

### Q4 : Que se passe-t-il si je désactive Secondary Org ?

Les formulaires continueront de fonctionner, mais ne créeront des Leads que dans Primary Org. Aucun Lead ne sera envoyé à Secondary Org.

### Q5 : Puis-je réactiver Secondary Org plus tard ?

**Oui.** Il suffit de recocher "Activer Secondary Org" dans les settings.

### Q6 : Les données Matomo sont-elles récupérées 2 fois ?

**Non.** Les données Matomo ne sont récupérées **qu'une seule fois** par soumission, puis envoyées aux 2 orgs. C'est optimisé.

---

## 🚀 Avantages du Dual-Org

✅ **Transition en douceur** : Aucune perte de données pendant la migration  
✅ **Backup automatique** : Les 2 orgs reçoivent les mêmes Leads  
✅ **Facile à désactiver** : Un simple toggle pour arrêter Secondary Org  
✅ **Logs séparés** : Traçabilité complète pour chaque org  
✅ **Gestion d'erreurs indépendante** : Si une org fail, l'autre continue  

---

## 📞 Support

Pour toute question :
- **Email** : j.miezin@isonic.fr
- **GitHub Issues** : https://github.com/jmiezin/isonic-analytics-plugin/issues
- **Documentation** : Voir `INSTALLATION.md` et `README.md`

