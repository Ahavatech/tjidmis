<?php
namespace App\Core;
/** Sends via PHPMailer/SMTP when configured; always records the message in email_log (the "outbox"). */
class Mailer {
    public static function send(string $to, string $subject, string $body, ?int $submissionId = null, ?string $cc = null): bool {
        $jid = Settings::journal()['id'] ?? null;
        $from = Settings::get('contactEmail') ?: (config('smtp')['from'] ?? '');
        $smtp = config('smtp', []); $sent = 0; $err = null;
        if (!empty($smtp['host']) && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $m = new \PHPMailer\PHPMailer\PHPMailer(true);
                $m->isSMTP(); $m->Host = $smtp['host']; $m->Port = (int)$smtp['port']; $m->SMTPAuth = $smtp['user'] !== '';
                $m->Username = $smtp['user']; $m->Password = $smtp['pass']; $m->SMTPSecure = $smtp['secure'] ?: '';
                $m->CharSet = 'UTF-8'; $m->setFrom($smtp['from'] ?: $from, Settings::get('name', ''));
                $m->addAddress($to); if ($cc) $m->addCC($cc); $m->Subject = $subject; $m->Body = $body;
                $m->send(); $sent = 1;
            } catch (\Throwable $e) { $err = $e->getMessage(); Log::write('MAIL ' . $err); }
        } else $err = 'No SMTP configured: message logged only.';
        DB::insert('email_log', ['journal_id' => $jid, 'submission_id' => $submissionId, 'from_addr' => $from, 'to_addr' => $to, 'cc' => $cc,
            'subject' => $subject, 'body' => $body, 'sent' => $sent, 'error' => $err, 'created_at' => date('Y-m-d H:i:s')]);
        return (bool)$sent;
    }
}
