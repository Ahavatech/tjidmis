<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings};

/** Tools > Statistics: cards, a monthly chart, editorial numbers and a top-articles table for a date range. */
class StatisticsController {
    const ROLES = ['editor', 'manager', 'site_admin'];
    public function index(): void {
        Auth::require(self::ROLES); $jid = (int)Settings::journal()['id'];
        $ok = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d);
        $to = $ok($_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d'); $from = $ok($_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-d', strtotime('-12 months'));
        if ($from > $to) [$from, $to] = [$to, $from];
        $m = ['abstract_view' => 0, 'galley_download' => 0];
        foreach (DB::all('SELECT type, SUM(count) n FROM metrics WHERE journal_id=? AND day BETWEEN ? AND ? GROUP BY type', [$jid, $from, $to]) as $r) $m[$r['type']] = (int)$r['n'];
        $series = DB::all("SELECT DATE_FORMAT(day,'%Y-%m') ym, SUM(CASE WHEN type='abstract_view' THEN count ELSE 0 END) views, SUM(CASE WHEN type='galley_download' THEN count ELSE 0 END) downloads
            FROM metrics WHERE journal_id=? AND day BETWEEN ? AND ? GROUP BY ym ORDER BY ym", [$jid, $from, $to]);
        $recv = (int)DB::val("SELECT COUNT(*) FROM submissions WHERE journal_id=? AND date_submitted IS NOT NULL AND DATE(date_submitted) BETWEEN ? AND ?", [$jid, $from, $to]);
        $dec = fn($list) => (int)DB::val("SELECT COUNT(DISTINCT d.submission_id) FROM edit_decisions d JOIN submissions s ON s.id=d.submission_id WHERE s.journal_id=? AND d.decision IN ($list) AND DATE(d.date_decided) BETWEEN ? AND ?", [$jid, $from, $to]);
        $accepted = $dec("'accept','accept_skip_review'"); $declined = $dec("'decline'");
        $days = DB::val("SELECT ROUND(AVG(x.d),1) FROM (SELECT DATEDIFF(MIN(d.date_decided), s.date_submitted) d FROM edit_decisions d JOIN submissions s ON s.id=d.submission_id
            WHERE s.journal_id=? AND s.date_submitted IS NOT NULL AND DATE(d.date_decided) BETWEEN ? AND ? GROUP BY s.id) x", [$jid, $from, $to]);
        $top = DB::all("SELECT s.id, p.title, SUM(CASE WHEN m.type='abstract_view' THEN m.count ELSE 0 END) views, SUM(CASE WHEN m.type='galley_download' THEN m.count ELSE 0 END) downloads
            FROM metrics m JOIN submissions s ON s.id=m.submission_id JOIN publications p ON p.id=s.current_publication_id WHERE m.journal_id=? AND m.day BETWEEN ? AND ? GROUP BY s.id, p.title ORDER BY views DESC, downloads DESC LIMIT 10", [$jid, $from, $to]);
        View::render('backend/statistics', ['title' => t('menu.statistics'), 'menu' => 'tools', 'from' => $from, 'to' => $to, 'm' => $m, 'series' => $series, 'recv' => $recv, 'accepted' => $accepted, 'declined' => $declined,
            'rate' => $accepted + $declined ? round(100 * $accepted / ($accepted + $declined)) : null, 'days' => $days, 'top' => $top], 'backend');
    }
}
