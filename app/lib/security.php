<?php
/* Sécurité : en-têtes, sessions de l'administration, CSRF, limites de débit, jetons, journal. */
declare(strict_types=1);


function security_headers(bool $admin = false): void
{
    $csp = [
        "default-src 'self'",
        "script-src 'self'",
        "style-src 'self'",
        "img-src 'self' data:",
        "font-src 'self'",
        "connect-src 'self'",
        "worker-src 'none'",
        "media-src 'self' blob:",
        "manifest-src 'self'",
        "frame-src https://www.google.com https://maps.google.com",
        "frame-ancestors 'none'",
        "base-uri 'none'",
        "form-action 'self'",
        "object-src 'none'",
    ];
    if (is_https()) $csp[] = 'upgrade-insecure-requests';
    header('Content-Security-Policy: ' . implode('; ', $csp));
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Permissions-Policy: ' . ($admin ? 'camera=(self)' : 'camera=()') . ', microphone=(), geolocation=(), payment=(), usb=()');
    if (is_https()) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header_remove('X-Powered-By');
}

function app_secret(): string
{
    $s = setting('app_secret');
    if (!$s) {
        $s = bin2hex(random_bytes(32));
        set_setting('app_secret', $s);
    }
    return $s;
}

/** Jeton aléatoire, lisible dans une adresse (base64url) */
function random_token(int $bytes = 24): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

/** Code court sans caractères ambigus (pour dicter ou taper au comptoir) */
function random_code(int $len = 6): string
{
    $abc = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < $len; $i++) $s .= $abc[random_int(0, strlen($abc) - 1)];
    return $s;
}

function client_ip(): string
{
    $h = config()['trusted_ip_header'] ?? null;
    if ($h) {
        $v = $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $h))] ?? '';
        $ip = trim(explode(',', $v)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** IP tronquée (RGPD) pour le journal */
function ip_anon(): string
{
    $ip = client_ip();
    if (str_contains($ip, ':')) return implode(':', array_slice(explode(':', $ip), 0, 3)) . '::';
    return preg_replace('/\.\d+$/', '.0', $ip) ?? '';
}

/**
 * Limite de débit : au plus $max actions par $window secondes pour ce seau et ce visiteur.
 * Retourne false si la limite est dépassée.
 */
function rate_limit(string $bucket, int $max, int $window, ?string $who = null): bool
{
    $k = $bucket . ':' . substr(hash_hmac('sha256', $who ?? client_ip(), app_secret()), 0, 32);
    $t = now();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $r = q1('SELECT hits, reset_at FROM rate_limits WHERE k = ?', [$k]);
        if (!$r || $r['reset_at'] <= $t) {
            q('REPLACE INTO rate_limits (k, hits, reset_at) VALUES (?, 1, ?)', [$k, $t + $window]);
            $ok = true;
        } else {
            $ok = $r['hits'] < $max;
            if ($ok) q('UPDATE rate_limits SET hits = hits + 1 WHERE k = ?', [$k]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    if (random_int(1, 200) === 1) q('DELETE FROM rate_limits WHERE reset_at < ?', [$t]);
    return $ok;
}

/** La requête vient-elle bien de notre site ? (en plus du CSRF) */
function same_origin(): bool
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    if ($origin === '') return ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin') === 'same-origin';
    $host = parse_url($origin, PHP_URL_HOST);
    $port = parse_url($origin, PHP_URL_PORT);
    $want = $_SERVER['HTTP_HOST'] ?? '';
    return $host !== null && strcasecmp($host . ($port ? ":$port" : ''), $want) === 0;
}

/* ---------------------------------------------------------------- Sessions de l'administration */

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $dir = DATA_DIR . '/sessions';
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    session_save_path($dir);
    $life = 14 * 86400;
    ini_set('session.gc_maxlifetime', (string) $life);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name(is_https() ? '__Host-ac_admin' : 'ac_admin');
    session_set_cookie_params(['lifetime' => $life, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
}

function current_user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    admin_session_start();
    $u = null;
    $id = $_SESSION['uid'] ?? null;
    if ($id) {
        $row = q1('SELECT id, email, name, role, active FROM users WHERE id = ?', [$id]);
        $fresh = ($_SESSION['seen'] ?? 0) > now() - 14 * 86400;
        if ($row && $row['active'] && $fresh && hash_equals($_SESSION['fp'] ?? '', session_fingerprint())) {
            $u = $row;
            $_SESSION['seen'] = now();
        } else {
            $_SESSION = [];
        }
    }
    return $u;
}

/** Lie la session au navigateur (un cookie volé sur un autre navigateur ne sert à rien) */
function session_fingerprint(): string
{
    return hash_hmac('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? ''), app_secret());
}

function login_user(array $user): void
{
    admin_session_start();
    session_regenerate_id(true);
    $_SESSION = ['uid' => (int) $user['id'], 'seen' => now(), 'fp' => session_fingerprint(), 'csrf' => random_token(32)];
    q('UPDATE users SET last_login = ? WHERE id = ?', [now(), $user['id']]);
}

function logout_user(): void
{
    admin_session_start();
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => 1, 'path' => '/', 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
    session_destroy();
}

function csrf_token(): string
{
    admin_session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = random_token(32);
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? (body()['csrf'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent) || !same_origin()) {
        json_error('Session expirée : rechargez la page.', 403);
    }
}

/** Exige une personne connectée ; $role = 'admin' pour les réglages réservés au gérant */
function require_user(string $role = 'staff'): array
{
    $u = current_user();
    if (!$u) json_error('Connexion requise', 401);
    if ($role === 'admin' && $u['role'] !== 'admin') json_error('Réservé au gérant', 403);
    return $u;
}

function audit(string $action, string $detail = ''): void
{
    $u = current_user();
    insert('audit_log', ['user_id' => $u['id'] ?? null, 'action' => $action, 'detail' => mb_substr($detail, 0, 500), 'ip' => ip_anon(), 'created_at' => now()]);
}

function hash_password(string $p): string
{
    return password_hash($p, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
}

/** Règle de mot de passe : 10 caractères au moins */
function password_ok(string $p): bool
{
    return mb_strlen($p) >= 10 && mb_strlen($p) <= 200;
}
