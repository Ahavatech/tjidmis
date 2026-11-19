<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Upload, Sanitizer, Countries};
use App\Models\SubmissionRepo;
use App\Services\{PermissionService, EmailTemplateService, NotificationService};

/** The 4-step author submission wizard (+ "Next steps" page). Saves after every step; drafts stay status=incomplete. */
class SubmissionWizardController {
    const SUBMIT_ROLES = ['author', 'editor', 'manager', 'site_admin', 'section_editor'];
    const COMPONENTS = ['Article Text', 'Research Instrument', 'Research Materials', 'Research Results', 'Transcripts', 'Data Analysis', 'Data Set', 'Source Texts', 'Other'];

    private function jid(): int { return (int)Settings::journal()['id']; }
    private function notFound(): void { http_response_code(404); View::render('public/error', ['code' => 404, 'message' => t('error.404'), 'title' => '404']); exit; }
    /** Loads a draft the signed-in user owns; sends everyone else to the right place. */
    private function draft(string $id): array {
        $sub = SubmissionRepo::find((int)$id, $this->jid());
        if (!$sub) $this->notFound();
        if (!PermissionService::canEditDraft($sub)) redirect(url('workflow', 'index', [$sub['id']]));
        return $sub;
    }

    public function wizard(string $id = '', string $step = ''): void {
        Auth::require(self::SUBMIT_ROLES);
        $jid = $this->jid(); $user = Auth::user(); $errors = [];
        if ($id === '') { $sub = null; $progress = 1; }
        else {
            $sub = SubmissionRepo::find((int)$id, $jid);
            if (!$sub) $this->notFound();
            if ($sub['status'] !== 'incomplete') {                         // finished: only the "Next steps" page is wizard business
                if ((int)$sub['created_by'] === (int)$user['id'] && $step === '5') { $this->done($sub); return; }
                redirect(url('workflow', 'index', [$sub['id']]));
            }
            if (!PermissionService::canEditDraft($sub)) redirect(url('workflow', 'index', [$sub['id']]));
            $progress = max(1, min(4, (int)$sub['submission_progress']));
        }
        $n = $step === '' ? $progress : (int)$step;
        if ($n < 1 || $n > 4) $n = $progress;
        if ($n > $progress) redirect(url('submission', 'wizard', [$sub['id'], $progress]));

        if (Request::isPost()) {
            $errors = match ($n) { 1 => $this->saveStart($sub), 2 => $this->saveUpload($sub), 3 => $this->saveMetadata($sub), default => [] };
            if (!$errors) return;   // the save methods redirect on success
            if ($n === 1 && $sub) $sub = array_merge($sub, ['section_id' => $_POST['section_id'] ?? $sub['section_id']]);
        }
        $this->render($sub, $n, $progress, $errors);
    }

    private function render(?array $sub, int $n, int $progress, array $errors): void {
        $jid = $this->jid(); $data = ['title' => t('wizard.title'), 'menu' => 'submissions', 'sub' => $sub, 'n' => $n, 'progress' => $progress, 'errors' => $errors];
        $data['sections'] = DB::all('SELECT id,title,policy FROM sections WHERE journal_id=? AND editor_restricted=0 ORDER BY seq', [$jid]);
        $data['checklist'] = Settings::json('submissionChecklist', []);
        $data['components'] = self::COMPONENTS;
        if ($sub) {
            $data['files'] = SubmissionRepo::files((int)$sub['id']);
            $data['authors'] = $sub['pid'] ? SubmissionRepo::authors((int)$sub['pid']) : [];
            $data['keywords'] = json_decode($sub['keywords'] ?? '[]', true) ?: [];
            if ($n === 4) $data['missing'] = $this->missing($sub);
        }
        View::render('backend/wizard', $data, 'backend');
    }

