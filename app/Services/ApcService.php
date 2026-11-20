<?php
namespace App\Services;
use App\Core\{DB, Settings};

/** Manual APC (article processing charge) tracking: the editor records whether a charge is pending, paid or waived. No payment gateway. */
class ApcService {
    const STATUSES = ['pending', 'paid', 'waived'];
    public static function enabled(): bool { return Settings::get('apcEnabled', '') === '1'; }
    public static function get(int $sid): ?array { return DB::one('SELECT * FROM apc_payments WHERE submission_id=?', [$sid]); }
    public static function defaults(): array { return ['amount' => (string)Settings::get('apcFee', ''), 'currency' => strtoupper((string)Settings::get('apcCurrency', 'USD')) ?: 'USD']; }
    /** Returns an error string or null. */
    public static function save(int $sid, array $p, int $actor): ?string {
        $status = (string)($p['status'] ?? '');
        if (!in_array($status, self::STATUSES, true)) return 'status';
        $amount = trim((string)($p['amount'] ?? ''));
        if ($amount !== '' && !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amount)) return 'amount';
        $cur = strtoupper(trim((string)($p['currency'] ?? ''))); if ($cur !== '' && !preg_match('/^[A-Z]{3}$/', $cur)) return 'currency';
        $old = self::get($sid);
        $paidAt = $status === 'paid' ? (($old && $old['status'] === 'paid' && $old['paid_at']) ? $old['paid_at'] : date('Y-m-d H:i:s')) : null;
        DB::exec('REPLACE INTO apc_payments (submission_id,status,amount,currency,reference,note,paid_at,updated_by,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW())',
            [$sid, $status, $amount === '' ? null : $amount, $cur ?: null, mb_substr(trim((string)($p['reference'] ?? '')), 0, 190) ?: null, mb_substr(trim((string)($p['note'] ?? '')), 0, 255) ?: null, $paidAt, $actor]);
        return null;
    }
}
