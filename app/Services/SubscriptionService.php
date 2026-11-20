<?php
namespace App\Services;
use App\Core\{DB, Settings, Auth};

/** Subscription access as in OJS 3.3 (IssueAction): subscription mode + issue access status + open access date decide whether a galley needs a subscription. */
class SubscriptionService {
    const ACTIVE = 1;
    const STATUSES = [1 => 'sub.status_active', 16 => 'sub.status_needs_information', 17 => 'sub.status_needs_approval', 18 => 'sub.status_awaiting_manual', 19 => 'sub.status_awaiting_online', 20 => 'sub.status_other'];
    const FORMATS = [1 => 'sub.format_online', 16 => 'sub.format_print', 17 => 'sub.format_print_online'];
    const ISSUE_OPEN = 1, ISSUE_SUBSCRIPTION = 2;

    public static function mode(): string { return Settings::get('publishingMode', 'open') ?: 'open'; }
    public static function enabled(): bool { return self::mode() === 'subscription'; }

    /** OJS IssueAction::subscriptionRequired(). */
    public static function issueRequires(?array $issue): bool {
        if (!$issue || !self::enabled()) return false;
        return (int)($issue['access_status'] ?? 1) !== self::ISSUE_OPEN && (empty($issue['open_access_date']) || strtotime($issue['open_access_date']) > time());
    }
    /** Does the galley of this published article need a subscription? (article "open access" overrides the issue.) */
    public static function articleRestricted(int $submissionId): bool {
        static $cache = []; if (isset($cache[$submissionId])) return $cache[$submissionId];
        if (!self::enabled()) return $cache[$submissionId] = false;
        $r = DB::one('SELECT p.access_status pa, i.* FROM submissions s JOIN publications p ON p.id=s.current_publication_id LEFT JOIN issues i ON i.id=p.issue_id WHERE s.id=?', [$submissionId]);
        if (!$r || !$r['id']) return $cache[$submissionId] = false;
        return $cache[$submissionId] = (int)$r['pa'] !== 1 && self::issueRequires($r);
    }
    /** Valid individual subscription: active status and not past its end date (non-expiring types have no end date). */
    public static function subscribedUser(?int $userId, bool $partialDate = false): bool {
        if (!$userId) return false;
        $jid = (int)Settings::journal()['id'];
        return (bool)DB::val("SELECT 1 FROM subscriptions s JOIN subscription_types t ON t.id=s.type_id WHERE s.journal_id=? AND s.user_id=? AND t.institutional=0 AND s.status=1 AND (s.date_start IS NULL OR s.date_start<=CURDATE()) AND (s.date_end IS NULL OR s.date_end>=CURDATE()) LIMIT 1", [$jid, $userId]);
    }
    /** Valid institutional subscription for the visitor's IP address or reverse-DNS domain. */
    public static function subscribedDomain(?string $ip = null, ?string $host = null): bool {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? ''); $jid = (int)Settings::journal()['id'];
        $rows = DB::all("SELECT s.domain, s.ip_ranges FROM subscriptions s JOIN subscription_types t ON t.id=s.type_id WHERE s.journal_id=? AND t.institutional=1 AND s.status=1 AND (s.date_start IS NULL OR s.date_start<=CURDATE()) AND (s.date_end IS NULL OR s.date_end>=CURDATE())", [$jid]);
        foreach ($rows as $r) {
            foreach (preg_split('/[\s,;]+/', (string)$r['ip_ranges'], -1, PREG_SPLIT_NO_EMPTY) as $range) if (self::ipMatches($ip, $range)) return true;
            if ($r['domain'] !== null && $r['domain'] !== '') {
                $host = $host ?? (($h = @gethostbyaddr($ip)) && $h !== $ip ? $h : '');
                if ($host !== '' && stripos($host, trim($r['domain'])) !== false) return true;
            }
        }
        return false;
    }
    /** An IPv4 address, CIDR block, wildcard pattern (192.168.*) or range (10.0.0.1-10.0.0.50). */
    public static function ipMatches(string $ip, string $range): bool {
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) return false;
        if ($ip === $range) return true;
        if (str_contains($range, '/')) {
            [$net, $bits] = explode('/', $range, 2); $a = @inet_pton($ip); $b = @inet_pton($net); if ($a === false || $b === false || strlen($a) !== strlen($b)) return false;
            $bits = (int)$bits; $full = intdiv($bits, 8); if ($full && substr($a, 0, $full) !== substr($b, 0, $full)) return false;
            if ($bits % 8) { $mask = (0xFF << (8 - $bits % 8)) & 0xFF; return (ord($a[$full]) & $mask) === (ord($b[$full]) & $mask); }
            return true;
        }
        if (str_contains($range, '-') && ($p = explode('-', $range, 2)) && filter_var($p[0], FILTER_VALIDATE_IP) && filter_var($p[1], FILTER_VALIDATE_IP)) { $n = ip2long($ip); return $n !== false && $n >= ip2long($p[0]) && $n <= ip2long($p[1]); }
        if (str_contains($range, '*')) return (bool)preg_match('/^' . str_replace('\*', '[0-9]+', preg_quote($range, '/')) . '$/', $ip);
        return false;
    }
    /** May this visitor read the galleys of a published article? Editorial users always may (OJS allowedPrePublicationAccess). */
    public static function canRead(int $submissionId): bool {
        if (!self::articleRestricted($submissionId)) return true;
        if (array_intersect(Auth::roles(), ['manager', 'editor', 'site_admin', 'section_editor'])) return true;
        return self::subscribedUser(Auth::id()) || self::subscribedDomain();
    }
    /** OJS "hasAccess" flag used for the restricted-galley markup: only subscribers (by user or domain) pass, editors included are not special here. */
    public static function hasAccess(int $submissionId): bool {
        return !self::articleRestricted($submissionId) || self::subscribedUser(Auth::id()) || self::subscribedDomain();
    }
    public static function duration(?int $months): string {
        if ($months === null) return t('sub.non_expiring');
        $y = intdiv($months, 12); $m = $months % 12; $o = [];
        if ($y) $o[] = $y . ' ' . t($y === 1 ? 'sub.year' : 'sub.years'); if ($m) $o[] = $m . ' ' . t($m === 1 ? 'sub.month' : 'sub.months');
        return implode(' ', $o) ?: '0 ' . t('sub.months');
    }
    public static function types(int $jid, ?bool $institutional = null, bool $publicOnly = false): array {
        $w = ''; $a = [$jid]; if ($institutional !== null) { $w .= ' AND institutional=?'; $a[] = $institutional ? 1 : 0; } if ($publicOnly) $w .= ' AND disable_public_display=0';
        return DB::all("SELECT * FROM subscription_types WHERE journal_id=?$w ORDER BY seq,id", $a);
    }
}
