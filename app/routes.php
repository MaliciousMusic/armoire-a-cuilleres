<?php
/* Toutes les adresses du site. Appelé par www/index.php. */
declare(strict_types=1);

function route(): void
{
    $path = request_path();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // ---------- Administration
    if ($path === '/admin' ) redirect('/admin/', 301);
    if (str_starts_with($path, '/admin/')) {
        require APP_DIR . '/admin.php';
        admin_route(substr($path, 6), $method);
        return;
    }

    // ---------- API publique
    if (str_starts_with($path, '/api/')) {
        security_headers();
        api_route(substr($path, 4), $method);
        return;
    }

    // ---------- Fichiers générés
    if ($path === '/sitemap.xml') text_out(sitemap_xml(), 'application/xml');
    if ($path === '/llms.txt') text_out(llms_txt(false), 'text/plain');
    if ($path === '/llms-full.txt') text_out(llms_txt(true), 'text/plain');

    // ---------- Accueil sans langue : la langue du navigateur
    if ($path === '/' || $path === '/index.php') {
        header('Vary: Accept-Language');
        redirect(url('home', negotiate_lang()), 302);
    }

    // ---------- Pages /{langue}/{page}/
    if (preg_match('#^/(fr|en|de|es|zh)(/.*)?$#', $path, $m)) {
        $l = lang($m[1]);
        $rest = trim($m[2] ?? '', '/');
        if (!str_ends_with($path, '/')) redirect($path . '/' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''), 301);
        $page = null;
        foreach (SLUGS as $p => $slugs) if ($slugs[$l] === $rest) $page = $p;
        if ($page === 'loyalty' && isset($_GET['c'])) {
            send_html(render_page('card', $l, false, ['card' => loyalty_by_token((string) $_GET['c'])]), 200, true);
        }
        if ($page === 'track') {
            send_html(render_page('track', $l, false, ['ticket' => ticket_by_token((string) ($_GET['t'] ?? ''))]), 200, true);
        }
        if ($page !== null) send_html(render_page($page, $l));
        not_found($l);
    }

    // Anciennes adresses du WordPress : vers l'accueil (garde le référencement acquis)
    if (preg_match('#^/(carte|la-carte|brunch|contact|boutique|notre-histoire|chocolat|commandes?)(/|$)#i', $path, $m)) {
        $w = strtolower($m[1]);
        if ($w === 'contact') redirect(url('contact', 'fr'), 301);
        $anchor = ['carte' => '#carte', 'la-carte' => '#carte', 'brunch' => '#brunch'][$w] ?? '';
        redirect(str_starts_with($w, 'commande') ? url('order', 'fr') : url('home', 'fr') . $anchor, 301);
    }

    not_found(negotiate_lang());
}

function redirect(string $to, int $code = 302): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function text_out(string $s, string $type): never
{
    header("Content-Type: $type; charset=utf-8");
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    echo $s;
    exit;
}

function not_found(string $l): never
{
    lang($l);
    send_html(render_page('404', $l, false), 404);
}

/* ---------------------------------------------------------------- API publique */

