<?php
namespace App\Models;
use App\Core\{DB, Auth};
class SubmissionRepo {
    const STAGES = [1 => 'stage.submission', 3 => 'stage.review', 4 => 'stage.copyediting', 5 => 'stage.production'];
    public static function find(int $id, int $jid): ?array {
        return DB::one('SELECT s.*, p.id pid, p.title, p.subtitle, p.prefix, p.abstract, p.keywords, p.references_text, p.primary_contact_id, sec.title section_title
            FROM submissions s LEFT JOIN publications p ON p.id=s.current_publication_id LEFT JOIN sections sec ON sec.id=s.section_id WHERE s.id=? AND s.journal_id=?', [$id, $jid]);
    }
    public static function authors(int $pid): array { return DB::all('SELECT * FROM authors WHERE publication_id=? ORDER BY seq,id', [$pid]); }
    public static function files(int $sid, string $stage = 'submission'): array {
        return DB::all('SELECT f.*, CONCAT(u.given_name," ",u.family_name) uploader FROM submission_files f LEFT JOIN users u ON u.id=f.uploader_user_id WHERE f.submission_id=? AND f.file_stage=? ORDER BY f.id', [$sid, $stage]);
    }
    public static function participants(int $sid): array {
        return DB::all('SELECT a.*, u.given_name, u.family_name, g.name role_name, g.role_key FROM stage_assignments a JOIN users u ON u.id=a.user_id JOIN user_groups g ON g.id=a.user_group_id WHERE a.submission_id=? ORDER BY g.id,u.family_name', [$sid]);
    }
    public static function log(int $sid, ?int $uid, string $event, string $msg = ''): void {
        DB::insert('event_log', ['submission_id' => $sid, 'user_id' => $uid, 'event' => $event, 'message' => $msg, 'created_at' => date('Y-m-d H:i:s')]);
    }
    public static function statusText(array $r): string {
        if ($r['status'] === 'incomplete') return t('status.incomplete', (int)$r['submission_progress']);
        if ($r['status'] === 'published') return t('status.published');
        if ($r['status'] === 'declined') return t('status.declined');
        if ($r['status'] === 'scheduled') return t('status.scheduled');
        if ((int)$r['stage_id'] === 1) return !empty($r['editors']) ? t('status.sub_inprogress') : t('status.awaiting_assignment');
        return t('status.in_stage');
    }

    /** Dashboard list for the signed-in user. $f: q, stage, section, incomplete. Returns [total, rows]. */
    public static function dashboard(int $jid, string $tab, array $f, int $page, int $per): array {
        $u = Auth::user(); $uid = (int)$u['id'];
        $staff = Auth::hasRole(['site_admin', 'manager', 'editor'], $jid);
        $w = ['s.journal_id=?']; $p = [$jid];
        $mine = '(s.created_by=? OR EXISTS (SELECT 1 FROM stage_assignments sa WHERE sa.submission_id=s.id AND sa.user_id=?) OR EXISTS (SELECT 1 FROM review_assignments ra WHERE ra.submission_id=s.id AND ra.reviewer_id=? AND ra.status NOT IN (\'cancelled\',\'declined\')))';
        switch ($tab) {
            case 'unassigned':
                if (!$staff) return [0, []];
                $w[] = "s.status='queued'"; $w[] = "NOT EXISTS (SELECT 1 FROM stage_assignments sa JOIN user_groups g ON g.id=sa.user_group_id WHERE sa.submission_id=s.id AND g.role_key IN ('editor','manager','section_editor'))"; break;
            case 'active':
                if (!$staff) return [0, []];
                $w[] = "s.status IN ('queued','scheduled')"; break;
            case 'archives':
                $w[] = "s.status IN ('published','declined')"; if (!$staff) { $w[] = $mine; array_push($p, $uid, $uid, $uid); } break;
            default:
                $w[] = "s.status IN ('incomplete','queued','scheduled')"; $w[] = $mine; array_push($p, $uid, $uid, $uid);
        }
        if (($f['q'] ?? '') !== '') {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $w[] = '(p.title LIKE ? OR s.id=? OR EXISTS (SELECT 1 FROM authors a WHERE a.publication_id=p.id AND CONCAT(a.given_name," ",a.family_name) LIKE ?))';
            array_push($p, $like, ctype_digit($f['q']) ? (int)$f['q'] : 0, $like);
        }
        if (!empty($f['stage'])) { $w[] = 's.stage_id=?'; $p[] = (int)$f['stage']; }
        if (!empty($f['section'])) { $w[] = 's.section_id=?'; $p[] = (int)$f['section']; }
        if (!empty($f['incomplete'])) $w[] = "s.status='incomplete'";
        $where = implode(' AND ', $w);
        $from = 'FROM submissions s LEFT JOIN publications p ON p.id=s.current_publication_id LEFT JOIN sections sec ON sec.id=s.section_id';
        $total = (int)DB::val("SELECT COUNT(*) $from WHERE $where", $p);
        $off = max(0, ($page - 1) * $per);
        $rows = DB::all("SELECT s.id, s.stage_id, s.status, s.submission_progress, s.date_submitted, s.date_last_activity, s.created_by, p.title, sec.title section_title,
            (SELECT GROUP_CONCAT(a.family_name ORDER BY a.seq SEPARATOR ', ') FROM authors a WHERE a.publication_id=p.id) authors_short,
            (SELECT COUNT(*) FROM queries q WHERE q.submission_id=s.id AND q.closed=0) open_discussions,
            (SELECT COUNT(*) FROM stage_assignments sa JOIN user_groups g ON g.id=sa.user_group_id WHERE sa.submission_id=s.id AND g.role_key IN ('editor','manager','section_editor')) editors
            $from WHERE $where ORDER BY COALESCE(s.date_last_activity, s.date_submitted) DESC, s.id DESC LIMIT $per OFFSET $off", $p);
        return [$total, $rows];
    }
}
