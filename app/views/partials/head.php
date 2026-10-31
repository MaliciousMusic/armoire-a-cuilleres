<?php
/** En-tête de rubrique : numéro, surtitre, titre (la fin après « & », « · » ou « , » passe en italique), chapeau.
 * @var string $num @var string $kicker @var string $title @var string $id @var string $lead */
$t = e($title);
foreach ([' & ', ' · ', ', '] as $sep) {
    if (str_contains($title, $sep)) {
        [$a, $b] = explode($sep, $title, 2);
        $t = e($a) . e(rtrim($sep)) . ' <em>' . e($b) . '</em>';
        break;
    }
}
$tag = $tag ?? 'h2';
?>
<header class="sh reveal">
  <p class="sh__top"><span class="sh__num"><?= e($num) ?></span><span class="sh__kicker"><?= e($kicker) ?></span></p>
  <<?= $tag ?> class="<?= $tag === 'h1' ? 'h1' : 'h2' ?>" id="<?= e($id) ?>"><?= $t ?></<?= $tag ?>>
<?php if (!empty($lead)): ?>
  <p class="lead"><?= e($lead) ?></p>
<?php endif ?>
</header>
