<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Upload, Sanitizer, Countries};
use App\Models\{SubmissionRepo, Catalog};
use App\Services\{PermissionService, PublishingService};

/** The Publication tab: Title & Abstract, Contributors, Metadata, References, Galleys, Permissions & Disclosure, Issue, Identifiers. */
class PublicationController {
    const TABS = ['title', 'contributors', 'metadata', 'references', 'galleys', 'permissions', 'issue', 'identifiers'];
    const LICENSES = ['https://creativecommons.org/licenses/by/4.0/' => 'CC BY 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/' => 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-nc/4.0/' => 'CC BY-NC 4.0',
        'https://creativecommons.org/licenses/by-nc-nd/4.0/' => 'CC BY-NC-ND 4.0'];
    private function ctx(string $id): array {
        $sub = WorkflowController::load($id);
        if (!PermissionService::canManage($sub)) WorkflowController::deny(403);
        if ($sub['status'] === 'incomplete' || $sub['status'] === 'declined') redirect(url('workflow', 'index', [$sub['id']]));
        return $sub;
    }
    private function back(array $sub, string $tab): never { redirect(url('publication', 'index', [$sub['id'], $tab])); }
    private function locked(array $sub): bool { return $sub['status'] === 'published'; }

    public function index(string $id = '', string $tab = 'title'): void {
        $sub = $this->ctx($id); $jid = (int)$sub['journal_id']; $pid = (int)$sub['pid']; if (!in_array($tab, self::TABS, true)) $tab = 'title';
        $pub = DB::one('SELECT p.*, i.id iid FROM publications p LEFT JOIN issues i ON i.id=p.issue_id WHERE p.id=?', [$pid]);
        $data = ['title' => t('pub.title'), 'menu' => 'submissions', 'sub' => $sub, 'pub' => $pub, 'tab' => $tab, 'locked' => $this->locked($sub), 'missing' => PublishingService::checklist($sub),
            'issue' => $pub['issue_id'] ? DB::one('SELECT * FROM issues WHERE id=?', [$pub['issue_id']]) : null, 'errors' => []];
        if ($tab === 'contributors') $data['authors'] = SubmissionRepo::authors($pid);
        if ($tab === 'galleys') $data['galleys'] = DB::all('SELECT g.*, f.original_name, f.size FROM galleys g LEFT JOIN submission_files f ON f.id=g.submission_file_id WHERE g.publication_id=? ORDER BY g.seq,g.id', [$pid]);
        if ($tab === 'issue') {
            $data['issues'] = DB::all('SELECT * FROM issues WHERE journal_id=? ORDER BY published, seq, id DESC', [$jid]);
            $data['sections'] = DB::all('SELECT id,title FROM sections WHERE journal_id=? ORDER BY seq', [$jid]);
        }
        $data['keywords'] = json_decode($sub['keywords'] ?? '[]', true) ?: []; $data['subjects'] = json_decode($pub['subjects'] ?? '[]', true) ?: [];
        View::render('backend/publication', $data, 'backend');
    }

