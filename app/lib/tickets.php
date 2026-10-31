<?php
/* Tickets : commandes de gâteaux (particuliers, pros), demandes de prestations pro, questions.
   Le client suit son ticket à une adresse secrète (/fr/suivi/?t=…) ; l'équipe le traite dans /admin. */
declare(strict_types=1);

const TICKET_TYPES = ['gateau', 'gateau_pro', 'pro', 'question'];
const TICKET_STATUSES = ['nouveau', 'en_cours', 'devis', 'confirme', 'pret', 'termine', 'annule'];

/** Crée un ticket depuis un formulaire public. Retourne le ticket ou une liste d'erreurs (clés de traduction). */
function ticket_create(array $in, string $l): array
{
    $type = in_array($in['type'] ?? '', TICKET_TYPES, true) ? $in['type'] : 'question';
    $name = clean_line($in['name'] ?? '', 120);
    $email = clean_email($in['email'] ?? '');
    $phone = clean_phone($in['phone'] ?? '');
    $company = clean_line($in['company'] ?? '', 160) ?: null;
    $product = clean_line($in['product'] ?? '', 160) ?: null;
    $guests = filter_var($in['guests'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2000]]) ?: null;
    $date = clean_line($in['date'] ?? '', 10);
    $msg = clean_text($in['message'] ?? '', 3000);

    $err = [];
    if (mb_strlen($name) < 2) $err['name'] = 'form.err.name';
    if (!$email && !$phone) $err['email'] = 'form.err.contact';
    if (($in['email'] ?? '') !== '' && !$email) $err['email'] = 'form.err.email';
    if (($in['phone'] ?? '') !== '' && !$phone) $err['phone'] = 'form.err.phone';
    if ($date !== '') {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $min = (new DateTimeImmutable('today'));
        if (!$d || $d->format('Y-m-d') !== $date || $d < $min || $d > $min->modify('+1 year')) $err['date'] = 'form.err.date';
    } else {
        $date = null;
        if ($type !== 'question') $err['date'] = 'form.err.date';
    }
    if (in_array($type, ['gateau_pro', 'pro'], true) && !$company) $err['company'] = 'form.err.company';
    if (mb_strlen($msg) < 5 && $type !== 'gateau') $err['message'] = 'form.err.message';
    if (empty($in['consent'])) $err['consent'] = 'form.err.consent';
    if ($err) return ['errors' => $err];

    $t = now();
    $id = insert('tickets', [
        'token' => random_token(), 'type' => $type, 'status' => 'nouveau', 'name' => $name, 'email' => $email, 'phone' => $phone,
        'company' => $company, 'wanted_date' => $date, 'guests' => $guests, 'product' => $product, 'message' => $msg,
        'lang' => $l, 'unread_staff' => 1, 'unread_client' => 0, 'created_at' => $t, 'updated_at' => $t,
    ]);
    $ref = 'AC-' . date('ym', $t) . '-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    q('UPDATE tickets SET ref = ? WHERE id = ?', [$ref, $id]);
    $ticket = ticket_get($id);

    $c = config();
    $lines = ["Nouveau ticket $ref (" . ticket_type_label($type) . ')', '', "Nom : $name"];
    if ($company) $lines[] = "Société : $company";
    if ($email) $lines[] = "E-mail : $email";
    if ($phone) $lines[] = "Téléphone : $phone";
    if ($date) $lines[] = "Pour le : $date";
    if ($guests) $lines[] = "Personnes / parts : $guests";
    if ($product) $lines[] = "Choix : $product";
    $lines[] = '';
    $lines[] = $msg;
    $lines[] = '';
    $lines[] = 'À traiter dans l’espace équipe : ' . $c['base_url'] . '/admin/#/tickets/' . $id;
    send_mail($c['mail_to'], "[$ref] " . ticket_type_label($type) . " · $name", implode("\n", $lines), $email);
    if ($email) {
        send_mail($email, t('mail.received.subject', ['ref' => $ref]), t('mail.received.body', ['name' => $name, 'ref' => $ref, 'url' => ticket_url($ticket)]));
    }
    return ['ticket' => $ticket];
}

function ticket_get(int $id): ?array
{
    return q1('SELECT * FROM tickets WHERE id = ?', [$id]);
}

