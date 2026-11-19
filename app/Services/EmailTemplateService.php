<?php
namespace App\Services;
use App\Core\{DB, Mailer, Settings};
class EmailTemplateService {
    /** Renders a template with {$var} placeholders. Returns [subject, body] or null if missing/disabled. */
    public static function render(string $key, array $vars): ?array {
        $jid = (int)(Settings::journal()['id'] ?? 0);
        $t = DB::one('SELECT subject, body FROM email_templates WHERE journal_id=? AND email_key=? AND enabled=1', [$jid, $key]);
        if (!$t) return null;
        $vars += ['journalName' => Settings::get('name', ''), 'signature' => "--\n" . Settings::get('name', '') . "\n" . Settings::get('contactEmail', '')];
        $rep = []; foreach ($vars as $k => $v) $rep['{$' . $k . '}'] = (string)$v;
        return [strtr($t['subject'], $rep), strtr($t['body'], $rep)];
    }
    public static function send(string $key, string $to, array $vars, ?int $submissionId = null): bool {
        $r = self::render($key, $vars); if (!$r) return false;
        return Mailer::send($to, $r[0], $r[1], $submissionId);
    }
}
