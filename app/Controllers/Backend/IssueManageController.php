<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Upload, Sanitizer};
use App\Models\{Catalog, SubmissionRepo};
use App\Services\PublishingService;

/** /manageIssues: Future Issues and Back Issues, create/edit, table of contents, publish, unpublish, make current, delete. */
class IssueManageController {
    const ROLES = ['editor', 'manager', 'site_admin'];
    private function jid(): int { return (int)Settings::journal()['id']; }
    private function guard(): void { Auth::require(self::ROLES); }
    private function issue(string $id): array {
        $this->guard(); $i = DB::one('SELECT * FROM issues WHERE id=? AND journal_id=?', [(int)$id, $this->jid()]);
        if (!$i) WorkflowController::deny(404);
        return $i;
    }
    public function index(): void {
        $this->guard(); $jid = $this->jid(); $tab = ($_GET['tab'] ?? 'future') === 'back' ? 'back' : 'future';
        $rows = DB::all('SELECT i.*, (SELECT COUNT(*) FROM publications p WHERE p.issue_id=i.id) articles FROM issues i WHERE i.journal_id=? AND i.published=? ORDER BY i.seq, i.date_published DESC, i.id DESC', [$jid, $tab === 'back' ? 1 : 0]);
        View::render('backend/issues_list', ['title' => t('menu.issues'), 'menu' => 'issues', 'tab' => $tab, 'rows' => $rows,
            'counts' => ['future' => (int)DB::val('SELECT COUNT(*) FROM issues WHERE journal_id=? AND published=0', [$jid]), 'back' => (int)DB::val('SELECT COUNT(*) FROM issues WHERE journal_id=? AND published=1', [$jid])]], 'backend');
    }
    /** GET: create/edit form. POST: save. */
    public function form(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $i = $id !== '' ? $this->issue($id) : null; $errors = [];
        if (Request::isPost()) {
            $p = $_POST; $vol = trim((string)($p['volume'] ?? '')); $num = trim((string)($p['number'] ?? '')); $yr = trim((string)($p['year'] ?? '')); $title = trim((string)($p['title'] ?? '')); $slug = trim((string)($p['url_path'] ?? ''));
            if ($vol !== '' && !ctype_digit($vol)) $errors['volume'] = t('iss.err_int');
            if ($yr !== '' && (!ctype_digit($yr) || (int)$yr < 1900 || (int)$yr > 2100)) $errors['year'] = t('iss.err_year');
            if (strlen($num) > 20) $errors['number'] = t('iss.err_long');
            if ($vol === '' && $num === '' && $yr === '' && $title === '') $errors['volume'] = t('iss.err_any');
            if ($slug !== '' && (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $slug) || DB::val('SELECT 1 FROM issues WHERE journal_id=? AND url_path=? AND id<>?', [$jid, $slug, (int)($i['id'] ?? 0)]))) $errors['url_path'] = t('pub.err_slug');
            $ipid = \App\Services\MetadataService::publisherIdOn('issue') ? trim((string)($p['publisher_id'] ?? '')) : null;
            if ($ipid && ($err = \App\Services\MetadataService::checkPublisherId('issues', $ipid, (int)($i['id'] ?? 0)))) $errors['publisher_id'] = $err;
            $cover = $i['cover_image'] ?? null;
            if (!$errors && !empty($_FILES['cover']['name'])) { try { $new = Upload::storePublic($_FILES['cover'], 'covers'); if ($cover) @unlink(ROOT . '/public/' . $cover); $cover = $new; } catch (\RuntimeException $x) { $errors['cover'] = $x->getMessage(); } }
            if (!$errors) {
                $row = ['volume' => $vol === '' ? null : (int)$vol, 'number' => $num === '' ? null : $num, 'year' => $yr === '' ? null : (int)$yr, 'title' => $title === '' ? null : mb_substr($title, 0, 255),
                    'show_volume' => empty($p['show_volume']) ? 0 : 1, 'show_number' => empty($p['show_number']) ? 0 : 1, 'show_year' => empty($p['show_year']) ? 0 : 1, 'show_title' => empty($p['show_title']) ? 0 : 1,
                    'description' => Sanitizer::clean((string)($p['description'] ?? '')) ?: null, 'cover_image' => $cover, 'url_path' => $slug ?: null];
                if ($ipid !== null) $row['publisher_id'] = $ipid ?: null;
                if ($i) { DB::exec('UPDATE issues SET ' . implode(',', array_map(fn($k) => "`$k`=?", array_keys($row))) . ' WHERE id=?', array_merge(array_values($row), [$i['id']])); $nid = (int)$i['id']; }
                else { $row += ['journal_id' => $jid, 'published' => 0, 'is_current' => 0, 'seq' => 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM issues WHERE journal_id=? AND published=0', [$jid]), 'created_at' => date('Y-m-d H:i:s')]; $nid = DB::insert('issues', $row); }
                flash(t('iss.saved')); redirect(url('manageIssues', 'edit', [$nid]));
            }
            $i = array_merge($i ?: ['show_volume' => 1, 'show_number' => 1, 'show_year' => 1], $p);
        }
        View::render('backend/issue_form', ['title' => $i && !empty($i['id']) ? t('iss.edit') : t('iss.create'), 'menu' => 'issues', 'i' => $i ?: ['show_volume' => 1, 'show_number' => 1, 'show_year' => 1], 'errors' => $errors, 'isEdit' => $id !== ''], 'backend');
    }
    public function edit(string $id = '', string $tab = 'toc'): void {
        $i = $this->issue($id); $tab = in_array($tab, ['toc', 'data'], true) ? $tab : 'toc';
        if ($tab === 'data') redirect(url('manageIssues', 'form', [$i['id']]));
        $rows = DB::all("SELECT p.id pid, p.title, p.seq, p.status pstatus, p.pages, s.id sid, s.status sstatus, sec.id secid, sec.title sectitle, COALESCE(iso.seq,999) sseq,
            (SELECT GROUP_CONCAT(a.family_name ORDER BY a.seq SEPARATOR ', ') FROM authors a WHERE a.publication_id=p.id) authors
            FROM publications p JOIN submissions s ON s.current_publication_id=p.id JOIN sections sec ON sec.id=p.section_id LEFT JOIN issue_section_order iso ON iso.issue_id=p.issue_id AND iso.section_id=sec.id
            WHERE p.issue_id=? ORDER BY sseq, sec.id, p.seq, p.id", [$i['id']]);
        $groups = []; foreach ($rows as $r) { $groups[$r['secid']]['title'] = $r['sectitle']; $groups[$r['secid']]['rows'][] = $r; }
        View::render('backend/issue_toc', ['title' => Catalog::issueLabel($i), 'menu' => 'issues', 'i' => $i, 'groups' => $groups, 'total' => count($rows),
            'ready' => DB::all("SELECT s.id, p.title FROM submissions s JOIN publications p ON p.id=s.current_publication_id WHERE s.journal_id=? AND s.stage_id=5 AND s.status='queued' AND p.issue_id IS NULL ORDER BY s.id DESC LIMIT 50", [$this->jid()])], 'backend');
    }
    /** POST: add a production-ready article straight into this issue (same rules as the Publication tab). */
    public function addArticle(string $id = ''): void {
        $i = $this->issue($id); if (!Request::isPost()) redirect(url('manageIssues', 'edit', [$i['id']]));
        $sub = SubmissionRepo::find((int)($_POST['submission'] ?? 0), $this->jid());
        if (!$sub || $sub['status'] !== 'queued') { flash(t('iss.err_article'), 'error'); redirect(url('manageIssues', 'edit', [$i['id']])); }
        if ($m = PublishingService::checklist($sub)) { flash(t('pub.cannot_schedule') . ' ' . implode(' ', $m), 'error'); redirect(url('manageIssues', 'edit', [$i['id']])); }
        PublishingService::schedule($sub, (int)$i['id'], Auth::id()); flash(t('iss.article_added')); redirect(url('manageIssues', 'edit', [$i['id']]));
    }
    public function moveArticle(string $id = '', string $sid = '', string $dir = 'up'): void {
        $i = $this->issue($id);
        if (Request::isPost()) {
            $p = DB::one('SELECT p.id, p.section_id FROM publications p JOIN submissions s ON s.current_publication_id=p.id WHERE s.id=? AND p.issue_id=?', [(int)$sid, $i['id']]);
            if ($p) {
                $list = array_map('intval', array_column(DB::all('SELECT id FROM publications WHERE issue_id=? AND section_id=? ORDER BY seq,id', [$i['id'], $p['section_id']]), 'id')); $k = array_search((int)$p['id'], $list, true); $j = $dir === 'up' ? $k - 1 : $k + 1;
                if ($k !== false && $j >= 0 && $j < count($list)) { [$list[$k], $list[$j]] = [$list[$j], $list[$k]]; foreach ($list as $n => $pid) DB::exec('UPDATE publications SET seq=? WHERE id=?', [$n + 1, $pid]); }
            }
        }
        redirect(url('manageIssues', 'edit', [$i['id']]));
    }
    public function moveSection(string $id = '', string $secId = '', string $dir = 'up'): void {
        $i = $this->issue($id);
        if (Request::isPost()) {
            $list = array_map('intval', array_column(DB::all('SELECT section_id FROM issue_section_order WHERE issue_id=? ORDER BY seq', [$i['id']]), 'section_id')); $k = array_search((int)$secId, $list, true); $j = $dir === 'up' ? $k - 1 : $k + 1;
            if ($k !== false && $j >= 0 && $j < count($list)) { [$list[$k], $list[$j]] = [$list[$j], $list[$k]]; foreach ($list as $n => $s) DB::exec('UPDATE issue_section_order SET seq=? WHERE issue_id=? AND section_id=?', [$n + 1, $i['id'], $s]); }
        }
        redirect(url('manageIssues', 'edit', [$i['id']]));
    }
    public function removeArticle(string $id = '', string $sid = ''): void {
        $i = $this->issue($id);
        if (Request::isPost()) { $sub = SubmissionRepo::find((int)$sid, $this->jid()); if ($sub && (int)DB::val('SELECT issue_id FROM publications WHERE id=?', [$sub['pid']]) === (int)$i['id']) { PublishingService::removeFromIssue($sub, Auth::id()); flash(t('iss.article_removed')); } }
        redirect(url('manageIssues', 'edit', [$i['id']]));
    }
    public function publish(string $id = ''): void {
        $i = $this->issue($id);
        if (Request::isPost() && !$i['published']) { $n = PublishingService::publishIssue((int)$i['id'], Auth::id(), !empty($_POST['notify'])); flash(t('iss.published', $n)); redirect(url('manageIssues', 'index', [], ['tab' => 'back'])); }
        redirect(url('manageIssues', 'edit', [$i['id']]));
    }
    public function unpublish(string $id = ''): void {
        $i = $this->issue($id); if (Request::isPost() && $i['published']) { PublishingService::unpublishIssue((int)$i['id'], Auth::id()); flash(t('iss.unpublished')); redirect(url('manageIssues')); }
        redirect(url('manageIssues', 'edit', [$i['id']]));
    }
    public function makeCurrent(string $id = ''): void {
        $i = $this->issue($id);
        if (Request::isPost() && $i['published']) { DB::exec('UPDATE issues SET is_current=0 WHERE journal_id=?', [$this->jid()]); DB::exec('UPDATE issues SET is_current=1 WHERE id=?', [$i['id']]); flash(t('iss.now_current')); }
        redirect(url('manageIssues', 'index', [], ['tab' => 'back']));
    }
    public function delete(string $id = ''): void {
        $i = $this->issue($id);
        if (Request::isPost()) {
            if (DB::val('SELECT COUNT(*) FROM publications WHERE issue_id=?', [$i['id']])) flash(t('iss.err_delete'), 'error');
            elseif ($i['published']) flash(t('iss.err_delete_published'), 'error');
            else { if ($i['cover_image']) @unlink(ROOT . '/public/' . $i['cover_image']); DB::exec('DELETE FROM issues WHERE id=?', [$i['id']]); flash(t('iss.deleted')); }
        }
        redirect(url('manageIssues'));
    }
}