    /** POST /publication/save/{id}/{tab}: saves one tab. */
    public function save(string $id = '', string $tab = ''): void {
        $sub = $this->ctx($id); if (!Request::isPost() || !in_array($tab, self::TABS, true)) $this->back($sub, 'title');
        if ($this->locked($sub)) { flash(t('pub.locked'), 'error'); $this->back($sub, $tab); }
        $pid = (int)$sub['pid']; $p = $_POST; $jid = (int)$sub['journal_id']; $sid = (int)$sub['id'];
        $paras = fn($t) => implode('', array_map(fn($x) => '<p>' . nl2br(e(trim($x))) . '</p>', array_filter(preg_split('/\R{2,}/', trim((string)$t)), fn($x) => trim($x) !== '')));
        switch ($tab) {
            case 'title':
                $abs = trim((string)($p['abstract'] ?? '')); if ($abs !== '' && stripos($abs, '<p') === false) $abs = $paras($abs);
                if (trim((string)($p['title'] ?? '')) === '') { flash(t('wizard.err_title'), 'error'); $this->back($sub, $tab); }
                DB::exec('UPDATE publications SET prefix=?, title=?, subtitle=?, abstract=? WHERE id=?', [trim((string)($p['prefix'] ?? '')) ?: null, mb_substr(trim($p['title']), 0, 500), trim((string)($p['subtitle'] ?? '')) ?: null, Sanitizer::clean($abs) ?: null, $pid]); break;
            case 'metadata':
                $split = fn($t) => array_values(array_unique(array_filter(array_map('trim', preg_split('/[;\n,]+/', (string)$t)))));
                $kw = $split($p['keywords'] ?? ''); $sj = $split($p['subjects'] ?? '');
                DB::exec('UPDATE publications SET keywords=?, subjects=? WHERE id=?', [$kw ? json_encode($kw, JSON_UNESCAPED_UNICODE) : null, $sj ? json_encode($sj, JSON_UNESCAPED_UNICODE) : null, $pid]); break;
            case 'references':
                $refs = implode('', array_map(fn($x) => '<p>' . e(trim($x)) . '</p>', array_filter(preg_split('/\R+/', (string)($p['references'] ?? '')), fn($x) => trim($x) !== '')));
                DB::exec('UPDATE publications SET references_text=? WHERE id=?', [$refs ?: null, $pid]); break;
            case 'permissions':
                $lic = trim((string)($p['license_url'] ?? '')); if ($lic !== '' && !preg_match('#^https?://#i', $lic)) { flash(t('profile.url_invalid'), 'error'); $this->back($sub, $tab); }
                $yr = (int)($p['copyright_year'] ?? 0);
                DB::exec('UPDATE publications SET copyright_holder=?, copyright_year=?, license_url=? WHERE id=?', [trim((string)($p['copyright_holder'] ?? '')) ?: null, $yr >= 1900 && $yr <= 2100 ? $yr : null, $lic ?: null, $pid]); break;
            case 'identifiers':
                $doi = trim((string)($p['doi'] ?? '')); $doi = preg_replace('#^https?://(dx\.)?doi\.org/#i', '', $doi);
                if ($doi !== '' && !preg_match('#^10\.\d{4,9}/\S+$#', $doi)) { flash(t('pub.err_doi'), 'error'); $this->back($sub, $tab); }
                if ($doi !== '' && DB::val('SELECT 1 FROM publications WHERE doi=? AND id<>?', [$doi, $pid])) { flash(t('pub.err_doi_dup'), 'error'); $this->back($sub, $tab); }
                DB::exec('UPDATE publications SET doi=? WHERE id=?', [$doi ?: null, $pid]); break;
            case 'issue':
                $sec = (int)($p['section_id'] ?? 0); $slug = trim((string)($p['url_path'] ?? ''));
                if (!DB::val('SELECT 1 FROM sections WHERE id=? AND journal_id=?', [$sec, $jid])) { flash(t('wizard.err_section'), 'error'); $this->back($sub, $tab); }
                if ($slug !== '' && (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $slug) || DB::val('SELECT 1 FROM publications WHERE url_path=? AND id<>?', [$slug, $pid]))) { flash(t('pub.err_slug'), 'error'); $this->back($sub, $tab); }
                $date = trim((string)($p['date_published'] ?? '')); if ($date !== '' && !(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date))) { flash(t('rev.err_date'), 'error'); $this->back($sub, $tab); }
                $cover = DB::val('SELECT cover_image FROM publications WHERE id=?', [$pid]);
                if (!empty($_FILES['cover']['name'])) { try { $new = Upload::storePublic($_FILES['cover'], 'covers'); if ($cover) @unlink(ROOT . '/public/' . $cover); $cover = $new; } catch (\RuntimeException $x) { flash($x->getMessage(), 'error'); $this->back($sub, $tab); } }
                DB::exec('UPDATE publications SET section_id=?, pages=?, url_path=?, date_published=?, cover_image=? WHERE id=?', [$sec, mb_substr(trim((string)($p['pages'] ?? '')), 0, 50) ?: null, $slug ?: null, $date ?: null, $cover, $pid]);
                DB::exec('UPDATE submissions SET section_id=? WHERE id=?', [$sec, $sid]);
                if ($pubIssue = DB::val('SELECT issue_id FROM publications WHERE id=?', [$pid])) PublishingService::issueOrder((int)$pubIssue, $sec);
                break;
        }
        DB::exec('UPDATE submissions SET date_last_activity=NOW() WHERE id=?', [$sid]); SubmissionRepo::log($sid, Auth::id(), 'publication_saved', t('pub.tab_' . $tab)); flash(t('profile.saved'));
        $this->back($sub, $tab);
    }
    /** "Assign DOI": prefix + submission id (editable afterwards). */
    public function assignDoi(string $id = ''): void {
        $sub = $this->ctx($id); if (Request::isPost() && !$this->locked($sub)) {
            if (\App\Services\DoiService::assign((int)$sub['id'])) flash(t('pub.doi_assigned')); else flash(t('pub.err_doi_dup'), 'error');
        }
        $this->back($sub, 'identifiers');
    }
    /** Record the APC status of a submission (manual tracking). */
    public function saveApc(string $id = ''): void {
        $sub = $this->ctx($id);
        if (Request::isPost() && \App\Services\ApcService::enabled()) {
            $err = \App\Services\ApcService::save((int)$sub['id'], $_POST, Auth::id());
            if ($err) flash(t('apc.err_' . $err), 'error'); else { SubmissionRepo::log((int)$sub['id'], Auth::id(), 'apc_saved', t('apc.title') . ': ' . t('apc.' . $_POST['status'])); flash(t('profile.saved')); }
        }
        redirect(url('workflow', 'index', [$sub['id']]));
    }

    // ------------------------------------------------------------ contributors
    public function contributor(string $id = '', string $authorId = ''): void {
        $sub = $this->ctx($id); $pid = (int)$sub['pid']; $errors = [];
        if ($this->locked($sub)) { flash(t('pub.locked'), 'error'); $this->back($sub, 'contributors'); }
        $a = $authorId !== '' ? DB::one('SELECT * FROM authors WHERE id=? AND publication_id=?', [(int)$authorId, $pid]) : null;
        if ($authorId !== '' && !$a) WorkflowController::deny(404);
        if (Request::isPost()) {
            $p = $_POST; $given = trim((string)($p['given_name'] ?? '')); $email = strtolower(trim((string)($p['email'] ?? ''))); $orcid = trim((string)($p['orcid'] ?? ''));
            if ($given === '') $errors['given_name'] = t('form.required_field');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = t('form.email_invalid');
            if (!isset(Countries::all()[$p['country'] ?? ''])) $errors['country'] = t('form.required_field');
            if ($orcid !== '') { if (preg_match('#^(?:https?://orcid\.org/)?(\d{4}-\d{4}-\d{4}-\d{3}[\dX])$#', $orcid, $mm)) $orcid = 'https://orcid.org/' . $mm[1]; else $errors['orcid'] = t('profile.orcid_invalid'); }
            if (!$errors) {
                $row = ['given_name' => mb_substr($given, 0, 120), 'family_name' => mb_substr(trim((string)($p['family_name'] ?? '')), 0, 120), 'email' => $email, 'country' => $p['country'], 'affiliation' => mb_substr(trim((string)($p['affiliation'] ?? '')), 0, 255) ?: null,
                    'orcid' => $orcid ?: null, 'url' => trim((string)($p['url'] ?? '')) ?: null, 'bio' => Sanitizer::clean((string)($p['bio'] ?? '')) ?: null, 'user_group' => ($p['role'] ?? '') === 'translator' ? 'translator' : 'author', 'include_in_browse' => empty($p['include_in_browse']) ? 0 : 1];
                if ($a) { DB::exec('UPDATE authors SET ' . implode(',', array_map(fn($k) => "`$k`=?", array_keys($row))) . ' WHERE id=?', array_merge(array_values($row), [$a['id']])); $aid = (int)$a['id']; }
                else { $row['publication_id'] = $pid; $row['seq'] = 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM authors WHERE publication_id=?', [$pid]); $aid = DB::insert('authors', $row); }
                if (!empty($p['primary']) || !DB::val('SELECT primary_contact_id FROM publications WHERE id=?', [$pid])) DB::exec('UPDATE publications SET primary_contact_id=? WHERE id=?', [$aid, $pid]);
                flash(t('wizard.contrib_saved')); $this->back($sub, 'contributors');
            }
            $a = array_merge($a ?: [], $p);
        }
        View::render('backend/contributor_form', ['title' => t('wizard.contributor'), 'menu' => 'submissions', 'sub' => $sub, 'a' => $a, 'errors' => $errors, 'isEdit' => $authorId !== '',
            'isPrimary' => $a && !empty($a['id']) ? (int)($sub['primary_contact_id'] ?? 0) === (int)$a['id'] : false,
            'action' => $authorId !== '' ? url('publication', 'contributor', [$sub['id'], $authorId]) : url('publication', 'contributor', [$sub['id']]), 'cancel' => url('publication', 'index', [$sub['id'], 'contributors'])], 'backend');
    }
    public function deleteContributor(string $id = '', string $authorId = ''): void {
        $sub = $this->ctx($id);
        if (Request::isPost() && !$this->locked($sub) && DB::val('SELECT COUNT(*) FROM authors WHERE publication_id=?', [$sub['pid']]) > 1) {
            DB::exec('DELETE FROM authors WHERE id=? AND publication_id=?', [(int)$authorId, $sub['pid']]);
            DB::exec('UPDATE publications SET primary_contact_id=(SELECT id FROM (SELECT id FROM authors WHERE publication_id=? ORDER BY seq LIMIT 1) x) WHERE id=? AND (primary_contact_id=? OR primary_contact_id IS NULL)', [$sub['pid'], $sub['pid'], (int)$authorId]);
            flash(t('wizard.contrib_deleted'));
        }
        $this->back($sub, 'contributors');
    }
    public function moveContributor(string $id = '', string $authorId = '', string $dir = 'up'): void {
        $sub = $this->ctx($id);
        if (Request::isPost() && !$this->locked($sub)) {
            $list = array_map('intval', array_column(DB::all('SELECT id FROM authors WHERE publication_id=? ORDER BY seq,id', [$sub['pid']]), 'id')); $i = array_search((int)$authorId, $list, true); $j = $dir === 'up' ? $i - 1 : $i + 1;
            if ($i !== false && $j >= 0 && $j < count($list)) { [$list[$i], $list[$j]] = [$list[$j], $list[$i]]; foreach ($list as $k => $aid) DB::exec('UPDATE authors SET seq=? WHERE id=?', [$k + 1, $aid]); }
        }
        $this->back($sub, 'contributors');
    }

    // ------------------------------------------------------------ galleys
    public function addGalley(string $id = ''): void {
        $sub = $this->ctx($id); if (!Request::isPost() || $this->locked($sub)) $this->back($sub, 'galleys');
        $label = trim((string)($_POST['label'] ?? '')); $remote = trim((string)($_POST['remote_url'] ?? ''));
        try {
            if ($label === '' || mb_strlen($label) > 30) throw new \RuntimeException(t('pub.err_label'));
            $fid = null;
            if ($remote !== '') { if (!preg_match('#^https?://#i', $remote)) throw new \RuntimeException(t('profile.url_invalid')); }
            else {
                if (empty($_FILES['file']['name'])) throw new \RuntimeException(t('pub.err_galley_file'));
                $f = Upload::store($_FILES['file'], "journals/{$sub['journal_id']}/submissions/{$sub['id']}/galley", ['pdf', 'doc', 'docx', 'odt', 'rtf', 'txt', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'zip']);
                $fid = DB::insert('submission_files', ['submission_id' => $sub['id'], 'file_stage' => 'galley', 'genre' => 'Galley', 'original_name' => $f['original_name'], 'stored_name' => $f['stored_name'], 'mime' => $f['mime'], 'size' => $f['size'],
                    'uploader_user_id' => Auth::id(), 'viewable' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            }
            DB::insert('galleys', ['publication_id' => $sub['pid'], 'label' => $label, 'submission_file_id' => $fid, 'remote_url' => $remote ?: null, 'seq' => 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM galleys WHERE publication_id=?', [$sub['pid']])]);
            SubmissionRepo::log((int)$sub['id'], Auth::id(), 'galley_added', $label); flash(t('pub.galley_added'));
        } catch (\RuntimeException $x) { flash($x->getMessage(), 'error'); }
        $this->back($sub, 'galleys');
    }
    public function renameGalley(string $id = '', string $galleyId = ''): void {
        $sub = $this->ctx($id); $label = trim((string)($_POST['label'] ?? ''));
        if (Request::isPost() && !$this->locked($sub) && $label !== '' && mb_strlen($label) <= 30) { DB::exec('UPDATE galleys SET label=? WHERE id=? AND publication_id=?', [$label, (int)$galleyId, $sub['pid']]); flash(t('pub.galley_renamed')); }
        $this->back($sub, 'galleys');
    }
    public function deleteGalley(string $id = '', string $galleyId = ''): void {
        $sub = $this->ctx($id);
        if (Request::isPost() && !$this->locked($sub)) {
            $g = DB::one('SELECT g.*, f.stored_name FROM galleys g LEFT JOIN submission_files f ON f.id=g.submission_file_id WHERE g.id=? AND g.publication_id=?', [(int)$galleyId, $sub['pid']]);
            if ($g) { if ($g['submission_file_id']) { @unlink(Upload::baseDir() . "/journals/{$sub['journal_id']}/submissions/{$sub['id']}/galley/{$g['stored_name']}"); DB::exec('DELETE FROM submission_files WHERE id=?', [$g['submission_file_id']]); } DB::exec('DELETE FROM galleys WHERE id=?', [$g['id']]); flash(t('pub.galley_deleted')); }
        }
        $this->back($sub, 'galleys');
    }

    // ------------------------------------------------------------ scheduling / publishing
    public function schedule(string $id = ''): void {
        $sub = $this->ctx($id); if (!Request::isPost()) $this->back($sub, 'issue');
        if ($sub['status'] === 'published') $this->back($sub, 'issue');
        if ($m = PublishingService::checklist($sub)) { flash(t('pub.cannot_schedule') . ' ' . implode(' ', $m), 'error'); $this->back($sub, 'issue'); }
        try { $r = PublishingService::schedule($sub, (int)($_POST['issue_id'] ?? 0), Auth::id()); flash($r === 'published' ? t('pub.now_published') : t('pub.now_scheduled')); }
        catch (\RuntimeException $x) { flash($x->getMessage(), 'error'); }
        $this->back($sub, 'issue');
    }
    public function publish(string $id = ''): void {
        $sub = $this->ctx($id); if (!Request::isPost()) $this->back($sub, 'issue');
        $issue = $sub['status'] === 'scheduled' ? DB::one('SELECT i.* FROM issues i JOIN publications p ON p.issue_id=i.id WHERE p.id=?', [$sub['pid']]) : null;
        if ($issue && (int)$issue['published'] === 1) { PublishingService::publishPublication((int)$sub['id'], Auth::id()); flash(t('pub.now_published')); }
        else flash(t('pub.publish_with_issue'), 'error');
        $this->back($sub, 'issue');
    }
    public function unpublish(string $id = ''): void {
        $sub = $this->ctx($id); if (Request::isPost() && $sub['status'] === 'published') { PublishingService::unpublishArticle($sub, Auth::id()); flash(t('pub.unpublished')); }
        $this->back($sub, 'issue');
    }
    public function unschedule(string $id = ''): void {
        $sub = $this->ctx($id); if (Request::isPost() && $sub['status'] === 'scheduled') { PublishingService::removeFromIssue($sub, Auth::id()); flash(t('pub.unscheduled')); }
        $this->back($sub, 'issue');
    }
}
