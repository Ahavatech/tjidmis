<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Upload};
use App\Models\SubmissionRepo;
use App\Services\PermissionService;

/** /workflow/index/{id}: the submission page. Phase 4 = read-only view for the author and a summary for staff; editorial actions arrive in Phase 5. */
class WorkflowController {
    private function jid(): int { return (int)Settings::journal()['id']; }
    private function deny(int $code = 403): void { http_response_code($code); View::render('public/error', ['code' => $code, 'message' => t($code === 404 ? 'error.404' : 'error.403'), 'title' => (string)$code]); exit; }
    private function load(string $id): array {
        Auth::requireLogin();
        $sub = SubmissionRepo::find((int)$id, $this->jid());
        if (!$sub) $this->deny(404);
        if (!PermissionService::canViewSubmission($sub)) $this->deny(403);
        return $sub;
    }
    public function index(string $id = '', string $stage = ''): void {
        $sub = $this->load($id);
        if (PermissionService::canEditDraft($sub)) redirect(url('submission', 'wizard', [$sub['id']]));
        $jid = $this->jid(); $staff = PermissionService::isStaff($jid);
        $tab = ($_GET['tab'] ?? 'workflow') === 'publication' ? 'publication' : 'workflow';
        View::render('backend/workflow', ['title' => '#' . $sub['id'], 'menu' => 'submissions', 'sub' => $sub, 'tab' => $tab, 'staff' => $staff,
            'authors' => $sub['pid'] ? SubmissionRepo::authors((int)$sub['pid']) : [], 'files' => SubmissionRepo::files((int)$sub['id']),
            'participants' => SubmissionRepo::participants((int)$sub['id']), 'keywords' => json_decode($sub['keywords'] ?? '[]', true) ?: [],
            'log' => $staff ? DB::all('SELECT l.*, CONCAT(u.given_name," ",u.family_name) who FROM event_log l LEFT JOIN users u ON u.id=l.user_id WHERE l.submission_id=? ORDER BY l.id DESC LIMIT 30', [$sub['id']]) : [],
            'discussions' => (int)DB::val('SELECT COUNT(*) FROM queries WHERE submission_id=? AND closed=0', [$sub['id']])], 'backend');
    }
    /** Streams a stored file after PermissionService says yes. Files live outside the web root. */
    public function download(string $id = '', string $fileId = ''): void {
        $sub = $this->load($id);
        $f = DB::one('SELECT * FROM submission_files WHERE id=? AND submission_id=?', [(int)$fileId, $sub['id']]);
        if (!$f || !PermissionService::canDownloadFile($sub, $f)) $this->deny(403);
        $path = Upload::baseDir() . "/journals/{$this->jid()}/submissions/{$sub['id']}/{$f['file_stage']}/{$f['stored_name']}";
        if (!is_file($path)) $this->deny(404);
        header('Content-Type: ' . $f['mime']); header('Content-Length: ' . filesize($path)); header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._ -]/', '_', $f['original_name']) . '"');
        readfile($path);
    }
}
