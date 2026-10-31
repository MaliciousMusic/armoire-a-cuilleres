<?php /** Contact : téléphone, e-mail, adresse, horaires, réseaux et une question. @var string $l */
$s = conf('shop');
$so = conf('social');
$tel = phone_intl($s['phone']);
?>
<section class="phead sec--cacao contact" aria-labelledby="contact-title">
  <div class="wrap">
    <nav class="crumbs" aria-label="<?= e(t('nav.crumbs')) ?>"><a href="<?= e(url('home', $l)) ?>"><?= e(t('nav.home')) ?></a> <span aria-hidden="true">/</span> <span aria-current="page"><?= e(t('nav.contact')) ?></span></nav>
    <?= view('partials/head', ['num' => '01', 'kicker' => t('contact.kicker'), 'title' => t('contact.title'), 'id' => 'contact-title', 'tag' => 'h1']) ?>
    <div class="contact__bento">
      <a class="ccard ccard--pop reveal" href="tel:<?= e($tel) ?>"><span class="ccard__k"><?= e(t('contact.phone')) ?></span><span class="ccard__v"><?= e($s['phone']) ?></span><span class="ccard__go"><?= e(t('contact.call')) ?></span></a>
      <a class="ccard reveal" href="mailto:<?= e($s['email']) ?>"><span class="ccard__k"><?= e(t('contact.email')) ?></span><span class="ccard__v ccard__v--sm"><?= e($s['email']) ?></span><span class="ccard__go"><?= e(t('contact.write')) ?></span></a>
      <a class="ccard reveal" href="<?= e($s['maps_url']) ?>" rel="noopener" target="_blank"><span class="ccard__k"><?= e(t('contact.address')) ?></span><span class="ccard__v ccard__v--sm"><?= e($s['street']) ?><br><?= e($s['postal'] . ' ' . $s['city']) ?></span></a>
      <div class="ccard ccard--hours reveal"><span class="ccard__k"><?= e(t('hours.title')) ?></span>
        <dl class="hours">
<?php foreach (hours_lines($l) as [$d, $h]): ?>
          <div><dt><?= e($d) ?></dt><dd><?= e($h) ?></dd></div>
<?php endforeach ?>
        </dl>
        <p class="status status--inline" data-status hidden><span data-status-text></span></p>
      </div>
      <div class="ccard ccard--social reveal"><span class="ccard__k"><?= e(t('contact.follow')) ?></span>
        <ul class="social" role="list">
          <li><a href="<?= e($so['instagram']) ?>" rel="noopener me" target="_blank">Instagram</a></li>
          <li><a href="<?= e($so['facebook']) ?>" rel="noopener me" target="_blank">Facebook</a></li>
<?php if ($so['tiktok']): ?>
          <li><a href="<?= e($so['tiktok']) ?>" rel="noopener me" target="_blank">TikTok</a></li>
<?php endif ?>
        </ul>
      </div>
      <div class="ccard ccard--form reveal">
        <h3 class="h3"><?= e(t('contact.question')) ?></h3>
        <p><?= e(t('contact.question.lead')) ?></p>
        <?= view('partials/order-form', ['l' => $l, 'mode' => 'question', 'id' => 'f-question']) ?>
      </div>
    </div>
  </div>
</section>

