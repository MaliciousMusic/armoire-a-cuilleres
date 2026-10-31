<?php
/** Formulaire qui crée un ticket. @var string $l @var string $mode order|pro|question @var string $id */
$f = fn(string $name) => $id . '-' . $name;
$req = '<span class="req">' . e(t('form.required')) . '</span>';
$opt = '<span class="opt">' . e(t('form.optional')) . '</span>';
?>
<form class="form" id="<?= e($id) ?>" action="/api/ticket" method="post" data-ticket-form data-lead="<?= (int) conf('orders')['lead_hours'] ?>">
  <input type="hidden" name="lang" value="<?= e($l) ?>">
  <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
<?php if ($mode === 'order'): ?>
  <fieldset class="field seg-field">
    <legend><?= e(t('form.who')) ?></legend>
    <div class="seg">
      <label><input type="radio" name="type" value="gateau" checked data-who><span><?= e(t('form.who.private')) ?></span></label>
      <label><input type="radio" name="type" value="gateau_pro" data-who><span><?= e(t('form.who.pro')) ?></span></label>
    </div>
  </fieldset>
<?php else: ?>
  <input type="hidden" name="type" value="<?= $mode === 'pro' ? 'pro' : 'question' ?>">
<?php endif ?>
  <div class="form__row">
    <div class="field">
      <label for="<?= $f('name') ?>"><?= e(t('form.name')) ?> <?= $req ?></label>
      <input id="<?= $f('name') ?>" name="name" type="text" required minlength="2" maxlength="120" autocomplete="name">
    </div>
<?php if ($mode !== 'question'): ?>
    <div class="field" <?= $mode === 'order' ? 'data-pro-only' : '' ?>>
      <label for="<?= $f('company') ?>"><?= e(t('form.company')) ?> <?= $mode === 'pro' ? $req : '' ?></label>
      <input id="<?= $f('company') ?>" name="company" type="text" maxlength="160" autocomplete="organization"<?= $mode === 'pro' ? ' required' : '' ?>>
    </div>
<?php endif ?>
  </div>
  <div class="form__row">
    <div class="field">
      <label for="<?= $f('email') ?>"><?= e(t('form.email')) ?></label>
      <input id="<?= $f('email') ?>" name="email" type="email" maxlength="190" autocomplete="email" inputmode="email">
    </div>
    <div class="field">
      <label for="<?= $f('phone') ?>"><?= e(t('form.phone')) ?></label>
      <input id="<?= $f('phone') ?>" name="phone" type="tel" maxlength="40" autocomplete="tel" inputmode="tel">
    </div>
  </div>
  <p class="hint"><?= e(t('form.contact.hint')) ?></p>
<?php if ($mode !== 'question'): ?>
  <div class="form__row">
    <div class="field">
      <label for="<?= $f('date') ?>"><?= e(t('form.date')) ?> <?= $req ?></label>
      <input id="<?= $f('date') ?>" name="date" type="date" required data-min-lead>
    </div>
    <div class="field">
      <label for="<?= $f('guests') ?>"><?= e(t('form.guests')) ?> <?= $opt ?></label>
      <input id="<?= $f('guests') ?>" name="guests" type="number" min="1" max="2000" inputmode="numeric">
    </div>
  </div>
<?php endif ?>
<?php if ($mode === 'order'): ?>
  <div class="field">
    <label for="<?= $f('product') ?>"><?= e(t('form.product')) ?> <?= $opt ?></label>
    <input id="<?= $f('product') ?>" name="product" type="text" maxlength="160" placeholder="<?= e(t('form.product.ph')) ?>">
  </div>
<?php endif ?>
  <div class="field">
    <label for="<?= $f('message') ?>"><?= e(t('form.message')) ?> <?= $mode === 'order' ? $opt : $req ?></label>
    <textarea id="<?= $f('message') ?>" name="message" rows="<?= $mode === 'question' ? 3 : 4 ?>" maxlength="3000"<?= $mode === 'order' ? '' : ' required minlength="5"' ?> placeholder="<?= e($mode === 'question' ? '' : t('form.message.ph')) ?>"></textarea>
  </div>
  <label class="check">
    <input type="checkbox" name="consent" value="1" required>
    <span><?= e(t('form.consent')) ?> <a href="<?= e(url('legal', $l)) ?>"><?= e(t('form.privacy')) ?></a></span>
  </label>
  <div class="form__msg" role="alert" aria-live="assertive" data-form-msg hidden></div>
  <button class="choc choc--dark choc--full" type="submit"><span><?= e($mode === 'pro' ? t('pro.cta') : t('form.send')) ?></span></button>
</form>
