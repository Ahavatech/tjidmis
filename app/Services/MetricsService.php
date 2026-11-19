<?php
namespace App\Services;
use App\Core\DB;
class MetricsService {
    const BOT = '/bot|crawl|spider|slurp|bing|facebookexternalhit|curl|wget|python-requests|headless/i';
    /** type: abstract_view | galley_download | issue_view. Skips bots and repeat hits within 30s. */
    public static function hit(int $jid, int $sid, string $type, int $galleyId = 0): void {
        if (!feature('usageStats')) return;
        if (preg_match(self::BOT, $_SERVER['HTTP_USER_AGENT'] ?? 'bot')) return;
        $k = "m_{$type}_{$sid}_{$galleyId}";
        if (isset($_SESSION[$k]) && time() - $_SESSION[$k] < 30) return;
        $_SESSION[$k] = time();
        DB::exec('INSERT INTO metrics (journal_id,submission_id,galley_id,type,day,count) VALUES (?,?,?,?,CURDATE(),1)
                  ON DUPLICATE KEY UPDATE count=count+1', [$jid, $sid, $galleyId, $type]);
    }
}
