<?php
namespace App\Services;
use App\Core\{DB, Auth, Settings};
use App\Models\SubmissionRepo;

/** Stage transitions and review rounds. Stage ids follow OJS: 1 submission, 3 review, 4 copyediting, 5 production. */
class WorkflowService {
    const SEND_REVIEW = 'send_to_review', SKIP = 'accept_skip_review', DECLINE = 'decline', REVISIONS = 'request_revisions', RESUBMIT = 'resubmit', ACCEPT = 'accept', NEW_ROUND = 'new_round', SEND_PRODUCTION = 'send_to_production';
    const EMAIL_KEY = [self::SEND_REVIEW => 'EDITOR_DECISION_SEND_TO_EXTERNAL', self::SKIP => 'EDITOR_DECISION_ACCEPT', self::DECLINE => 'EDITOR_DECISION_DECLINE',
        self::REVISIONS => 'EDITOR_DECISION_REVISIONS', self::RESUBMIT => 'EDITOR_DECISION_RESUBMIT', self::ACCEPT => 'EDITOR_DECISION_ACCEPT', self::SEND_PRODUCTION => 'EDITOR_DECISION_SEND_TO_PRODUCTION'];

    public static function currentRound(int $sid): ?array { return DB::one('SELECT * FROM review_rounds WHERE submission_id=? ORDER BY round DESC LIMIT 1', [$sid]); }
    public static function rounds(int $sid): array { return DB::all('SELECT * FROM review_rounds WHERE submission_id=? ORDER BY round', [$sid]); }
    public static function lastDecision(int $sid, ?int $roundId): ?array {
        return DB::one('SELECT * FROM edit_decisions WHERE submission_id=? AND (review_round_id <=> ?) ORDER BY id DESC LIMIT 1', [$sid, $roundId]);
    }
    /** Decisions the editor can take now, in the order the buttons appear. */
    public static function available(array $sub): array {
        if ($sub['status'] !== 'queued') return [];
        if ((int)$sub['stage_id'] === 1) return [self::SEND_REVIEW, self::SKIP, self::DECLINE];
        if ((int)$sub['stage_id'] === 3) {
            $r = self::currentRound((int)$sub['id']); $last = $r ? self::lastDecision((int)$sub['id'], (int)$r['id']) : null;
            $d = [self::REVISIONS, self::RESUBMIT, self::ACCEPT, self::DECLINE];
            if ($last && $last['decision'] === self::REVISIONS) array_unshift($d, self::NEW_ROUND);
            return $d;
        }
        if ((int)$sub['stage_id'] === 4) return [self::SEND_PRODUCTION];
        return [];
    }
    public static function createRound(int $sid, array $fileIds): int {
        $n = 1 + (int)DB::val('SELECT COALESCE(MAX(round),0) FROM review_rounds WHERE submission_id=?', [$sid]);
        $rid = DB::insert('review_rounds', ['submission_id' => $sid, 'stage_id' => 3, 'round' => $n, 'status' => 0]);
        foreach (array_unique(array_map('intval', $fileIds)) as $f)
            if (DB::val('SELECT 1 FROM submission_files WHERE id=? AND submission_id=?', [$f, $sid])) DB::exec('INSERT IGNORE INTO review_round_files (review_round_id,submission_file_id) VALUES (?,?)', [$rid, $f]);
        return $rid;
    }
    /** Files that can seed a round: the author's latest revisions if any, otherwise the previous round's files / submission files. */
    public static function candidateFiles(array $sub, string $decision): array {
        $sid = (int)$sub['id'];
        if ($decision === self::SEND_REVIEW) return SubmissionRepo::files($sid, 'submission');
        if ($decision === self::SEND_PRODUCTION) return SubmissionRepo::files($sid, 'copyedit');
        $r = self::currentRound($sid);
        $rev = $r ? DB::all("SELECT f.*, 'review_revision' src FROM submission_files f WHERE f.submission_id=? AND f.file_stage='review_revision' AND f.review_round_id=?", [$sid, $r['id']]) : [];
        $prev = $r ? DB::all("SELECT f.*, 'review_file' src FROM submission_files f JOIN review_round_files rf ON rf.submission_file_id=f.id WHERE rf.review_round_id=?", [$r['id']]) : [];
        return array_merge($rev, $prev);
    }
    /** Performs the transition and records the decision. $opts: files[], */
    public static function apply(string $decision, array $sub, int $editorId, array $opts = []): void {
        $sid = (int)$sub['id']; $now = date('Y-m-d H:i:s'); $stage = (int)$sub['stage_id']; $round = self::currentRound($sid); $roundId = $round ? (int)$round['id'] : null;
        switch ($decision) {
            case self::SEND_REVIEW:
                $roundId = self::createRound($sid, $opts['files'] ?? []);
                DB::exec('UPDATE submissions SET stage_id=3 WHERE id=?', [$sid]); $stage = 3; break;
            case self::SEND_PRODUCTION:
                foreach (array_map('intval', $opts['files'] ?? []) as $fid) { $f = DB::one("SELECT * FROM submission_files WHERE id=? AND submission_id=? AND file_stage='copyedit'", [$fid, $sid]); if ($f) self::copyFile($sub, $f, 'production_ready', 'Production Ready', $editorId); }
                DB::exec('UPDATE submissions SET stage_id=5 WHERE id=?', [$sid]); $stage = 4; break;
            case self::SKIP: case self::ACCEPT:
                DB::exec('UPDATE submissions SET stage_id=4 WHERE id=?', [$sid]); break;
            case self::DECLINE:
                DB::exec("UPDATE submissions SET status='declined' WHERE id=?", [$sid]); break;
            case self::RESUBMIT: case self::NEW_ROUND:
                DB::insert('edit_decisions', ['submission_id' => $sid, 'review_round_id' => $roundId, 'stage_id' => 3, 'editor_id' => $editorId, 'decision' => $decision, 'date_decided' => $now]);
                $newId = self::createRound($sid, $opts['files'] ?? []);
                DB::exec('UPDATE submissions SET date_last_activity=? WHERE id=?', [$now, $sid]);
                SubmissionRepo::log($sid, $editorId, 'decision_' . $decision, t('log.decision_' . $decision, (int)DB::val('SELECT round FROM review_rounds WHERE id=?', [$newId])));
                return;
        }
        DB::insert('edit_decisions', ['submission_id' => $sid, 'review_round_id' => $stage === 3 ? $roundId : null, 'stage_id' => $stage, 'editor_id' => $editorId, 'decision' => $decision, 'date_decided' => $now]);
        DB::exec('UPDATE submissions SET date_last_activity=? WHERE id=?', [$now, $sid]);
        SubmissionRepo::log($sid, $editorId, 'decision_' . $decision, t('log.decision_' . $decision));
    }
    /** Author-role participants (name + email) who receive decision emails. */
    public static function authorRecipients(int $sid): array {
        return DB::all("SELECT DISTINCT u.id,u.email,u.given_name,u.family_name FROM stage_assignments a JOIN user_groups g ON g.id=a.user_group_id JOIN users u ON u.id=a.user_id WHERE a.submission_id=? AND g.role_key='author' AND u.disabled=0", [$sid]);
    }
    /** Anonymised comments from submitted reviews ("Reviewer A: ...") for the decision email. */
    public static function reviewerCommentsText(int $sid, ?int $roundId): string {
        if (!$roundId) return '';
        $out = '';
        foreach (DB::all("SELECT comments_author FROM review_assignments WHERE review_round_id=? AND status IN ('review_submitted','thanked') AND comments_author IS NOT NULL AND comments_author<>'' ORDER BY id", [$roundId]) as $i => $r)
            $out .= ReviewService::pseudonym($i) . ":\n" . trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>'], "\n", $r['comments_author'])))) . "\n\n";
        return trim($out);
    }
    /** Notify and e-mail every user holding one of the given roles on the submission (editors/managers/assigned editors). */
    public static function editorsOf(int $sid, int $jid, int $exceptUser = 0): array {
        return DB::all("SELECT DISTINCT u.id,u.email,u.given_name,u.family_name FROM users u WHERE u.disabled=0 AND u.id<>? AND (
            u.id IN (SELECT a.user_id FROM stage_assignments a JOIN user_groups g ON g.id=a.user_group_id WHERE a.submission_id=? AND g.role_key IN ('editor','manager','section_editor'))
            OR (NOT EXISTS (SELECT 1 FROM stage_assignments a JOIN user_groups g ON g.id=a.user_group_id WHERE a.submission_id=? AND g.role_key IN ('editor','manager','section_editor'))
                AND u.id IN (SELECT x.user_id FROM user_user_groups x JOIN user_groups g ON g.id=x.user_group_id WHERE g.role_key IN ('editor','manager') AND g.journal_id=?)))", [$exceptUser, $sid, $sid, $jid]);
    }
    /** Copies a stored file into another file stage (the physical file is duplicated so stages stay independent). Returns the new file id. */
    public static function copyFile(array $sub, array $f, string $toStage, string $genre, int $uploader): ?int {
        $base = \App\Core\Upload::baseDir() . "/journals/{$sub['journal_id']}/submissions/{$sub['id']}";
        $src = "$base/{$f['file_stage']}/{$f['stored_name']}"; if (!is_file($src)) return null;
        $dir = "$base/$toStage"; if (!is_dir($dir)) mkdir($dir, 0775, true);
        $stored = bin2hex(random_bytes(16)) . '.' . pathinfo($f['stored_name'], PATHINFO_EXTENSION);
        if (!copy($src, "$dir/$stored")) return null;
        return DB::insert('submission_files', ['submission_id' => $sub['id'], 'file_stage' => $toStage, 'genre' => $genre, 'original_name' => $f['original_name'], 'stored_name' => $stored, 'mime' => $f['mime'],
            'size' => $f['size'], 'uploader_user_id' => $uploader, 'viewable' => 0, 'created_at' => date('Y-m-d H:i:s')]);
    }
}
