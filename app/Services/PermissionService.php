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
        if (Auth::hasRole(['reviewer'], $jid) && self::isReviewerOf($uid, (int)$sub['id'])) return false;   // reviewers use the review screens, not the workflow page
        return false;
    }
    public static function canEditDraft(array $sub): bool {
        $u = Auth::user(); return $u && $sub['status'] === 'incomplete' && (int)$sub['created_by'] === (int)$u['id'];
    }
    /** Files: staff and assigned editors see everything; authors see submission-stage files and files marked viewable. */
    public static function canDownloadFile(array $sub, array $file): bool {
        if (!self::canViewSubmission($sub)) return false;
        $u = Auth::user(); $jid = (int)$sub['journal_id'];
        if (self::isStaff($jid) || (Auth::hasRole(['section_editor'], $jid) && self::isAssigned((int)$u['id'], (int)$sub['id']))) return true;
        return $file['file_stage'] === 'submission' || (int)$file['viewable'] === 1;
    }
    public static function submissionRoleIds(int $jid, string $roleKey): ?int {
        $id = DB::val('SELECT id FROM user_groups WHERE journal_id=? AND role_key=?', [$jid, $roleKey]); return $id ? (int)$id : null;
    }
}
