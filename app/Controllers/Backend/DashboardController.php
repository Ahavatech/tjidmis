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
        if ($staff) { $tabs['unassigned'] = t('dash.unassigned'); $tabs['active'] = t('dash.all_active'); }
        $tabs['archives'] = t('dash.archives');
        $tab = $_GET['tab'] ?? 'queue'; if (!isset($tabs[$tab])) $tab = 'queue';
        $f = ['q' => trim((string)($_GET['q'] ?? '')), 'stage' => (int)($_GET['stage'] ?? 0), 'section' => (int)($_GET['section'] ?? 0), 'incomplete' => !empty($_GET['incomplete'])];
        $page = max(1, (int)($_GET['page'] ?? 1)); $per = 20;
        [$total, $rows] = SubmissionRepo::dashboard($jid, $tab, $f, $page, $per);
        $counts = [];
        foreach (array_keys($tabs) as $k) $counts[$k] = $k === $tab ? $total : SubmissionRepo::dashboard($jid, $k, [], 1, 1)[0];
        View::render('backend/dashboard', ['title' => t('menu.submissions'), 'menu' => 'submissions', 'tabs' => $tabs, 'tab' => $tab, 'counts' => $counts, 'f' => $f, 'rows' => $rows,
            'total' => $total, 'page' => $page, 'per' => $per, 'sections' => DB::all('SELECT id,title FROM sections WHERE journal_id=? ORDER BY seq', [$jid]),
            'canSubmit' => (bool)array_intersect(['author', 'editor', 'manager', 'site_admin', 'section_editor'], $r)], 'backend');
    }
}
