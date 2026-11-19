<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Upload, Sanitizer};
use App\Models\SubmissionRepo;
use App\Services\{WorkflowService, ReviewService, EmailTemplateService, NotificationService};

/** The reviewer's own screens: /reviewer/submission/{assignmentId}/{step}. Reviewers never get the editorial workflow page. */
class ReviewerController {
    private function load(string $aid): array {
        Auth::requireLogin();
        $ra = DB::one('SELECT ra.*, s.journal_id, s.stage_id, s.status sub_status, p.id pid, p.title, p.abstract FROM review_assignments ra JOIN submissions s ON s.id=ra.submission_id LEFT JOIN publications p ON p.id=s.current_publication_id
            WHERE ra.id=? AND ra.reviewer_id=? AND s.journal_id=?', [(int)$aid, Auth::id(), (int)Settings::journal()['id']]);
        if (!$ra || $ra['status'] === 'cancelled') WorkflowController::deny(404);
        return $ra;
    }
    private function done(array $ra): bool { return in_array($ra['status'], ['review_submitted', 'thanked'], true); }
    private function files(array $ra): array {
        return DB::all('SELECT f.* FROM submission_files f JOIN review_round_files rf ON rf.submission_file_id=f.id WHERE rf.review_round_id=? ORDER BY f.id', [$ra['review_round_id']]);
    }
    private function attachments(array $ra): array {
        return DB::all("SELECT * FROM submission_files WHERE submission_id=? AND file_stage='review_attachment' AND assoc_type=1 AND assoc_id=? ORDER BY id", [$ra['submission_id'], $ra['id']]);
    }
    public function submission(string $aid = '', string $step = ''): void {
        $ra = $this->load($aid);
        if ($ra['status'] === 'awaiting_response' || $ra['status'] === 'declined') { $max = 1; $n = 1; }
        else { $max = $this->done($ra) ? 4 : 3; $n = (int)$step ?: ($this->done($ra) ? 4 : 2); $n = max(1, min($max, $n)); }
        $authors = $ra['review_method'] === 'double_anonymous' ? [] : SubmissionRepo::authors((int)$ra['pid']);
        View::render('backend/reviewer_flow', ['title' => t('rflow.title'), 'menu' => 'submissions', 'ra' => $ra, 'n' => $n, 'max' => $max, 'authors' => $authors,
            'files' => $max === 1 ? [] : $this->files($ra), 'attachments' => $this->attachments($ra), 'guidelines' => Settings::get('reviewGuidelines', ''),
            'competing' => Settings::get('competingInterests', ''), 'readonly' => $this->done($ra)], 'backend');
    }
    public function accept(string $aid = ''): void {
        $ra = $this->load($aid); if (!Request::isPost() || $ra['status'] !== 'awaiting_response') redirect(url('reviewer', 'submission', [$aid]));
        if (empty($_POST['consent'])) { flash(t('rflow.err_consent'), 'error'); redirect(url('reviewer', 'submission', [$aid, 1])); }
        $ci = ($_POST['competing'] ?? '') === 'yes' ? trim((string)($_POST['competing_text'] ?? '')) : null;
        DB::exec("UPDATE review_assignments SET status='accepted', date_confirmed=NOW(), competing_interests=? WHERE id=?", [$ci ?: null, $ra['id']]);
        $this->notifyEditors($ra, 'REVIEW_CONFIRM', t('notif.review_accepted', $ra['title']), []);
        SubmissionRepo::log((int)$ra['submission_id'], Auth::id(), 'review_accepted', ''); flash(t('rflow.accepted'));
        redirect(url('reviewer', 'submission', [$aid, 2]));
    }
    public function decline(string $aid = ''): void {
        $ra = $this->load($aid); if (!Request::isPost() || $ra['status'] !== 'awaiting_response') redirect(url('reviewer', 'submission', [$aid]));
        DB::exec("UPDATE review_assignments SET status='declined', date_confirmed=NOW() WHERE id=?", [$ra['id']]);
        $reason = trim((string)($_POST['reason'] ?? ''));
        $this->notifyEditors($ra, 'REVIEW_DECLINE', t('notif.review_declined', $ra['title']), ['declineReason' => $reason]);
        SubmissionRepo::log((int)$ra['submission_id'], Auth::id(), 'review_declined', $reason); flash(t('rflow.declined'));
        redirect(url('submissions'));
    }
    public function saveReview(string $aid = ''): void {
        $ra = $this->load($aid); if (!Request::isPost() || $ra['status'] !== 'accepted') redirect(url('reviewer', 'submission', [$aid]));
        $rec = array_key_exists($_POST['recommendation'] ?? '', ReviewService::RECOMMENDATIONS) ? $_POST['recommendation'] : null;
        $wrap = function ($t) { $t = trim((string)$t); if ($t === '') return null; return Sanitizer::clean(stripos($t, '<p') === false ? implode('', array_map(fn($x) => '<p>' . nl2br(e(trim($x))) . '</p>', preg_split('/\R{2,}/', $t))) : $t); };
        DB::exec('UPDATE review_assignments SET comments_author=?, comments_editor=?, recommendation=? WHERE id=?', [$wrap($_POST['comments_author'] ?? ''), $wrap($_POST['comments_editor'] ?? ''), $rec, $ra['id']]);
        if (($_POST['action'] ?? '') === 'save') { flash(t('rflow.saved')); redirect(url('reviewer', 'submission', [$aid, 3])); }
        $r = DB::one('SELECT * FROM review_assignments WHERE id=?', [$ra['id']]);
        if (!$r['recommendation'] || (!$r['comments_author'] && !$r['comments_editor'])) { flash(t('rflow.err_review'), 'error'); redirect(url('reviewer', 'submission', [$aid, 3])); }
        DB::exec("UPDATE review_assignments SET status='review_submitted', date_completed=NOW() WHERE id=?", [$ra['id']]);
        $this->notifyEditors($ra, 'REVIEW_COMPLETE', t('notif.review_complete', $ra['title']), []);
        DB::exec('UPDATE submissions SET date_last_activity=NOW() WHERE id=?', [$ra['submission_id']]);
        SubmissionRepo::log((int)$ra['submission_id'], Auth::id(), 'review_submitted', ''); flash(t('rflow.submitted'));
        redirect(url('reviewer', 'submission', [$aid, 4]));
    }
    public function uploadAttachment(string $aid = ''): void {
        $ra = $this->load($aid); if (!Request::isPost() || $ra['status'] !== 'accepted') redirect(url('reviewer', 'submission', [$aid]));
        try {
            if (empty($_FILES['file']['name'])) throw new \RuntimeException(t('wizard.err_no_file'));
            $f = Upload::store($_FILES['file'], "journals/{$ra['journal_id']}/submissions/{$ra['submission_id']}/review_attachment");
            DB::insert('submission_files', ['submission_id' => $ra['submission_id'], 'file_stage' => 'review_attachment', 'genre' => 'Review Attachment', 'original_name' => $f['original_name'], 'stored_name' => $f['stored_name'],
                'mime' => $f['mime'], 'size' => $f['size'], 'uploader_user_id' => Auth::id(), 'review_round_id' => $ra['review_round_id'], 'assoc_type' => 1, 'assoc_id' => $ra['id'], 'viewable' => 0, 'created_at' => date('Y-m-d H:i:s')]);
            flash(t('wizard.uploaded', $f['original_name']));
        } catch (\RuntimeException $x) { flash($x->getMessage(), 'error'); }
        redirect(url('reviewer', 'submission', [$aid, 3]));
    }
    public function deleteAttachment(string $aid = '', string $fileId = ''): void {
        $ra = $this->load($aid); if (!Request::isPost() || $ra['status'] !== 'accepted') redirect(url('reviewer', 'submission', [$aid]));
        $f = DB::one("SELECT * FROM submission_files WHERE id=? AND file_stage='review_attachment' AND assoc_type=1 AND assoc_id=?", [(int)$fileId, $ra['id']]);
        if ($f) { @unlink(Upload::baseDir() . "/journals/{$ra['journal_id']}/submissions/{$ra['submission_id']}/review_attachment/{$f['stored_name']}"); DB::exec('DELETE FROM submission_files WHERE id=?', [$f['id']]); flash(t('wizard.deleted')); }
        redirect(url('reviewer', 'submission', [$aid, 3]));
    }
    /** Reviewers may download only the files of their own round, and only after accepting. */
    public function download(string $aid = '', string $fileId = ''): void {
        $ra = $this->load($aid); if ($ra['status'] === 'awaiting_response' || $ra['status'] === 'declined') WorkflowController::deny(403);
        $f = DB::one('SELECT f.* FROM submission_files f WHERE f.id=? AND f.submission_id=?', [(int)$fileId, $ra['submission_id']]);
        $inRound = $f && DB::val('SELECT 1 FROM review_round_files WHERE review_round_id=? AND submission_file_id=?', [$ra['review_round_id'], $f['id']]);
        $own = $f && $f['file_stage'] === 'review_attachment' && (int)$f['assoc_id'] === (int)$ra['id'];
        if (!$inRound && !$own) WorkflowController::deny(403);
        WorkflowController::send(['id' => $ra['submission_id'], 'journal_id' => $ra['journal_id']], $f);
    }
    private function notifyEditors(array $ra, string $key, string $note, array $extra): void {
        $u = Auth::user(); $jid = (int)$ra['journal_id'];
        foreach (WorkflowService::editorsOf((int)$ra['submission_id'], $jid, Auth::id()) as $ed) {
            EmailTemplateService::send($key, $ed['email'], $extra + ['editorName' => trim($ed['given_name'] . ' ' . $ed['family_name']), 'reviewerName' => trim($u['given_name'] . ' ' . $u['family_name']),
                'submissionTitle' => $ra['title'], 'submissionUrl' => abs_url(url('workflow', 'index', [$ra['submission_id'], 3]))], (int)$ra['submission_id']);
            NotificationService::add((int)$ed['id'], $jid, 'review_update', $note, 'submission', (int)$ra['submission_id']);
        }
    }
}
