<?php
/** @var string $l */
$s = conf('shop');
$br = conf('brunch');
$ra = conf('rating');
$menu = menu();
$tel = phone_intl($s['phone']);
$img = fn(string $n, string $alt, string $cls = '', string $sizes = '(min-width: 900px) 30vw, 90vw') =>
    '<img class="' . $cls . '" src="/assets/img/insta/' . $n . '.webp" srcset="/assets/img/insta/' . $n . '-360.webp 360w, /assets/img/insta/' . $n . '.webp 720w" sizes="' . $sizes . '" width="720" height="900" alt="' . e($alt) . '" loading="lazy" decoding="async">';
$title = t('hero.title');
// Le titre du hero défile : le lieu, le brunch, les gâteaux, les jours d'ouverture
$rot = [[t('hero.rot.choc.a'), t('hero.rot.choc.b')]];
if ($br['enabled']) $rot[] = [t('hero.rot.brunch.a'), t('hero.rot.brunch.b', ['day' => explode(',', t('days.long'))[(int) $br['day']], 'from' => $br['from']])];
$rot[] = [t('hero.rot.cake.a'), t('hero.rot.cake.b')];
$rot[] = open_days_phrase($l);
// Les allergènes d'un produit, en toutes lettres
$alIcons = function (string $csv) {
    $list = array_values(array_filter(explode(',', $csv), fn($a) => in_array($a, ALLERGENS, true)));
    if (!$list) return '';
    return '<p class="mi__al"><span class="mi__al-k">' . e(t('al.title')) . '</span> ' . implode('', array_map(fn($a) => '<span class="al-tag">' . e(t("al.$a")) . '</span>', $list)) . '</p>';
};
$ard = ardoise();
$ardOn = $ard['visible'] && $ard['items'];
$ardBefore = null;
foreach ($menu as $sec) if ($ardOn && $ardBefore === null && str_contains(mb_strtolower(tr($sec['title'], 'fr')), 'pâtisserie')) $ardBefore = (int) $sec['id'];
if ($ardOn && $ardBefore === null && $menu) $ardBefore = (int) $menu[0]['id'];
// La galerie de l'accueil : le lieu, puis les pâtisseries maison
$gallery = [
    ['rue', 'La terrasse de L’Armoire à Cuillères, rue des Chaussetiers, dans le vieux Clermont-Ferrand'],
    ['table', 'Goûter sur une table menthe : chocolat chaud, pâtisseries et vaisselle ancienne'],
    ['fondants', 'Fondants au chocolat faits maison, lait caramélisé et noir pointe de sel'],
    ['cheesecake', 'Cheesecake maison et son coulis, sur une assiette ancienne'],
    ['tarte', 'Tarte meringuée aux fruits rouges, faite maison'],
];
$crusSection = null;
foreach ($menu as $sec) if (count(crus_of($sec, $l)) >= 3) { $crusSection = (int) $sec['id']; break; }
?>
<section class="hero" aria-labelledby="hero-title">
  <img class="hero__media" src="/assets/img/hero-1920.webp" srcset="/assets/img/hero-768.webp 768w, /assets/img/hero-1280.webp 1280w, /assets/img/hero-1920.webp 1920w" sizes="100vw" width="1920" height="1442" alt="" fetchpriority="high" decoding="async">
<?php if (is_file(public_dir() . '/assets/video/hero.mp4')): ?>
  <video class="hero__media hero__video" autoplay muted loop playsinline preload="auto" poster="<?= is_file(public_dir() . '/assets/video/hero-poster.webp') ? '/assets/video/hero-poster.webp' : '/assets/img/hero-1280.webp' ?>" aria-hidden="true" tabindex="-1" data-hero-video>
<?php if (is_file(public_dir() . '/assets/video/hero.webm')): ?>
    <source src="/assets/video/hero.webm" type="video/webm">
<?php endif ?>
    <source src="/assets/video/hero.mp4" type="video/mp4">
  </video>
<?php endif ?>
  <div class="hero__veil" aria-hidden="true"></div>
  <div class="hero__content">
    <h1 class="hero__title" id="hero-title">
      <span class="sr-only"><?= e($title) ?></span>
      <span class="rot" aria-hidden="true" data-rot>
<?php foreach ($rot as $i => [$ra1, $rb1]): ?>
        <span class="rot__item<?= $i ? '' : ' is-on' ?>"><span><?= e($ra1) ?></span> <em><?= e($rb1) ?></em></span>
<?php endforeach ?>
      </span>
    </h1>
    <p class="hero__place"><?= e(t('hero.place')) ?></p>
    <a class="choc choc--white hero__cta" href="#carte"><span><?= e(t('hero.cta.menu')) ?></span></a>
  </div>
</section>

<?= view('partials/band', ['words' => t('band.words'), 'tone' => 'cacao', 'above' => 'none', 'below' => 'coquille']) ?>

