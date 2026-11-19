<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Mailer};
use App\Models\SubmissionRepo;
use App\Services\{PermissionService, WorkflowService, ReviewService, EmailTemplateService, NotificationService};

/** Editor side of peer review: find and invite reviewers, adjust deadlines, remind, cancel, reinstate, thank, read reviews. */
class ReviewController {
    private function ctx(string $sid): array {
        $sub = WorkflowController::load($sid);
        if (!PermissionService::canManage($sub)) WorkflowController::deny(403);
        if ((int)$sub['stage_id'] !== 3 || $sub['status'] !== 'queued') { flash(t('rev.not_in_review'), 'error'); redirect(url('workflow', 'index', [$sub['id']])); }
        return $sub;
    }
    private function assignment(array $sub, string $aid): array {
        $ra = DB::one('SELECT ra.*, u.given_name, u.family_name, u.email FROM review_assignments ra JOIN users u ON u.id=ra.reviewer_id WHERE ra.id=? AND ra.submission_id=?', [(int)$aid, $sub['id']]);
        if (!$ra) WorkflowController::deny(404);
        return $ra;
    }
    private function back(array $sub): never { redirect(url('workflow', 'index', [$sub['id'], 3])); }
    private function name(array $ra): string { return trim($ra['given_name'] . ' ' . $ra['family_name']); }
    private function validDate(string $d): bool { return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false; }

