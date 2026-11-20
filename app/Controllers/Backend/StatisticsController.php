<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings};

/** Statistics menu (as in OJS 3.3): Articles, Editorial Activity, Users, Reports. */
class StatisticsController {
    const ROLES = ['editor', 'manager', 'site_admin'];
    private function range(): array {
        $ok = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d);
        $to = $ok($_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d'); $from = $ok($_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-d', strtotime('-12 months'));
        if ($from > $to) [$from, $to] = [$to, $from];
        return [$from, $to];
    }
    public function index(): void { $this->articles(); }
    public function articles(): void {
        Auth::require(self::ROLES); $jid = (int)Settings::journal()['id']; [$from, $to] = $this->range();
        $m = ['abstract_view' => 0, 'galley_download' => 0];
        foreach (DB::all('SELECT type, SUM(count) n FROM metrics WHERE journal_id=? AND day BETWEEN ? AND ? GROUP BY type', [$jid, $from, $to]) as $r) $m[$r['type']] = (int)$r['n'];
        $series = DB::all("SELECT DATE_FORMAT(day,'%Y-%m') ym, SUM(CASE WHEN type='abstract_view' THEN count ELSE 0 END) views, SUM(CASE WHEN type='galley_download' THEN count ELSE 0 END) downloads
            FROM metrics WHERE journal_id=? AND day BETWEEN ? AND ? GROUP BY ym ORDER BY ym", [$jid, $from, $to]);
        $top = DB::all("SELECT s.id, p.title, SUM(CASE WHEN m.type='abstract_view' THEN m.count ELSE 0 END) views, SUM(CASE WHEN m.type='galley_download' THEN m.count ELSE 0 END) downloads
            FROM metrics m JOIN submissions s ON s.id=m.submission_id JOIN publications p ON p.id=s.current_publication_id WHERE m.journal_id=? AND m.day BETWEEN ? AND ? GROUP BY s.id, p.title ORDER BY views DESC, downloads DESC LIMIT 20", [$jid, $from, $to]);
        View::render('backend/statistics', ['title' => t('stat.menu_articles'), 'menu' => 'statistics', 'from' => $from, 'to' => $to, 'm' => $m, 'series' => $series, 'top' => $top], 'backend');
    }
    public function editorial(): void {
        Auth::require(self::ROLES); $jid = (int)Settings::journal()['id']; [$from, $to] = $this->range();
        $recv = (int)DB::val("SELECT COUNT(*) FROM submissions WHERE journal_id=? AND date_submitted IS NOT NULL AND DATE(date_submitted) BETWEEN ? AND ?", [$jid, $from, $to]);
        $dec = fn($list) => (int)DB::val("SELECT COUNT(DISTINCT d.submission_id) FROM edit_decisions d JOIN submissions s ON s.id=d.submission_id WHERE s.journal_id=? AND d.decision IN ($list) AND DATE(d.date_decided) BETWEEN ? AND ?", [$jid, $from, $to]);
        $accepted = $dec("'accept','accept_skip_review'"); $declined = $dec("'decline'");
        $days = DB::val("SELECT ROUND(AVG(x.d),1) FROM (SELECT DATEDIFF(MIN(d.date_decided), s.date_submitted) d FROM edit_decisions d JOIN submissions s ON s.id=d.submission_id
            WHERE s.journal_id=? AND s.date_submitted IS NOT NULL AND DATE(d.date_decided) BETWEEN ? AND ? GROUP BY s.id) x", [$jid, $from, $to]);
        $published = (int)DB::val("SELECT COUNT(*) FROM publications p JOIN submissions s ON s.id=p.submission_id WHERE s.journal_id=? AND p.status='published' AND p.date_published BETWEEN ? AND ?", [$jid, $from, $to]);
        View::render('backend/statistics_editorial', ['title' => t('stat.menu_editorial'), 'menu' => 'statistics', 'from' => $from, 'to' => $to, 'recv' => $recv, 'accepted' => $accepted, 'declined' => $declined, 'published' => $published,
            'rate' => $accepted + $declined ? round(100 * $accepted / ($accepted + $declined)) : null, 'days' => $days], 'backend');
    }
    public function users(): void {
        Auth::require(self::ROLES); $jid = (int)Settings::journal()['id']; [$from, $to] = $this->range();
        $roles = DB::all("SELECT g.name, COUNT(DISTINCT uug.user_id) n FROM user_groups g LEFT JOIN user_user_groups uug ON uug.user_group_id=g.id WHERE g.journal_id=? GROUP BY g.id, g.name ORDER BY g.id", [$jid]);
        $total = (int)DB::val('SELECT COUNT(*) FROM users'); $new = (int)DB::val('SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?', [$from, $to]);
        View::render('backend/statistics_users', ['title' => t('stat.menu_users'), 'menu' => 'statistics', 'from' => $from, 'to' => $to, 'roles' => $roles, 'total' => $total, 'new' => $new], 'backend');
    }
    public function reports(): void {
        Auth::require(self::ROLES);
        View::render('backend/statistics_reports', ['title' => t('stat.menu_reports'), 'menu' => 'statistics'], 'backend');
    }
    /** GET /statistics/report/{articles|reviews|users}: CSV downloads. */
    public function report(string $type = ''): void {
        Auth::require(self::ROLES); $jid = (int)Settings::journal()['id'];
        if ($type === 'articles') {
            $head = ['ID', 'Title', 'Authors', 'Section', 'Issue', 'Date published', 'Abstract views', 'Galley downloads'];
            $rows = DB::all("SELECT s.id, p.title, (SELECT GROUP_CONCAT(CONCAT(a.given_name,' ',a.family_name) ORDER BY a.seq SEPARATOR '; ') FROM authors a WHERE a.publication_id=p.id) au, se.title sec, CONCAT_WS(' ', 'Vol.', i.volume, 'No.', i.number, CONCAT('(', i.year, ')')) iss, p.date_published,
                (SELECT COALESCE(SUM(count),0) FROM metrics WHERE submission_id=s.id AND type='abstract_view') v, (SELECT COALESCE(SUM(count),0) FROM metrics WHERE submission_id=s.id AND type='galley_download') d
                FROM submissions s JOIN publications p ON p.id=s.current_publication_id LEFT JOIN sections se ON se.id=p.section_id LEFT JOIN issues i ON i.id=p.issue_id WHERE s.journal_id=? AND s.status='published' ORDER BY s.id", [$jid]);
        } elseif ($type === 'reviews') {
            $head = ['Submission ID', 'Title', 'Reviewer', 'Date assigned', 'Date completed', 'Recommendation'];
            $rows = DB::all("SELECT s.id, p.title, CONCAT(u.given_name,' ',u.family_name) rv, ra.date_assigned, ra.date_completed, ra.recommendation FROM review_assignments ra JOIN submissions s ON s.id=ra.submission_id
                JOIN publications p ON p.id=s.current_publication_id JOIN users u ON u.id=ra.reviewer_id WHERE s.journal_id=? ORDER BY ra.id", [$jid]);
        } elseif ($type === 'users') {
            $head = ['Username', 'Email', 'Given name', 'Family name', 'Affiliation', 'Country', 'Registered', 'Roles'];
            $rows = DB::all("SELECT u.username, u.email, u.given_name, u.family_name, u.affiliation, u.country, u.created_at, (SELECT GROUP_CONCAT(g.name SEPARATOR '; ') FROM user_user_groups x JOIN user_groups g ON g.id=x.user_group_id WHERE x.user_id=u.id) roles FROM users u ORDER BY u.id");
        } else { http_response_code(404); echo 'Not found'; return; }
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $type . '-report-' . date('Y-m-d') . '.csv"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, $head);
        foreach ($rows as $r) fputcsv($o, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, array_values($r)));   // spreadsheet formula guard
        fclose($o);
    }
}