function ticket_by_token(string $token): ?array
{
    if (!preg_match('/^[A-Za-z0-9_-]{20,64}$/', $token)) return null;
    return q1('SELECT * FROM tickets WHERE token = ?', [$token]);
}

function ticket_url(array $t): string
{
    return url('track', $t['lang'], true) . '?t=' . $t['token'];
}

function ticket_messages(int $id, bool $withInternal): array
{
    return q('SELECT m.id, m.author, m.body, m.internal, m.created_at, u.name AS user_name FROM ticket_messages m LEFT JOIN users u ON u.id = m.user_id
              WHERE m.ticket_id = ?' . ($withInternal ? '' : ' AND m.internal = 0') . ' ORDER BY m.created_at, m.id', [$id])->fetchAll();
}

/** Vue publique d'un ticket (pour la page de suivi) : rien d'interne */
function ticket_public(array $t): array
{
    return [
        'ref' => $t['ref'], 'status' => $t['status'], 'type' => $t['type'], 'name' => $t['name'], 'date' => $t['wanted_date'],
        'guests' => $t['guests'], 'product' => $t['product'], 'message' => $t['message'], 'created_at' => $t['created_at'], 'updated_at' => $t['updated_at'],
        'messages' => array_map(fn($m) => ['author' => $m['author'], 'body' => $m['body'], 'at' => $m['created_at'], 'by' => $m['author'] === 'staff' ? ($m['user_name'] ? strtok($m['user_name'], ' ') : null) : null], ticket_messages((int) $t['id'], false)),
    ];
}

/** Le client écrit sur son ticket */
function ticket_client_reply(array $t, string $body): void
{
    insert('ticket_messages', ['ticket_id' => $t['id'], 'author' => 'client', 'body' => $body, 'internal' => 0, 'created_at' => now()]);
    q('UPDATE tickets SET unread_staff = 1, unread_client = 0, updated_at = ? WHERE id = ?', [now(), $t['id']]);
    $c = config();
    send_mail($c['mail_to'], "[{$t['ref']}] Nouveau message de {$t['name']}", $body . "\n\n" . $c['base_url'] . '/admin/#/tickets/' . $t['id'], $t['email']);
}

/** L'équipe répond, change le statut, ou laisse une note interne */
function ticket_staff_update(array $t, array $user, ?string $status, string $body, bool $internal): array
{
    $changes = [];
    $notify = false;
    if ($status && $status !== $t['status'] && in_array($status, TICKET_STATUSES, true)) {
        q('UPDATE tickets SET status = ?, updated_at = ? WHERE id = ?', [$status, now(), $t['id']]);
        insert('ticket_messages', ['ticket_id' => $t['id'], 'author' => 'system', 'user_id' => $user['id'], 'body' => 'status:' . $status, 'internal' => 0, 'created_at' => now()]);
        $changes[] = 'statut ' . $status;
        $notify = true;
    }
    if ($body !== '') {
        insert('ticket_messages', ['ticket_id' => $t['id'], 'author' => 'staff', 'user_id' => $user['id'], 'body' => $body, 'internal' => $internal ? 1 : 0, 'created_at' => now()]);
        $changes[] = $internal ? 'note interne' : 'réponse';
        if (!$internal) $notify = true;
    }
    if ($notify) q('UPDATE tickets SET unread_client = 1, updated_at = ? WHERE id = ?', [now(), $t['id']]);
    q('UPDATE tickets SET unread_staff = 0 WHERE id = ?', [$t['id']]);

    if ($notify && $t['email']) {
        $prev = lang();
        lang($t['lang']);
        $st = t('status.' . ($status ?: $t['status']));
        $text = t('mail.update.body', ['name' => $t['name'], 'ref' => $t['ref'], 'status' => $st, 'message' => ($body !== '' && !$internal) ? "\n« $body »\n" : '', 'url' => ticket_url($t)]);
        send_mail($t['email'], t('mail.update.subject', ['ref' => $t['ref'], 'status' => $st]), $text, config()['mail_to']);
        lang($prev);
    }
    if ($changes) audit('ticket', $t['ref'] . ' : ' . implode(', ', $changes));
    return ticket_get((int) $t['id']);
}

function ticket_type_label(string $type): string
{
    return ['gateau' => 'Gâteau (particulier)', 'gateau_pro' => 'Gâteau (pro)', 'pro' => 'Prestation pro', 'question' => 'Question'][$type] ?? $type;
}
