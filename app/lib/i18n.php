<?php
/* Langues : le français est la source ; anglais, allemand, espagnol, chinois (simplifié). */
declare(strict_types=1);

const LANGS = [
    'fr' => ['name' => 'Français', 'short' => 'FR', 'locale' => 'fr_FR', 'hreflang' => 'fr'],
    'en' => ['name' => 'English', 'short' => 'EN', 'locale' => 'en_GB', 'hreflang' => 'en'],
    'de' => ['name' => 'Deutsch', 'short' => 'DE', 'locale' => 'de_DE', 'hreflang' => 'de'],
    'es' => ['name' => 'Español', 'short' => 'ES', 'locale' => 'es_ES', 'hreflang' => 'es'],
    'zh' => ['name' => '中文', 'short' => '中文', 'locale' => 'zh_CN', 'hreflang' => 'zh-Hans'],
];

/** Les pages et leur adresse dans chaque langue */
const SLUGS = [
    'home' => ['fr' => '', 'en' => '', 'de' => '', 'es' => '', 'zh' => ''],
    'order' => ['fr' => 'commander-un-gateau', 'en' => 'order-a-cake', 'de' => 'torte-bestellen', 'es' => 'encargar-tarta', 'zh' => 'dingzhi-dangao'],
    'pro' => ['fr' => 'prestations-pro', 'en' => 'corporate-catering', 'de' => 'firmenkunden', 'es' => 'empresas', 'zh' => 'qiye-fuwu'],
    'loyalty' => ['fr' => 'fidelite', 'en' => 'loyalty-card', 'de' => 'treuekarte', 'es' => 'tarjeta-fidelidad', 'zh' => 'huiyuanka'],
    'team' => ['fr' => 'equipe', 'en' => 'team', 'de' => 'team', 'es' => 'equipo', 'zh' => 'tuandui'],
    'access' => ['fr' => 'nous-trouver', 'en' => 'find-us', 'de' => 'anfahrt', 'es' => 'como-llegar', 'zh' => 'jiaotong'],
    'contact' => ['fr' => 'contact', 'en' => 'contact', 'de' => 'kontakt', 'es' => 'contacto', 'zh' => 'lianxi'],
    'track' => ['fr' => 'suivi', 'en' => 'order-status', 'de' => 'bestellstatus', 'es' => 'seguimiento', 'zh' => 'dingdan'],
    'legal' => ['fr' => 'mentions-legales', 'en' => 'legal-notice', 'de' => 'impressum', 'es' => 'aviso-legal', 'zh' => 'falv-shengming'],
];

function lang(?string $set = null): string
{
    static $l = 'fr';
    if ($set !== null && isset(LANGS[$set])) $l = $set;
    return $l;
}

/** Traduction d'une clé de l'interface ; {var} remplacés. Repli sur le français. */
function t(string $key, array $vars = []): string
{
    static $dict = [];
    $l = lang();
    if (!isset($dict[$l])) $dict[$l] = is_file(APP_DIR . "/lang/$l.php") ? require APP_DIR . "/lang/$l.php" : [];
    if (!isset($dict['fr'])) $dict['fr'] = require APP_DIR . '/lang/fr.php';
    $s = $dict[$l][$key] ?? $dict['fr'][$key] ?? $key;
    if ($vars) $s = preg_replace_callback('/\{(\w+)\}/', fn($m) => array_key_exists($m[1], $vars) ? (string) $vars[$m[1]] : $m[0], $s);
    return $s;
}

/** Valeur traduisible stockée en base : {"fr": "...", "en": "..."} ou simple texte */
function tr(mixed $v, ?string $l = null): string
{
    $l ??= lang();
    if (is_string($v)) {
        $d = json_decode($v, true);
        if (!is_array($d)) return $v;
        $v = $d;
    }
    if (!is_array($v)) return '';
    return (string) (($v[$l] ?? '') !== '' ? $v[$l] : ($v['fr'] ?? ''));
}

/** Adresse d'une page : url('pro') → /fr/prestations-pro/ */
function url(string $page = 'home', ?string $l = null, bool $abs = false): string
{
    $l ??= lang();
    $slug = SLUGS[$page][$l] ?? '';
    $p = '/' . $l . '/' . ($slug !== '' ? $slug . '/' : '');
    return ($abs ? config()['base_url'] : '') . $p;
}

/** Langue préférée du navigateur parmi les nôtres */
function negotiate_lang(): string
{
    $h = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    $best = 'fr';
    $bq = 0.0;
    foreach (explode(',', $h) as $part) {
        $bits = explode(';q=', trim($part));
        $code = substr(trim($bits[0]), 0, 2);
        $qv = isset($bits[1]) ? (float) $bits[1] : 1.0;
        if (isset(LANGS[$code]) && $qv > $bq) { $best = $code; $bq = $qv; }
    }
    return $best;
}