<section class="sec intro" id="lieu" aria-labelledby="intro-title">
  <div class="wrap intro__grid">
    <div class="intro__text">
      <?= view('partials/head', ['num' => '01', 'kicker' => t('intro.kicker'), 'title' => t('intro.title'), 'id' => 'intro-title']) ?>
      <p class="lead reveal"><?= e(t('intro.p1')) ?></p>
      <p class="reveal"><?= e(t('intro.p2')) ?></p>
<?php if ($ra['show']): ?>
      <a class="rating reveal" href="<?= e($s['maps_url']) ?>" rel="noopener" target="_blank"><span><?= e(t('rating.text', ['value' => str_replace('.', in_array($l, ['en', 'zh'], true) ? '.' : ',', (string) $ra['value']), 'count' => $ra['count']])) ?></span></a>
<?php endif ?>
    </div>
    <div class="gal reveal" data-gal>
      <div class="gal__stage">
<?php foreach ($gallery as $i => [$gn, $galt]): ?>
        <img class="gal__img<?= $i ? '' : ' is-on' ?>" src="/assets/img/insta/<?= $gn ?>.webp" srcset="/assets/img/insta/<?= $gn ?>-360.webp 360w, /assets/img/insta/<?= $gn ?>.webp 720w" sizes="(min-width: 960px) 46vw, 92vw" width="720" height="900" alt="<?= e($galt) ?>" loading="lazy" decoding="async">
<?php endforeach ?>
      </div>
    </div>
  </div>
</section>

<div class="spoons" aria-hidden="true"><?= str_repeat('<span></span>', 16) ?></div>

<section class="sec sec--lin menu" id="carte" aria-labelledby="menu-title">
  <div class="wrap">
    <?= view('partials/head', ['num' => '02', 'kicker' => t('menu.kicker'), 'title' => t('menu.title'), 'id' => 'menu-title', 'lead' => t('menu.lead')]) ?>
    <div class="menu__layout">
      <div class="menu__bar" data-menubar>
        <nav class="menu__cats" aria-label="<?= e(t('menu.jump')) ?>" data-pills data-pills-scroll>
          <span class="pills__ind" aria-hidden="true"></span>
          <ul class="pills" role="list">
<?php foreach ($menu as $sec): ?>
<?php if ((int) $sec['id'] === $ardBefore): ?>
            <li><a class="pill" href="#carte-ardoise" data-spy="carte-ardoise"><?= e(t('ardoise.title')) ?></a></li>
<?php endif ?>
            <li><a class="pill" href="#carte-<?= (int) $sec['id'] ?>" data-spy="carte-<?= (int) $sec['id'] ?>"><?= e(tr($sec['title'], $l)) ?></a></li>
<?php endforeach ?>
          </ul>
        </nav>
        <button class="menu__altoggle" type="button" aria-expanded="false" aria-controls="al-panel" data-al-open><span><?= e(t('al.title')) ?></span><b data-al-count hidden>0</b></button>
        <div class="al-panel" id="al-panel" role="group" aria-label="<?= e(t('al.title')) ?>" hidden>
          <p class="al-panel__hint"><?= e(t('al.hint')) ?></p>
          <div class="al-grid">
<?php foreach (ALLERGENS as $a): ?>
            <button class="al" type="button" aria-pressed="false" data-al-toggle="<?= e($a) ?>"><?= e(t("al.$a")) ?></button>
<?php endforeach ?>
          </div>
          <div class="al-panel__foot">
            <button class="al-panel__reset" type="button" data-al-reset hidden><?= e(t('al.reset')) ?></button>
            <button class="al-panel__close" type="button" data-al-close><?= e(t('nav.close')) ?></button>
          </div>
        </div>
      </div>
      <div class="menu__body">
<?php foreach ($menu as $sec): ?>
<?php if ((int) $sec['id'] === $ardBefore): ?>
        <section class="msec msec--ardoise" id="carte-ardoise" aria-labelledby="ms-ardoise">
          <div class="msec__head">
            <h3 class="h3" id="ms-ardoise"><?= e(t('ardoise.title')) ?></h3>
<?php if ($ard['date']): ?>
            <p class="msec__price"><?= e(ardoise_date($ard['date'], $l)) ?></p>
<?php endif ?>
          </div>
          <ul class="msec__items" role="list">
<?php foreach ($ard['items'] as $it): ?>
            <li class="mi" data-al="<?= e($it['allergens'] ?? '') ?>">
              <p class="mi__top"><span class="mi__name"><?= e(tr($it['name'], $l)) ?></span><span class="mi__dots" aria-hidden="true"></span><?php if (($it['price_cents'] ?? null) !== null): ?><span class="mi__price"><?= e(fmt_price((int) $it['price_cents'], $l)) ?></span><?php endif ?></p>
<?php if (!empty($it['allergens'])): ?>
              <div class="mi__foot"><?= $alIcons($it['allergens']) ?></div>
<?php endif ?>
            </li>
<?php endforeach ?>
          </ul>
<?php if (tr($ard['note'], $l) !== ''): ?>
          <p class="ardoise__sign"><?= e(tr($ard['note'], $l)) ?></p>
<?php endif ?>
        </section>
