<?php
/* Configuration, base de données, réglages, petits outils. */
declare(strict_types=1);

function config(): array
{
    static $c;
    if ($c === null) {
        $file = APP_DIR . '/config.php';
        if (!is_file($file)) $file = APP_DIR . '/config.sample.php';
        $c = require $file;
        $c['base_url'] = rtrim($c['base_url'] ?? '', '/');
        // en local (serveur de dev PHP), on prend l'adresse réelle
        if (PHP_SAPI === 'cli-server' && !empty($_SERVER['HTTP_HOST'])) $c['base_url'] = 'http://' . $_SERVER['HTTP_HOST'];
    }
    return $c;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

function now(): int { return time(); }

function request_path(): string
{
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return '/' . ltrim(rawurldecode($p), '/');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' && config()['trusted_ip_header']);
}

/* ---------------------------------------------------------------- Base de données */

function db(): PDO
{
    static $pdo;
    if ($pdo) return $pdo;
    $c = config()['db'];
    if (str_starts_with($c['dsn'], 'sqlite:') && !is_dir(DATA_DIR)) mkdir(DATA_DIR, 0750, true);
    $pdo = new PDO($c['dsn'], $c['user'] ?? null, $c['pass'] ?? null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    if (db_driver($pdo) === 'sqlite') {
        $pdo->exec('PRAGMA journal_mode = WAL; PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 4000;');
    }
    migrate($pdo);
    return $pdo;
}

function db_driver(?PDO $pdo = null): string
{
    return ($pdo ?? db())->getAttribute(PDO::ATTR_DRIVER_NAME);
}

function q(string $sql, array $args = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st;
}

function q1(string $sql, array $args = []): ?array
{
    $r = q($sql, $args)->fetch();
    return $r === false ? null : $r;
}

function qv(string $sql, array $args = []): mixed
{
    $r = q($sql, $args)->fetchColumn();
    return $r === false ? null : $r;
}

function insert(string $table, array $row): int
{
    $cols = array_keys($row);
    q("INSERT INTO $table (" . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($row));
    return (int) db()->lastInsertId();
}

function update(string $table, int $id, array $row): void
{
    if (!$row) return;
    $set = implode(',', array_map(fn($c) => "$c = ?", array_keys($row)));
    q("UPDATE $table SET $set WHERE id = ?", [...array_values($row), $id]);
}

const SCHEMA_VERSION = 2;

function migrate(PDO $pdo): void
{
    $mysql = db_driver($pdo) === 'mysql';
    $pk = $mysql ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $key = $mysql ? 'VARCHAR(191)' : 'TEXT';
    $txt = $mysql ? 'MEDIUMTEXT' : 'TEXT';
    $tail = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (k $key PRIMARY KEY, v $txt NOT NULL)$tail");
    $v = (int) $pdo->query("SELECT v FROM settings WHERE k = 'schema_version'")->fetchColumn();
    if ($v >= SCHEMA_VERSION) return;

    $tables = [
        "users (id $pk, email $key NOT NULL UNIQUE, name TEXT NOT NULL, pass_hash TEXT NOT NULL, role VARCHAR(10) NOT NULL DEFAULT 'staff',
            active INT NOT NULL DEFAULT 1, created_at INT NOT NULL, last_login INT NULL)",
        "menu_sections (id $pk, position INT NOT NULL DEFAULT 0, title $txt NOT NULL, note $txt NULL, price_cents INT NULL, visible INT NOT NULL DEFAULT 1)",
        "menu_items (id $pk, section_id INT NOT NULL, position INT NOT NULL DEFAULT 0, name $txt NOT NULL, descr $txt NULL,
            price_cents INT NULL, is_extra INT NOT NULL DEFAULT 0, tags VARCHAR(120) NOT NULL DEFAULT '', visible INT NOT NULL DEFAULT 1)",
        "team (id $pk, position INT NOT NULL DEFAULT 0, name VARCHAR(80) NOT NULL, role $txt NOT NULL, bio $txt NULL, photo VARCHAR(120) NULL,
            tint VARCHAR(10) NOT NULL DEFAULT 'taupe', visible INT NOT NULL DEFAULT 1)",
        "tickets (id $pk, ref VARCHAR(20) NULL, token $key NOT NULL UNIQUE, type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'nouveau',
            name VARCHAR(120) NOT NULL, email VARCHAR(190) NULL, phone VARCHAR(40) NULL, company VARCHAR(160) NULL, wanted_date VARCHAR(10) NULL,
            guests INT NULL, product VARCHAR(160) NULL, message $txt NULL, lang VARCHAR(5) NOT NULL DEFAULT 'fr',
            unread_staff INT NOT NULL DEFAULT 1, unread_client INT NOT NULL DEFAULT 0, created_at INT NOT NULL, updated_at INT NOT NULL)",
        "ticket_messages (id $pk, ticket_id INT NOT NULL, author VARCHAR(10) NOT NULL, user_id INT NULL, body $txt NOT NULL,
            internal INT NOT NULL DEFAULT 0, created_at INT NOT NULL)",
        "loyalty_cards (id $pk, token $key NOT NULL UNIQUE, code $key NOT NULL UNIQUE, first_name VARCHAR(80) NOT NULL, email VARCHAR(190) NULL,
            stamps INT NOT NULL DEFAULT 0, rewards INT NOT NULL DEFAULT 0, visits INT NOT NULL DEFAULT 0, active INT NOT NULL DEFAULT 1,
            created_at INT NOT NULL, last_stamp_at INT NULL)",
        "loyalty_events (id $pk, card_id INT NOT NULL, user_id INT NULL, kind VARCHAR(10) NOT NULL, delta INT NOT NULL DEFAULT 0, created_at INT NOT NULL)",
        "rate_limits (k $key PRIMARY KEY, hits INT NOT NULL, reset_at INT NOT NULL)",
        "audit_log (id $pk, user_id INT NULL, action VARCHAR(40) NOT NULL, detail $txt NULL, ip VARCHAR(64) NULL, created_at INT NOT NULL)",
    ];
    foreach ($tables as $t) $pdo->exec("CREATE TABLE IF NOT EXISTS $t$tail");
    foreach ([
        'idx_items_section ON menu_items (section_id, position)',
        'idx_tickets_status ON tickets (status, updated_at)',
        'idx_msg_ticket ON ticket_messages (ticket_id, created_at)',
        'idx_events_card ON loyalty_events (card_id, created_at)',
    ] as $i) {
        try { $pdo->exec(($mysql ? 'CREATE INDEX ' : 'CREATE INDEX IF NOT EXISTS ') . $i); } catch (PDOException) { /* existe déjà (MySQL) */ }
    }
    // v2 : les allergènes de chaque article (codes séparés par des virgules)
    $cols = $mysql ? array_column($pdo->query('SHOW COLUMNS FROM menu_items')->fetchAll(), 'Field') : array_column($pdo->query('PRAGMA table_info(menu_items)')->fetchAll(), 'name');
    if (!in_array('allergens', $cols, true)) $pdo->exec("ALTER TABLE menu_items ADD COLUMN allergens VARCHAR(160) NOT NULL DEFAULT ''");
    seed_content($pdo);
    if ($v < 2) seed_allergens($pdo);
    $pdo->prepare('REPLACE INTO settings (k, v) VALUES (?, ?)')->execute(['schema_version', (string) SCHEMA_VERSION]);
}