function api_route(string $path, string $method): void
{
    $in = body();
    $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    lang(isset(LANGS[$in['lang'] ?? '']) ? $in['lang'] : (isset(LANGS[$_GET['lang'] ?? '']) ? $_GET['lang'] : 'fr'));
    $l = lang();

    if ($method === 'POST') {
        if (!same_origin()) json_error('Origine refusée', 403);
        if (($in['website'] ?? '') !== '') json_out(['ok' => true]); // pot de miel : un robot a rempli le champ caché
    }

    // Nouveau ticket (commande, devis, question)
    if ($path === '/ticket' && $method === 'POST') {
        if (!rate_limit('ticket', 6, 3600)) api_fail($wantsJson, ['_' => 'form.err.rate'], 429);
        $r = ticket_create($in, $l);
        if (isset($r['errors'])) api_fail($wantsJson, $r['errors'], 422);
        $url = ticket_url($r['ticket']);
        if (!$wantsJson) redirect($url, 303);
        json_out(['ok' => true, 'ref' => $r['ticket']['ref'], 'url' => $url]);
    }

    // État d'un ticket (la page de suivi se met à jour toute seule)
    if ($path === '/ticket' && $method === 'GET') {
        $t = ticket_by_token((string) ($_GET['t'] ?? ''));
        if (!$t) json_error('Introuvable', 404);
        if ($t['unread_client']) q('UPDATE tickets SET unread_client = 0 WHERE id = ?', [$t['id']]);
        json_out(['ok' => true, 'ticket' => ticket_public($t)]);
    }

    // Le client répond sur son ticket
    if ($path === '/ticket/reply' && $method === 'POST') {
        if (!rate_limit('reply', 20, 3600)) api_fail($wantsJson, ['_' => 'form.err.rate'], 429);
        $t = ticket_by_token((string) ($in['t'] ?? ''));
        if (!$t) json_error('Introuvable', 404);
        if (in_array($t['status'], ['termine', 'annule'], true)) json_error(t('track.closed'), 409);
        $msg = clean_text($in['message'] ?? '', 3000);
        if (mb_strlen($msg) < 2) api_fail($wantsJson, ['message' => 'form.err.message'], 422);
        ticket_client_reply($t, $msg);
        if (!$wantsJson) redirect(ticket_url($t), 303);
        json_out(['ok' => true, 'ticket' => ticket_public(ticket_get((int) $t['id']))]);
    }

    // Nouvelle carte de fidélité
    if ($path === '/loyalty' && $method === 'POST') {
        if (!rate_limit('loyalty', 5, 3600)) api_fail($wantsJson, ['_' => 'form.err.rate'], 429);
        $first = clean_line($in['first_name'] ?? '', 60);
        $email = clean_email($in['email'] ?? '');
        $err = [];
        if (mb_strlen($first) < 2) $err['first_name'] = 'form.err.name';
        if (($in['email'] ?? '') !== '' && !$email) $err['email'] = 'form.err.email';
        if (empty($in['consent'])) $err['consent'] = 'form.err.consent';
        if ($err) api_fail($wantsJson, $err, 422);
        $c = loyalty_create($first, $email);
        $url = url('loyalty', $l) . '?c=' . $c['token'];
        if (!$wantsJson) redirect($url, 303);
        json_out(['ok' => true, 'token' => $c['token'], 'url' => $url]);
    }

    // État d'une carte (pour la rafraîchir après un scan)
    if ($path === '/loyalty' && $method === 'GET') {
        if (!rate_limit('loyalty-get', 120, 600)) json_error('Trop de requêtes', 429);
        $c = loyalty_by_token((string) ($_GET['c'] ?? ''));
        if (!$c) json_error('Introuvable', 404);
        json_out(['ok' => true, 'card' => loyalty_public($c)]);
    }

    json_error('Adresse inconnue', 404);
}

/** Échec d'un formulaire public : JSON (avec JavaScript) ou petite page (sans) */
function api_fail(bool $json, array $errors, int $code): never
{
    $msgs = array_map(fn($k) => t($k, ['phone' => conf('shop')['phone']]), $errors);
    if ($json) json_out(['ok' => false, 'errors' => $msgs], $code);
    $l = lang();
    $list = implode('', array_map(fn($m) => '<li>' . e($m) . '</li>', $msgs));
    send_html('<!doctype html><html lang="' . e($l) . '"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e(t('form.err.title')) . '</title>'
        . '<link rel="stylesheet" href="' . e(asset('assets/css/site.css')) . '"><body><main class="sec sec--egg"><div class="wrap wrap--narrow"><h1 class="h2">' . e(t('form.err.title')) . '</h1><ul class="errors">' . $list
        . '</ul><p><a class="choc choc--dark" href="' . e(url('order', $l)) . '"><span>' . e(t('nav.order')) . '</span></a></p></div></main></body></html>', $code, true);
}
