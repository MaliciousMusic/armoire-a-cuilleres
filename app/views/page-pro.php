<?php /** Prestations pro (page dédiée, en cuivre doré). @var string $l */ ?>
<section class="phead sec--cuivre">
  <div class="wrap phead__grid">
    <div>
      <nav class="crumbs" aria-label="<?= e(t('nav.crumbs')) ?>"><a href="<?= e(url('home', $l)) ?>"><?= e(t('nav.home')) ?></a> <span aria-hidden="true">/</span> <span aria-current="page"><?= e(t('nav.pro')) ?></span></nav>
      <?= view('partials/head', ['num' => '01', 'kicker' => t('pro.kicker'), 'title' => t('pro.title'), 'id' => 'pro-title', 'lead' => t('pro.lead'), 'tag' => 'h1']) ?>
      <a class="choc choc--dark" href="#devis"><span><?= e(t('pro.cta')) ?></span></a>
    </div>
    <div class="phead__photo">
      <img src="/assets/img/insta/fondants.webp" srcset="/assets/img/insta/fondants-360.webp 360w, /assets/img/insta/fondants.webp 720w" sizes="(min-width: 900px) 34vw, 90vw" width="720" height="900" alt="Fondants au chocolat faits maison, lait caramélisé et noir pointe de sel" fetchpriority="high">
    </div>
  </div>
</section>

<?= view('partials/band', ['words' => t('band.words2'), 'tone' => 'cacao', 'above' => 'none', 'below' => 'coquille']) ?>

<section class="sec">
  <div class="wrap">
    <ul class="services" role="list">
<?php foreach (range(1, 6) as $i): ?>
      <li class="service service--<?= ['cacao', 'cuivre', 'rose', 'sauge', 'sand', 'cacao'][$i - 1] ?> reveal">
        <span class="service__n" aria-hidden="true"><?= sprintf('%02d', $i) ?></span>
        <h2 class="h3"><?= e(t("pro.s$i.t")) ?></h2>
        <p><?= e(t("pro.s$i.d")) ?></p>
      </li>
<?php endforeach ?>
    </ul>
  </div>
</section>

<section class="sec sec--lin" id="devis">
  <div class="wrap order__grid">
    <div class="reveal">
      <h2 class="h2"><?= e(t('pro.why.title')) ?></h2>
      <ul class="checks" role="list">
<?php foreach (range(1, 4) as $i): ?>
        <li><?= e(t("pro.w$i")) ?></li>
<?php endforeach ?>
      </ul>
      <p class="contact-line"><a href="tel:<?= e(phone_intl(conf('shop')['phone'])) ?>"><?= e(conf('shop')['phone']) ?></a></p>
      <p class="contact-line"><a href="mailto:<?= e(conf('shop')['email']) ?>"><?= e(conf('shop')['email']) ?></a></p>
    </div>
    <div class="reveal">
      <h2 class="h3"><?= e(t('pro.form.title')) ?></h2>
      <p><?= e(t('pro.form.lead')) ?></p>
      <?= view('partials/order-form', ['l' => $l, 'mode' => 'pro', 'id' => 'f-pro']) ?>
    </div>
  </div>
</section>