/* ---------------------------------------------------------------- Réglages (JSON) */

function setting(string $k, mixed $default = null): mixed
{
    static $cache = null;
    if ($k === '__reset') { $cache = null; return null; }
    if ($cache === null) {
        $cache = [];
        foreach (q('SELECT k, v FROM settings')->fetchAll() as $r) $cache[$r['k']] = json_decode($r['v'], true);
    }
    return $cache[$k] ?? $default;
}

function set_setting(string $k, mixed $v): void
{
    q('REPLACE INTO settings (k, v) VALUES (?, ?)', [$k, json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    setting('__reset');
}

/* ---------------------------------------------------------------- Réponses */

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $msg, int $code = 400, array $extra = []): never
{
    json_out(['ok' => false, 'error' => $msg] + $extra, $code);
}

/** Corps de la requête : JSON ou formulaire classique */
function body(): array
{
    static $b;
    if ($b !== null) return $b;
    if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        $raw = file_get_contents('php://input', false, null, 0, 200_000);
        $b = json_decode($raw ?: '[]', true, 32, JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_array($b)) $b = [];
    } else {
        $b = $_POST;
    }
    return $b;
}

/** Texte nettoyé : une ligne, sans caractères de contrôle, longueur bornée */
function clean_line(mixed $v, int $max = 160): string
{
    $s = is_scalar($v) ? (string) $v : '';
    $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s) ?? '';
    return mb_substr(trim(preg_replace('/\s+/u', ' ', $s) ?? ''), 0, $max);
}

/** Texte nettoyé sur plusieurs lignes */
function clean_text(mixed $v, int $max = 4000): string
{
    $s = is_scalar($v) ? (string) $v : '';
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $s) ?? '';
    return mb_substr(trim(preg_replace("/\n{3,}/", "\n\n", $s) ?? ''), 0, $max);
}

function clean_email(mixed $v): ?string
{
    $s = clean_line($v, 190);
    return $s !== '' && filter_var($s, FILTER_VALIDATE_EMAIL) ? $s : null;
}

function clean_phone(mixed $v): ?string
{
    $s = clean_line($v, 40);
    return preg_match('/^[+0-9 ().\-]{6,40}$/', $s) ? $s : null;
}

function fmt_price(?int $cents, string $lang = 'fr'): string
{
    if ($cents === null) return '';
    $n = $cents / 100;
    return match ($lang) {
        'en' => '€' . number_format($n, 2, '.', ','),
        'zh' => '€' . number_format($n, 2, '.', ''),
        default => number_format($n, 2, ',', ' ') . ' €',
    };
}

function page_cache_clear(): void
{
    foreach (glob(DATA_DIR . '/cache/*.html') ?: [] as $f) @unlink($f);
}