    public function search(string $sid = ''): void {
        $sub = $this->ctx($sid); $q = trim((string)($_GET['q'] ?? ''));
        $round = WorkflowService::currentRound((int)$sub['id']);
        $taken = array_column(DB::all("SELECT reviewer_id FROM review_assignments WHERE review_round_id=? AND status NOT IN ('cancelled','declined')", [$round['id']]), 'reviewer_id');
        View::render('backend/review_search', ['title' => t('rev.add'), 'menu' => 'submissions', 'sub' => $sub, 'q' => $q, 'taken' => array_map('intval', $taken),
            'results' => ReviewService::search((int)$sub['journal_id'], (int)$sub['id'], $q)], 'backend');
    }
    public function add(string $sid = '', string $userId = ''): void {
        $sub = $this->ctx($sid); $round = WorkflowService::currentRound((int)$sub['id']); $jid = (int)$sub['journal_id'];
        $rv = DB::one("SELECT u.* FROM users u JOIN user_user_groups x ON x.user_id=u.id JOIN user_groups g ON g.id=x.user_group_id WHERE u.id=? AND g.role_key='reviewer' AND g.journal_id=? AND u.disabled=0", [(int)$userId, $jid]);
        if (!$rv) WorkflowController::deny(404);
        if (DB::val("SELECT 1 FROM review_assignments WHERE review_round_id=? AND reviewer_id=? AND status NOT IN ('cancelled','declined')", [$round['id'], $rv['id']])) { flash(t('rev.already'), 'error'); $this->back($sub); }
        [$respDue, $revDue] = ReviewService::defaultDates(); $errors = [];
        $rname = trim($rv['given_name'] . ' ' . $rv['family_name']);
        $mail = EmailTemplateService::render('REVIEW_REQUEST', ['reviewerName' => $rname, 'submissionTitle' => $sub['title'], 'responseDueDate' => $respDue, 'reviewDueDate' => $revDue, 'reviewUrl' => '{$reviewUrl}']);
        if (Request::isPost()) {
            $method = array_key_exists($_POST['method'] ?? '', ReviewService::METHODS) ? $_POST['method'] : Settings::get('defaultReviewMode', 'anonymous');
            $rd = (string)($_POST['response_due'] ?? ''); $vd = (string)($_POST['review_due'] ?? '');
            if (!$this->validDate($rd)) $errors['response_due'] = t('rev.err_date');
            if (!$this->validDate($vd)) $errors['review_due'] = t('rev.err_date');
            if (!$errors && strtotime($vd) < strtotime($rd)) $errors['review_due'] = t('rev.err_order');
            if (!$errors) {
                $now = date('Y-m-d H:i:s'); $send = empty($_POST['skip_email']);
                $aid = DB::insert('review_assignments', ['submission_id' => $sub['id'], 'review_round_id' => $round['id'], 'reviewer_id' => $rv['id'], 'review_method' => $method, 'status' => 'awaiting_response',
                    'date_assigned' => $now, 'date_notified' => $send ? $now : null, 'date_response_due' => $rd . ' 23:59:59', 'date_due' => $vd . ' 23:59:59']);
                if ($send) Mailer::send($rv['email'], trim((string)$_POST['subject']), str_replace('{$reviewUrl}', abs_url(url('reviewer', 'submission', [$aid])), (string)$_POST['body']), (int)$sub['id']);
                NotificationService::add((int)$rv['id'], $jid, 'review_request', t('notif.review_request', $sub['title']), 'review_assignment', $aid);
                SubmissionRepo::log((int)$sub['id'], Auth::id(), 'reviewer_added', $rname);
                DB::exec('UPDATE submissions SET date_last_activity=? WHERE id=?', [$now, $sub['id']]);
                flash(t('rev.added', $rname)); $this->back($sub);
            }
        }
        View::render('backend/review_add', ['title' => t('rev.add'), 'menu' => 'submissions', 'sub' => $sub, 'rv' => $rv, 'mail' => $mail, 'respDue' => $_POST['response_due'] ?? $respDue, 'revDue' => $_POST['review_due'] ?? $revDue,
            'method' => $_POST['method'] ?? Settings::get('defaultReviewMode', 'anonymous'), 'errors' => $errors], 'backend');
    }
    public function edit(string $sid = '', string $aid = ''): void {
        $sub = $this->ctx($sid); $ra = $this->assignment($sub, $aid); $errors = [];
        if (Request::isPost()) {
            $rd = (string)($_POST['response_due'] ?? ''); $vd = (string)($_POST['review_due'] ?? '');
            if (!$this->validDate($rd)) $errors['response_due'] = t('rev.err_date');
            if (!$this->validDate($vd)) $errors['review_due'] = t('rev.err_date');
            if (!$errors) {
                DB::exec('UPDATE review_assignments SET date_response_due=?, date_due=?, review_method=? WHERE id=?', [$rd . ' 23:59:59', $vd . ' 23:59:59', array_key_exists($_POST['method'] ?? '', ReviewService::METHODS) ? $_POST['method'] : $ra['review_method'], $ra['id']]);
                SubmissionRepo::log((int)$sub['id'], Auth::id(), 'review_deadlines', $this->name($ra)); flash(t('rev.updated')); $this->back($sub);
            }
            $ra = array_merge($ra, ['date_response_due' => $rd, 'date_due' => $vd]);
        }
        View::render('backend/review_edit', ['title' => t('rev.edit_deadlines'), 'menu' => 'submissions', 'sub' => $sub, 'ra' => $ra, 'errors' => $errors], 'backend');
    }
    public function unassign(string $sid = '', string $aid = ''): void {
        $sub = $this->ctx($sid); $ra = $this->assignment($sub, $aid); if (!Request::isPost()) $this->back($sub);
        if (in_array($ra['status'], ['awaiting_response', 'accepted'], true)) {
            DB::exec("UPDATE review_assignments SET status='cancelled' WHERE id=?", [$ra['id']]);
            EmailTemplateService::send('REVIEW_CANCEL', $ra['email'], ['reviewerName' => $this->name($ra), 'submissionTitle' => $sub['title']], (int)$sub['id']);
            SubmissionRepo::log((int)$sub['id'], Auth::id(), 'reviewer_unassigned', $this->name($ra)); flash(t('rev.unassigned'));
        }
        $this->back($sub);
    }
    public function reinstate(string $sid = '', string $aid = ''): void {
        $sub = $this->ctx($sid); $ra = $this->assignment($sub, $aid); if (!Request::isPost()) $this->back($sub);
        if (in_array($ra['status'], ['cancelled', 'declined'], true)) {
            DB::exec("UPDATE review_assignments SET status='awaiting_response', date_confirmed=NULL WHERE id=?", [$ra['id']]);
            NotificationService::add((int)$ra['reviewer_id'], (int)$sub['journal_id'], 'review_request', t('notif.review_request', $sub['title']), 'review_assignment', (int)$ra['id']);
            SubmissionRepo::log((int)$sub['id'], Auth::id(), 'reviewer_reinstated', $this->name($ra)); flash(t('rev.reinstated'));
        }
        $this->back($sub);
    }
    public function remind(string $sid = '', string $aid = ''): void {
        $sub = $this->ctx($sid); $ra = $this->assignment($sub, $aid); if (!Request::isPost()) $this->back($sub);
        if (in_array($ra['status'], ['awaiting_response', 'accepted'], true)) {
            EmailTemplateService::send('REVIEW_REMIND', $ra['email'], ['reviewerName' => $this->name($ra), 'submissionTitle' => $sub['title'],
                'reviewDueDate' => fmt_date($ra['date_due']), 'reviewUrl' => abs_url(url('reviewer', 'submission', [$ra['id']]))], (int)$sub['id']);
            DB::exec('UPDATE review_assignments SET date_reminded=NOW() WHERE id=?', [$ra['id']]);
            NotificationService::add((int)$ra['reviewer_id'], (int)$sub['journal_id'], 'review_reminder', t('notif.review_reminder', $sub['title']), 'review_assignment', (int)$ra['id']);
            SubmissionRepo::log((int)$sub['id'], Auth::id(), 'reviewer_reminded', $this->name($ra)); flash(t('rev.reminded'));
        }
        $this->back($sub);
    }
    public function thank(string $sid = '', string $aid = ''): void {
        $sub = WorkflowController::load($sid); if (!PermissionService::canManage($sub)) WorkflowController::deny(403);
        $ra = $this->assignment($sub, $aid); if (!Request::isPost()) $this->back($sub);
        if ($ra['status'] === 'review_submitted') {
            DB::exec("UPDATE review_assignments SET status='thanked', date_acknowledged=NOW() WHERE id=?", [$ra['id']]);
            EmailTemplateService::send('REVIEW_ACK', $ra['email'], ['reviewerName' => $this->name($ra), 'submissionTitle' => $sub['title']], (int)$sub['id']);
            SubmissionRepo::log((int)$sub['id'], Auth::id(), 'reviewer_thanked', $this->name($ra)); flash(t('rev.thanked'));
        }
        $this->back($sub);
    }
    /** Editor view of a submitted review: both comment boxes, recommendation, competing interests, reviewer attachments. */
    public function details(string $sid = '', string $aid = ''): void {
        $sub = WorkflowController::load($sid); if (!PermissionService::canManage($sub)) WorkflowController::deny(403);
        $ra = $this->assignment($sub, $aid);
        View::render('backend/review_details', ['title' => t('rev.details'), 'menu' => 'submissions', 'sub' => $sub, 'ra' => $ra,
            'files' => DB::all("SELECT * FROM submission_files WHERE submission_id=? AND file_stage='review_attachment' AND assoc_type=1 AND assoc_id=?", [$sub['id'], $ra['id']])], 'backend');
    }
}
