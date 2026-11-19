<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Upload, Request};
use App\Models\SubmissionRepo;
use App\Services\{PermissionService, WorkflowService, ReviewService, EmailTemplateService, NotificationService};

/** /workflow/index/{id}/{stage}: the submission page for editors, section editors and authors. */
class WorkflowController {
    private function jid(): int { return (int)Settings::journal()['id']; }
    public static function deny(int $code = 403): void { http_response_code($code); View::render('public/error', ['code' => $code, 'message' => t($code === 404 ? 'error.404' : 'error.403'), 'title' => (string)$code]); exit; }
    public static function load(string $id): array {
        Auth::requireLogin();
        $sub = SubmissionRepo::find((int)$id, (int)Settings::journal()['id']);
        if (!$sub) self::deny(404);
        if (!PermissionService::canViewSubmission($sub)) self::deny(403);
        return $sub;
    }
    public function index(string $id = '', string $stage = ''): void {
        $sub = self::load($id);
        if (PermissionService::canEditDraft($sub)) redirect(url('submission', 'wizard', [$sub['id']]));
        $jid = $this->jid(); $sid = (int)$sub['id']; $manage = PermissionService::canManage($sub); $decide = PermissionService::canDecide($sub);
        $cur = (int)$sub['stage_id']; $st = in_array((int)$stage, [1, 3, 4, 5], true) ? (int)$stage : $cur;
        if ($st > $cur && $sub['status'] !== 'published') $st = $cur;
        $tab = ($_GET['tab'] ?? 'workflow') === 'publication' ? 'publication' : 'workflow';
        if ($tab === 'publication' && $manage) redirect(url('publication', 'index', [$sid]));
        $allowed = PermissionService::allowedStages($sub);
        if ($allowed !== null && !in_array($st, $allowed, true)) { if (!$allowed) self::deny(403); $st = in_array($cur, $allowed, true) ? $cur : $allowed[0]; }
        $rounds = WorkflowService::rounds($sid); $roundNo = (int)($_GET['round'] ?? 0);
        $round = null; foreach ($rounds as $r) if ($roundNo ? (int)$r['round'] === $roundNo : true) $round = $r;
        if (!$roundNo && $rounds) $round = end($rounds);
        $data = ['title' => '#' . $sid, 'menu' => 'submissions', 'sub' => $sub, 'tab' => $tab, 'manage' => $manage, 'decide' => $decide, 'stage' => $st, 'cur' => $cur, 'rounds' => $rounds, 'round' => $round,
            'authors' => $sub['pid'] ? SubmissionRepo::authors((int)$sub['pid']) : [], 'participants' => SubmissionRepo::participants($sid), 'keywords' => json_decode($sub['keywords'] ?? '[]', true) ?: [],
            'files' => SubmissionRepo::files($sid), 'decisions' => $decide ? WorkflowService::available($sub) : [], 'me' => Auth::id(),
            'isAuthor' => PermissionService::isAuthorOf(Auth::id(), $sub)];
        if ($st === 3 && $round) {
            $rid = (int)$round['id'];
            $data['reviewFiles'] = DB::all('SELECT f.* FROM submission_files f JOIN review_round_files rf ON rf.submission_file_id=f.id WHERE rf.review_round_id=? ORDER BY f.id', [$rid]);
            $data['revisions'] = DB::all("SELECT f.*, CONCAT(u.given_name,' ',u.family_name) uploader FROM submission_files f LEFT JOIN users u ON u.id=f.uploader_user_id WHERE f.submission_id=? AND f.file_stage='review_revision' AND f.review_round_id=? ORDER BY f.id", [$sid, $rid]);
            $data['reviewers'] = $manage ? ReviewService::active($rid) : [];
            $data['roundDecisions'] = DB::all("SELECT d.*, CONCAT(u.given_name,' ',u.family_name) who FROM edit_decisions d JOIN users u ON u.id=d.editor_id WHERE d.submission_id=? AND d.review_round_id=? ORDER BY d.id", [$sid, $rid]);
            $last = end($data['roundDecisions']); $data['lastDecision'] = $last ?: null;
            $data['canRevise'] = $data['isAuthor'] && $sub['status'] === 'queued' && (int)$round['id'] === (int)WorkflowService::currentRound($sid)['id'] && $last && $last['decision'] === WorkflowService::REVISIONS;
        }
        if ($st === 4) {
            $data['draftFiles'] = SubmissionRepo::files($sid, 'copyedit');
            $data['candidates'] = array_merge(SubmissionRepo::files($sid, 'submission'), DB::all("SELECT * FROM submission_files WHERE submission_id=? AND file_stage='review_revision' ORDER BY id", [$sid]));
        }
        if ($st === 5) {
            $data['prodFiles'] = SubmissionRepo::files($sid, 'production_ready'); $data['candidates'] = SubmissionRepo::files($sid, 'copyedit');
            $data['galleys'] = $sub['pid'] ? DB::all('SELECT g.*, f.original_name FROM galleys g LEFT JOIN submission_files f ON f.id=g.submission_file_id WHERE g.publication_id=? ORDER BY g.seq,g.id', [$sub['pid']]) : [];
        }
        // authors must not see the names of internal copyediting/production files
        foreach (['draftFiles', 'prodFiles', 'candidates'] as $k) if (isset($data[$k])) $data[$k] = array_values(array_filter($data[$k], fn($f) => PermissionService::canDownloadFile($sub, $f)));
        $data['canWork'] = PermissionService::canWorkStage($sub, $st);
        $data['discussions'] = DB::all('SELECT q.*, (SELECT COUNT(*) FROM query_notes n WHERE n.query_id=q.id) notes,
            (SELECT COUNT(*) FROM query_notes n WHERE n.query_id=q.id AND n.user_id<>? AND n.created_at>COALESCE((SELECT r.last_read_at FROM query_reads r WHERE r.query_id=q.id AND r.user_id=?),"1970-01-01")) unread
            FROM queries q JOIN query_participants p ON p.query_id=q.id AND p.user_id=? WHERE q.submission_id=? AND q.stage_id=? ORDER BY q.id DESC', [Auth::id(), Auth::id(), Auth::id(), $sid, $st]);
        if (!$data['discussions'] && $manage) $data['discussions'] = DB::all('SELECT q.*, (SELECT COUNT(*) FROM query_notes n WHERE n.query_id=q.id) notes, 0 unread FROM queries q WHERE q.submission_id=? AND q.stage_id=? ORDER BY q.id DESC', [$sid, $st]);
        $data['discTotal'] = array_column(DB::all('SELECT stage_id, COUNT(*) c FROM queries WHERE submission_id=? AND closed=0 GROUP BY stage_id', [$sid]), 'c', 'stage_id');
        View::render('backend/workflow', $data, 'backend');
    }
    public function log(string $id = ''): void {
        $sub = self::load($id); if (!PermissionService::canManage($sub)) self::deny(403);
        View::render('backend/workflow_log', ['title' => t('wf.log'), 'menu' => 'submissions', 'sub' => $sub,
            'log' => DB::all('SELECT l.*, CONCAT(u.given_name," ",u.family_name) who FROM event_log l LEFT JOIN users u ON u.id=l.user_id WHERE l.submission_id=? ORDER BY l.id DESC', [$sub['id']]),
            'emails' => DB::all('SELECT id,to_addr,subject,created_at,sent FROM email_log WHERE submission_id=? ORDER BY id DESC', [$sub['id']])], 'backend');
    }
    /** Streams a stored file after PermissionService says yes. Files live outside the web root. */
    public function download(string $id = '', string $fileId = ''): void {
        $sub = self::load($id);
        $f = DB::one('SELECT * FROM submission_files WHERE id=? AND submission_id=?', [(int)$fileId, $sub['id']]);
        if (!$f || !PermissionService::canDownloadFile($sub, $f)) self::deny(403);
        self::send($sub, $f);
    }
    public static function send(array $sub, array $f): void {
        $path = Upload::baseDir() . "/journals/{$sub['journal_id']}/submissions/{$sub['id']}/{$f['file_stage']}/{$f['stored_name']}";
        if (!is_file($path)) self::deny(404);
        header('Content-Type: ' . $f['mime']); header('Content-Length: ' . filesize($path)); header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._ -]/', '_', $f['original_name']) . '"');
        readfile($path);
    }
    /** POST: staff add review files to the current round; the author uploads revisions after "Request Revisions". */
    public function uploadFile(string $id = '', string $roundId = ''): void {
        $sub = self::load($id); $sid = (int)$sub['id']; $jid = $this->jid();
        if (!Request::isPost()) redirect(url('workflow', 'index', [$sid]));
        $round = DB::one('SELECT * FROM review_rounds WHERE id=? AND submission_id=?', [(int)$roundId, $sid]);
        $manage = PermissionService::canManage($sub); $last = $round ? WorkflowService::lastDecision($sid, (int)$round['id']) : null;
        $isRev = !$manage && PermissionService::isAuthorOf(Auth::id(), $sub) && $round && $last && $last['decision'] === WorkflowService::REVISIONS && $sub['status'] === 'queued'
            && (int)WorkflowService::currentRound($sid)['id'] === (int)$round['id'];
        if (!$round || !($manage || $isRev)) self::deny(403);
        $stage = $isRev ? 'review_revision' : 'review_file';
        try {
            if (empty($_FILES['file']['name'])) throw new \RuntimeException(t('wizard.err_no_file'));
            $f = Upload::store($_FILES['file'], "journals/$jid/submissions/$sid/$stage");
            $fid = DB::insert('submission_files', ['submission_id' => $sid, 'file_stage' => $stage, 'genre' => $isRev ? 'Revised Article Text' : 'Review File', 'original_name' => $f['original_name'], 'stored_name' => $f['stored_name'],
                'mime' => $f['mime'], 'size' => $f['size'], 'uploader_user_id' => Auth::id(), 'review_round_id' => $round['id'], 'viewable' => $isRev ? 1 : 0, 'created_at' => date('Y-m-d H:i:s')]);
            if (!$isRev) DB::exec('INSERT IGNORE INTO review_round_files (review_round_id,submission_file_id) VALUES (?,?)', [$round['id'], $fid]);
            SubmissionRepo::log($sid, Auth::id(), $isRev ? 'revision_uploaded' : 'review_file_added', $f['original_name']);
            DB::exec('UPDATE submissions SET date_last_activity=NOW() WHERE id=?', [$sid]);
            if ($isRev) {
                $u = Auth::user();
                foreach (WorkflowService::editorsOf($sid, $jid, Auth::id()) as $ed) {
                    EmailTemplateService::send('REVISED_VERSION_NOTIFY', $ed['email'], ['editorName' => trim($ed['given_name'] . ' ' . $ed['family_name']), 'submissionTitle' => $sub['title'], 'submissionUrl' => abs_url(url('workflow', 'index', [$sid, 3]))], $sid);
                    NotificationService::add((int)$ed['id'], $jid, 'revision_uploaded', t('notif.revision', $sub['title']), 'submission', $sid);
                }
            }
            flash(t('wizard.uploaded', $f['original_name']));
        } catch (\RuntimeException $x) { flash($x->getMessage(), 'error'); }
        redirect(url('workflow', 'index', [$sid, 3], ['round' => $round['round']]));
    }
    public function deleteFile(string $id = '', string $fileId = ''): void {
        $sub = self::load($id); $sid = (int)$sub['id'];
        $f = DB::one("SELECT * FROM submission_files WHERE id=? AND submission_id=? AND file_stage IN ('review_file','review_revision','submission','copyedit','production_ready')", [(int)$fileId, $sid]);
        if (!$f || !Request::isPost()) self::deny(403);
        $stageOf = ['copyedit' => 4, 'production_ready' => 5][$f['file_stage']] ?? 0;
        $ok = PermissionService::canManage($sub) || ($stageOf && PermissionService::canWorkStage($sub, $stageOf)) || ($f['file_stage'] === 'review_revision' && (int)$f['uploader_user_id'] === Auth::id() && $sub['status'] === 'queued');
        if (!$ok) self::deny(403);
        @unlink(Upload::baseDir() . "/journals/{$sub['journal_id']}/submissions/$sid/{$f['file_stage']}/{$f['stored_name']}");
        DB::exec('DELETE FROM submission_files WHERE id=?', [$f['id']]); SubmissionRepo::log($sid, Auth::id(), 'file_deleted', $f['original_name']); flash(t('wizard.deleted'));
        redirect(url('workflow', 'index', [$sid, ['submission' => 1, 'copyedit' => 4, 'production_ready' => 5][$f['file_stage']] ?? 3]));
    }
    /** POST: add a file to Copyediting (draft or copyedited) or Production (production-ready). */
    public function uploadStageFile(string $id = '', string $stage = ''): void {
        $sub = self::load($id); $sid = (int)$sub['id']; $st = (int)$stage;
        if (!Request::isPost() || !in_array($st, [4, 5], true) || !PermissionService::canWorkStage($sub, $st) || $sub['status'] === 'declined') self::deny(403);
        $fs = $st === 4 ? 'copyedit' : 'production_ready';
        $genre = $st === 4 ? (in_array($_POST['genre'] ?? '', ['Draft', 'Copyedited'], true) ? $_POST['genre'] : 'Draft') : 'Production Ready';
        try {
            if (empty($_FILES['file']['name'])) throw new \RuntimeException(t('wizard.err_no_file'));
            $f = Upload::store($_FILES['file'], "journals/{$sub['journal_id']}/submissions/$sid/$fs");
            DB::insert('submission_files', ['submission_id' => $sid, 'file_stage' => $fs, 'genre' => $genre, 'original_name' => $f['original_name'], 'stored_name' => $f['stored_name'], 'mime' => $f['mime'], 'size' => $f['size'],
                'uploader_user_id' => Auth::id(), 'viewable' => 0, 'created_at' => date('Y-m-d H:i:s')]);
            SubmissionRepo::log($sid, Auth::id(), 'stage_file_added', $f['original_name'] . ' (' . $genre . ')'); DB::exec('UPDATE submissions SET date_last_activity=NOW() WHERE id=?', [$sid]);
            flash(t('wizard.uploaded', $f['original_name']));
        } catch (\RuntimeException $x) { flash($x->getMessage(), 'error'); }
        redirect(url('workflow', 'index', [$sid, $st]));
    }
    /** POST: copy an existing file into Copyediting (as a draft) or into Production (as production-ready). */
    public function promote(string $id = '', string $fileId = '', string $to = ''): void {
        $sub = self::load($id); $st = $to === 'production_ready' ? 5 : 4;
        if (!Request::isPost() || !PermissionService::canManage($sub)) self::deny(403);
        $f = DB::one("SELECT * FROM submission_files WHERE id=? AND submission_id=? AND file_stage IN ('submission','review_revision','copyedit')", [(int)$fileId, $sub['id']]);
        if ($f && WorkflowService::copyFile($sub, $f, $st === 5 ? 'production_ready' : 'copyedit', $st === 5 ? 'Production Ready' : 'Draft', Auth::id())) { SubmissionRepo::log((int)$sub['id'], Auth::id(), 'file_promoted', $f['original_name']); flash(t('wf.file_added')); }
        redirect(url('workflow', 'index', [$sub['id'], $st]));
    }
}
