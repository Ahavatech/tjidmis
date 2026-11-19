<?php
namespace App\Services;
use App\Core\DB;
class ReviewService {
    const METHODS = ['anonymous' => 'review.method_anonymous', 'double_anonymous' => 'review.method_double', 'open' => 'review.method_open'];
    const RECOMMENDATIONS = ['accept' => 'review.rec_accept', 'pending_revisions' => 'review.rec_revisions', 'resubmit_here' => 'review.rec_resubmit_here',
        'resubmit_elsewhere' => 'review.rec_resubmit_elsewhere', 'decline' => 'review.rec_decline', 'see_comments' => 'review.rec_see_comments'];
    public static function isOverdue(array $ra): bool {
        if (in_array($ra['status'], ['review_submitted', 'thanked', 'declined', 'cancelled'], true)) return false;
        $due = $ra['status'] === 'awaiting_response' ? ($ra['date_response_due'] ?: $ra['date_due']) : $ra['date_due'];
        return $due && strtotime($due) < time();
    }
    public static function statusLabel(array $ra): string {
        $base = t('review.status_' . $ra['status']);
        return self::isOverdue($ra) ? $base . ' (' . t('review.overdue') . ')' : $base;
    }
    /** Active, unfinished assignments in the round (not cancelled/declined). */
    public static function active(int $roundId): array {
        return DB::all("SELECT ra.*, u.given_name, u.family_name, u.email, u.affiliation FROM review_assignments ra JOIN users u ON u.id=ra.reviewer_id WHERE ra.review_round_id=? ORDER BY ra.id", [$roundId]);
    }
    /** Reviewer search with the stats editors use to choose: active reviews, last completed, average days. */
    public static function search(int $jid, int $sid, string $q): array {
        $w = ["g.role_key='reviewer'", 'u.disabled=0', 'g.journal_id=?', 'u.id NOT IN (SELECT x.user_id FROM stage_assignments x JOIN user_groups xg ON xg.id=x.user_group_id WHERE x.submission_id=? AND xg.role_key=\'author\')']; $p = [$jid, $sid];
        if ($q !== '') { $like = '%' . addcslashes($q, '%_\\') . '%'; $w[] = '(CONCAT(u.given_name," ",u.family_name) LIKE ? OR u.reviewing_interests LIKE ? OR u.email LIKE ? OR u.affiliation LIKE ?)'; array_push($p, $like, $like, $like, $like); }
        return DB::all("SELECT DISTINCT u.id,u.given_name,u.family_name,u.email,u.affiliation,u.reviewing_interests,
            (SELECT COUNT(*) FROM review_assignments r WHERE r.reviewer_id=u.id AND r.status IN ('awaiting_response','accepted')) active_reviews,
            (SELECT MAX(r.date_completed) FROM review_assignments r WHERE r.reviewer_id=u.id AND r.date_completed IS NOT NULL) last_completed,
            (SELECT ROUND(AVG(DATEDIFF(r.date_completed,r.date_assigned)),1) FROM review_assignments r WHERE r.reviewer_id=u.id AND r.date_completed IS NOT NULL) avg_days
            FROM users u JOIN user_user_groups x ON x.user_id=u.id JOIN user_groups g ON g.id=x.user_group_id WHERE " . implode(' AND ', $w) . ' ORDER BY u.family_name,u.given_name LIMIT 50', $p);
    }
    public static function defaultDates(): array {
        $r = (int)\App\Core\Settings::get('numWeeksPerResponse', '3'); $v = (int)\App\Core\Settings::get('numWeeksPerReview', '4');
        return [date('Y-m-d', strtotime("+$r weeks")), date('Y-m-d', strtotime("+$v weeks"))];
    }
    public static function pseudonym(int $i): string { return t('review.reviewer_letter', chr(65 + $i % 26)); }
}
