<?php /** @var string $l */
$lo = conf('loyalty');
$vars = ['goal' => $lo['goal'], 'reward' => tr($lo['reward'], $l)];
?>
<section class="phead sec--pistache">
  <div class="wrap phead__grid">
    <div>
      <nav class="crumbs" aria-label="<?= e(t('nav.crumbs')) ?>"><a href="<?= e(url('home', $l)) ?>"><?= e(t('nav.home')) ?></a> <span aria-hidden="true">/</span> <span aria-current="page"><?= e(t('nav.loyalty')) ?></span></nav>
      <?= view('partials/head', ['num' => '01', 'kicker' => t('loyalty.kicker'), 'title' => t('loyalty.title'), 'id' => 'loyalty-title', 'lead' => t('loyalty.lead', $vars), 'tag' => 'h1']) ?>
      <p data-my-card hidden><a class="choc choc--milk" href="#"><span><?= e(t('loyalty.mine')) ?></span></a></p>
      <ol class="steps" role="list">
        <li><span>1</span><span><?= e(t('loyalty.s1')) ?></span></li>
        <li><span>2</span><span><?= e(t('loyalty.s2')) ?></span></li>
        <li><span>3</span><span><?= e(t('loyalty.s3', $vars)) ?></span></li>
      </ol>
      <div class="loyalty__visual" aria-hidden="true">
        <div class="lcard">
          <div class="lcard__top"><img src="/assets/brand/logo-cacao.svg" alt="" width="110" height="66" loading="lazy"><span class="lcard__count">7&thinsp;/&thinsp;<?= (int) $lo['goal'] ?></span></div>
          <div class="lcard__spoons">
<?php for ($i = 1; $i <= (int) $lo['goal']; $i++): ?>
            <span class="<?= $i <= 7 ? 'on' : '' ?><?= $i === (int) $lo['goal'] ? ' gift' : '' ?>"></span>
<?php endfor ?>
          </div>
          <p class="lcard__hint"><?= e(t('loyalty.card.left', ['n' => (int) $lo['goal'] - 7, 'reward' => $vars['reward']])) ?></p>
        </div>
      </div>
    </div>
    <div>
      <form class="form card" action="/api/loyalty" method="post" data-loyalty-form>
        <h2 class="h3"><?= e(t('loyalty.form.title')) ?></h2>
        <input type="hidden" name="lang" value="<?= e($l) ?>">
        <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="field">
          <label for="lf-first"><?= e(t('loyalty.form.first')) ?> <span class="req"><?= e(t('form.required')) ?></span></label>
          <input id="lf-first" name="first_name" type="text" required minlength="2" maxlength="60" autocomplete="given-name">
        </div>
        <div class="field">
          <label for="lf-email"><?= e(t('loyalty.form.email')) ?> <span class="opt"><?= e(t('form.optional')) ?></span></label>
          <input id="lf-email" name="email" type="email" maxlength="190" autocomplete="email">
        </div>
        <label class="check"><input type="checkbox" name="consent" value="1" required><span><?= e(t('loyalty.form.consent')) ?></span></label>
        <div class="form__msg" role="alert" aria-live="assertive" data-form-msg hidden></div>
        <button class="choc choc--dark choc--full" type="submit"><span><?= e(t('loyalty.form.send')) ?></span></button>
        <p class="small"><?= e(t('loyalty.rules')) ?></p>
      </form>
    </div>
  </div>
</section>
