<?php /** Suivi d'un ticket par le client (page privée). @var string $l @var ?array $ticket */
$ticket = $ticket ?? null;
$steps = ['nouveau', 'en_cours', 'confirme', 'pret', 'termine'];
?>
<section class="sec sec--egg trackpage">
  <div class="wrap wrap--narrow">
    <h1 class="h2"><?= e(t('track.title')) ?></h1>
<?php if (!$ticket): ?>
    <p class="lead"><?= e(t('track.none', ['phone' => conf('shop')['phone']])) ?></p>
<?php else:
    $p = ticket_public($ticket);
    $closed = in_array($p['status'], ['termine', 'annule'], true); ?>
    <div data-track="<?= e($ticket['token']) ?>">
      <p class="kicker"><?= e(t('track.ref', ['ref' => $p['ref']])) ?> · <?= e(t('type.' . $p['type'])) ?></p>
      <h2 class="h3"><?= e(t('track.status')) ?></h2>
      <p class="spill spill--<?= e($p['status']) ?>" data-status-pill><?= e(t('status.' . $p['status'])) ?></p>
      <ol class="progress" data-progress>
<?php $cur = array_search($p['status'] === 'devis' ? 'en_cours' : $p['status'], $steps, true);
      foreach ($steps as $k => $st): ?>
        <li class="<?= $cur !== false && $k <= $cur ? 'done' : '' ?>"><?= e(t("status.$st")) ?></li>
<?php endforeach ?>
      </ol>
      <h2 class="h3"><?= e(t('track.details')) ?></h2>
      <dl class="details">
        <div><dt><?= e(t('form.name')) ?></dt><dd><?= e($p['name']) ?></dd></div>
<?php if ($p['date']): ?><div><dt><?= e(t('form.date')) ?></dt><dd><?= e($p['date']) ?></dd></div><?php endif ?>
<?php if ($p['guests']): ?><div><dt><?= e(t('form.guests')) ?></dt><dd><?= (int) $p['guests'] ?></dd></div><?php endif ?>
<?php if ($p['product']): ?><div><dt><?= e(t('form.product')) ?></dt><dd><?= e($p['product']) ?></dd></div><?php endif ?>
<?php if ($p['message']): ?><div><dt><?= e(t('form.message')) ?></dt><dd class="pre"><?= e($p['message']) ?></dd></div><?php endif ?>
      </dl>
      <h2 class="h3"><?= e(t('track.thread')) ?></h2>
      <ol class="thread" data-thread aria-live="polite">
<?php if (!$p['messages']): ?>
        <li class="thread__empty"><?= e(t('track.empty')) ?></li>
<?php endif ?>
<?php foreach ($p['messages'] as $m): ?>
<?php if ($m['author'] === 'system'): ?>
        <li class="msg msg--system"><?= e(t('track.status.changed', ['status' => t('status.' . substr($m['body'], 7))])) ?></li>
<?php else: ?>
        <li class="msg msg--<?= $m['author'] === 'client' ? 'me' : 'team' ?>"><span class="msg__by"><?= e($m['author'] === 'client' ? t('track.you') : ($m['by'] ?: t('track.team'))) ?></span><p class="pre"><?= e($m['body']) ?></p><time datetime="<?= date('c', $m['at']) ?>"><?= e(date('d/m H:i', $m['at'])) ?></time></li>
<?php endif ?>
<?php endforeach ?>
      </ol>
<?php if (!$closed): ?>
      <form class="form" action="/api/ticket/reply" method="post" data-reply-form>
        <input type="hidden" name="t" value="<?= e($ticket['token']) ?>">
        <input type="hidden" name="lang" value="<?= e($l) ?>">
        <div class="field">
          <label for="reply"><?= e(t('track.reply')) ?></label>
          <textarea id="reply" name="message" rows="3" required minlength="2" maxlength="3000"></textarea>
        </div>
        <div class="form__msg" role="alert" data-form-msg hidden></div>
        <button class="choc choc--dark" type="submit"><span><?= e(t('track.send')) ?></span></button>
      </form>
<?php else: ?>
      <p><?= e(t('track.closed')) ?></p>
<?php endif ?>
      <p class="small"><?= e(t('track.live')) ?> <?= e(t('track.private')) ?></p>
    </div>
<?php endif ?>
  </div>
</section>