    // ---------------------------------------------------------------- step 1
    private function saveStart(?array $sub): array {
        $jid = $this->jid(); $e = []; $p = $_POST; $items = Settings::json('submissionChecklist', []);
        $secId = (int)($p['section_id'] ?? 0);
        if (!DB::val('SELECT id FROM sections WHERE id=? AND journal_id=? AND editor_restricted=0', [$secId, $jid])) $e['section_id'] = t('wizard.err_section');
        if (count(array_filter((array)($p['checklist'] ?? []))) < count($items)) $e['checklist'] = t('wizard.err_checklist');
        if (empty($p['privacyConsent'])) $e['privacyConsent'] = t('reg.consent_required');
        if (Settings::get('copyrightNotice', '') !== '' && empty($p['copyrightAgree'])) $e['copyrightAgree'] = t('wizard.err_copyright');
        if ($e) return $e;
        $uid = Auth::id(); $now = date('Y-m-d H:i:s'); $comments = trim((string)($p['comments_to_editor'] ?? '')) ?: null;
        if (!$sub) {
            $sid = DB::insert('submissions', ['journal_id' => $jid, 'section_id' => $secId, 'locale' => 'en', 'stage_id' => 1, 'status' => 'incomplete', 'submission_progress' => 2,
                'comments_to_editor' => $comments, 'date_last_activity' => $now, 'created_by' => $uid]);
            $pid = DB::insert('publications', ['submission_id' => $sid, 'version' => 1, 'title' => '', 'section_id' => $secId, 'status' => 'unscheduled', 'seq' => 0,
                'license_url' => Settings::get('licenseUrl'), 'copyright_year' => (int)date('Y')]);
            DB::exec('UPDATE submissions SET current_publication_id=? WHERE id=?', [$pid, $sid]);
            if ($g = PermissionService::submissionRoleIds($jid, 'author'))
                DB::exec('INSERT IGNORE INTO stage_assignments (submission_id,user_group_id,user_id,can_change_metadata,date_assigned) VALUES (?,?,?,1,?)', [$sid, $g, $uid, $now]);
            SubmissionRepo::log($sid, $uid, 'submission_started', t('log.started'));
        } else {
            $sid = (int)$sub['id'];
            DB::exec('UPDATE submissions SET section_id=?, comments_to_editor=?, submission_progress=GREATEST(submission_progress,2), date_last_activity=? WHERE id=?', [$secId, $comments, $now, $sid]);
            DB::exec('UPDATE publications SET section_id=? WHERE id=?', [$secId, $sub['pid']]);
        }
        redirect(url('submission', 'wizard', [$sid, 2]));
    }

