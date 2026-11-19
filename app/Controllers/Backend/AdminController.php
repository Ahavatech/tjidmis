<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request};

/** Site administration (site administrators only): hosted journals, site settings, system information, tools, e-mail outbox. */
class AdminController {
    const TABS = ['journals', 'site', 'system', 'tools', 'outbox'];
    private function guard(): void { Auth::require(['site_admin']); }
    private function back(string $tab): never { redirect(url('admin', 'index', [$tab])); }
    public function index(string $tab = 'journals'): void {
        $this->guard(); if (!in_array($tab, self::TABS, true)) $tab = 'journals';
        $d = ['title' => t('menu.admin'), 'menu' => 'admin', 'tab' => $tab];
        if ($tab === 'journals') $d['journals'] = DB::all('SELECT j.*, (SELECT value FROM journal_settings s WHERE s.journal_id=j.id AND s.name=\'name\') name, (SELECT COUNT(*) FROM submissions x WHERE x.journal_id=j.id) subs FROM journals j ORDER BY j.seq,j.id');
        if ($tab === 'site') { $d['site'] = ['title' => Settings::site('title', ''), 'intro' => Settings::site('intro', ''), 'redirect' => Settings::site('redirect_journal', ''), 'minPasswordLength' => Settings::site('minPasswordLength', '8')]; $d['journalPaths'] = DB::all('SELECT path FROM journals WHERE enabled=1 ORDER BY seq'); }
        if ($tab === 'system') $d['info'] = $this->system();
        if ($tab === 'outbox') {
            $page = max(1, (int)($_GET['page'] ?? 1)); $per = 25; $q = trim((string)($_GET['q'] ?? '')); $w = '1=1'; $p = [];
            if ($q !== '') { $like = '%' . addcslashes($q, '%_\\') . '%'; $w = '(to_addr LIKE ? OR subject LIKE ?)'; $p = [$like, $like]; }
            $d['total'] = (int)DB::val("SELECT COUNT(*) FROM email_log WHERE $w", $p); $d['rows'] = DB::all("SELECT id,to_addr,subject,sent,error,created_at,submission_id FROM email_log WHERE $w ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
            $d += ['page' => $page, 'per' => $per, 'q' => $q];
        }
        View::render('backend/admin', $d, 'backend');
    }
    private function system(): array {
        $c = $GLOBALS['config']; $free = @disk_free_space(ROOT);
        return [t('adm.php') => PHP_VERSION, t('adm.sapi') => PHP_SAPI, t('adm.db') => DB::val('SELECT VERSION()'), t('adm.server') => $_SERVER['SERVER_SOFTWARE'] ?? '-',
            t('adm.ext') => implode(', ', array_map(fn($e) => $e . ':' . (extension_loaded($e) ? 'yes' : 'NO'), ['pdo_mysql', 'mbstring', 'fileinfo', 'dom', 'gd', 'intl'])),
            t('adm.upload') => ini_get('upload_max_filesize') . ' / post ' . ini_get('post_max_size'), t('adm.memory') => ini_get('memory_limit'),
            t('adm.files_dir') => $c['files_dir'] . ' (' . (is_writable($c['files_dir']) ? t('adm.writable') : t('adm.not_writable')) . ')', t('adm.public_dir') => is_writable(ROOT . '/public') ? t('adm.writable') : t('adm.not_writable'),
            t('adm.url_mode') => ($c['url_mode'] ?? 'path') . (!empty($c['pretty_urls']) ? ' + pretty URLs' : ''), t('adm.timezone') => $c['timezone'] ?? 'UTC', t('adm.debug') => !empty($c['debug']) ? 'on' : 'off', t('adm.demo') => !empty($c['demo_mode']) ? 'on' : 'off',
            t('adm.smtp') => !empty($c['smtp']['host']) ? $c['smtp']['host'] . ':' . $c['smtp']['port'] : t('adm.smtp_none'), t('adm.disk') => $free ? round($free / 1048576) . ' MB free' : '-', t('adm.users') => (string)DB::val('SELECT COUNT(*) FROM users'),
            t('adm.submissions') => (string)DB::val('SELECT COUNT(*) FROM submissions')];
    }
    // ---------------------------------------------------------------- hosted journals
    public function journal(string $id = ''): void {
        $this->guard(); $j = $id !== '' ? DB::one('SELECT j.*, (SELECT value FROM journal_settings s WHERE s.journal_id=j.id AND s.name=\'name\') name FROM journals j WHERE j.id=?', [(int)$id]) : null; $errors = [];
        if ($id !== '' && !$j) WorkflowController::deny(404);
        if (Request::isPost()) {
            $path = trim((string)($_POST['path'] ?? '')); $name = trim((string)($_POST['name'] ?? ''));
            if (!preg_match('/^[a-z0-9_-]{1,32}$/', $path)) $errors['path'] = t('adm.err_path');
            elseif (in_array($path, array_map('strtolower', ContentSettingsController::RESERVED), true)) $errors['path'] = t('set.err_path_reserved');
            elseif (DB::val('SELECT 1 FROM journals WHERE path=? AND id<>?', [$path, (int)($j['id'] ?? 0)])) $errors['path'] = t('set.err_path_taken');
            if ($name === '') $errors['name'] = t('form.required_field');
            if (!$errors) {
                $enabled = empty($_POST['enabled']) ? 0 : 1;
                if ($j) { DB::exec('UPDATE journals SET path=?, enabled=? WHERE id=?', [$path, $enabled, $j['id']]); Settings::set('name', $name, (int)$j['id']); $jid = (int)$j['id']; }
                else { $jid = $this->createJournal($path, $name, $enabled); }
                flash(t('adm.journal_saved')); $this->back('journals');
            }
            $j = array_merge($j ?: [], $_POST);
        }
        View::render('backend/admin_journal', ['title' => t('adm.journal'), 'menu' => 'admin', 'tab' => 'journals', 'j' => $j ?: ['enabled' => 1], 'errors' => $errors, 'isEdit' => $id !== ''], 'backend');
    }
    /** New journal: copy the roles (with stage access), default settings, one section, navigation and e-mail templates from the first journal. */
    private function createJournal(string $path, string $name, int $enabled): int {
        $src = (int)DB::val('SELECT id FROM journals ORDER BY id LIMIT 1');
        $jid = DB::insert('journals', ['path' => $path, 'seq' => 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM journals'), 'enabled' => $enabled, 'created_at' => date('Y-m-d H:i:s')]);
        foreach (DB::all('SELECT * FROM user_groups WHERE journal_id=?', [$src]) as $g) {
            $ng = DB::insert('user_groups', ['journal_id' => $jid, 'role_key' => $g['role_key'], 'name' => $g['name'], 'permit_self_registration' => $g['permit_self_registration']]);
            DB::exec('INSERT INTO user_group_stages (user_group_id,stage_id) SELECT ?,stage_id FROM user_group_stages WHERE user_group_id=?', [$ng, $g['id']]);
        }
        foreach (['abbreviation' => strtoupper(substr($path, 0, 8)), 'themeColor' => Settings::get('themeColor', '#d79f24', $src), 'itemsPerPage' => '25', 'defaultReviewMode' => 'anonymous', 'numWeeksPerResponse' => '3', 'numWeeksPerReview' => '4',
                 'sidebarBlocks' => json_encode(['information', 'web_feed']), 'submissionChecklist' => '[]', 'name' => $name, 'mailFrom' => Settings::get('mailFrom', 'noreply@localhost', $src), 'licenseUrl' => 'https://creativecommons.org/licenses/by/4.0/'] as $k => $v) Settings::set($k, $v, $jid);
        DB::insert('sections', ['journal_id' => $jid, 'title' => 'Articles', 'abbrev' => 'ART', 'seq' => 1]);
        DB::exec('INSERT INTO email_templates (journal_id,email_key,subject,body,enabled,is_custom) SELECT ?,email_key,subject,body,enabled,0 FROM email_templates WHERE journal_id=?', [$jid, $src]);
        foreach ([['About', 'about'], ['Current', 'current'], ['Archives', 'archives'], ['Submissions', 'submissions'], ['Announcements', 'announcements']] as $i => [$t, $ty])
            DB::insert('navigation_menu_items', ['journal_id' => $jid, 'menu' => 'primary', 'type' => $ty, 'title' => $t, 'seq' => $i + 1]);
        return $jid;
    }
    public function deleteJournal(string $id = ''): void {
        $this->guard();
        if (Request::isPost()) {
            $first = (int)DB::val('SELECT id FROM journals ORDER BY id LIMIT 1');
            if ((int)$id === $first) flash(t('adm.err_default_journal'), 'error'); elseif (DB::val('SELECT COUNT(*) FROM submissions WHERE journal_id=?', [(int)$id])) flash(t('adm.err_journal_used'), 'error');
            else { DB::exec('DELETE FROM journals WHERE id=?', [(int)$id]); flash(t('set.deleted')); }
        }
        $this->back('journals');
    }
    public function moveJournal(string $id = '', string $dir = 'up'): void {
        $this->guard();
        if (Request::isPost()) { $l = array_map('intval', array_column(DB::all('SELECT id FROM journals ORDER BY seq,id'), 'id')); $i = array_search((int)$id, $l, true); $j = $dir === 'up' ? $i - 1 : $i + 1;
            if ($i !== false && $j >= 0 && $j < count($l)) { [$l[$i], $l[$j]] = [$l[$j], $l[$i]]; foreach ($l as $n => $x) DB::exec('UPDATE journals SET seq=? WHERE id=?', [$n + 1, $x]); } }
        $this->back('journals');
    }
    // ---------------------------------------------------------------- site settings / tools / outbox
    public function saveSite(): void {
        $this->guard();
        if (Request::isPost()) {
            $title = trim((string)($_POST['title'] ?? '')); $min = (int)($_POST['minPasswordLength'] ?? 8); $red = (string)($_POST['redirect_journal'] ?? '');
            if ($title === '' || $min < 6 || $min > 32 || !DB::val('SELECT 1 FROM journals WHERE path=?', [$red])) { flash(t('set.fix'), 'error'); $this->back('site'); }
            Settings::setSite('title', mb_substr($title, 0, 255)); Settings::setSite('intro', trim((string)($_POST['intro'] ?? ''))); Settings::setSite('redirect_journal', $red); Settings::setSite('minPasswordLength', (string)$min);
            flash(t('profile.saved'));
        }
        $this->back('site');
    }
    public function clearCache(): void {
        $this->guard();
        if (Request::isPost()) { $n = 0; foreach (glob(rtrim($GLOBALS['config']['files_dir'], '/\\') . '/tmp/*') ?: [] as $f) if (is_file($f) && @unlink($f)) $n++; flash(t('adm.cache_cleared', $n)); }
        $this->back('tools');
    }
    public function expireSessions(): void {
        $this->guard();
        if (Request::isPost()) { $now = time(); Settings::setSite('sessionsEpoch', (string)$now); $_SESSION['epoch'] = $now + 1; DB::exec('DELETE FROM sessions_remember'); flash(t('adm.sessions_expired')); }
        $this->back('tools');
    }
    public function email(string $id = ''): void {
        $this->guard(); $e = DB::one('SELECT * FROM email_log WHERE id=?', [(int)$id]); if (!$e) WorkflowController::deny(404);
        View::render('backend/admin_email', ['title' => t('adm.email'), 'menu' => 'admin', 'tab' => 'outbox', 'e' => $e], 'backend');
    }
    public function purgeOutbox(): void {
        $this->guard(); if (Request::isPost()) { $n = DB::exec('DELETE FROM email_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'); flash(t('adm.outbox_purged', $n)); }
        $this->back('outbox');
    }
}
