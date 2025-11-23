# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

## [1.1.0] - 2025-11-23

### ✨ NOUVEAU : Multi-Org Salesforce

Le plugin supporte maintenant l'**envoi simultané à 2 orgs Salesforce** !

**Cas d'usage principal** : Migration entre anciennes et nouvelles orgs
- **Primary Org** : Nouvelle org de production (toujours active)
- **Secondary Org** : Ancienne org (désactivable une fois la migration terminée)

Chaque soumission de formulaire Gravity Forms est maintenant envoyée aux **2 orgs en parallèle** avec les Campaign IDs correspondants pour chaque org.

#### Ajouté

**Configuration Dual-Org**
- Settings pour Primary Org (nouvelle org production)
  - Instance URL, Consumer Key, Consumer Secret, Username, Password, Security Token
  - Toggle d'activation/désactivation
- Settings pour Secondary Org (ancienne org migration)
  - Instance URL, Consumer Key, Consumer Secret, Username, Password, Security Token
  - Toggle d'activation/désactivation
- Boutons "Test Connection" séparés pour chaque org
- Affichage des Campaign IDs pour les 2 orgs

**Constantes Campaign IDs**
- `ISONIC_PRIMARY_CAMPAIGN_SITE_WEB` : 701Jv00000oEi1EIAS
- `ISONIC_PRIMARY_CAMPAIGN_CONTENU_PEDAGOGIQUE` : 701Jv00000oEgv7IAC
- `ISONIC_SECONDARY_CAMPAIGN_SITE_WEB` : 7013X000001msrWQAQ
- `ISONIC_SECONDARY_CAMPAIGN_CONTENU_PEDAGOGIQUE` : 701IV00000xTZBhYAO

**Architecture**
- `Isonic_Salesforce_API` accepte maintenant un paramètre `$org_type` ('primary' ou 'secondary')
- Cache OAuth séparé par org (clés transient différentes)
- Logs préfixés `[PRIMARY]` ou `[SECONDARY]` pour traçabilité
- `Isonic_Form_Enricher::send_to_org()` : nouvelle méthode privée pour envoyer à une org spécifique
- `Isonic_Campaign_Mapper::get_campaign_id()` accepte `$org_type` pour retourner les bons Campaign IDs

#### Modifié

- `class-salesforce-api.php` : Support multi-org avec org_type
- `class-form-enricher.php` : Envoie aux 2 orgs si activées
- `class-campaign-mapper.php` : Mapping des campaigns par org
- `admin/settings-page.php` : Interface complète pour 2 orgs
- `isonic-analytics.php` : Constantes pour les 2 orgs
- `README.md` : Documentation dual-org

#### Workflow de Migration

1. **Phase 1 - Migration en cours**
   - ✅ Activer Primary Org (nouvelle)
   - ✅ Activer Secondary Org (ancienne)
   - Chaque formulaire crée 2 Leads (1 dans chaque org)

2. **Phase 2 - Migration terminée**
   - ✅ Primary Org reste activée
   - ❌ Désactiver Secondary Org
   - Les formulaires créent uniquement des Leads dans Primary Org

#### Backward Compatibility

- Les constantes legacy (`ISONIC_CAMPAIGN_SITE_WEB`, `ISONIC_CAMPAIGN_CONTENU_PEDAGOGIQUE`) fonctionnent toujours
- Migration automatique depuis version 1.0.0
- Pas de breaking changes

---

## [1.0.0] - 2025-11-23

### ✨ Ajouté

#### OAuth2 Salesforce
- Implémentation complète du Username-Password flow
- Authentification automatique avec cache (1 heure)
- Méthode `test_connection()` pour valider les credentials
- Gestion des erreurs OAuth avec logs détaillés

#### Page Settings Admin
- Interface complète pour configuration Matomo
  - URL Matomo
  - Site ID
  - Auth Token
- Interface complète pour configuration Salesforce
  - Instance URL
  - Consumer Key
  - Consumer Secret
  - Username
  - Password
  - Security Token
- Boutons "🔍 Test Connection" pour Matomo et Salesforce
- Status en temps réel (activé/désactivé)
- Affichage des Campaign IDs configurées
- Descriptions pour chaque champ

#### Tests & Validation
- Script de test complet (`test-plugin.php`)
  - Validation de toutes les classes PHP
  - Vérification des dépendances (Gravity Forms)
  - Test configuration Matomo avec appel API
  - Test configuration Salesforce avec appel API
  - Interface HTML avec status badges colorés
  - Résumé de l'état du plugin
- Méthode `test_connection()` pour Matomo API
  - Validation Auth Token
  - Test avec méthode `SitesManager.getSiteFromId`

#### Documentation
- Guide d'installation complet (`INSTALLATION.md`)
  - Création Connected App Salesforce (step-by-step)
  - Configuration Matomo Auth Token
  - Configuration WordPress
  - Procédure de validation
  - Test en production
  - Troubleshooting
- README mis à jour avec badges
- CHANGELOG.md

### 🔧 Modifié
- `class-salesforce-api.php` : OAuth2 complet remplace le TODO
- `class-matomo-api.php` : Ajout méthode `test_connection()`
- `admin/settings-page.php` : Refonte complète de l'interface
- `README.md` : Section Installation et TODO mis à jour

### 📦 Fichiers Créés
- `INSTALLATION.md` : Guide d'installation détaillé
- `test-plugin.php` : Script de validation complet
- `CHANGELOG.md` : Ce fichier
- `LICENSE` : GPL-2.0
- `.gitignore` : Protection credentials

---

## [0.1.0] - 2025-11-23 (Initial Commit)

### ✨ Initial Release

#### Structure de Base
- Plugin WordPress avec autoloader
- 5 classes PHP :
  - `Isonic_Logger` : Gestion des logs
  - `Isonic_Matomo_API` : Interface Matomo
  - `Isonic_Salesforce_API` : Interface Salesforce
  - `Isonic_Campaign_Mapper` : Mapping Formulaires → Campaigns
  - `Isonic_Form_Enricher` : Enrichissement Gravity Forms

#### Fonctionnalités Core
- Hook Gravity Forms (`gform_after_submission`)
- Récupération données Matomo (13 champs analytics)
- Création Lead Salesforce avec enrichissement
- Création CampaignMember automatique
- Mapping intelligent :
  - Form "Inscription Isonic" → Campaign "Contenu pédagogique"
  - Autres forms → Campaign "Site web isonic.fr"

#### Documentation
- README.md complet
- Documentation technique (`docs/SPEC_TECHNIQUE.md`)
- Spécification des champs (`docs/SPEC_CHAMPS_ANALYTICS.md`)
- Mapping des campaigns (`docs/SPEC_MAPPING_CAMPAGNE.md`)

---

## Roadmap

### v1.1 (Planifié)
- [ ] Améliorer parsing Journey Matomo (format lisible)
- [ ] Retry logic pour API failures
- [ ] Queue système (fallback si Salesforce down)
- [ ] Encryption credentials (WordPress Salts)

### v1.2 (Planifié)
- [ ] Dashboard analytics dans WP Admin
- [ ] Logs UI (voir submissions en temps réel)
- [ ] Export CSV des submissions
- [ ] Tests unitaires PHPUnit

### v2.0 (Future)
- [ ] Support multi-sites WordPress
- [ ] A/B testing form variants
- [ ] Predictive lead scoring
- [ ] Intégration Google Analytics (en plus de Matomo)

