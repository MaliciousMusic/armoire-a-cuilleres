<?php
/* Courriels (fonction mail() d'OVH). En-têtes protégés contre l'injection. */
declare(strict_types=1);

function send_mail(string $to, string $subject, string $text, ?string $replyTo = null): bool
{
    $c = config();
    $to = clean_email($to);
    if (!$to || empty($c['mail_from'])) return false;
    $from = clean_email($c['mail_from']);
    $headers = [
        'From' => '=?UTF-8?B?' . base64_encode('L’Armoire à Cuillères') . "?= <$from>",
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
        'X-Mailer' => 'armoire',
    ];
    if ($replyTo && ($r = clean_email($replyTo))) $headers['Reply-To'] = $r;
    $subject = '=?UTF-8?B?' . base64_encode(clean_line($subject, 200)) . '?=';
    if (PHP_SAPI === 'cli-server') { // en local : on écrit dans un fichier au lieu d'envoyer
        file_put_contents(DATA_DIR . '/mails.log', date('c') . " → $to\n$subject\n$text\n\n", FILE_APPEND);
        return true;
    }
    return @mail($to, $subject, $text, $headers, '-f' . $from);
}
