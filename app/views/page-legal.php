<?php /** @var string $l */
$s = conf('shop');
$v = ['owner' => $s['owner'], 'siren' => $s['siren'], 'address' => shop_address_line(), 'phone' => $s['phone'], 'email' => $s['email']];
?>
<section class="sec sec--egg legal">
  <div class="wrap wrap--narrow">
    <nav class="crumbs" aria-label="<?= e(t('nav.crumbs')) ?>"><a href="<?= e(url('home', $l)) ?>"><?= e(t('nav.home')) ?></a> <span aria-hidden="true">/</span> <span aria-current="page"><?= e(t('footer.legal')) ?></span></nav>
    <h1 class="h1"><?= e(t('legal.title')) ?></h1>
<?php foreach (['editor', 'host', 'data', 'cookies', 'credits'] as $k): ?>
    <h2 class="h3"><?= e(t("legal.$k")) ?></h2>
    <p><?= e(t("legal.$k.text", $v)) ?></p>
<?php endforeach ?>
  </div>
</section>
