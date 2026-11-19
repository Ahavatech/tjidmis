<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request};
use App\Models\SubmissionRepo;
use App\Services\{PermissionService, EmailTemplateService, NotificationService};

/** Assign / remove participants on a submission (editors, section editors, copyeditors, layout editors, proofreaders). */
class ParticipantController {
    const ROLES = ['editor', 'section_editor', 'copyeditor', 'layout_editor', 'proofreader'];
    public function assign(string $id = ''): void {
        $sub = WorkflowController::load($id); if (!PermissionService::canManage($sub)) WorkflowController::deny(403);
        $jid = (int)Settings::journal()['id']; $sid = (int)$sub['id']; $errors = [];
        $groups = DB::all("SELECT id,role_key,name FROM user_groups WHERE journal_id=? AND role_key IN ('" . implode("','", self::ROLES) . "') ORDER BY id", [$jid]);
        $role = (int)($_POST['group'] ?? $_GET['group'] ?? ($groups[0]['id'] ?? 0));
        $candidates = DB::all("SELECT DISTINCT u.id,u.given_name,u.family_name,u.email FROM users u JOIN user_user_groups x ON x.user_id=u.id WHERE x.user_group_id=? AND u.disabled=0
            AND u.id NOT IN (SELECT user_id FROM stage_assignments WHERE submission_id=? AND user_group_id=?) ORDER BY u.family_name", [$role, $sid, $role]);
        $msg = ['assign' => ''];
        if (Request::isPost() && isset($_POST['user'])) {
            $uid = (int)$_POST['user']; $g = DB::one('SELECT * FROM user_groups WHERE id=? AND journal_id=?', [$role, $jid]);
            if (!$g || !in_array($g['role_key'], self::ROLES, true) || !in_array($uid, array_map('intval', array_column($candidates, 'id')), true)) $errors['user'] = t('part.err_user');
            else {
                DB::exec('INSERT INTO stage_assignments (submission_id,user_group_id,user_id,recommend_only,can_change_metadata,date_assigned) VALUES (?,?,?,?,?,NOW())',
                    [$sid, $role, $uid, !empty($_POST['recommend_only']) ? 1 : 0, !empty($_POST['can_change_metadata']) ? 1 : 0]);
                $u = DB::one('SELECT * FROM users WHERE id=?', [$uid]);
                SubmissionRepo::log($sid, Auth::id(), 'participant_assigned', trim($u['given_name'] . ' ' . $u['family_name']) . ' (' . $g['name'] . ')');
                NotificationService::add($uid, $jid, 'assigned', t('notif.assigned', $sub['title']), 'submission', $sid);
                if (empty($_POST['no_email'])) EmailTemplateService::send('EDITOR_ASSIGN', $u['email'], ['recipientName' => trim($u['given_name'] . ' ' . $u['family_name']), 'roleName' => $g['name'],
                    'submissionTitle' => $sub['title'], 'submissionUrl' => abs_url(url('workflow', 'index', [$sid])), 'message' => trim((string)($_POST['message'] ?? ''))], $sid);
                flash(t('part.assigned', trim($u['given_name'] . ' ' . $u['family_name']))); redirect(url('workflow', 'index', [$sid]));
            }
        }
        View::render('backend/assign_participant', ['title' => t('part.assign'), 'menu' => 'submissions', 'sub' => $sub, 'groups' => $groups, 'role' => $role, 'candidates' => $candidates, 'errors' => $errors], 'backend');
    }
    public function remove(string $id = '', string $assignmentId = ''): void {
        $sub = WorkflowController::load($id); if (!PermissionService::canManage($sub) || !Request::isPost()) WorkflowController::deny(403);
        $a = DB::one("SELECT a.*, g.role_key FROM stage_assignments a JOIN user_groups g ON g.id=a.user_group_id WHERE a.id=? AND a.submission_id=?", [(int)$assignmentId, $sub['id']]);
        if ($a && $a['role_key'] !== 'author') { DB::exec('DELETE FROM stage_assignments WHERE id=?', [$a['id']]); SubmissionRepo::log((int)$sub['id'], Auth::id(), 'participant_removed', (string)$a['user_id']); flash(t('part.removed')); }
        redirect(url('workflow', 'index', [$sub['id']]));
    }
}
