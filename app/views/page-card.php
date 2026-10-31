<?php /** Carte de fidélité d'un client (page privée). @var string $l @var ?array $card */
$card = $card ?? null;
?>
<section class="sec sec--sauge cardpage">
  <div class="wrap wrap--narrow">
<?php if (!$card): ?>
    <h1 class="h2"><?= e(t('loyalty.kicker')) ?></h1>
    <p class="lead"><?= e(t('loyalty.card.lost')) ?></p>
    <p><a class="choc choc--dark" href="<?= e(url('loyalty', $l)) ?>" data-forget-card><span><?= e(t('loyalty.cta')) ?></span></a></p>
<?php else:
    $p = loyalty_public($card);
    $qr = url('loyalty', $l, true) . '?c=' . $card['token']; ?>
    <div class="lcard lcard--big" data-card="<?= e($card['token']) ?>">
      <img src="/assets/brand/logo-cacao.svg" alt="L’Armoire à Cuillères" width="160" height="96">
      <h1 class="lcard__name"><?= e(t('loyalty.card.of', ['name' => $p['first_name']])) ?></h1>
      <div class="qr" data-qr="<?= e($qr) ?>" role="img" aria-label="QR code"></div>
      <p class="lcard__code"><?= e(t('loyalty.card.code', ['code' => $p['code']])) ?></p>
      <div class="lcard__spoons" data-spoons>
<?php for ($i = 1; $i <= $p['goal']; $i++): ?>
        <span class="<?= $i <= min($p['stamps'], $p['goal']) ? 'on' : '' ?><?= $i === $p['goal'] ? ' gift' : '' ?>"></span>
<?php endfor ?>
      </div>
      <p class="lcard__count" data-count><?= e(t('loyalty.card.count', ['n' => $p['stamps'], 'goal' => $p['goal']])) ?></p>
      <p class="lcard__msg<?= $p['ready'] ? ' lcard__msg--ready' : '' ?>" data-msg><?= e($p['ready'] ? t('loyalty.card.ready', ['reward' => $p['reward']]) : t('loyalty.card.left', ['n' => $p['goal'] - $p['stamps'], 'reward' => $p['reward']])) ?></p>
<?php if ($p['rewards']): ?>
      <p class="small"><?= e(t('loyalty.card.rewards', ['n' => $p['rewards']])) ?></p>
<?php endif ?>
    </div>
    <p class="cardpage__hint"><?= e(t('loyalty.card.show')) ?></p>
    <p class="small"><?= e(t('loyalty.card.save')) ?></p>
    <p class="small"><a href="<?= e(url('loyalty', $l)) ?>" data-forget-card><?= e(t('loyalty.card.forget')) ?></a></p>
    <script src="<?= e(asset('assets/vendor/qrcode.js')) ?>" defer></script>
<?php endif ?>
  </div>
</section>
