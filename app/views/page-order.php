<?php /** Gâteaux sur commande (particuliers et pros). @var string $l */
$or = conf('orders');
$s = conf('shop');
$img = fn(string $n, string $alt) => '<img class="arch" src="/assets/img/insta/' . $n . '.webp" srcset="/assets/img/insta/' . $n . '-360.webp 360w, /assets/img/insta/' . $n . '.webp 720w" sizes="(min-width: 900px) 18vw, 45vw" width="720" height="900" alt="' . e($alt) . '" loading="lazy" decoding="async">';
?>
<section class="phead page-order">
  <div class="wrap order__grid">
    <div class="order__intro">
      <nav class="crumbs" aria-label="<?= e(t('nav.crumbs')) ?>"><a href="<?= e(url('home', $l)) ?>"><?= e(t('nav.home')) ?></a> <span aria-hidden="true">/</span> <span aria-current="page"><?= e(t('nav.order')) ?></span></nav>
      <?= view('partials/head', ['num' => '01', 'kicker' => t('order.kicker'), 'title' => t('order.title'), 'id' => 'order-title', 'lead' => t('order.lead', ['lead' => $or['lead_hours']]), 'tag' => 'h1']) ?>
      <ol class="steps reveal" role="list">
        <li><span>1</span><span><?= e(t('order.s1')) ?></span></li>
        <li><span>2</span><span><?= e(t('order.s2')) ?></span></li>
        <li><span>3</span><span><?= e(t('order.s3')) ?></span></li>
      </ol>
      <div class="order__photos reveal">
        <?= $img('tarte', 'Tarte meringuée aux fruits rouges, faite maison') ?>
        <?= $img('cheesecake', 'Cheesecake maison et son coulis, sur une assiette ancienne') ?>
      </div>
    </div>
    <div class="order__form reveal">
<?php if ($or['enabled']): ?>
      <?= view('partials/order-form', ['l' => $l, 'mode' => 'order', 'id' => 'f-order']) ?>
<?php else: ?>
      <p class="card"><?= e(t('order.closed', ['phone' => $s['phone']])) ?></p>
<?php endif ?>
    </div>
  </div>
</section>
