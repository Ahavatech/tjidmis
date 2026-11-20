<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings};
use App\Models\SubmissionRepo;
class DashboardController {
    public function index(): void {
        Auth::requireLogin();
        $jid = (int)Settings::journal()['id']; $r = Auth::roles($jid);
        $staff = (bool)array_intersect(['site_admin', 'manager', 'editor'], $r);
        $tabs = ['queue' => t('dash.my_queue')];
        if ($staff) { $tabs['unassigned'] = t('dash.unassigned'); $tabs['active'] = t('dash.active'); }
        $tabs['archives'] = t('dash.archives');
        $tab = $_GET['tab'] ?? 'queue'; if (!isset($tabs[$tab])) $tab = 'queue';
        $ints = fn($v) => array_values(array_filter(array_map('intval', (array)$v)));
        $f = ['q' => trim((string)($_GET['q'] ?? '')), 'stage' => $ints($_GET['stage'] ?? []), 'section' => $ints($_GET['section'] ?? []), 'incomplete' => !empty($_GET['incomplete']), 'overdue' => !empty($_GET['overdue']),
            'days' => max(0, (int)($_GET['days'] ?? 0)), 'assigned' => (int)($_GET['assigned'] ?? 0), 'issue' => (int)($_GET['issue'] ?? 0)];
        $page = max(1, (int)($_GET['page'] ?? 1)); $per = 20;
        [$total, $rows] = SubmissionRepo::dashboard($jid, $tab, $f, $page, $per);
        foreach ($rows as &$row) {   // reviewers see their assignment, not the editorial workflow page
            $row['review_only'] = false;
            if (!empty($row['my_review_id']) && !$staff) {
                $row['review_only'] = !\App\Services\PermissionService::isAuthorOf(Auth::id(), ['id' => $row['id'], 'created_by' => $row['created_by']]) && !\App\Services\PermissionService::isAssigned(Auth::id(), (int)$row['id']);
            }
        } unset($row);
        $counts = [];
        foreach (array_keys($tabs) as $k) $counts[$k] = $k === $tab ? $total : SubmissionRepo::dashboard($jid, $k, [], 1, 1)[0];
        View::render('backend/dashboard', ['title' => t('menu.submissions'), 'menu' => 'submissions', 'tabs' => $tabs, 'tab' => $tab, 'counts' => $counts, 'f' => $f, 'rows' => $rows,
            'editors' => $staff ? DB::all("SELECT DISTINCT u.id, u.given_name, u.family_name FROM users u JOIN user_user_groups x ON x.user_id=u.id JOIN user_groups g ON g.id=x.user_group_id WHERE g.journal_id=? AND g.role_key IN ('editor','manager','section_editor') ORDER BY u.family_name, u.given_name", [$jid]) : [],
            'issues' => DB::all('SELECT * FROM issues WHERE journal_id=? ORDER BY seq, id DESC', [$jid]), 'total' => $total, 'page' => $page, 'per' => $per, 'sections' => DB::all('SELECT id,title FROM sections WHERE journal_id=? ORDER BY seq', [$jid]),
            'canSubmit' => Settings::get('submissionsDisabled', '0') !== '1' && (bool)array_intersect(['author', 'editor', 'manager', 'site_admin', 'section_editor'], $r)], 'backend');
    }
}