<?php endif ?>
        <section class="msec<?= (int) $sec['id'] === $crusSection ? ' msec--crus' : '' ?>" id="carte-<?= (int) $sec['id'] ?>" aria-labelledby="ms-<?= (int) $sec['id'] ?>">
          <div class="msec__head">
            <h3 class="h3" id="ms-<?= (int) $sec['id'] ?>"><?= e(tr($sec['title'], $l)) ?></h3>
<?php if ($sec['price_cents'] !== null): ?>
            <p class="msec__price"><?= e(t('menu.all', ['price' => fmt_price((int) $sec['price_cents'], $l)])) ?></p>
<?php endif ?>
          </div>
<?php if ($sec['note']): ?>
          <p class="msec__note"><?= e(tr($sec['note'], $l)) ?></p>
<?php endif ?>
<?php if ((int) $sec['id'] === $crusSection): ?>
          <?= view('partials/nuancier', ['crus' => crus_of($sec, $l), 'l' => $l, 'alIcons' => $alIcons]) ?>
<?php else: ?>
          <ul class="msec__items" role="list">
<?php foreach ($sec['items'] as $it):
            $tags = array_filter(explode(',', $it['tags']), fn($t) => in_array($t, ['bio', 'vegan', 'froid', 'nouveau', 'signature'], true)); ?>
            <li class="mi" data-al="<?= e($it['allergens']) ?>">
              <p class="mi__top">
                <span class="mi__name"><?= e(tr($it['name'], $l)) ?></span>
                <span class="mi__dots" aria-hidden="true"></span>
<?php if ($it['price_cents'] !== null): ?>
                <span class="mi__price"><?= $it['is_extra'] ? '+&thinsp;' : '' ?><?= e(fmt_price((int) $it['price_cents'], $l)) ?></span>
<?php endif ?>
              </p>
<?php if ($it['descr']): ?>
              <p class="mi__desc"><?= e(tr($it['descr'], $l)) ?></p>
<?php endif ?>
<?php if ($tags || $it['allergens']): ?>
              <div class="mi__foot">
<?php if ($tags): ?><p class="mi__tags"><?php foreach ($tags as $tg): ?><span class="tag tag--<?= e($tg) ?>"><?= e(t("menu.tag.$tg")) ?></span><?php endforeach ?></p><?php endif ?>
                <?= $alIcons($it['allergens']) ?>
              </div>
<?php endif ?>
            </li>
<?php endforeach ?>
          </ul>
<?php endif ?>
        </section>
<?php endforeach ?>
        <p class="menu__foot"><?= e(t('menu.allergens')) ?></p>
      </div>
    </div>
  </div>
</section>

<?= view('partials/band', ['words' => t('band.words2'), 'tone' => 'caramel', 'above' => 'lin', 'below' => (conf('brunch')['enabled'] ? 'rose' : 'lin')]) ?>

<?php if ($br['enabled']): ?>
<section class="sec sec--rose brunch" id="brunch" aria-labelledby="brunch-title">
  <div class="wrap brunch__grid">
    <figure class="brunch__photo reveal"><?= $img('brunch', 'Le buffet du brunch du dimanche : pain, confitures, fruits frais et gourmandises', '', '(min-width: 900px) 40vw, 90vw') ?>
      <span class="badge-round" aria-hidden="true"><?= e(t('brunch.from', ['from' => $br['from']])) ?></span>
    </figure>
    <div>
      <?= view('partials/head', ['num' => '03', 'kicker' => t('brunch.kicker'), 'title' => t('brunch.title'), 'id' => 'brunch-title']) ?>
      <div class="ticket reveal">
        <p class="ticket__lead"><?= e(t('brunch.lead')) ?></p>
        <ul class="checks" role="list">
<?php for ($i = 1; $i <= 4; $i++): ?>
          <li><?= e(t("brunch.i$i")) ?></li>
<?php endfor ?>
        </ul>
        <p class="ticket__buffet"><?= e(t('brunch.buffet')) ?></p>
        <div class="ticket__foot">
          <p class="ticket__price"><?= e(t('brunch.price', ['price' => fmt_price((int) $br['price_cents'], $l)])) ?></p>
          <a class="choc choc--white" href="tel:<?= e($tel) ?>"><span><?= e(t('brunch.book', ['phone' => $s['phone']])) ?></span></a>
        </div>
        <p class="small"><?= e(t('brunch.note')) ?></p>
      </div>
    </div>
  </div>
</section>
<?php endif ?>

<section class="sec faq" id="faq" aria-labelledby="faq-title">
  <div class="wrap faq__grid">
    <div class="faq__side"><?= view('partials/head', ['num' => '04', 'kicker' => 'FAQ', 'title' => t('faq.title'), 'id' => 'faq-title']) ?></div>
    <div class="faq__list">
<?php foreach (faq($l) as $i => [$qq, $a]): ?>
      <details class="faq__item"<?= $i === 0 ? ' open' : '' ?>>
        <summary><span><?= e($qq) ?></span><span class="faq__plus" aria-hidden="true"></span></summary>
        <p><?= e($a) ?></p>
      </details>
<?php endforeach ?>
    </div>
  </div>
</section>
