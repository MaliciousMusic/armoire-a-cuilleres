<?php
/** « Nous trouver » : un cadre qui contient la fiche de la boutique (collée en haut) et le plan Google.
 * @var string $l */
$s = conf('shop');
$q = rawurlencode('L\'Armoire à cuillères, ' . shop_address_line());
$hl = ['zh' => 'zh-CN'][$l] ?? $l;
$embed = "https://www.google.com/maps?q=$q&hl=$hl&z=17&output=embed";
?>
<div class="wrap">
  <div class="gmap">
    <div class="mapcard">
      <div class="mapcard__id">
        <h3 class="mapcard__t">L’Armoire à Cuillères</h3>
        <p class="mapcard__addr"><?= e($s['street']) ?>, <?= e($s['postal'] . ' ' . $s['city']) ?></p>
        <p class="status" data-status hidden><span data-status-text></span></p>
      </div>
      <div class="mapcard__more">
        <p class="mapcard__k"><?= e(t('access.from')) ?></p>
        <ul class="mapcard__routes" role="list">
<?php foreach (walking_routes() as $r): ?>
          <li><span><?= e(t('lm.' . $r['id'])) ?></span><b><?= e(t('access.walk', ['min' => $r['min']])) ?></b></li>
<?php endforeach ?>
        </ul>
        <ul class="mapcard__info" role="list">
          <li><?= e(t('access.tram')) ?></li>
          <li><?= e(t('access.parking')) ?></li>
        </ul>
      </div>
      <a class="choc choc--dark choc--sm mapcard__go" href="<?= e($s['maps_url']) ?>" rel="noopener" target="_blank"><span><?= e(t('access.open')) ?></span></a>
    </div>
    <div class="gmap__frame">
      <iframe src="<?= e($embed) ?>" title="<?= e(t('access.map')) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    </div>
  </div>
</div>
