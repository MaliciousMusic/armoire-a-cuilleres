# L’Armoire à Cuillères — le site

Bar à chocolat & salon de thé, 11 rue des Chaussetiers, Clermont-Ferrand.
Site vitrine en **5 langues** (FR, EN, DE, ES, 中文) avec la carte modifiable, les commandes de gâteaux et devis pros
suivis en **tickets** en direct, une **carte de fidélité QR** et un **espace équipe** (`/admin`).

PHP 8.2+ sans framework ni dépendance serveur, base SQLite (ou MySQL OVH). Fait pour l’hébergement mutualisé OVH.

## Ce qu’il y a dedans

| Page | Contenu |
|---|---|
| `/fr/` (et `/en/`, `/de/`, `/es/`, `/zh/`) | Hero plein écran (photo, ou vidéo dès qu’elle est déposée) · bandeaux en vague · le lieu · **la carte** (depuis la base) avec le nuancier des grands crus et le **panneau des allergènes** · brunch · **plan Google Maps** avec la fiche de la boutique et les temps de marche (Victoire, Jaude, Gaillard) · contact, horaires, réseaux · FAQ |
| `/fr/commander-un-gateau/` | Gâteaux sur commande, particuliers et pros (formulaire → ticket suivi en direct) |
| `/fr/prestations-pro/` | La page dédiée aux professionnels, avec demande de devis |
| `/fr/equipe/` | Les portraits de l’équipe |
| `/fr/fidelite/` | Création de la carte de fidélité ; `?c=…` = la carte du client (QR + tampons, se met à jour seule) |
| `/fr/suivi/?t=…` | Suivi d’une commande par le client : statut, échanges avec l’équipe, réponse |
| `/admin/` | Espace équipe : tickets (son + notification à chaque nouveau), scanner de fidélité, la carte, l’équipe, réglages, comptes, journal |
| `/llms.txt`, `/llms-full.txt`, `/sitemap.xml` | Fiche et carte complète pour les assistants IA ; plan du site avec les 5 langues |

**Rôles** : le *gérant* (admin) a tous les droits ; les *collaborateurs* (staff) voient les tickets, scannent les cartes de
fidélité et peuvent masquer un article épuisé.

## Arborescence

```
.ovhconfig        PHP 8.3 sur OVH
app/              le code (PRIVÉ : à côté de www, jamais servi)
  config.sample.php → à copier en config.php
  lib/            base, sécurité, langues, contenu, tickets, fidélité, rendu
  lang/           fr.php (source), en.php, de.php, es.php, zh.php
  views/          gabarits des pages
  data/           base SQLite, cache des pages, journal d’erreurs (créé tout seul)
www/              le dossier public (= « www » chez OVH)
  index.php       point d’entrée unique
  assets/         css, js, polices, images, vidéo, librairies
src/              sources CSS/JS (non publiées) → node tools/build.mjs
tools/            build, vidéo provisoire du hero (non publiés)
```

## Lancer en local

PHP 8.2+ avec les extensions `pdo_sqlite`, `gd`, `mbstring` :

```bash
php -S localhost:8080 -t www www/index.php
```

Puis http://localhost:8080 (le site) et http://localhost:8080/admin/ (le premier passage crée le compte du gérant,
avec le code `setup_token` de `app/config.php`). En local, les e-mails sont écrits dans `app/data/mails.log`.

Après une modification de `src/` : `cd tools && npm install` (une fois), puis `node tools/build.mjs`.

## Démo à faire consulter

Une copie statique du site (toutes les pages, dans les cinq langues) est publiée sur GitHub Pages :
https://maliciousmusic.github.io/armoire-a-cuilleres/fr/ — non indexée par les moteurs de recherche.
Les formulaires, la carte de fidélité et l’espace équipe ont besoin du serveur PHP : ils ne marchent pas sur la démo.

Pour la mettre à jour : lancer le serveur local (voir plus haut), puis `sh tools/deploy-demo.sh`.

## Mettre en ligne chez OVH

1. Copier `app/config.sample.php` en `app/config.php` et le remplir : **changer `setup_token`**, vérifier `mail_from`
   (une adresse du domaine) et `mail_to`. Laisser `debug => false`.
2. Par FTP/SFTP, à la racine de l’hébergement : envoyer `.ovhconfig`, le dossier `app/` **à côté** de `www/`, et le
   contenu de `www/` dans `www/` (remplace le WordPress : **faire une sauvegarde complète avant**).
   Ne pas envoyer `src/`, `tools/`, `README.md`, ni `app/data/` (la base de test locale) : il se recrée tout seul.
3. Vérifier que `app/data/` est inscriptible (droits 750/700).
4. Activer le certificat SSL (Let’s Encrypt, gratuit dans l’espace client OVH) : le `.htaccess` force `https://www.`
5. Ouvrir `/admin/`, créer le compte du gérant, puis les comptes des collaborateurs (Comptes).
6. Dans **Réglages** : vérifier horaires, téléphone, réseaux, brunch, fidélité. Dans **La carte** : relire les prix.
7. Google Search Console + Bing Webmaster Tools : ajouter le site et envoyer `/sitemap.xml`.

