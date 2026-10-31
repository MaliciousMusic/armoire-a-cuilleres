<?php
/** @var string $page @var string $l @var string $body */
$s = conf('shop');
$so = conf('social');
$base = config()['base_url'];
$isHome = $page === 'home';
$seoKey = ['home' => 'home', 'order' => 'order', 'pro' => 'pro', 'loyalty' => 'loyalty', 'team' => 'team', 'access' => 'access', 'contact' => 'contact', 'track' => 'track', 'card' => 'loyalty', 'legal' => 'legal', '404' => 'home'][$page] ?? 'home';
$title = $page === '404' ? t('notfound.title') . ' · L’Armoire à Cuillères' : t("seo.title.$seoKey");
$desc = t('seo.desc.' . (in_array($seoKey, ['home', 'order', 'pro', 'loyalty', 'team', 'access', 'contact'], true) ? $seoKey : 'home'));
$indexable = in_array($page, ['home', 'order', 'pro', 'loyalty', 'team', 'access', 'contact', 'legal'], true);
$canonical = $indexable ? url($page, $l, true) : null;
$home = url('home', $l);
$a = fn(string $id) => ($isHome ? '' : $home) . '#' . $id;
// Les rubriques : libellé complet (menu plein écran), libellé court + icône (barre de l'en-tête), section suivie
$nav = [
    ['href' => $a('carte'), 'label' => t('nav.menu'), 'short' => t('nav.s.menu'), 'icon' => 'cup', 'spy' => 'carte', 'dock' => true],
    ['href' => $a('brunch'), 'label' => t('nav.brunch'), 'short' => t('nav.s.brunch'), 'icon' => 'sun', 'spy' => 'brunch'],
    ['href' => url('order', $l), 'label' => t('nav.order'), 'short' => t('nav.s.order'), 'icon' => 'cake', 'current' => $page === 'order', 'dock' => true],
    ['href' => url('pro', $l), 'label' => t('nav.pro'), 'short' => t('nav.s.pro'), 'icon' => 'briefcase', 'current' => $page === 'pro'],
    ['href' => url('loyalty', $l), 'label' => t('nav.loyalty'), 'short' => t('nav.s.loyalty'), 'icon' => 'spoon', 'current' => in_array($page, ['loyalty', 'card'], true), 'dock' => true],
    ['href' => url('team', $l), 'label' => t('nav.team'), 'short' => t('nav.s.team'), 'icon' => 'users', 'current' => $page === 'team'],
    ['href' => url('access', $l), 'label' => t('nav.access'), 'short' => t('nav.s.access'), 'icon' => 'pin', 'current' => $page === 'access', 'dock' => true],
    ['href' => url('contact', $l), 'label' => t('nav.contact'), 'short' => t('nav.s.contact'), 'icon' => 'phone', 'current' => $page === 'contact'],
];
if (!conf('brunch')['enabled']) unset($nav[1]);

// Données structurées : le site, la boutique (avec la carte), la page, la FAQ, le fil d'Ariane
$graph = [
    ['@type' => 'WebSite', '@id' => $base . '/#site', 'url' => $base . '/', 'name' => 'L’Armoire à Cuillères', 'inLanguage' => array_map(fn($x) => $x['hreflang'], array_values(LANGS)), 'publisher' => ['@id' => $base . '/#boutique']],
    jsonld_business($l),
];
if ($indexable) {
    $wp = ['@type' => 'WebPage', '@id' => $canonical . '#page', 'url' => $canonical, 'name' => $title, 'description' => $desc, 'inLanguage' => LANGS[$l]['hreflang'],
        'isPartOf' => ['@id' => $base . '/#site'], 'about' => ['@id' => $base . '/#boutique'], 'primaryImageOfPage' => $base . '/assets/img/og-armoire.jpg'];
    if (!$isHome) {
        $wp['breadcrumb'] = ['@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => t('nav.home'), 'item' => url('home', $l, true)],
            ['@type' => 'ListItem', 'position' => 2, 'name' => ['order' => t('nav.order'), 'pro' => t('nav.pro'), 'loyalty' => t('nav.loyalty'), 'team' => t('nav.team'), 'access' => t('nav.access'), 'contact' => t('nav.contact')][$page] ?? t('footer.legal'), 'item' => $canonical],
        ]];
    }
    $graph[] = $wp;
}
if ($isHome) {
    $graph[] = ['@type' => 'FAQPage', '@id' => $canonical . '#faq', 'inLanguage' => LANGS[$l]['hreflang'],
        'mainEntity' => array_map(fn($x) => ['@type' => 'Question', 'name' => $x[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $x[1]]], faq($l))];
}
if ($page === 'pro') {
    $graph[] = ['@type' => 'Service', 'name' => t('pro.title'), 'serviceType' => 'Catering', 'provider' => ['@id' => $base . '/#boutique'], 'areaServed' => 'Clermont-Ferrand',
        'description' => t('pro.lead'), 'url' => $canonical, 'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => t('pro.title'),
        'itemListElement' => array_map(fn($i) => ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => t("pro.s$i.t"), 'description' => t("pro.s$i.d")]], range(1, 6))]];
}
$jsonld = json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