    // ---------------------------------------------------------------- step 2
    private function saveUpload(array $sub): array {
        $has = DB::val("SELECT COUNT(*) FROM submission_files WHERE submission_id=? AND file_stage='submission' AND genre='Article Text'", [$sub['id']]);
        if (!$has) return ['files' => t('wizard.err_article_text')];
        DB::exec('UPDATE submissions SET submission_progress=GREATEST(submission_progress,3), date_last_activity=NOW() WHERE id=?', [$sub['id']]);
        redirect(url('submission', 'wizard', [$sub['id'], 3]));
    }
    public function uploadFile(string $id = ''): void {
        Auth::require(self::SUBMIT_ROLES); $sub = $this->draft($id);
        if (!Request::isPost()) redirect(url('submission', 'wizard', [$id, 2]));
        $comp = (string)($_POST['component'] ?? '');
        try {
            if (!in_array($comp, self::COMPONENTS, true)) throw new \RuntimeException(t('wizard.err_component'));
            if (empty($_FILES['file']['name'])) throw new \RuntimeException(t('wizard.err_no_file'));
            $f = Upload::store($_FILES['file'], 'journals/' . $this->jid() . '/submissions/' . (int)$sub['id'] . '/submission');
            $fid = DB::insert('submission_files', ['submission_id' => $sub['id'], 'file_stage' => 'submission', 'genre' => $comp, 'original_name' => $f['original_name'], 'stored_name' => $f['stored_name'],
                'mime' => $f['mime'], 'size' => $f['size'], 'uploader_user_id' => Auth::id(), 'viewable' => 0, 'created_at' => date('Y-m-d H:i:s')]);
            SubmissionRepo::log((int)$sub['id'], Auth::id(), 'file_uploaded', $f['original_name']);
            flash(t('wizard.uploaded', $f['original_name']));
        } catch (\RuntimeException $x) { flash($x->getMessage(), 'error'); }
        redirect(url('submission', 'wizard', [$id, 2]));
    }
    public function renameFile(string $id = '', string $fileId = ''): void {
        Auth::require(self::SUBMIT_ROLES); $sub = $this->draft($id);
        if (Request::isPost()) {
            $f = DB::one("SELECT * FROM submission_files WHERE id=? AND submission_id=? AND file_stage='submission'", [(int)$fileId, $sub['id']]);
            $name = preg_replace('/[^\w .()\-]+/u', '', trim((string)($_POST['name'] ?? '')));
            if ($f && $name !== '') {
                $ext = pathinfo($f['stored_name'], PATHINFO_EXTENSION);
                if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== $ext) $name .= '.' . $ext;
                DB::exec('UPDATE submission_files SET original_name=? WHERE id=?', [mb_substr($name, 0, 200), $f['id']]); flash(t('wizard.renamed'));
            }
        }
        redirect(url('submission', 'wizard', [$id, 2]));
    }
    public function deleteFile(string $id = '', string $fileId = ''): void {
        Auth::require(self::SUBMIT_ROLES); $sub = $this->draft($id);
        if (Request::isPost()) {
            $f = DB::one("SELECT * FROM submission_files WHERE id=? AND submission_id=? AND file_stage='submission'", [(int)$fileId, $sub['id']]);
            if ($f) {
                @unlink(Upload::baseDir() . '/journals/' . $this->jid() . '/submissions/' . (int)$sub['id'] . '/submission/' . $f['stored_name']);
                DB::exec('DELETE FROM submission_files WHERE id=?', [$f['id']]); SubmissionRepo::log((int)$sub['id'], Auth::id(), 'file_deleted', $f['original_name']); flash(t('wizard.deleted'));
            }
        }
        redirect(url('submission', 'wizard', [$id, 2]));
    }

    // ---------------------------------------------------------------- step 3
    private function metadataFromPost(): array {
        $p = $_POST;
        $abs = trim((string)($p['abstract'] ?? ''));
        if ($abs !== '' && stripos($abs, '<p') === false)
            $abs = implode('', array_map(fn($x) => '<p>' . e(trim($x)) . '</p>', preg_split('/\R{2,}/', $abs)));
        $kw = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,;\n]+/', (string)($p['keywords'] ?? ''))))));
        $refs = implode('', array_map(fn($x) => '<p>' . e(trim($x)) . '</p>', array_filter(preg_split('/\R+/', (string)($p['references'] ?? '')), fn($x) => trim($x) !== '')));
        return ['prefix' => trim((string)($p['prefix'] ?? '')) ?: null, 'title' => trim((string)($p['title'] ?? '')), 'subtitle' => trim((string)($p['subtitle'] ?? '')) ?: null,
            'abstract' => Sanitizer::clean($abs) ?: null, 'keywords' => $kw ? json_encode($kw, JSON_UNESCAPED_UNICODE) : null, 'references_text' => $refs ?: null];
    }
    private function saveMetadata(array $sub): array {
        $m = $this->metadataFromPost(); $pid = (int)$sub['pid']; $action = (string)($_POST['action'] ?? 'continue');
        DB::exec('UPDATE publications SET prefix=?, title=?, subtitle=?, abstract=?, keywords=?, references_text=? WHERE id=?', [$m['prefix'], mb_substr($m['title'], 0, 500), $m['subtitle'], $m['abstract'], $m['keywords'], $m['references_text'], $pid]);
        DB::exec('UPDATE submissions SET date_last_activity=NOW() WHERE id=?', [$sub['id']]);
        if ($action === 'add_contributor') redirect(url('submission', 'contributor', [$sub['id']]));
        if ($action === 'save') { flash(t('wizard.saved')); redirect(url('submission', 'wizard', [$sub['id'], 3])); }
        $e = [];
        if ($m['title'] === '') $e['title'] = t('form.required_field');
        if (!$m['abstract']) $e['abstract'] = t('form.required_field');
        if (!DB::val('SELECT COUNT(*) FROM authors WHERE publication_id=? AND email IS NOT NULL AND email<>""', [$pid])) $e['authors'] = t('wizard.err_contributor');
        if ($e) return $e;
        DB::exec('UPDATE submissions SET submission_progress=GREATEST(submission_progress,4) WHERE id=?', [$sub['id']]);
        redirect(url('submission', 'wizard', [$sub['id'], 4]));
    }

    /** Contributor add/edit page (the OJS "Add Contributor" dialog as a page). */
    public function contributor(string $id = '', string $authorId = ''): void {
        Auth::require(self::SUBMIT_ROLES); $sub = $this->draft($id); $pid = (int)$sub['pid']; $errors = []; $u = Auth::user();
        $a = $authorId !== '' ? DB::one('SELECT * FROM authors WHERE id=? AND publication_id=?', [(int)$authorId, $pid]) : null;
        if ($authorId !== '' && !$a) $this->notFound();
        if (Request::isPost()) {
            $p = $_POST; $given = trim((string)($p['given_name'] ?? '')); $email = strtolower(trim((string)($p['email'] ?? ''))); $orcid = trim((string)($p['orcid'] ?? ''));
            if ($given === '') $errors['given_name'] = t('form.required_field');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = t('form.email_invalid');
            if (!isset(Countries::all()[$p['country'] ?? ''])) $errors['country'] = t('form.required_field');
            if ($orcid !== '') { if (preg_match('#^(?:https?://orcid\.org/)?(\d{4}-\d{4}-\d{4}-\d{3}[\dX])$#', $orcid, $mm)) $orcid = 'https://orcid.org/' . $mm[1]; else $errors['orcid'] = t('profile.orcid_invalid'); }
            if (!empty($p['url']) && !preg_match('#^https?://#i', trim($p['url']))) $errors['url'] = t('profile.url_invalid');
            if (!$errors) {
                $row = ['given_name' => mb_substr($given, 0, 120), 'family_name' => mb_substr(trim((string)($p['family_name'] ?? '')), 0, 120), 'email' => $email, 'country' => $p['country'],
                    'affiliation' => mb_substr(trim((string)($p['affiliation'] ?? '')), 0, 255) ?: null, 'orcid' => $orcid ?: null, 'url' => trim((string)($p['url'] ?? '')) ?: null,
                    'bio' => Sanitizer::clean((string)($p['bio'] ?? '')) ?: null, 'user_group' => ($p['role'] ?? '') === 'translator' ? 'translator' : 'author', 'include_in_browse' => empty($p['include_in_browse']) ? 0 : 1];
                if ($a) { $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($row))); DB::exec("UPDATE authors SET $set WHERE id=?", array_merge(array_values($row), [$a['id']])); $aid = (int)$a['id']; }
                else { $row['publication_id'] = $pid; $row['seq'] = 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM authors WHERE publication_id=?', [$pid]); $aid = DB::insert('authors', $row); }
                if (!empty($p['primary']) || !DB::val('SELECT primary_contact_id FROM publications WHERE id=?', [$pid])) DB::exec('UPDATE publications SET primary_contact_id=? WHERE id=?', [$aid, $pid]);
                flash(t('wizard.contrib_saved')); redirect(url('submission', 'wizard', [$sub['id'], 3]));
            }
            $a = array_merge($a ?: [], $p);
        } elseif (!$a && !DB::val('SELECT COUNT(*) FROM authors WHERE publication_id=?', [$pid])) {
            $a = ['given_name' => $u['given_name'], 'family_name' => $u['family_name'], 'email' => $u['email'], 'country' => $u['country'], 'affiliation' => $u['affiliation'], 'orcid' => $u['orcid'], 'primary' => 1];   // first contributor defaults to the submitter
        }
        View::render('backend/contributor_form', ['title' => t('wizard.contributor'), 'menu' => 'submissions', 'sub' => $sub, 'a' => $a, 'errors' => $errors, 'isEdit' => $authorId !== '',
            'isPrimary' => $a && !empty($a['id']) ? (int)($sub['primary_contact_id'] ?? 0) === (int)$a['id'] : !empty($a['primary'])], 'backend');
    }
    public function deleteContributor(string $id = '', string $authorId = ''): void {
        Auth::require(self::SUBMIT_ROLES); $sub = $this->draft($id);
        if (Request::isPost()) {
            DB::exec('DELETE FROM authors WHERE id=? AND publication_id=?', [(int)$authorId, $sub['pid']]);
            if ((int)$sub['primary_contact_id'] === (int)$authorId) DB::exec('UPDATE publications SET primary_contact_id=(SELECT id FROM (SELECT id FROM authors WHERE publication_id=? ORDER BY seq LIMIT 1) x) WHERE id=?', [$sub['pid'], $sub['pid']]);
            flash(t('wizard.contrib_deleted'));
        }
        redirect(url('submission', 'wizard', [$id, 3]));
    }
    public function moveContributor(string $id = '', string $authorId = '', string $dir = 'up'): void {
        Auth::require(self::SUBMIT_ROLES); $sub = $this->draft($id);
        if (Request::isPost()) {
            $list = array_column(DB::all('SELECT id FROM authors WHERE publication_id=? ORDER BY seq,id', [$sub['pid']]), 'id'); $i = array_search((int)$authorId, array_map('intval', $list), true);
            $j = $dir === 'up' ? $i - 1 : $i + 1;
            if ($i !== false && $j >= 0 && $j < count($list)) { [$list[$i], $list[$j]] = [$list[$j], $list[$i]]; foreach ($list as $k => $aid) DB::exec('UPDATE authors SET seq=? WHERE id=?', [$k + 1, $aid]); }
        }
        redirect(url('submission', 'wizard', [$id, 3]));
    }

    // ---------------------------------------------------------------- step 4 / finish / step 5
    private function missing(array $sub): array {
        $m = []; $pid = (int)$sub['pid'];
        if (!$sub['section_id']) $m[] = t('wizard.err_section');
        if (!DB::val("SELECT COUNT(*) FROM submission_files WHERE submission_id=? AND file_stage='submission' AND genre='Article Text'", [$sub['id']])) $m[] = t('wizard.err_article_text');
        if (trim((string)$sub['title']) === '') $m[] = t('wizard.err_title');
        if (!$sub['abstract']) $m[] = t('wizard.err_abstract');
        if (!DB::val('SELECT COUNT(*) FROM authors WHERE publication_id=? AND email<>""', [$pid])) $m[] = t('wizard.err_contributor');
        return $m;
    }
    public function finish(string $id = ''): void {
        Auth::require(self::SUBMIT_ROLES); $sub = $this->draft($id);
        if (!Request::isPost()) redirect(url('submission', 'wizard', [$id, 4]));
        if ($this->missing($sub)) { flash(t('wizard.err_incomplete'), 'error'); redirect(url('submission', 'wizard', [$id, 4])); }
        $jid = $this->jid(); $uid = Auth::id(); $now = date('Y-m-d H:i:s'); $sid = (int)$sub['id'];
        DB::exec("UPDATE submissions SET status='queued', stage_id=1, submission_progress=0, date_submitted=?, date_last_activity=? WHERE id=?", [$now, $now, $sid]);
        SubmissionRepo::log($sid, $uid, 'submission_submitted', t('log.submitted'));
        $u = Auth::user(); $url = abs_url(url('workflow', 'index', [$sid]));
        $vars = ['authorName' => trim($u['given_name'] . ' ' . $u['family_name']), 'submissionTitle' => $sub['title'], 'submissionUrl' => $url];
        EmailTemplateService::send('SUBMISSION_ACK', $u['email'], $vars, $sid);
        NotificationService::add($uid, $jid, 'submission_ack', t('notif.ack', $sub['title']), 'submission', $sid);
        foreach (DB::all("SELECT DISTINCT u.id,u.email FROM users u JOIN user_user_groups x ON x.user_id=u.id JOIN user_groups g ON g.id=x.user_group_id
                WHERE u.disabled=0 AND g.role_key IN ('editor','manager') AND g.journal_id=? AND u.id<>?", [$jid, $uid]) as $ed) {
            EmailTemplateService::send('SUBMISSION_NOTIFY_EDITORS', $ed['email'], $vars + ['recipientName' => ''], $sid);
            NotificationService::add((int)$ed['id'], $jid, 'submission_received', t('notif.new_submission', $sub['title']), 'submission', $sid);
        }
        redirect(url('submission', 'wizard', [$sid, 5]));
    }
    private function done(array $sub): void {
        View::render('backend/wizard_done', ['title' => t('wizard.title'), 'menu' => 'submissions', 'sub' => $sub], 'backend');
    }
    public function deleteDraft(string $id = ''): void {
        Auth::require(self::SUBMIT_ROLES); $sub = $this->draft($id);
        if (Request::isPost()) {
            $dir = Upload::baseDir() . '/journals/' . $this->jid() . '/submissions/' . (int)$sub['id'];
            foreach (glob($dir . '/submission/*') ?: [] as $f) @unlink($f);
            DB::exec('UPDATE submissions SET current_publication_id=NULL WHERE id=?', [$sub['id']]);
            DB::exec('DELETE FROM submissions WHERE id=?', [$sub['id']]);
            flash(t('wizard.draft_deleted'));
        }
        redirect(url('submissions'));
    }
}
