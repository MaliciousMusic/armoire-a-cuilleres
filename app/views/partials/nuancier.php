<?php
/** Le nuancier des grands crus : des carrés de chocolat moulés à la couleur de chaque cru, alignés à l'horizontale
 *  du plus doux au plus intense (repris de l'ancienne appli), et la fiche du cru touché.
 * @var array $crus @var string $l @var callable $alIcons */
if (!$crus) return;
// la cuillère du logo, en relief au milieu du carré
$f2 = fn($n) => round($n, 2);
$c = M_SQRT1_2;
[$cx, $cy, $rx, $ry] = [25.4, 25.4, 6, 8.4];
$pa = [$cx - $rx * $c, $cy + $rx * $c];
$pb = [$cx + $rx * $c, $cy - $rx * $c];
$spoon = 'M' . $f2($pa[0]) . ' ' . $f2($pa[1]) . "A$rx $ry -45 1 0 " . $f2($pb[0]) . ' ' . $f2($pb[1]) . "A$rx $ry -45 1 0 " . $f2($pa[0]) . ' ' . $f2($pa[1]) . 'Z';
$A = [30.4, 30.4];
$B = [44.6, 44.6];
$q = fn($P, $w, $sg) => $f2($P[0] + (-$c * $w * $sg) / 2) . ' ' . $f2($P[1] + ($c * $w * $sg) / 2);
$spoon .= 'M' . $q($A, 2.4, 1) . 'L' . $q($B, 4, 1) . 'A2 2 0 0 0 ' . $q($B, 4, -1) . 'L' . $q($A, 2.4, -1) . 'A1.2 1.2 0 0 0 ' . $q($A, 2.4, 1) . 'Z';
$square = fn(string $C) => '<svg class="carre__choc" viewBox="0 0 64 64" aria-hidden="true" focusable="false">'
    . '<rect x=".8" y=".8" width="62.4" height="62.4" rx="8.6" fill="' . $C . '"/><g clip-path="url(#nu-clip)">'
    . '<path d="M.8 .8H63.2L51 13H13Z" fill="#fff" opacity=".3"/><path d="M63.2 .8V63.2L51 51V13Z" fill="#000" opacity=".16"/>'
    . '<path d="M63.2 63.2H.8L13 51H51Z" fill="#000" opacity=".34"/><path d="M.8 63.2V.8L13 13V51Z" fill="#fff" opacity=".13"/></g>'
    . '<rect x="1.2" y="1.2" width="61.6" height="61.6" rx="8.2" fill="none" stroke="#000" stroke-opacity=".2" stroke-width=".8"/>'
    . '<rect x="13" y="13" width="38" height="38" rx="3.5" fill="' . $C . '"/>'
    . '<path d="' . $spoon . '" fill="#000" opacity=".42" transform="translate(1 1)"/><path d="' . $spoon . '" fill="#fff" opacity=".38" transform="translate(-.8 -.8)"/>'
    . '<path d="' . $spoon . '" fill="' . $C . '"/><rect x="13" y="13" width="38" height="38" rx="3.5" fill="url(#nu-bombe)"/>'
    . '<path d="M17 18.4C23 15.8 33 15.4 41 16.8" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" opacity=".3"/></svg>';
$groups = [t('crus.soft'), t('crus.balanced'), t('crus.intense')];
// le cru montré au départ : le premier noir équilibré (comme l'ancienne appli : Vanuari Noir)
$def = 0;
foreach ($crus as $k => $cr) if ($cr['group'] === 1 && $cr['pc'] >= 60) { $def = $k; break; }
$d = $crus[$def];
$pos = fn($pc) => max(3, min(97, ($pc - 30) / 70 * 100));
?>
<div class="nuancier" data-nuancier>
  <svg width="0" height="0" class="nu-defs" aria-hidden="true" focusable="false"><defs>
    <clipPath id="nu-clip"><rect x=".8" y=".8" width="62.4" height="62.4" rx="8.6"/></clipPath>
    <radialGradient id="nu-bombe" cx=".32" cy=".26" r=".9"><stop offset="0" stop-color="#fff" stop-opacity=".22"/><stop offset=".48" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".22"/></radialGradient>
  </defs></svg>
  <p class="nu-pick"><?= e(t('crus.pick')) ?></p>
  <div class="nu-groups" role="radiogroup" aria-label="<?= e(t('menu.title')) ?>">
<?php foreach ([0, 1, 2] as $g): $list = array_filter($crus, fn($x) => $x['group'] === $g); if (!$list) continue; ?>
    <div class="nu-group">
      <p class="nu-group__t"><?= e($groups[$g]) ?></p>
      <div class="nu-row">
<?php foreach ($list as $k => $cr): ?>
        <button class="carre" type="button" role="radio" aria-checked="<?= $k === $def ? 'true' : 'false' ?>" data-al="<?= e($cr['allergens']) ?>"
          data-cru="<?= e(json_encode(['name' => $cr['name'], 'pc' => $cr['pc'], 'notes' => $cr['notes'], 'bio' => $cr['bio'], 'color' => $cr['color'], 'al' => $alIcons($cr['allergens'])], JSON_UNESCAPED_UNICODE)) ?>">
          <span class="carre__body"><?= $square($cr['color']) ?></span>
          <b><?= (int) $cr['pc'] ?>&thinsp;%</b>
          <span class="carre__nom"><?= e($cr['name']) ?></span>
          <span class="sr-only"><?= e($cr['notes']) ?></span>
        </button>
<?php endforeach ?>
      </div>
    </div>
<?php endforeach ?>
  </div>
  <div class="nu-fiche" aria-live="polite">
    <div class="nu-fiche__top">
      <p class="nu-fiche__nom" data-f="name"><?= e($d['name']) ?></p>
      <p class="nu-fiche__pc" data-f="pc"><?= e(t('crus.cocoa', ['n' => $d['pc']])) ?></p>
      <span class="tag tag--bio" data-f="bio"<?= $d['bio'] ? '' : ' hidden' ?>><?= e(t('menu.tag.bio')) ?></span>
    </div>
    <p class="nu-fiche__notes" data-f="notes"><?= e($d['notes']) ?></p>
    <div class="nu-meter" aria-hidden="true">
      <div class="nu-meter__bar"><span class="nu-meter__fill" data-f="fill" data-pc="<?= (int) $d['pc'] ?>" data-color="<?= e($d['color']) ?>"></span></div>
      <div class="nu-meter__scale"><span><?= e(t('crus.soft')) ?></span><span>50 %</span><span><?= e(t('crus.intense')) ?></span></div>
    </div>
    <div data-f="al"><?= $alIcons($d['allergens']) ?></div>
  </div>
  <script type="application/json" data-cocoa-label><?= json_encode(t('crus.cocoa'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
</div>
