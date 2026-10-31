<?php
/* Carte de fidélité : un tampon par passage, scanné par l'équipe (QR code) depuis /admin.
   Au bout de N tampons (10 par défaut), une récompense ; l'équipe la « offre » et le compteur repart. */
declare(strict_types=1);

function loyalty_create(string $firstName, ?string $email): array
{
    do {
        $code = random_code(6);
    } while (qv('SELECT 1 FROM loyalty_cards WHERE code = ?', [$code]));
    $id = insert('loyalty_cards', ['token' => random_token(), 'code' => $code, 'first_name' => $firstName, 'email' => $email,
        'stamps' => 0, 'rewards' => 0, 'visits' => 0, 'active' => 1, 'created_at' => now()]);
    insert('loyalty_events', ['card_id' => $id, 'user_id' => current_user_id(), 'kind' => 'create', 'delta' => 0, 'created_at' => now()]);
    return q1('SELECT * FROM loyalty_cards WHERE id = ?', [$id]);
}

function current_user_id(): ?int
{
    return session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;
}

function loyalty_by_token(string $token): ?array
{
    if (!preg_match('/^[A-Za-z0-9_-]{20,64}$/', $token)) return null;
    return q1('SELECT * FROM loyalty_cards WHERE token = ? AND active = 1', [$token]);
}

/** Trouve une carte depuis ce que le scanner a lu : l'adresse complète, le jeton seul, ou le code court */
function loyalty_find(string $scan): ?array
{
    $scan = trim($scan);
    if (preg_match('/[?&]c=([A-Za-z0-9_-]{20,64})/', $scan, $m)) return loyalty_by_token($m[1]);
    if (preg_match('/^[A-Za-z0-9_-]{20,64}$/', $scan)) return loyalty_by_token($scan);
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $scan) ?? '');
    if (strlen($code) === 6) return q1('SELECT * FROM loyalty_cards WHERE code = ? AND active = 1', [$code]);
    return null;
}

function loyalty_public(array $c): array
{
    $goal = (int) conf('loyalty')['goal'];
    return [
        'first_name' => $c['first_name'], 'code' => $c['code'], 'stamps' => (int) $c['stamps'], 'goal' => $goal,
        'ready' => (int) $c['stamps'] >= $goal, 'rewards' => (int) $c['rewards'], 'visits' => (int) $c['visits'],
        'reward' => tr(conf('loyalty')['reward']), 'last' => $c['last_stamp_at'] ? (int) $c['last_stamp_at'] : null,
    ];
}

/** Vue de l'équipe */
function loyalty_admin(array $c): array
{
    return loyalty_public($c) + ['id' => (int) $c['id'], 'email' => $c['email'], 'created_at' => (int) $c['created_at'], 'token' => $c['token'],
        'history' => q('SELECT e.kind, e.delta, e.created_at, u.name AS by_name FROM loyalty_events e LEFT JOIN users u ON u.id = e.user_id
                        WHERE e.card_id = ? ORDER BY e.created_at DESC, e.id DESC LIMIT 15', [$c['id']])->fetchAll()];
}

/** Ajoute un tampon. Refuse (sauf $force) si le dernier est trop récent. */
function loyalty_stamp(array $c, array $user, bool $force): array
{
    $cool = (int) conf('loyalty')['cooldown_min'] * 60;
    if (!$force && $c['last_stamp_at'] && now() - (int) $c['last_stamp_at'] < $cool) {
        return ['error' => 'recent', 'minutes' => intdiv(now() - (int) $c['last_stamp_at'], 60)];
    }
    q('UPDATE loyalty_cards SET stamps = stamps + 1, visits = visits + 1, last_stamp_at = ? WHERE id = ?', [now(), $c['id']]);
    insert('loyalty_events', ['card_id' => $c['id'], 'user_id' => $user['id'], 'kind' => 'stamp', 'delta' => 1, 'created_at' => now()]);
    audit('fidelite', "+1 tampon · {$c['first_name']} ({$c['code']})" . ($force ? ' (forcé)' : ''));
    return ['card' => q1('SELECT * FROM loyalty_cards WHERE id = ?', [$c['id']])];
}

function loyalty_redeem(array $c, array $user): array
{
    $goal = (int) conf('loyalty')['goal'];
    if ((int) $c['stamps'] < $goal) return ['error' => 'not_ready'];
    q('UPDATE loyalty_cards SET stamps = stamps - ?, rewards = rewards + 1 WHERE id = ?', [$goal, $c['id']]);
    insert('loyalty_events', ['card_id' => $c['id'], 'user_id' => $user['id'], 'kind' => 'reward', 'delta' => -$goal, 'created_at' => now()]);
    audit('fidelite', "Récompense offerte · {$c['first_name']} ({$c['code']})");
    return ['card' => q1('SELECT * FROM loyalty_cards WHERE id = ?', [$c['id']])];
}

function loyalty_adjust(array $c, array $user, int $delta): array
{
    $new = max(0, (int) $c['stamps'] + $delta);
    q('UPDATE loyalty_cards SET stamps = ? WHERE id = ?', [$new, $c['id']]);
    insert('loyalty_events', ['card_id' => $c['id'], 'user_id' => $user['id'], 'kind' => 'adjust', 'delta' => $new - (int) $c['stamps'], 'created_at' => now()]);
    audit('fidelite', "Correction " . sprintf('%+d', $delta) . " · {$c['first_name']} ({$c['code']})");
    return ['card' => q1('SELECT * FROM loyalty_cards WHERE id = ?', [$c['id']])];
}
