<?php
/* L'espace équipe (/admin) : tickets en direct, fidélité (scanner), carte, équipe, réglages, comptes.
   Deux rôles : « admin » (le gérant : tout) et « staff » (les collaborateurs : tickets, fidélité, disponibilité des articles). */
declare(strict_types=1);

function admin_route(string $path, string $method): void
{
    security_headers(true);
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');

    if (str_starts_with($path, '/api/')) {
        admin_api(substr($path, 4), $method);
        return;
    }
    $hasUsers = (int) qv('SELECT COUNT(*) FROM users') > 0;

    if ($path === '/setup' && $method === 'POST') admin_setup($hasUsers);
    if ($path === '/login' && $method === 'POST') admin_login();
    if ($path === '/logout' && $method === 'POST') {
        if (same_origin() && hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) { audit('deconnexion'); logout_user(); }
        redirect('/admin/', 303);
    }
    if ($path !== '/' && $path !== '/index.php') redirect('/admin/', 302);

    if (!$hasUsers) admin_page('setup');
    if (!current_user()) admin_page('login');
    admin_page('app');
}

function admin_page(string $view, array $vars = []): never
{
    header('Content-Type: text/html; charset=utf-8');
    echo view('admin', ['view' => $view, 'csrf' => csrf_token(), 'user' => current_user()] + $vars);
    exit;
}

function admin_setup(bool $hasUsers): never
{
    if ($hasUsers || !same_origin()) redirect('/admin/', 303);
    if (!rate_limit('setup', 8, 3600)) admin_page('setup', ['error' => 'Trop d’essais. Réessayez dans une heure.']);
    $token = (string) config()['setup_token'];
    $in = $_POST;
    $err = null;
    if (!hash_equals(csrf_token(), (string) ($in['csrf'] ?? ''))) $err = 'Session expirée, réessayez.';
    elseif ($token === '' || str_starts_with($token, 'CHANGER') || !hash_equals($token, (string) ($in['setup_token'] ?? ''))) $err = 'Code d’installation incorrect (voir app/config.php).';
    elseif (!($email = clean_email($in['email'] ?? ''))) $err = 'E-mail invalide.';
    elseif (mb_strlen($name = clean_line($in['name'] ?? '', 80)) < 2) $err = 'Indiquez votre nom.';
    elseif (!password_ok($pass = (string) ($in['password'] ?? ''))) $err = 'Mot de passe : 10 caractères au moins.';
    if ($err) admin_page('setup', ['error' => $err]);
    $id = insert('users', ['email' => mb_strtolower($email), 'name' => $name, 'pass_hash' => hash_password($pass), 'role' => 'admin', 'active' => 1, 'created_at' => now()]);
    login_user(q1('SELECT * FROM users WHERE id = ?', [$id]));
    audit('installation', 'Premier compte administrateur : ' . $email);
    redirect('/admin/', 303);
}

