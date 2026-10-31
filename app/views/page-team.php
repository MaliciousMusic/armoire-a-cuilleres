<?php /** L'équipe : portraits. @var string $l */ ?>
<section class="phead page-team">
  <div class="wrap">
    <nav class="crumbs" aria-label="<?= e(t('nav.crumbs')) ?>"><a href="<?= e(url('home', $l)) ?>"><?= e(t('nav.home')) ?></a> <span aria-hidden="true">/</span> <span aria-current="page"><?= e(t('nav.team')) ?></span></nav>
    <?= view('partials/head', ['num' => '01', 'kicker' => t('team.kicker'), 'title' => t('team.title'), 'id' => 'team-title', 'lead' => t('team.lead'), 'tag' => 'h1']) ?>
    <ul class="team__list" role="list">
<?php foreach (team() as $m): ?>
      <li class="person person--<?= e($m['tint']) ?> reveal">
        <div class="person__photo">
<?php if ($m['photo']): ?>
          <img src="/assets/img/equipe/<?= e($m['photo']) ?>" alt="<?= e($m['name']) ?>" width="600" height="750" loading="lazy" decoding="async">
<?php else: ?>
          <span class="person__spoon" aria-hidden="true"></span>
<?php endif ?>
          <span class="person__role"><?= e(tr($m['role'], $l)) ?></span>
        </div>
        <h2 class="person__name"><?= e($m['name']) ?></h2>
<?php if ($m['bio']): ?>
        <p class="person__bio"><?= e(tr($m['bio'], $l)) ?></p>
<?php endif ?>
      </li>
<?php endforeach ?>
    </ul>
  </div>
</section>
