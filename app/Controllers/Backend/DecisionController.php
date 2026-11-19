<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Mailer};
use App\Models\SubmissionRepo;
use App\Services\{PermissionService, WorkflowService, EmailTemplateService, NotificationService};

/** Editorial decisions with an editable e-mail to the author(s). GET shows the form, POST records the decision. */
class DecisionController {
    private function ctx(string $id, string $decision): array {
        $sub = WorkflowController::load($id);
        if (!PermissionService::canDecide($sub)) WorkflowController::deny(403);
        if (!in_array($decision, WorkflowService::available($sub), true)) { flash(t('dec.not_available'), 'error'); redirect(url('workflow', 'index', [$sub['id']])); }
        return $sub;
    }
    public function index(string $id = '', string $decision = ''): void {
        $sub = $this->ctx($id, $decision); $sid = (int)$sub['id']; $round = WorkflowService::currentRound($sid);
        $recips = WorkflowService::authorRecipients($sid);
        $names = implode(', ', array_map(fn($r) => trim($r['given_name'] . ' ' . $r['family_name']), $recips));
        $rendered = isset(WorkflowService::EMAIL_KEY[$decision]) ? EmailTemplateService::render(WorkflowService::EMAIL_KEY[$decision], [
            'authorName' => $names, 'submissionTitle' => $sub['title'], 'submissionUrl' => abs_url(url('workflow', 'index', [$sid])),
            'reviewerComments' => WorkflowService::reviewerCommentsText($sid, $round ? (int)$round['id'] : null)]) : null;
        View::render('backend/decision', ['title' => t('dec.' . $decision), 'menu' => 'submissions', 'sub' => $sub, 'decision' => $decision, 'recipients' => $recips, 'mail' => $rendered,
            'files' => in_array($decision, [WorkflowService::SEND_REVIEW, WorkflowService::RESUBMIT, WorkflowService::NEW_ROUND], true) ? WorkflowService::candidateFiles($sub, $decision) : []], 'backend');
    }
    public function save(string $id = '', string $decision = ''): void {
        $sub = $this->ctx($id, $decision); if (!Request::isPost()) redirect(url('decision', 'index', [$id, $decision]));
        $sid = (int)$sub['id']; $jid = (int)Settings::journal()['id']; $files = array_map('intval', (array)($_POST['files'] ?? []));
        if (in_array($decision, [WorkflowService::SEND_REVIEW, WorkflowService::RESUBMIT, WorkflowService::NEW_ROUND], true) && !$files) { flash(t('dec.err_files'), 'error'); redirect(url('decision', 'index', [$id, $decision])); }
        WorkflowService::apply($decision, $sub, Auth::id(), ['files' => $files]);
        $recips = WorkflowService::authorRecipients($sid);
        if (empty($_POST['skip_email']) && trim((string)($_POST['body'] ?? '')) !== '')
            foreach ($recips as $r) Mailer::send($r['email'], trim((string)$_POST['subject']) ?: t('dec.' . $decision), (string)$_POST['body'], $sid);
        foreach ($recips as $r) NotificationService::add((int)$r['id'], $jid, 'decision', t('notif.decision', t('dec.' . $decision), $sub['title']), 'submission', $sid);
        flash(t('dec.recorded', t('dec.' . $decision)));
        $next = $decision === WorkflowService::DECLINE ? (int)$sub['stage_id'] : (in_array($decision, [WorkflowService::SEND_REVIEW, WorkflowService::RESUBMIT, WorkflowService::NEW_ROUND], true) ? 3 : ($decision === WorkflowService::REVISIONS ? 3 : 4));
        redirect(url('workflow', 'index', [$sid, $next]));
    }
}
