<?php
/* L'Armoire à Cuillères — démarrage commun (site public, API, administration). */
declare(strict_types=1);

const APP_DIR = __DIR__;
const DATA_DIR = __DIR__ . '/data';

if (PHP_VERSION_ID < 80200) {
    http_response_code(500);
    exit('PHP 8.2 ou plus récent est nécessaire (OVH : fichier .ovhconfig, app.engine.version=8.3).');
}

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Paris');

foreach (['core', 'security', 'i18n', 'content', 'mail', 'tickets', 'loyalty', 'render'] as $f) {
    require APP_DIR . "/lib/$f.php";
}

$cfg = config();
ini_set('display_errors', $cfg['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', DATA_DIR . '/php-errors.log');
error_reporting(E_ALL);
set_exception_handler(function (Throwable $e) {
    error_log((string) $e);
    if (!headers_sent()) http_response_code(500);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_starts_with(request_path(), '/api/') || str_starts_with(request_path(), '/admin/api/')) {
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => config()['debug'] ? $e->getMessage() : 'Erreur du serveur']);
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Erreur</title><p style="font:18px system-ui;padding:2rem">Un petit souci technique. Réessayez dans un instant.</p>';
    }
});
