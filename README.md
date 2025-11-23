# iSonic Analytics Enrichment for Salesforce

Plugin WordPress qui enrichit automatiquement les soumissions Gravity Forms avec les données Matomo avant envoi à Salesforce.

## 🎯 Objectif

Capturer le contexte web complet de chaque Lead pour :
- Améliorer le scoring (leads "chauds" = parcours long, pages stratégiques)
- Personnaliser l'approche commerciale (connaître l'intérêt réel)
- Mesurer le ROI des campagnes web

## 📊 Données Capturées (13 champs)

### Parcours Web
- **Temps passé** : Durée totale de la session
- **Page d'entrée** : Première page visitée
- **Parcours complet** : Liste des pages consultées

### Source & Campagne
- **Source web** : Google, Facebook, Direct, etc.
- **Medium** : cpc, email, social, etc.
- **Mot-clé** : Mot-clé de recherche

### Formulaire
- **Page formulaire** : URL exacte du formulaire
- **Type formulaire** : Demo, Support, Formation, etc.
- **Nom formulaire** : Nom Gravity Forms

### Engagement
- **Nombre de visites** : Visites avant soumission
- **Première visite** : Date de découverte du site
- **Pages consultées** : Nombre total de pages vues

## 🚀 Installation

1. Uploader le plugin dans `/wp-content/plugins/`
2. Activer via WordPress Admin
3. Configurer dans **Réglages → iSonic Analytics**

## ⚙️ Configuration

### Matomo
- URL : `https://matomo.isonic.fr`
- Site ID : `1`
- Auth Token : [Générer dans Matomo → Personal → Security]

### Salesforce
- Connected App Consumer Key
- Consumer Secret
- Username + Security Token

## 📋 Campaigns Salesforce

| Formulaire | Campaign Salesforce | ID |
|-----------|---------------------|-----|
| Inscription Isonic | Contenu pédagogique | 701Jv00000oEgv7IAC |
| Autres formulaires | Site web isonic.fr | 701Jv00000oEi1EIAS |

## 🔧 Développement

### Structure

```
isonic-analytics-plugin/
├── isonic-analytics.php (Main plugin)
├── includes/
│   ├── class-logger.php
│   ├── class-matomo-api.php
│   ├── class-salesforce-api.php
│   ├── class-campaign-mapper.php
│   └── class-form-enricher.php
├── admin/
│   └── settings-page.php
└── docs/
    └── (Documentation technique)
```

### Hooks

Le plugin utilise `gform_after_submission` pour intercepter les soumissions Gravity Forms.

### Logs

Activez `WP_DEBUG` et `WP_DEBUG_LOG` dans `wp-config.php` :

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Logs dans : `wp-content/debug.log`

## 📝 TODO

- [ ] Implémenter OAuth2 complet pour Salesforce
- [ ] Améliorer parsing des données Matomo
- [ ] Ajouter retry logic pour API failures
- [ ] Dashboard WordPress avec métriques
- [ ] Tests unitaires

## 🔗 Documentation

Voir dossier `docs/` pour documentation complète :
- `SPEC_TECHNIQUE.md` : Spécifications techniques
- `SPEC_CHAMPS_ANALYTICS.md` : Détail des 13 champs
- `SPEC_MAPPING_CAMPAGNE.md` : Mapping formulaires → campaigns

## ⚠️ Prérequis

- WordPress 6.0+
- PHP 8.0+
- Gravity Forms 2.5+
- Matomo installé et configuré
- Salesforce avec champs custom déployés

## 📄 Licence

GPL-2.0+

## 👤 Auteur

iSonic - https://isonic.fr