// Ce dont le JavaScript a besoin (statut ouvert/fermé, textes, carte)
$jsData = [
    'lang' => $l,
    'hours' => hours_payload(),
    'days' => explode(',', t('days.long')),
    't' => array_combine(
        ['open', 'soon', 'closed', 'today', 'tomorrow', 'day', 'sending', 'send', 'generic', 'pause', 'play', 'menu', 'close', 'loading', 'nomap', 'walk', 'replay', 'okTitle', 'okText', 'okLink', 'errTitle', 'count', 'left', 'ready', 'code', 'of', 'lost', 'rewards', 'you', 'team', 'changed', 'empty', 'coopDesktop', 'coopMac', 'coopMobile', 'shortOpen', 'shortSoon', 'shortClosed'],
        array_map('t', ['status.open', 'status.soon', 'status.closed', 'status.today', 'status.tomorrow', 'status.day', 'form.sending', 'form.send', 'form.err.generic', 'hero.video.pause', 'hero.video.play', 'nav.open', 'nav.close', 'access.loading', 'access.nomap', 'access.walk', 'access.replay', 'form.ok.title', 'form.ok.text', 'form.ok.link', 'form.err.title', 'loyalty.card.count', 'loyalty.card.left', 'loyalty.card.ready', 'loyalty.card.code', 'loyalty.card.of', 'loyalty.card.lost', 'loyalty.card.rewards', 'track.you', 'track.team', 'track.status.changed', 'track.empty', 'access.coop.desktop', 'access.coop.mac', 'access.coop.mobile', 'status.short.open', 'status.short.soon', 'status.short.closed'])
    ),
    'status' => array_combine(TICKET_STATUSES, array_map(fn($x) => t("status.$x"), TICKET_STATUSES)),
    'phone' => $s['phone'],
    'loyaltyUrl' => url('loyalty', $l),
];
$ann = tr(setting('announcement', default_settings()['announcement']), $l);
?><!doctype html>
<html lang="<?= e(LANGS[$l]['hreflang']) ?>" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if ($indexable): ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<?php foreach (LANGS as $k => $L): ?>
<link rel="alternate" hreflang="<?= e($L['hreflang']) ?>" href="<?= e(url($page, $k, true)) ?>">
<?php endforeach ?>
<link rel="alternate" hreflang="x-default" href="<?= e(url($page, 'fr', true)) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<?php else: ?>
<meta name="robots" content="noindex, nofollow">
<?php endif ?>
<meta name="theme-color" content="#F4EDE1">
<meta name="color-scheme" content="light">
<meta name="format-detection" content="telephone=no">
<meta property="og:type" content="website">
<meta property="og:site_name" content="L’Armoire à Cuillères">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical ?? url('home', $l, true)) ?>">
<meta property="og:image" content="<?= e($base) ?>/assets/img/og-armoire.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="<?= e(LANGS[$l]['locale']) ?>">
<?php foreach (LANGS as $k => $L) if ($k !== $l): ?>
<meta property="og:locale:alternate" content="<?= e($L['locale']) ?>">
<?php endif ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="geo.region" content="FR-63">
<meta name="geo.placename" content="Clermont-Ferrand">
<meta name="geo.position" content="<?= e($s['lat'] . ';' . $s['lng']) ?>">
<meta name="ICBM" content="<?= e($s['lat'] . ', ' . $s['lng']) ?>">
<link rel="icon" href="/assets/icons/favicon.png" type="image/png">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="alternate" type="text/plain" href="/llms.txt" title="llms.txt">
<link rel="preload" href="/assets/fonts/newsreader-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/fraunces-normal.woff2" as="font" type="font/woff2" crossorigin>
<?php if ($isHome): ?>
<link rel="preload" as="image" href="/assets/img/hero-1920.webp" imagesrcset="/assets/img/hero-768.webp 768w, /assets/img/hero-1280.webp 1280w, /assets/img/hero-1920.webp 1920w" imagesizes="100vw" fetchpriority="high">
<?php endif ?>
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
<script src="<?= e(asset('assets/js/site.js')) ?>" defer></script>
<script type="application/ld+json"><?= $jsonld ?></script>
<script type="application/json" id="ac-data"><?= json_encode($jsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</head>
<body class="p-<?= e($page) ?>">
<a class="skip" href="#contenu"><?= e(t('nav.skip')) ?></a>
<?php if ($ann !== ''): ?>
<div class="ann" role="note"><?= e($ann) ?></div>
<?php endif ?>
<?php
$langPage = in_array($page, ['home', 'order', 'pro', 'loyalty', 'team', 'access', 'contact', 'legal'], true) ? $page : 'home';
$pill = fn(array $n, string $cls = 'pill') => '<a class="' . $cls . '" href="' . e($n['href']) . '"' . (isset($n['spy']) ? ' data-spy="' . e($n['spy']) . '"' : '')
    . (!empty($n['current']) ? ' aria-current="page"' : '') . '>' . e($n['short']) . '</a>';
// Le choix de la langue : à droite sur grand écran, à côté de « Ouvert » sur téléphone
$langs = function (string $cls) use ($l, $langPage) {
    $h = '<details class="langs ' . $cls . '"><summary aria-label="' . e(t('nav.lang')) . '"><span>' . e(LANGS[$l]['short']) . '</span></summary><ul role="list">';
    foreach (LANGS as $k => $L) {
        $h .= '<li><a href="' . e(url($langPage, $k)) . '" hreflang="' . e($L['hreflang']) . '" lang="' . e($L['hreflang']) . '"' . ($k === $l ? ' aria-current="true"' : '') . '>' . e($L['name']) . '</a></li>';
    }
    return $h . '</ul></details>';
};
?>
<header class="hd hd--<?= in_array($page, ['home', 'contact', 'pro'], true) ? 'dark' : 'light' ?>" data-hd>
  <nav class="hd__bar" aria-label="<?= e(t('nav.main')) ?>">
    <div class="hd__side hd__side--l">
      <details class="hd__hours" data-hours>
        <summary class="status hd__status" data-status title="<?= e(t('hours.toggle')) ?>"><span class="hd__status-long" data-status-text><?= e(t('hours.title')) ?></span><span class="hd__status-short" data-status-short><?= e(t('hours.title')) ?></span></summary>
        <div class="hd__pop">
          <p class="hd__pop-t"><?= e(t('hours.title')) ?></p>
          <dl class="hours">
<?php foreach (hours_lines($l) as [$d, $h]): ?>
            <div><dt><?= e($d) ?></dt><dd><?= e($h) ?></dd></div>
<?php endforeach ?>
          </dl>
<?php if (conf('brunch')['enabled']): ?>
          <p class="hd__pop-note"><?= e(t('hours.brunch', ['from' => conf('brunch')['from']])) ?></p>
<?php endif ?>
          <p class="hd__pop-addr"><?= e(shop_address_line()) ?> · <a href="<?= e(url('access', $l)) ?>"><?= e(t('hours.more')) ?></a></p>
        </div>
      </details>
      <?= $langs('langs--m') ?>
      <div class="hd__pills" data-pills>
        <span class="pills__ind" aria-hidden="true"></span>
        <ul class="pills" role="list">
<?php foreach (array_slice(array_values($nav), 0, (int) ceil(count($nav) / 2)) as $n): ?>
          <li><?= $pill($n) ?></li>
<?php endforeach ?>
        </ul>
      </div>
    </div>
    <a class="hd__logo" href="<?= e($home) ?>" aria-label="L’Armoire à Cuillères, <?= e(t('nav.home')) ?>">
      <img class="hd__logo-l" src="/assets/brand/logo-creme.svg" alt="" width="134" height="80">
      <img class="hd__logo-c" src="/assets/brand/logo-sienne.svg" alt="" width="134" height="80">
    </a>
    <div class="hd__side hd__side--r">
      <div class="hd__pills" data-pills>
        <span class="pills__ind" aria-hidden="true"></span>
        <ul class="pills" role="list">
<?php foreach (array_slice(array_values($nav), (int) ceil(count($nav) / 2)) as $n): ?>
          <li><?= $pill($n) ?></li>
<?php endforeach ?>
        </ul>
      </div>
      <?= $langs('langs--d') ?>
      <a class="choc choc--milk hd__cta" href="<?= e(url('order', $l)) ?>"><span><?= e(t('nav.cta')) ?></span></a>
      <button class="choc choc--milk choc--sm hd__burger" type="button" aria-expanded="false" aria-controls="sheet" data-burger><span data-burger-label><?= e(t('nav.open')) ?></span></button>
    </div>
  </nav>
  <div class="sheet" id="sheet" data-sheet hidden>
    <nav aria-label="<?= e(t('nav.main')) ?>">
      <ul class="tablet" role="list">
<?php foreach (array_values($nav) as $i => $n): ?>
        <li><a class="choc choc--<?= ['dark', 'milk', 'white', 'dark', 'milk', 'white', 'dark', 'milk'][$i % 8] ?>" href="<?= e($n['href']) ?>"<?= !empty($n['current']) ? ' aria-current="page"' : '' ?>><span><?= e($n['label']) ?></span></a></li>
<?php endforeach ?>
      </ul>
    </nav>
  </div>
</header>
<nav class="dock" aria-label="<?= e(t('nav.sections')) ?>" data-pills>
  <span class="pills__ind" aria-hidden="true"></span>
  <ul class="pills" role="list">
<?php foreach ($nav as $n) if (!empty($n['dock'])): ?>
    <li><a class="pill" href="<?= e($n['href']) ?>"<?= isset($n['spy']) ? ' data-spy="' . e($n['spy']) . '"' : '' ?><?= !empty($n['current']) ? ' aria-current="page"' : '' ?>><?= e($n['short']) ?></a></li>
<?php endif ?>
    <li><button class="pill" type="button" data-burger-dock aria-controls="sheet"><?= e(t('nav.open')) ?></button></li>
  </ul>
</nav>
<main id="contenu" tabindex="-1">
<?= $body ?>
</main>
<footer class="ft">
  <div class="wrap ft__grid">
    <div class="ft__brand">
      <img src="/assets/brand/logo-creme.svg" alt="L’Armoire à Cuillères" width="200" height="120" loading="lazy" decoding="async">
      <p><?= e(t('footer.tagline')) ?></p>
    </div>
    <div>
      <p class="ft__h"><?= e(t('contact.address')) ?></p>
      <address>
        <a href="<?= e($s['maps_url']) ?>" rel="noopener" target="_blank"><?= e($s['street']) ?><br><?= e($s['postal'] . ' ' . $s['city']) ?></a><br>
        <a href="tel:<?= e(phone_intl($s['phone'])) ?>"><?= e($s['phone']) ?></a><br>
        <a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a>
      </address>
    </div>
    <div>
      <p class="ft__h"><?= e(t('hours.title')) ?></p>
      <dl class="hours hours--ft">
<?php foreach (hours_lines($l) as [$d, $h]): ?>
        <div><dt><?= e($d) ?></dt><dd><?= e($h) ?></dd></div>
<?php endforeach ?>
      </dl>
    </div>
    <div>
      <p class="ft__h"><?= e(t('contact.follow')) ?></p>
      <ul class="social">
        <li><a href="<?= e($so['instagram']) ?>" rel="noopener me" target="_blank">Instagram</a></li>
        <li><a href="<?= e($so['facebook']) ?>" rel="noopener me" target="_blank">Facebook</a></li>
<?php if ($so['tiktok']): ?>
        <li><a href="<?= e($so['tiktok']) ?>" rel="noopener me" target="_blank">TikTok</a></li>
<?php endif ?>
      </ul>
    </div>
  </div>
  <div class="wrap ft__bottom">
    <p><?= e(t('footer.rights', ['year' => date('Y')])) ?></p>
    <p><a href="<?= e(url('legal', $l)) ?>"><?= e(t('footer.legal')) ?></a> · <a href="/admin/" rel="nofollow"><?= e(t('footer.staff')) ?></a></p>
    <p class="ft__langs"><?php foreach (LANGS as $k => $L): ?><a href="<?= e(url($langPage, $k)) ?>" hreflang="<?= e($L['hreflang']) ?>" lang="<?= e($L['hreflang']) ?>"><?= e($L['name']) ?></a> <?php endforeach ?></p>
  </div>
</footer>
</body>
</html>
