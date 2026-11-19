<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Sanitizer};
use App\Models\SubmissionRepo;
use App\Services\{PermissionService, EmailTemplateService, NotificationService};

/** Per-stage discussion threads between the participants of a submission. */
class DiscussionController {
    private function thread(string $qid): array {
        Auth::requireLogin();
        $q = DB::one('SELECT q.*, s.journal_id FROM queries q JOIN submissions s ON s.id=q.submission_id WHERE q.id=? AND s.journal_id=?', [(int)$qid, (int)Settings::journal()['id']]);
        if (!$q) WorkflowController::deny(404);
        $sub = SubmissionRepo::find((int)$q['submission_id'], (int)$q['journal_id']);
        $isPart = (bool)DB::val('SELECT 1 FROM query_participants WHERE query_id=? AND user_id=?', [$q['id'], Auth::id()]);
        if (!$isPart && !PermissionService::canManage($sub)) WorkflowController::deny(403);
        return [$q, $sub];
    }
    private function body(): ?string {
        $t = trim((string)($_POST['message'] ?? '')); if ($t === '') return null;
        return Sanitizer::clean(implode('', array_map(fn($x) => '<p>' . nl2br(e(trim($x))) . '</p>', preg_split('/\R{2,}/', $t))));
    }
    public function create(string $sid = '', string $stage = '1'): void {
        $sub = WorkflowController::load($sid); $sid = (int)$sub['id']; $stage = in_array((int)$stage, [1, 3, 4, 5], true) ? (int)$stage : 1;
        $me = Auth::id(); $errors = [];
        $people = DB::all("SELECT u.id,u.given_name,u.family_name,MIN(g.name) role_name FROM users u JOIN (
                SELECT a.user_id uid, a.user_group_id gid FROM stage_assignments a WHERE a.submission_id=?
                UNION SELECT x.user_id, x.user_group_id FROM user_user_groups x JOIN user_groups g2 ON g2.id=x.user_group_id WHERE g2.role_key IN ('editor','manager') AND g2.journal_id=?) m ON m.uid=u.id
            JOIN user_groups g ON g.id=m.gid WHERE u.id<>? AND u.disabled=0 GROUP BY u.id ORDER BY u.family_name", [$sid, (int)$sub['journal_id'], $me]);
        if (Request::isPost()) {
            $subject = trim((string)($_POST['subject'] ?? '')); $body = $this->body(); $ids = array_map('intval', (array)($_POST['participants'] ?? []));
            $ids = array_values(array_intersect($ids, array_map('intval', array_column($people, 'id'))));
            if ($subject === '') $errors['subject'] = t('form.required_field');
            if (!$body) $errors['message'] = t('form.required_field');
            if (!$ids) $errors['participants'] = t('disc.err_participants');
            if (!$errors) {
                $qid = DB::insert('queries', ['submission_id' => $sid, 'stage_id' => $stage, 'subject' => mb_substr($subject, 0, 255), 'closed' => 0, 'created_by' => $me, 'created_at' => date('Y-m-d H:i:s')]);
                foreach (array_merge([$me], $ids) as $uid) DB::exec('INSERT IGNORE INTO query_participants (query_id,user_id) VALUES (?,?)', [$qid, $uid]);
                DB::insert('query_notes', ['query_id' => $qid, 'user_id' => $me, 'contents' => $body, 'created_at' => date('Y-m-d H:i:s')]);
                DB::exec('REPLACE INTO query_reads (query_id,user_id,last_read_at) VALUES (?,?,NOW())', [$qid, $me]);
                $this->notify($qid, $sub, $subject, $ids); SubmissionRepo::log($sid, $me, 'discussion_created', $subject);
                flash(t('disc.created')); redirect(url('discussion', 'view', [$qid]));
            }
        }
        View::render('backend/discussion_new', ['title' => t('disc.add'), 'menu' => 'submissions', 'sub' => $sub, 'stage' => $stage, 'people' => $people, 'errors' => $errors], 'backend');
    }
    public function view(string $qid = ''): void {
        [$q, $sub] = $this->thread($qid);
        View::render('backend/discussion_view', ['title' => $q['subject'], 'menu' => 'submissions', 'q' => $q, 'sub' => $sub, 'canClose' => (int)$q['created_by'] === Auth::id() || PermissionService::canManage($sub),
            'notes' => DB::all("SELECT n.*, CONCAT(u.given_name,' ',u.family_name) who FROM query_notes n LEFT JOIN users u ON u.id=n.user_id WHERE n.query_id=? ORDER BY n.id", [$q['id']]),
            'people' => DB::all("SELECT CONCAT(u.given_name,' ',u.family_name) name FROM query_participants p JOIN users u ON u.id=p.user_id WHERE p.query_id=?", [$q['id']])], 'backend');
        DB::exec('REPLACE INTO query_reads (query_id,user_id,last_read_at) VALUES (?,?,NOW())', [$q['id'], Auth::id()]);
    }
    public function reply(string $qid = ''): void {
        [$q, $sub] = $this->thread($qid); if (!Request::isPost()) redirect(url('discussion', 'view', [$qid]));
        $body = $this->body();
        if ($q['closed']) flash(t('disc.closed_note'), 'error');
        elseif (!$body) flash(t('form.required_field'), 'error');
        else {
            DB::insert('query_notes', ['query_id' => $q['id'], 'user_id' => Auth::id(), 'contents' => $body, 'created_at' => date('Y-m-d H:i:s')]);
            DB::exec('REPLACE INTO query_reads (query_id,user_id,last_read_at) VALUES (?,?,NOW())', [$q['id'], Auth::id()]);
            $others = array_column(DB::all('SELECT user_id FROM query_participants WHERE query_id=? AND user_id<>?', [$q['id'], Auth::id()]), 'user_id');
            $this->notify((int)$q['id'], $sub, $q['subject'], array_map('intval', $others)); DB::exec('UPDATE submissions SET date_last_activity=NOW() WHERE id=?', [$sub['id']]);
        }
        redirect(url('discussion', 'view', [$qid]));
    }
    public function close(string $qid = ''): void {
        [$q, $sub] = $this->thread($qid);
        if (Request::isPost() && ((int)$q['created_by'] === Auth::id() || PermissionService::canManage($sub))) { DB::exec('UPDATE queries SET closed=? WHERE id=?', [$q['closed'] ? 0 : 1, $q['id']]); flash($q['closed'] ? t('disc.reopened') : t('disc.closed')); }
        redirect(url('discussion', 'view', [$qid]));
    }
    private function notify(int $qid, array $sub, string $subject, array $userIds): void {
        $jid = (int)$sub['journal_id'];
        foreach ($userIds as $uid) {
            $u = DB::one('SELECT * FROM users WHERE id=? AND disabled=0', [$uid]); if (!$u) continue;
            NotificationService::add($uid, $jid, 'discussion', t('notif.discussion', $subject), 'discussion', $qid);
            $pref = DB::val("SELECT value FROM user_settings WHERE user_id=? AND journal_id=? AND name='emailPref_discussions'", [$uid, $jid]);
            if ($pref !== '0') EmailTemplateService::send('DISCUSSION_NOTIFICATION', $u['email'], ['recipientName' => trim($u['given_name'] . ' ' . $u['family_name']), 'discussionSubject' => $subject,
                'submissionTitle' => $sub['title'], 'submissionUrl' => abs_url(url('discussion', 'view', [$qid]))], (int)$sub['id']);
        }
    }
}
