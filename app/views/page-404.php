<?php /** @var string $l */ ?>
<section class="sec sec--egg notfound">
  <div class="wrap wrap--narrow">
    <img src="/assets/brand/cuillere.svg" alt="" width="60" height="330" class="notfound__spoon">
    <h1 class="h1"><?= e(t('notfound.title')) ?></h1>
    <p class="lead"><?= e(t('notfound.text')) ?></p>
    <p><a class="choc choc--dark" href="<?= e(url('home', $l)) ?>"><span><?= e(t('notfound.back')) ?></span></a></p>
  </div>
</section>