function admin_login(): never
{
    $in = $_POST;
    $email = mb_strtolower(clean_line($in['email'] ?? '', 190));
    $fail = fn(string $m) => admin_page('login', ['error' => $m, 'email' => $email]);
    if (!same_origin() || !hash_equals(csrf_token(), (string) ($in['csrf'] ?? ''))) $fail('Session expirée, réessayez.');
    if (!rate_limit('login-ip', 10, 900) || !rate_limit('login-mail', 6, 900, $email)) $fail('Trop d’essais : patientez un quart d’heure.');
    $u = q1('SELECT * FROM users WHERE email = ? AND active = 1', [$email]);
    // même durée de calcul que le compte existe ou non
    $ok = password_verify((string) ($in['password'] ?? ''), $u['pass_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$RdescudvJCsgt3ub+b+dWRWJTmaaJObG');
    if (!$u || !$ok) { usleep(random_int(150000, 400000)); $fail('E-mail ou mot de passe incorrect.'); }
    if (password_needs_rehash($u['pass_hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT)) q('UPDATE users SET pass_hash = ? WHERE id = ?', [hash_password((string) $in['password']), $u['id']]);
    login_user($u);
    audit('connexion');
    redirect('/admin/', 303);
}

/** Une modification de contenu : on vide le cache des pages publiques */
function content_changed(): void
{
    page_cache_clear();
    set_setting('content_updated', now());
}

/* ---------------------------------------------------------------- API de l'administration */

function admin_api(string $path, string $method): void
{
    $u = require_user();
    if ($method === 'POST') csrf_check();
    $in = body();
    $isAdmin = $u['role'] === 'admin';
    $id = preg_match('#/(\d+)(/|$)#', $path, $m) ? (int) $m[1] : 0;
    $route = $method . ' ' . preg_replace('#/\d+#', '/:id', $path);

    switch ($route) {
        case 'GET /me':
            json_out(['ok' => true, 'user' => $u]);

        // ---------- Tableau de bord et tickets en direct
        case 'GET /poll':
            $since = (int) ($_GET['since'] ?? 0);
            json_out(['ok' => true, 'now' => now(),
                'unread' => (int) qv('SELECT COUNT(*) FROM tickets WHERE unread_staff = 1'),
                'open' => (int) qv("SELECT COUNT(*) FROM tickets WHERE status NOT IN ('termine','annule')"),
                'changed' => (int) qv('SELECT COUNT(*) FROM tickets WHERE updated_at > ?', [$since]),
                'stampsToday' => (int) qv("SELECT COUNT(*) FROM loyalty_events WHERE kind = 'stamp' AND created_at >= ?", [strtotime('today')]),
                'cards' => (int) qv('SELECT COUNT(*) FROM loyalty_cards WHERE active = 1'),
            ]);

        case 'GET /tickets':
            $where = [];
            $args = [];
            $st = (string) ($_GET['status'] ?? 'ouverts');
            if ($st === 'ouverts') $where[] = "status NOT IN ('termine','annule')";
            elseif (in_array($st, TICKET_STATUSES, true)) { $where[] = 'status = ?'; $args[] = $st; }
            if (in_array($_GET['type'] ?? '', TICKET_TYPES, true)) { $where[] = 'type = ?'; $args[] = $_GET['type']; }
            if (($qs = clean_line($_GET['q'] ?? '', 80)) !== '') {
                $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ? OR ref LIKE ?)';
                array_push($args, ...array_fill(0, 5, '%' . $qs . '%'));
            }
            $rows = q('SELECT id, ref, type, status, name, company, email, phone, wanted_date, guests, unread_staff, created_at, updated_at FROM tickets'
                . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY unread_staff DESC, updated_at DESC LIMIT 200', $args)->fetchAll();
            json_out(['ok' => true, 'tickets' => $rows]);

        case 'GET /tickets/:id':
            $t = ticket_get($id) ?? json_error('Introuvable', 404);
            q('UPDATE tickets SET unread_staff = 0 WHERE id = ?', [$id]);
            unset($t['token']);
            $t['track_url'] = ticket_url(ticket_get($id));
            json_out(['ok' => true, 'ticket' => $t, 'messages' => ticket_messages($id, true)]);

        case 'POST /tickets/:id':
            $t = ticket_get($id) ?? json_error('Introuvable', 404);
            $status = isset($in['status']) && in_array($in['status'], TICKET_STATUSES, true) ? $in['status'] : null;
            $body = clean_text($in['body'] ?? '', 4000);
            ticket_staff_update($t, $u, $status, $body, !empty($in['internal']));
            json_out(['ok' => true, 'messages' => ticket_messages($id, true), 'ticket' => array_diff_key(ticket_get($id), ['token' => 1])]);

        // ---------- Fidélité
        case 'GET /loyalty':
            $qs = clean_line($_GET['q'] ?? '', 80);
            $rows = $qs === ''
                ? q('SELECT id, code, first_name, email, stamps, rewards, visits, last_stamp_at FROM loyalty_cards WHERE active = 1 ORDER BY COALESCE(last_stamp_at, created_at) DESC LIMIT 40')->fetchAll()
                : q('SELECT id, code, first_name, email, stamps, rewards, visits, last_stamp_at FROM loyalty_cards WHERE active = 1 AND (first_name LIKE ? OR email LIKE ? OR code = ?) ORDER BY first_name LIMIT 40', ['%' . $qs . '%', '%' . $qs . '%', strtoupper($qs)])->fetchAll();
            json_out(['ok' => true, 'cards' => $rows, 'goal' => (int) conf('loyalty')['goal']]);

        case 'POST /loyalty/scan':
            if (!rate_limit('scan:' . $u['id'], 120, 600)) json_error('Trop de scans d’un coup', 429);
            $c = loyalty_find((string) ($in['scan'] ?? '')) ?? json_error('Carte inconnue', 404);
            json_out(['ok' => true, 'card' => loyalty_admin($c)]);

        case 'POST /loyalty/new':
            $first = clean_line($in['first_name'] ?? '', 60);
            if (mb_strlen($first) < 2) json_error('Prénom trop court');
            $c = loyalty_create($first, clean_email($in['email'] ?? ''));
            audit('fidelite', "Nouvelle carte · $first ({$c['code']})");
            json_out(['ok' => true, 'card' => loyalty_admin($c), 'url' => url('loyalty', 'fr', true) . '?c=' . $c['token']]);

        case 'GET /loyalty/:id':
            $c = q1('SELECT * FROM loyalty_cards WHERE id = ?', [$id]) ?? json_error('Introuvable', 404);
            json_out(['ok' => true, 'card' => loyalty_admin($c), 'url' => url('loyalty', 'fr', true) . '?c=' . $c['token']]);

        case 'POST /loyalty/:id/stamp':
            $c = q1('SELECT * FROM loyalty_cards WHERE id = ? AND active = 1', [$id]) ?? json_error('Introuvable', 404);
            $r = loyalty_stamp($c, $u, !empty($in['force']));
            if (isset($r['error'])) json_out(['ok' => false, 'error' => 'recent', 'minutes' => $r['minutes']], 409);
            json_out(['ok' => true, 'card' => loyalty_admin($r['card'])]);

        case 'POST /loyalty/:id/redeem':
            $c = q1('SELECT * FROM loyalty_cards WHERE id = ? AND active = 1', [$id]) ?? json_error('Introuvable', 404);
            $r = loyalty_redeem($c, $u);
            if (isset($r['error'])) json_error('Pas encore assez de tampons', 409);
            json_out(['ok' => true, 'card' => loyalty_admin($r['card'])]);

        case 'POST /loyalty/:id/adjust':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            $c = q1('SELECT * FROM loyalty_cards WHERE id = ?', [$id]) ?? json_error('Introuvable', 404);
            $delta = max(-50, min(50, (int) ($in['delta'] ?? 0)));
            json_out(['ok' => true, 'card' => loyalty_admin(loyalty_adjust($c, $u, $delta)['card'])]);

        case 'POST /loyalty/:id/delete':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            q('UPDATE loyalty_cards SET active = 0, email = NULL WHERE id = ?', [$id]);
            audit('fidelite', "Carte #$id supprimée");
            json_out(['ok' => true]);

        // ---------- La carte (menu)
        case 'GET /menu':
            json_out(['ok' => true, 'menu' => menu(true), 'langs' => array_keys(LANGS)]);

        case 'POST /menu/item/:id/visible':
            q('UPDATE menu_items SET visible = ? WHERE id = ?', [!empty($in['visible']) ? 1 : 0, $id]);
            content_changed();
            audit('carte', "Article #$id " . (!empty($in['visible']) ? 'disponible' : 'masqué'));
            json_out(['ok' => true]);

        case 'POST /menu/section':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            $row = ['title' => tr_json($in['title'] ?? [], 120), 'note' => tr_json($in['note'] ?? [], 300, true), 'price_cents' => cents($in['price'] ?? null), 'visible' => !empty($in['visible']) ? 1 : 0];
            if (json_decode($row['title'], true)['fr'] === '') json_error('Le titre en français est obligatoire');
            if (!empty($in['id'])) update('menu_sections', (int) $in['id'], $row);
            else { $row['position'] = (int) qv('SELECT COALESCE(MAX(position), 0) + 10 FROM menu_sections'); insert('menu_sections', $row); }
            content_changed();
            audit('carte', 'Rubrique : ' . json_decode($row['title'], true)['fr']);
            json_out(['ok' => true, 'menu' => menu(true)]);

        case 'POST /menu/section/:id/delete':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            q('DELETE FROM menu_items WHERE section_id = ?', [$id]);
            q('DELETE FROM menu_sections WHERE id = ?', [$id]);
            content_changed();
            audit('carte', "Rubrique #$id supprimée");
            json_out(['ok' => true, 'menu' => menu(true)]);

        case 'POST /menu/item':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            $sid = (int) ($in['section_id'] ?? 0);
            if (!qv('SELECT 1 FROM menu_sections WHERE id = ?', [$sid])) json_error('Rubrique inconnue');
            $tags = implode(',', array_values(array_intersect(array_map('trim', explode(',', (string) ($in['tags'] ?? ''))), ['bio', 'vegan', 'froid', 'nouveau', 'signature', 'extra'])));
            $row = ['section_id' => $sid, 'name' => tr_json($in['name'] ?? [], 120), 'descr' => tr_json($in['descr'] ?? [], 400, true),
                'price_cents' => cents($in['price'] ?? null), 'is_extra' => str_contains($tags, 'extra') ? 1 : 0, 'tags' => $tags, 'visible' => !empty($in['visible']) ? 1 : 0,
                'allergens' => implode(',', array_values(array_intersect(ALLERGENS, array_map('trim', explode(',', (string) ($in['allergens'] ?? ''))))))];
            if (json_decode($row['name'], true)['fr'] === '') json_error('Le nom en français est obligatoire');
            if (!empty($in['id'])) update('menu_items', (int) $in['id'], $row);
            else { $row['position'] = (int) qv('SELECT COALESCE(MAX(position), 0) + 10 FROM menu_items WHERE section_id = ?', [$sid]); insert('menu_items', $row); }
            content_changed();
            audit('carte', 'Article : ' . json_decode($row['name'], true)['fr']);
            json_out(['ok' => true, 'menu' => menu(true)]);

        case 'POST /menu/item/:id/delete':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            q('DELETE FROM menu_items WHERE id = ?', [$id]);
            content_changed();
            audit('carte', "Article #$id supprimé");
            json_out(['ok' => true, 'menu' => menu(true)]);

        case 'POST /menu/reorder':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            foreach (array_values((array) ($in['sections'] ?? [])) as $i => $sid) q('UPDATE menu_sections SET position = ? WHERE id = ?', [$i * 10, (int) $sid]);
            foreach ((array) ($in['items'] ?? []) as $sid => $ids) {
                foreach (array_values((array) $ids) as $i => $iid) q('UPDATE menu_items SET position = ?, section_id = ? WHERE id = ?', [$i * 10, (int) $sid, (int) $iid]);
            }
            content_changed();
            json_out(['ok' => true, 'menu' => menu(true)]);

        // ---------- L'ardoise du jour (toute l'équipe)
        case 'GET /ardoise':
            json_out(['ok' => true, 'ardoise' => ardoise(), 'allergens' => ALLERGENS]);

        case 'POST /ardoise':
            $items = [];
            foreach (array_slice((array) ($in['items'] ?? []), 0, 24) as $it) {
                $name = json_decode(tr_json($it['name'] ?? [], 80), true);
                if (($name['fr'] ?? '') === '') continue;
                $items[] = ['name' => $name, 'price_cents' => cents($it['price'] ?? null),
                    'allergens' => implode(',', array_values(array_intersect(ALLERGENS, array_map('trim', explode(',', (string) ($it['allergens'] ?? ''))))))];
            }
            set_setting('ardoise', ['visible' => !empty($in['visible']), 'date' => date('Y-m-d'), 'note' => json_decode(tr_json($in['note'] ?? [], 160), true), 'items' => $items]);
            content_changed();
            audit('ardoise', count($items) . ' pâtisserie(s)');
            json_out(['ok' => true, 'ardoise' => ardoise()]);

        // ---------- L'équipe
        case 'GET /team':
            json_out(['ok' => true, 'team' => team(true)]);

        case 'POST /team':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            $tint = in_array($in['tint'] ?? '', ['cacao', 'rose', 'sauge', 'taupe', 'sand'], true) ? $in['tint'] : 'taupe';
            $row = ['name' => clean_line($in['name'] ?? '', 80), 'role' => tr_json($in['role'] ?? [], 120), 'bio' => tr_json($in['bio'] ?? [], 600, true), 'tint' => $tint, 'visible' => !empty($in['visible']) ? 1 : 0];
            if (mb_strlen($row['name']) < 2) json_error('Le prénom est obligatoire');
            if (!empty($in['id'])) update('team', (int) $in['id'], $row);
            else { $row['position'] = (int) qv('SELECT COALESCE(MAX(position), 0) + 10 FROM team'); insert('team', $row); }
            if (isset($in['order']) && is_array($in['order'])) foreach (array_values($in['order']) as $i => $tid) q('UPDATE team SET position = ? WHERE id = ?', [$i * 10, (int) $tid]);
            content_changed();
            audit('equipe', $row['name']);
            json_out(['ok' => true, 'team' => team(true)]);

        case 'POST /team/:id/delete':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            $m = q1('SELECT photo FROM team WHERE id = ?', [$id]);
            if ($m && $m['photo']) @unlink(www_dir() . '/assets/img/equipe/' . basename($m['photo']));
            q('DELETE FROM team WHERE id = ?', [$id]);
            content_changed();
            audit('equipe', "Portrait #$id supprimé");
            json_out(['ok' => true, 'team' => team(true)]);

        case 'POST /team/:id/photo':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            $m = q1('SELECT * FROM team WHERE id = ?', [$id]) ?? json_error('Introuvable', 404);
            $name = save_portrait($_FILES['photo'] ?? null);
            if ($m['photo']) @unlink(www_dir() . '/assets/img/equipe/' . basename($m['photo']));
            q('UPDATE team SET photo = ? WHERE id = ?', [$name, $id]);
            content_changed();
            audit('equipe', "Photo de {$m['name']}");
            json_out(['ok' => true, 'team' => team(true)]);

        // ---------- Réglages
        case 'GET /settings':
            $out = [];
            foreach (array_keys(default_settings()) as $k) $out[$k] = $k === 'announcement' ? array_replace(default_settings()['announcement'], (array) setting('announcement', [])) : conf($k);
            json_out(['ok' => true, 'settings' => $out, 'langs' => array_keys(LANGS)]);

        case 'POST /settings':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            $saved = save_settings((array) ($in['settings'] ?? []));
            content_changed();
            audit('reglages', implode(', ', $saved));
            json_out(['ok' => true]);

        // ---------- Comptes
        case 'GET /users':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            json_out(['ok' => true, 'users' => q('SELECT id, email, name, role, active, created_at, last_login FROM users ORDER BY role, name')->fetchAll()]);

        case 'POST /users':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            $email = clean_email($in['email'] ?? '') ?? json_error('E-mail invalide');
            $email = mb_strtolower($email);
            $name = clean_line($in['name'] ?? '', 80);
            if (mb_strlen($name) < 2) json_error('Nom trop court');
            $role = ($in['role'] ?? '') === 'admin' ? 'admin' : 'staff';
            $pass = (string) ($in['password'] ?? '');
            $uid = (int) ($in['id'] ?? 0);
            if ($uid === (int) $u['id'] && ($role !== 'admin' || empty($in['active']))) json_error('Vous ne pouvez pas retirer vos propres droits');
            if ($pass !== '' && !password_ok($pass)) json_error('Mot de passe : 10 caractères au moins');
            $dup = qv('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $uid]);
            if ($dup) json_error('Cet e-mail a déjà un compte');
            $row = ['email' => $email, 'name' => $name, 'role' => $role, 'active' => !empty($in['active']) ? 1 : 0];
            if ($pass !== '') $row['pass_hash'] = hash_password($pass);
            if ($uid) update('users', $uid, $row);
            else {
                if ($pass === '') json_error('Choisissez un mot de passe');
                insert('users', $row + ['created_at' => now()]);
            }
            audit('comptes', ($uid ? 'Modifié : ' : 'Créé : ') . "$name ($role)");
            json_out(['ok' => true]);

        case 'POST /password':
            $me = q1('SELECT * FROM users WHERE id = ?', [$u['id']]);
            if (!password_verify((string) ($in['current'] ?? ''), $me['pass_hash'])) json_error('Mot de passe actuel incorrect');
            if (!password_ok((string) ($in['new'] ?? ''))) json_error('Nouveau mot de passe : 10 caractères au moins');
            q('UPDATE users SET pass_hash = ? WHERE id = ?', [hash_password((string) $in['new']), $u['id']]);
            audit('comptes', 'Mot de passe changé');
            json_out(['ok' => true]);

        case 'GET /audit':
            if (!$isAdmin) json_error('Réservé au gérant', 403);
            json_out(['ok' => true, 'log' => q('SELECT a.action, a.detail, a.ip, a.created_at, u.name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 200')->fetchAll()]);
    }
    json_error('Adresse inconnue', 404);
}

/** Texte traduisible reçu de l'admin → JSON propre ({"fr": "...", "en": "..."}) */
function tr_json(mixed $v, int $max, bool $nullable = false): ?string
{
    $out = [];
    foreach (array_keys(LANGS) as $l) {
        $s = is_array($v) ? ($v[$l] ?? '') : ($l === 'fr' ? $v : '');
        $s = clean_text($s, $max);
        if ($s !== '' || $l === 'fr') $out[$l] = $s;
    }
    if ($nullable && ($out['fr'] ?? '') === '') return null;
    return json_encode($out, JSON_UNESCAPED_UNICODE);
}

/** « 5,20 » ou « 5.2 » → 520 ; vide → null */
function cents(mixed $v): ?int
{
    if ($v === null || $v === '') return null;
    $s = str_replace([' ', '€', ','], ['', '', '.'], (string) $v);
    if (!is_numeric($s)) json_error('Prix invalide : ' . $v);
    $c = (int) round((float) $s * 100);
    if ($c < 0 || $c > 100000) json_error('Prix invalide : ' . $v);
    return $c;
}

function www_dir(): string
{
    $d = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    return $d && is_dir($d . '/assets') ? $d : dirname(APP_DIR) . '/www';
}

/** Photo de l'équipe : vérifiée, recadrée en 4:5, ré-encodée en WebP (aucun fichier envoyé n'est servi tel quel) */
function save_portrait(?array $f): string
{
    if (!$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) json_error('Envoi de la photo impossible');
    if ($f['size'] > 10 * 1024 * 1024) json_error('Photo trop lourde (10 Mo maximum)');
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) json_error('Format accepté : JPEG, PNG ou WebP');
    if ($info[0] * $info[1] > 40_000_000) json_error('Photo trop grande');
    if (!function_exists('imagecreatefromstring')) json_error('Le serveur ne sait pas traiter les images (extension GD)');
    $src = @imagecreatefromstring((string) file_get_contents($f['tmp_name']));
    if (!$src) json_error('Photo illisible');
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $o = (int) (@exif_read_data($f['tmp_name'])['Orientation'] ?? 1);
        if ($o === 3) $src = imagerotate($src, 180, 0);
        if ($o === 6) $src = imagerotate($src, -90, 0);
        if ($o === 8) $src = imagerotate($src, 90, 0);
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $tw = 600;
    $th = 750;
    $scale = max($tw / $w, $th / $h);
    $cw = (int) round($tw / $scale);
    $ch = (int) round($th / $scale);
    $dst = imagecreatetruecolor($tw, $th);
    imagecopyresampled($dst, $src, 0, 0, (int) (($w - $cw) / 2), (int) (($h - $ch) / 4), $tw, $th, $cw, $ch);
    $dir = www_dir() . '/assets/img/equipe';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.webp';
    if (!imagewebp($dst, "$dir/$name", 80)) json_error('Enregistrement de la photo impossible');
    return $name;
}

/** Enregistre les réglages reçus, champ par champ, avec des types sûrs */
function save_settings(array $in): array
{
    $saved = [];
    $d = default_settings();
    $str = fn($v, $n = 200) => clean_line($v, $n);
    if (isset($in['shop'])) {
        $s = (array) $in['shop'];
        $cur = conf('shop');
        $email = clean_email($s['email'] ?? '') ?? $cur['email'];
        $phone = clean_phone($s['phone'] ?? '') ?? $cur['phone'];
        $maps = filter_var($s['maps_url'] ?? '', FILTER_VALIDATE_URL) && str_starts_with((string) $s['maps_url'], 'https://') ? $s['maps_url'] : $cur['maps_url'];
        set_setting('shop', array_replace($cur, ['name' => $str($s['name'] ?? $cur['name'], 80) ?: $cur['name'], 'phone' => $phone, 'email' => $email,
            'street' => $str($s['street'] ?? $cur['street']), 'postal' => $str($s['postal'] ?? $cur['postal'], 10), 'city' => $str($s['city'] ?? $cur['city'], 80),
            'lat' => (float) ($s['lat'] ?? $cur['lat']), 'lng' => (float) ($s['lng'] ?? $cur['lng']), 'maps_url' => $maps,
            'owner' => $str($s['owner'] ?? $cur['owner'], 80), 'siren' => $str($s['siren'] ?? $cur['siren'], 20), 'since' => (int) ($s['since'] ?? $cur['since'])]));
        $saved[] = 'boutique';
    }
    if (isset($in['social'])) {
        $out = [];
        foreach (['instagram' => 'instagram.com', 'facebook' => 'facebook.com', 'tiktok' => 'tiktok.com'] as $k => $host) {
            $v = trim((string) ($in['social'][$k] ?? ''));
            $h = (string) parse_url($v, PHP_URL_HOST);
            $out[$k] = ($v === '') ? '' : (str_starts_with($v, 'https://') && ($h === $host || str_ends_with($h, '.' . $host)) ? $v : conf('social')[$k]);
        }
        set_setting('social', $out);
        $saved[] = 'réseaux';
    }
    if (isset($in['hours'])) {
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $slots = [];
            foreach ((array) ($in['hours']['days'][$i] ?? []) as $sl) {
                $a = (int) ($sl[0] ?? -1);
                $b = (int) ($sl[1] ?? -1);
                if ($a >= 0 && $b > $a && $b <= 1440) $slots[] = [$a, $b];
            }
            $days[] = array_slice($slots, 0, 3);
        }
        $closures = [];
        foreach ((array) ($in['hours']['closures'] ?? []) as $c) {
            $f = (string) ($c['from'] ?? '');
            $t = (string) ($c['to'] ?? $f);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) && $t >= $f) $closures[] = ['from' => $f, 'to' => $t, 'label' => $str($c['label'] ?? '', 80)];
        }
        set_setting('hours', ['days' => $days, 'closures' => array_slice($closures, 0, 30)]);
        $saved[] = 'horaires';
    }
    if (isset($in['brunch'])) {
        $b = (array) $in['brunch'];
        set_setting('brunch', ['enabled' => !empty($b['enabled']), 'price_cents' => cents($b['price'] ?? null) ?? $d['brunch']['price_cents'], 'day' => 0, 'from' => $str($b['from'] ?? '11h30', 10)]);
        $saved[] = 'brunch';
    }
    if (isset($in['loyalty'])) {
        $l = (array) $in['loyalty'];
        set_setting('loyalty', ['goal' => max(2, min(30, (int) ($l['goal'] ?? 10))), 'reward' => json_decode(tr_json($l['reward'] ?? [], 120), true), 'cooldown_min' => max(0, min(1440, (int) ($l['cooldown_min'] ?? 120)))]);
        $saved[] = 'fidélité';
    }
    if (isset($in['orders'])) {
        $o = (array) $in['orders'];
        set_setting('orders', ['enabled' => !empty($o['enabled']), 'lead_hours' => max(0, min(720, (int) ($o['lead_hours'] ?? 48)))]);
        $saved[] = 'commandes';
    }
    if (isset($in['rating'])) {
        $r = (array) $in['rating'];
        set_setting('rating', ['value' => max(0, min(5, round((float) str_replace(',', '.', (string) ($r['value'] ?? 0)), 1))), 'count' => max(0, (int) ($r['count'] ?? 0)), 'show' => !empty($r['show'])]);
        $saved[] = 'note';
    }
    if (isset($in['announcement'])) {
        set_setting('announcement', json_decode(tr_json($in['announcement'], 200), true));
        $saved[] = 'annonce';
    }
    return $saved;
}
