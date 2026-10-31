<?php /** Nous trouver : le plan Google et la fiche de la boutique. @var string $l */ ?>
<section class="phead sec--lin access" aria-labelledby="access-title">
  <div class="wrap">
    <nav class="crumbs" aria-label="<?= e(t('nav.crumbs')) ?>"><a href="<?= e(url('home', $l)) ?>"><?= e(t('nav.home')) ?></a> <span aria-hidden="true">/</span> <span aria-current="page"><?= e(t('nav.access')) ?></span></nav>
    <?= view('partials/head', ['num' => '01', 'kicker' => t('access.kicker'), 'title' => t('access.title'), 'id' => 'access-title', 'lead' => t('access.lead'), 'tag' => 'h1']) ?>
  </div>
  <?= view('partials/gmap', ['l' => $l]) ?>
</section>
