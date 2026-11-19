<?php
namespace App\Services;
use App\Core\{DB, Settings};
use App\Models\{SubmissionRepo, Catalog};

/** Scheduling, publishing and unpublishing articles and issues. Public pages only show publication.status=published in a published issue. */
class PublishingService {
    /** What is still missing before an article can be scheduled. */
    public static function checklist(array $sub): array {
        $m = []; $pid = (int)$sub['pid'];
        if (trim((string)$sub['title']) === '') $m[] = t('pub.need_title');
        if (!$sub['abstract']) $m[] = t('pub.need_abstract');
        if (!DB::val('SELECT COUNT(*) FROM authors WHERE publication_id=?', [$pid])) $m[] = t('pub.need_author');
        if (!DB::val('SELECT COUNT(*) FROM galleys WHERE publication_id=?', [$pid])) $m[] = t('pub.need_galley');
        if (!$sub['section_id']) $m[] = t('pub.need_section');
        return $m;
    }
    public static function issueOrder(int $issueId, int $sectionId): void {
        if (!DB::val('SELECT 1 FROM issue_section_order WHERE issue_id=? AND section_id=?', [$issueId, $sectionId]))
            DB::exec('INSERT INTO issue_section_order (issue_id,section_id,seq) VALUES (?,?,?)', [$issueId, $sectionId, 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM issue_section_order WHERE issue_id=?', [$issueId])]);
    }
    /** Put the article in an issue. Returns 'published' (issue already live) or 'scheduled'. */
    public static function schedule(array $sub, int $issueId, int $actor): string {
        $sid = (int)$sub['id']; $pid = (int)$sub['pid'];
        $issue = DB::one('SELECT * FROM issues WHERE id=? AND journal_id=?', [$issueId, $sub['journal_id']]);
        if (!$issue) throw new \RuntimeException(t('pub.err_issue'));
        $seq = 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM publications WHERE issue_id=?', [$issueId]);
        DB::exec("UPDATE publications SET issue_id=?, status='scheduled', seq=? WHERE id=?", [$issueId, $seq, $pid]);
        DB::exec("UPDATE submissions SET status='scheduled', stage_id=5, date_last_activity=NOW() WHERE id=?", [$sid]);
        self::issueOrder($issueId, (int)$sub['section_id']);
        self::copyrightDefaults($sub, $issue);
        SubmissionRepo::log($sid, $actor, 'scheduled', t('log.scheduled', Catalog::issueLabel($issue)));
        if ((int)$issue['published'] === 1) { self::publishPublication($sid, $actor); return 'published'; }
        return 'scheduled';
    }
    /** Fills the copyright holder / year from Settings > Distribution > License when the editor left them empty. */
    private static function copyrightDefaults(array $sub, array $issue): void {
        $p = DB::one('SELECT copyright_holder, copyright_year, license_url FROM publications WHERE id=?', [$sub['pid']]); $jid = (int)$sub['journal_id'];
        $type = Settings::get('copyrightHolderType', 'author', $jid);
        $holder = $type === 'journal' ? Settings::get('name', '', $jid) : ($type === 'other' ? Settings::get('copyrightHolderOther', '', $jid) : implode(', ', array_map(fn($a) => trim($a['given_name'] . ' ' . $a['family_name']), SubmissionRepo::authors((int)$sub['pid']))));
        $year = Settings::get('copyrightYearBasis', 'issue', $jid) === 'issue' && $issue['year'] ? (int)$issue['year'] : (int)date('Y');
        DB::exec('UPDATE publications SET copyright_holder=COALESCE(NULLIF(copyright_holder,""),?), copyright_year=COALESCE(copyright_year,?), license_url=COALESCE(NULLIF(license_url,""),?) WHERE id=?', [$holder ?: null, $year, Settings::get('licenseUrl', '', $jid) ?: null, $sub['pid']]);
    }
    public static function publishPublication(int $sid, int $actor): void {
        $s = DB::one('SELECT s.*, p.id pid, p.title, p.date_published FROM submissions s JOIN publications p ON p.id=s.current_publication_id WHERE s.id=?', [$sid]);
        if (!$s) return;
        DB::exec("UPDATE publications SET status='published', date_published=COALESCE(date_published, CURDATE()) WHERE id=?", [$s['pid']]);
        DB::exec("UPDATE submissions SET status='published', stage_id=5, date_last_activity=NOW() WHERE id=?", [$sid]);
        SubmissionRepo::log($sid, $actor, 'published', t('log.published'));
        $url = abs_url(url('article', 'view', [$sid]));
        foreach (WorkflowService::authorRecipients($sid) as $a) {
            EmailTemplateService::send('PUBLICATION_NOTIFY', $a['email'], ['authorName' => trim($a['given_name'] . ' ' . $a['family_name']), 'submissionTitle' => $s['title'], 'submissionUrl' => $url], $sid);
            NotificationService::add((int)$a['id'], (int)$s['journal_id'], 'published', t('notif.published', $s['title']), 'submission', $sid);
        }
    }
    public static function unpublishArticle(array $sub, int $actor): void {
        DB::exec("UPDATE publications SET status='unscheduled' WHERE id=?", [$sub['pid']]);
        DB::exec("UPDATE submissions SET status='queued', stage_id=5, date_last_activity=NOW() WHERE id=?", [$sub['id']]);
        SubmissionRepo::log((int)$sub['id'], $actor, 'unpublished', t('log.unpublished'));
    }
    public static function removeFromIssue(array $sub, int $actor): void {
        DB::exec("UPDATE publications SET issue_id=NULL, status='unscheduled' WHERE id=?", [$sub['pid']]);
        DB::exec("UPDATE submissions SET status='queued', stage_id=5, date_last_activity=NOW() WHERE id=? AND status IN ('scheduled','published')", [$sub['id']]);
        SubmissionRepo::log((int)$sub['id'], $actor, 'removed_from_issue', t('log.removed_from_issue'));
    }
    /** Publishes the issue, makes it the current issue and publishes every scheduled article in it. */
    public static function publishIssue(int $issueId, int $actor, bool $notifyReaders = false): int {
        $issue = DB::one('SELECT * FROM issues WHERE id=?', [$issueId]); $jid = (int)$issue['journal_id'];
        $minSeq = (int)DB::val('SELECT COALESCE(MIN(seq),1) FROM issues WHERE journal_id=?', [$jid]) - 1;
        DB::exec('UPDATE issues SET is_current=0 WHERE journal_id=?', [$jid]);
        DB::exec('UPDATE issues SET published=1, is_current=1, date_published=COALESCE(date_published, NOW()), seq=? WHERE id=?', [$minSeq, $issueId]);
        $n = 0;
        foreach (DB::all("SELECT s.id FROM publications p JOIN submissions s ON s.current_publication_id=p.id WHERE p.issue_id=? AND p.status='scheduled'", [$issueId]) as $r) { self::publishPublication((int)$r['id'], $actor); $n++; }
        if ($notifyReaders) {
            $label = Catalog::issueLabel(DB::one('SELECT * FROM issues WHERE id=?', [$issueId]));
            foreach (DB::all("SELECT u.id,u.email,u.given_name,u.family_name FROM users u JOIN user_settings us ON us.user_id=u.id AND us.name='emailConsent' AND us.value='1' WHERE u.disabled=0") as $u)
                EmailTemplateService::send('ISSUE_PUBLISHED', $u['email'], ['recipientName' => trim($u['given_name'] . ' ' . $u['family_name']), 'issueName' => $label, 'issueUrl' => abs_url(url('issue', 'view', [$issueId]))]);
        }
        return $n;
    }
    public static function unpublishIssue(int $issueId, int $actor): void {
        $issue = DB::one('SELECT * FROM issues WHERE id=?', [$issueId]); $jid = (int)$issue['journal_id'];
        DB::exec('UPDATE issues SET published=0, is_current=0 WHERE id=?', [$issueId]);
        foreach (DB::all("SELECT s.id FROM publications p JOIN submissions s ON s.current_publication_id=p.id WHERE p.issue_id=? AND p.status='published'", [$issueId]) as $r) {
            DB::exec("UPDATE publications SET status='scheduled' WHERE id=(SELECT current_publication_id FROM submissions WHERE id=?)", [$r['id']]);
            DB::exec("UPDATE submissions SET status='scheduled' WHERE id=?", [$r['id']]);
            SubmissionRepo::log((int)$r['id'], $actor, 'issue_unpublished', t('log.issue_unpublished'));
        }
        if (!DB::val('SELECT 1 FROM issues WHERE journal_id=? AND is_current=1 AND published=1', [$jid]))
            DB::exec('UPDATE issues SET is_current=1 WHERE id=(SELECT id FROM (SELECT id FROM issues WHERE journal_id=? AND published=1 ORDER BY date_published DESC, id DESC LIMIT 1) x)', [$jid]);
    }
}
