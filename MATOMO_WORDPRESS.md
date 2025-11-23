# 🎉 Matomo WordPress Plugin - Configuration Simplifiée

Si vous utilisez le **plugin Matomo WordPress** (Matomo Analytics), la configuration est **ultra-simple** !

---

## ✅ Détection Automatique

Le plugin iSonic Analytics **détecte automatiquement** si Matomo WordPress est installé et l'utilise **directement** sans passer par l'API HTTP.

**Avantages** :
- ✅ **Pas de token nécessaire** - Pas besoin de créer un Auth Token
- ✅ **Plus rapide** - Accès direct aux données Matomo via PHP
- ✅ **Plus simple** - Juste un champ à remplir (Site ID)
- ✅ **Plus sûr** - Utilise les permissions WordPress natives

---

## ⚙️ Configuration Minimale

**WordPress Admin → Réglages → iSonic Analytics**

### Section Matomo :

```
☑ Activer Matomo

Site ID: 1

URL Matomo (optionnel): [laisser vide]
Auth Token (optionnel): [laisser vide]
```

**C'est tout !** 🎉

---

## 🧪 Test de Connexion

Cliquez sur **"🔍 Tester Matomo"**

✅ **Résultat attendu** :
```
Matomo WordPress Plugin detected and ready! 
Site ID: 1 (Site isonic.fr). 
No Auth Token needed.
```

---

## 📊 Vérifier le Site ID

Si vous n'êtes pas sûr du Site ID :

1. **Matomo Analytics** (menu WordPress) → **Settings** ⚙️
2. Menu gauche : **"Websites"** → **"Manage"**
3. Vous verrez votre site avec son **ID** (probablement 1)

---

## 🔄 Comment ça marche ?

### Ancienne méthode (API HTTP) :
```
WordPress → HTTP Request → Matomo API → Réponse JSON → WordPress
```
Requiert : URL + Auth Token

### Nouvelle méthode (Natif) :
```
WordPress → Appel PHP direct → Matomo WordPress Plugin → Données
```
Requiert : Juste Site ID !

---

## ❓ FAQ

### Q: Est-ce que je dois quand même remplir URL et Auth Token ?

**Non !** Si Matomo WordPress Plugin est installé, ces champs sont **optionnels** et ignorés.

### Q: Que se passe-t-il si je remplis quand même URL/Token ?

Le plugin utilisera **quand même** l'API native Matomo WordPress (plus rapide). Les champs URL/Token ne sont utilisés que si le plugin Matomo WP n'est **pas** détecté.

### Q: Je vois "Matomo WordPress Plugin detected" mais ça ne marche pas

Vérifiez que :
1. Le plugin **Matomo Analytics** est bien **activé**
2. Le **Site ID** est correct (généralement 1)
3. Matomo **tracking** fonctionne sur votre site (testez en visitant une page)

### Q: Puis-je utiliser un Matomo externe en même temps ?

Oui, mais le plugin privilégiera toujours Matomo WordPress Plugin s'il est détecté.

---

## 🎯 Résumé

**Avec Matomo WordPress Plugin** :
- Configuration : **1 champ** (Site ID)
- Authentification : **Aucune**
- Performance : **Optimale** (PHP direct)

**Avec Matomo externe** :
- Configuration : **3 champs** (URL, Site ID, Token)
- Authentification : **Auth Token requis**
- Performance : **Bonne** (HTTP API)

---

## 🚀 Prochaines Étapes

Une fois Matomo configuré, passez à :
- **Salesforce PRIMARY Org** (obligatoire)
- **Salesforce SECONDARY Org** (optionnel - migration)

Voir : `DUAL_ORG_SETUP.md` pour le guide complet.

