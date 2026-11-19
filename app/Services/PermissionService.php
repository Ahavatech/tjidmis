<?php
namespace App\Services;
use App\Core\{Auth, DB};
/** Central access rules for submissions and files. Every back-end route calls these. */
class PermissionService {
    const STAFF = ['site_admin', 'manager', 'editor'];
    public static function isStaff(?int $jid = null): bool { return Auth::hasRole(self::STAFF, $jid); }
    public static function isAssigned(int $userId, int $submissionId): bool {
        return (bool)DB::val('SELECT 1 FROM stage_assignments WHERE submission_id=? AND user_id=? LIMIT 1', [$submissionId, $userId]);
    }
    public static function isAuthorOf(int $userId, array $sub): bool {
        return (int)($sub['created_by'] ?? 0) === $userId
            || (bool)DB::val("SELECT 1 FROM stage_assignments a JOIN user_groups g ON g.id=a.user_group_id WHERE a.submission_id=? AND a.user_id=? AND g.role_key='author' LIMIT 1", [$sub['id'], $userId]);
    }
    public static function isReviewerOf(int $userId, int $submissionId): bool {
        return (bool)DB::val("SELECT 1 FROM review_assignments WHERE submission_id=? AND reviewer_id=? AND status NOT IN ('cancelled','declined') LIMIT 1", [$submissionId, $userId]);
    }
    /** May this user open the submission at all? */
    public static function canViewSubmission(array $sub): bool {
        $u = Auth::user(); if (!$u) return false; $jid = (int)$sub['journal_id'];
        if (self::isStaff($jid)) return true;
        $uid = (int)$u['id'];
        if (self::isAuthorOf($uid, $sub)) return true;
        if (Auth::hasRole(['section_editor'], $jid) && self::isAssigned($uid, (int)$sub['id'])) return true;
        if (self::stageRoles((int)$sub['id'], $uid)) return true;
        if (Auth::hasRole(['reviewer'], $jid) && self::isReviewerOf($uid, (int)$sub['id'])) return false;   // reviewers use the review screens, not the workflow page
        return false;
    }
    /** Role keys of copyeditor / layout editor / proofreader assignments the user holds on this submission. */
    public static function stageRoles(int $sid, int $uid): array {
        return array_column(DB::all("SELECT DISTINCT g.role_key FROM stage_assignments a JOIN user_groups g ON g.id=a.user_group_id WHERE a.submission_id=? AND a.user_id=? AND g.role_key IN ('copyeditor','layout_editor','proofreader')", [$sid, $uid]), 'role_key');
    }
    /** Stages a stage-limited participant may open (null = no restriction). */
    public static function allowedStages(array $sub): ?array {
        if (self::canManage($sub)) return null;
        $u = Auth::user(); $uid = (int)$u['id'];
        if (self::isAuthorOf($uid, $sub)) return null;
        $roles = self::stageRoles((int)$sub['id'], $uid); if (!$roles) return null;
        return array_map('intval', array_column(DB::all("SELECT DISTINCT s.stage_id FROM stage_assignments a JOIN user_groups g ON g.id=a.user_group_id JOIN user_group_stages s ON s.user_group_id=g.id WHERE a.submission_id=? AND a.user_id=? AND g.role_key IN ('copyeditor','layout_editor','proofreader')", [$sub['id'], $uid]), 'stage_id'));
    }
    /** May the user add/remove files in this stage? Editors always; copyeditors in 4; layout editors and proofreaders in 5. */
    public static function canWorkStage(array $sub, int $stage): bool {
        if (self::canManage($sub)) return true;
        $roles = self::stageRoles((int)$sub['id'], Auth::id());
        return ($stage === 4 && in_array('copyeditor', $roles, true)) || ($stage === 5 && (bool)array_intersect(['layout_editor', 'proofreader'], $roles));
    }
    public static function canEditDraft(array $sub): bool {
        $u = Auth::user(); return $u && $sub['status'] === 'incomplete' && (int)$sub['created_by'] === (int)$u['id'];
    }
    /** Files: staff and assigned editors see everything; authors see submission-stage files and files marked viewable. */
    public static function canDownloadFile(array $sub, array $file): bool {
        if (!self::canViewSubmission($sub)) return false;
        $u = Auth::user(); $jid = (int)$sub['journal_id'];
        if (self::isStaff($jid) || (Auth::hasRole(['section_editor'], $jid) && self::isAssigned((int)$u['id'], (int)$sub['id']))) return true;
        $roles = self::stageRoles((int)$sub['id'], (int)$u['id']);
        if ($roles && !PermissionService::isAuthorOf((int)$u['id'], $sub)) {
            $st = ['copyedit' => 4, 'production_ready' => 5, 'galley' => 5, 'proof' => 5][$file['file_stage']] ?? 0;
            $allowed = self::allowedStages($sub) ?? [];
            return $st && in_array($st, $allowed, true);
        }
        return $file['file_stage'] === 'submission' || (int)$file['viewable'] === 1 || (int)$file['uploader_user_id'] === (int)$u['id'];
    }
    /** Staff always; a section editor only when assigned to the submission. */
    public static function canManage(array $sub): bool {
        $u = Auth::user(); if (!$u) return false; $jid = (int)$sub['journal_id'];
        return self::isStaff($jid) || (Auth::hasRole(['section_editor'], $jid) && self::isAssigned((int)$u['id'], (int)$sub['id']));
    }
    /** Recording decisions: editors/managers/admins, or an assigned section editor who is not recommend-only. */
    public static function canDecide(array $sub): bool {
        $u = Auth::user(); if (!$u) return false; $jid = (int)$sub['journal_id'];
        if (self::isStaff($jid)) return true;
        return Auth::hasRole(['section_editor'], $jid) && (bool)DB::val('SELECT 1 FROM stage_assignments WHERE submission_id=? AND user_id=? AND recommend_only=0 LIMIT 1', [$sub['id'], $u['id']]);
    }
    public static function submissionRoleIds(int $jid, string $roleKey): ?int {
        $id = DB::val('SELECT id FROM user_groups WHERE journal_id=? AND role_key=?', [$jid, $roleKey]); return $id ? (int)$id : null;
    }
}
