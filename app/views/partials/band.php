<?php
/** Bandeau en vague : un ruban ondulé FIXE qui fait la jonction entre deux rubriques
 *  (au-dessus : la couleur de la rubrique d'avant ; en dessous : celle d'après) ; le texte court dessus,
 *  de droite à gauche, sans s'arrêter. Le chemin est recalculé à la largeur de l'écran (celui-ci sert sans JavaScript).
 * @var string $words @var string $tone couleur du ruban @var string $above @var string $below
 *  ($above = 'none' : le haut est transparent et le bandeau remonte sur la rubrique d'avant) */
static $n = 0;
$n++;
$above = $above ?? 'coquille';
$below = $below ?? 'coquille';
$list = array_map('trim', explode(',', $words));
$unit = implode('  ✦  ', $list) . '  ✦  ';
// vague de départ pour 1440 × 200
$pts = [];
for ($x = -640; $x <= 2080; $x += 20) $pts[] = $x . ' ' . round(100 + 34 * sin(2 * M_PI * $x / 640), 1);
$line = 'M' . implode('L', $pts);
$fill = $line . 'L2080 0L-640 0Z';
$fillBelow = $line . 'L2080 200L-640 200Z';
?>
<div class="wave wave--<?= e($tone) ?> wave--a-<?= e($above) ?> wave--b-<?= e($below) ?>" data-wave>
  <p class="sr-only"><?= e(implode(' · ', $list)) ?></p>
  <svg class="wave__svg" viewBox="0 0 1440 200" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <path class="wave__above" d="<?= $fill ?>"/>
    <path class="wave__below" d="<?= $fillBelow ?>"/>
    <path id="wave-<?= $n ?>" class="wave__ribbon" d="<?= $line ?>"/>
    <text class="wave__text" dominant-baseline="central"><textPath href="#wave-<?= $n ?>" startOffset="0" data-wave-text><?= e(str_repeat($unit, 6)) ?></textPath></text>
  </svg>
  <template data-wave-unit><?= e($unit) ?></template>
</div>
