<?php
/** Pages de l'espace équipe. @var string $view setup|login|app @var string $csrf @var ?array $user */
$error = $error ?? null;
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#3B2A24">
<title>Espace équipe · L’Armoire à Cuillères</title>
<link rel="icon" href="/assets/icons/favicon.png" type="image/png">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
<?php if ($view === 'app'): ?>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
<script type="application/json" id="adm"><?= json_encode([
    'csrf' => $csrf,
    'user' => $user,
    'statuses' => array_combine(TICKET_STATUSES, ['Nouveau', 'En cours', 'Devis envoyé', 'Confirmé', 'Prêt', 'Terminé', 'Annulé']),
    'types' => ['gateau' => 'Gâteau · particulier', 'gateau_pro' => 'Gâteau · pro', 'pro' => 'Prestation pro', 'question' => 'Question'],
    'langs' => ['fr' => 'Français', 'en' => 'English', 'de' => 'Deutsch', 'es' => 'Español', 'zh' => '中文'],
    'tags' => ['bio' => 'Bio', 'vegan' => 'Végétal', 'froid' => 'Glacé', 'nouveau' => 'Nouveau', 'signature' => 'Coup de cœur', 'extra' => 'Supplément (+)'],
    'allergens' => ['gluten' => 'Gluten', 'crustaces' => 'Crustacés', 'oeufs' => 'Œufs', 'poisson' => 'Poisson', 'arachides' => 'Arachides', 'soja' => 'Soja', 'lait' => 'Lait', 'fruits_coque' => 'Fruits à coque', 'celeri' => 'Céleri', 'moutarde' => 'Moutarde', 'sesame' => 'Sésame', 'sulfites' => 'Sulfites', 'lupin' => 'Lupin', 'mollusques' => 'Mollusques'],
    'jsqr' => asset('assets/vendor/jsqr.js'),
    'qrcode' => asset('assets/vendor/qrcode.js'),
    'site' => url('home', 'fr'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif ?>
</head>
<body class="adm adm--<?= e($view) ?>">
<?php if ($view === 'setup' || $view === 'login'): ?>
<main class="gate">
  <form class="gate__card" method="post" action="/admin/<?= $view ?>">
    <img src="/assets/brand/logo-cacao.svg" alt="L’Armoire à Cuillères" width="180" height="108">
    <h1><?= $view === 'setup' ? 'Créer le compte du gérant' : 'Espace équipe' ?></h1>
<?php if ($error): ?>
    <p class="alert" role="alert"><?= e($error) ?></p>
<?php endif ?>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
<?php if ($view === 'setup'): ?>
    <p class="muted">Première connexion : ce compte aura tous les droits. Le code d’installation se trouve dans <code>app/config.php</code>.</p>
    <label>Code d’installation<input name="setup_token" type="password" required autocomplete="off"></label>
    <label>Votre nom<input name="name" type="text" required autocomplete="name"></label>
<?php endif ?>
    <label>E-mail<input name="email" type="email" required autocomplete="username" value="<?= e($email ?? '') ?>"></label>
    <label>Mot de passe<input name="password" type="password" required minlength="<?= $view === 'setup' ? 10 : 1 ?>" autocomplete="<?= $view === 'setup' ? 'new-password' : 'current-password' ?>"></label>
    <button class="b b--main b--full" type="submit"><?= $view === 'setup' ? 'Créer le compte' : 'Se connecter' ?></button>
    <p class="muted"><a href="/">← Retour au site</a></p>
  </form>
</main>
<?php else: ?>
<header class="top">
  <a class="top__brand" href="#/"><img src="/assets/brand/logo-creme.svg" alt="" width="70" height="42"><span>Espace équipe</span></a>
  <nav class="top__nav" aria-label="Rubriques">
    <a href="#/tickets" data-nav="tickets">Tickets <b class="badge" data-badge hidden></b></a>
    <a href="#/fidelite" data-nav="fidelite">Fidélité</a>
    <a href="#/ardoise" data-nav="ardoise">Ardoise du jour</a>
    <a href="#/carte" data-nav="carte">La carte</a>
<?php if ($user['role'] === 'admin'): ?>
    <a href="#/equipe" data-nav="equipe">Équipe</a>
    <a href="#/reglages" data-nav="reglages">Réglages</a>
    <a href="#/comptes" data-nav="comptes">Comptes</a>
    <a href="#/journal" data-nav="journal">Journal</a>
<?php endif ?>
  </nav>
  <div class="top__me">
    <a href="#/moi" data-nav="moi"><?= e($user['name']) ?></a>
    <form method="post" action="/admin/logout"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><button class="b b--ghost-light b--sm" type="submit">Déconnexion</button></form>
  </div>
</header>
<main class="view" id="view" tabindex="-1" aria-live="polite"></main>
<div class="toast" role="status" aria-live="polite" data-toast hidden></div>
<?php endif ?>
</body>
</html>