MySQL plutôt que SQLite : créer une base dans l’espace client OVH et remplir `db` dans `config.php` (les tables se créent
seules). SQLite suffit largement pour ce volume.

## Sécurité

- Code et données **hors du dossier public** ; `.htaccess` de refus en plus ; listing de dossiers coupé.
- **CSP stricte** (aucun script inline, aucune ressource tierce sauf le plan Google Maps en iframe), HSTS, `X-Frame-Options`,
  `nosniff`, `Referrer-Policy`, `Permissions-Policy` (caméra autorisée seulement dans `/admin` pour le scanner).
- Requêtes SQL **préparées** partout ; sorties échappées ; l’admin construit son interface sans `innerHTML` de données.
- Admin : mots de passe **Argon2id**, session `__Host-` `HttpOnly` `SameSite=Strict` liée au navigateur, **CSRF** +
  vérification de l’origine, **limitation des essais** de connexion (IP et compte), journal d’audit, rôles.
- Formulaires publics : pot de miel anti-robots, limites de débit par IP, validation serveur, en-têtes de mail protégés.
- Liens de suivi et cartes de fidélité : jetons aléatoires de 192 bits (impossibles à deviner).
- Photos de l’équipe : vérifiées, recadrées et **ré-encodées** en WebP (aucun fichier envoyé n’est servi tel quel).
- Anciennes adresses WordPress (`wp-admin`, `xmlrpc.php`…) : réponse 410.

## Référencement Google et assistants IA

- Pages rendues côté serveur (tout le texte et **toute la carte** sont dans le HTML), mises en cache.
- **5 langues** avec `hreflang` + `x-default`, adresses traduites, `sitemap.xml` multilingue.
- Données structurées **schema.org** : `CafeOrCoffeeShop` + `Bakery` avec adresse, GPS, horaires (et fermetures),
  téléphone, réseaux (`sameAs`), **menu complet avec les prix** (`Menu` / `MenuItem`), `FAQPage`, `Service` (page pro),
  fil d’Ariane.
- **`/llms.txt` et `/llms-full.txt`** : la fiche de la boutique et la carte en texte clair pour ChatGPT, Claude, Gemini,
  Perplexity ; `robots.txt` les autorise explicitement.
- Performance : vidéo chargée **après** la page (et jamais en mode économie de données), image d’attente en
  `fetchpriority=high`, polices hébergées sur le site et préchargées, plan Google chargé seulement à l’approche,
  CSS/JS minifiés (≈ 10 Ko de JS au chargement), cache navigateur d’un an sur les fichiers versionnés.

**Ce qui compte autant que le site pour monter dans Google et les IA** (à faire par la boutique) : la fiche
**Google Business Profile** à jour (même nom, adresse, téléphone **04 63 22 63 56**, horaires, lien vers le site,
catégories « Salon de thé », « Chocolaterie », « Café », « Brunch »), des photos récentes, répondre aux avis ; la même
fiche sur Apple Plans (Business Connect), Bing Places, TripAdvisor. Aucun site ne peut garantir la première place, mais
ces signaux locaux + ce site complet et structuré sont ce qui la rend possible.

## À valider avec le gérant

0. **Allergènes (obligatoire avant la mise en ligne)** : des valeurs de départ ont été déduites des recettes habituelles
   (lait et soja pour les chocolats, gluten-œufs-lait pour les pâtisseries…). La boutique doit vérifier chaque article
   dans **admin → La carte → Modifier → Allergènes**. Le site rappelle aussi de demander en cas d’allergie.

1. **Téléphone** : 04 63 22 63 56 (fiche Google, 31 oct. 2026) remplace le 07 83 41 21 45 des anciennes affiches.
2. **Horaires** (fiche Google) : mar.–sam. 13h–19h, dim. 11h–19h, lundi fermé. Le brunch : dimanche dès 11h30, 23 €.
3. **TikTok** : `@larmoireacuilleres` est supposé (introuvable en recherche) : corriger dans Réglages.
4. **Fidélité** : récompense provisoire « une boisson chaude et une pâtisserie offertes » au 10e passage, un tampon
   toutes les 2 h au plus : à ajuster dans Réglages.
5. **Prestations pro** : les 6 offres (plateaux, bar à chocolat, gâteaux d’équipe, buffets, cadeaux, privatisation)
   sont proposées d’après l’ancien site : à confirmer.
6. **Équipe** : le lieu a changé de propriétaires ; les portraits sont des emplacements « [Prénom] » à compléter dans Équipe (et la signature de l’ardoise, et le nom et le SIREN dans Réglages → La boutique pour les mentions légales).
7. **Vidéo du hero** : en attendant, le hero montre une photo de la boutique. Pour la vidéo, déposer
   `www/assets/video/hero.mp4` (et si possible `hero.webm`), 1920×1080, 10–15 s, sans son, < 4 Mo : elle s’affiche toute seule.
8. **Plan Google Maps** : intégré tel quel ; Google peut y déposer ses cookies (c’est indiqué dans les mentions légales).
9. La note Google affichée (4,6/5, 600+ avis) se met à jour à la main dans Réglages.
