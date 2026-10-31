<?php
/* L'Armoire à Cuillères — point d'entrée unique (le code vit dans ../app, hors du dossier public). */
declare(strict_types=1);

// Serveur de développement PHP (php -S) : il sert lui-même les fichiers statiques
if (PHP_SAPI === 'cli-server') {
    $f = __DIR__ . rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    if (is_file($f) && !str_ends_with($f, '.php')) return false;
}

$app = is_file(__DIR__ . '/../app/bootstrap.php') ? __DIR__ . '/../app' : __DIR__ . '/app';
require $app . '/bootstrap.php';
require $app . '/routes.php';
route();
