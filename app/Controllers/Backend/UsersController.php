<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Countries, Mailer};
use App\Services\{EmailTemplateService, NotificationService};

/** Users & Roles: users grid, add/edit, roles, e-mail, disable/enable, remove from journal; roles grid; site access options. */
class UsersController {
    const ROLES = ['manager', 'site_admin'];
    private function jid(): int { return (int)Settings::journal()['id']; }
    private function guard(): void { Auth::require(self::ROLES); }
    private function isSiteAdmin(): bool { return Auth::hasRole(['site_admin']); }
    private function user(string $id): array { $this->guard(); $u = DB::one('SELECT * FROM users WHERE id=?', [(int)$id]); if (!$u) WorkflowController::deny(404); return $u; }
    /** Groups the signed-in user may hand out: this journal's groups, plus the site administrator group for site admins only. */
    private function assignable(): array {
        return DB::all('SELECT id,role_key,name,journal_id FROM user_groups WHERE journal_id=?' . ($this->isSiteAdmin() ? ' OR journal_id IS NULL' : '') . ' ORDER BY journal_id IS NULL DESC, id', [$this->jid()]);
    }
    private function isLastSiteAdmin(int $uid): bool {
        return (bool)DB::val("SELECT 1 FROM user_user_groups x JOIN user_groups g ON g.id=x.user_group_id WHERE g.role_key='site_admin' AND x.user_id=?", [$uid])
            && (int)DB::val("SELECT COUNT(DISTINCT x.user_id) FROM user_user_groups x JOIN user_groups g ON g.id=x.user_group_id JOIN users u ON u.id=x.user_id WHERE g.role_key='site_admin' AND u.disabled=0") <= 1;
    }
    private function protectedAdmin(array $u): bool {   // managers may not touch site administrators
        return !$this->isSiteAdmin() && (bool)DB::val("SELECT 1 FROM user_user_groups x JOIN user_groups g ON g.id=x.user_group_id WHERE g.role_key='site_admin' AND x.user_id=?", [$u['id']]);
    }

    public function index(): void {
        $this->guard(); $jid = $this->jid(); $q = trim((string)($_GET['q'] ?? '')); $field = (string)($_GET['field'] ?? ''); $role = (int)($_GET['role'] ?? 0); $page = max(1, (int)($_GET['page'] ?? 1)); $per = 25;
        $w = ['1=1']; $p = [$jid];
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%'; $col = ['given' => 'u.given_name', 'family' => 'u.family_name', 'username' => 'u.username', 'email' => 'u.email'][$field] ?? null;
            if ($col) { $w[] = "$col LIKE ?"; $p[] = $like; } else { $w[] = '(u.username LIKE ? OR u.email LIKE ? OR CONCAT(u.given_name," ",u.family_name) LIKE ?)'; array_push($p, $like, $like, $like); }
        }
        if ($role) { $w[] = 'u.id IN (SELECT user_id FROM user_user_groups WHERE user_group_id=?)'; $p[] = $role; }
        $where = implode(' AND ', $w); $cp = array_slice($p, 1);
        $total = (int)DB::val("SELECT COUNT(*) FROM users u WHERE $where", $cp);
        $rows = DB::all("SELECT u.id,u.username,u.email,u.given_name,u.family_name,u.disabled,u.last_login_at, GROUP_CONCAT(DISTINCT g.name ORDER BY g.id SEPARATOR ', ') roles
            FROM users u LEFT JOIN user_user_groups x ON x.user_id=u.id LEFT JOIN user_groups g ON g.id=x.user_group_id AND (g.journal_id IS NULL OR g.journal_id=?) WHERE $where GROUP BY u.id ORDER BY u.family_name, u.given_name LIMIT $per OFFSET " . (($page - 1) * $per), $p);
        View::render('backend/users', ['title' => t('menu.users'), 'menu' => 'settings', 'sub' => 'users', 'rows' => $rows, 'q' => $q, 'field' => $field, 'role' => $role, 'groups' => $this->assignable(), 'total' => $total, 'page' => $page, 'per' => $per, 'me' => Auth::id()], 'backend');
    }
    private function validate(array $p, ?array $u, bool $creating): array {
        $e = []; $email = strtolower(trim((string)($p['email'] ?? ''))); $uname = strtolower(trim((string)($p['username'] ?? '')));
        if (trim((string)($p['given_name'] ?? '')) === '') $e['given_name'] = t('form.required_field');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $e['email'] = t('form.email_invalid'); elseif (DB::val('SELECT 1 FROM users WHERE email=? AND id<>?', [$email, (int)($u['id'] ?? 0)])) $e['email'] = t('reg.email_taken');
        if ($creating) { if (!preg_match('/^[a-z0-9]+([\-_][a-z0-9]+)*$/', $uname) || strlen($uname) < 3 || strlen($uname) > 32) $e['username'] = t('reg.username_rule'); elseif (DB::val('SELECT 1 FROM users WHERE username=?', [$uname])) $e['username'] = t('reg.username_taken'); }
        if (!isset(Countries::all()[$p['country'] ?? ''])) $e['country'] = t('form.required_field');
        return $e;
    }
    public function form(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $u = $id !== '' ? $this->user($id) : null; $errors = [];
        if ($u && $this->protectedAdmin($u)) WorkflowController::deny(403);
        $groups = $this->assignable(); $min = (int)Settings::site('minPasswordLength', '8');
        if (Request::isPost()) {
            $p = $_POST; $errors = $this->validate($p, $u, !$u); $pw = (string)($p['password'] ?? '');
            if ((!$u && $pw === '') || ($pw !== '' && (strlen($pw) < $min || strlen($pw) > 72))) $errors['password'] = t('form.password_short', $min);
            if (!$errors) {
                $row = ['given_name' => trim($p['given_name']), 'family_name' => trim((string)($p['family_name'] ?? '')), 'email' => strtolower(trim($p['email'])), 'affiliation' => trim((string)($p['affiliation'] ?? '')), 'country' => $p['country']];
                if ($u) { if ($pw !== '') $row['password_hash'] = password_hash($pw, PASSWORD_DEFAULT); DB::exec('UPDATE users SET ' . implode(',', array_map(fn($k) => "`$k`=?", array_keys($row))) . ' WHERE id=?', array_merge(array_values($row), [$u['id']])); $uid = (int)$u['id']; flash(t('users.saved')); }
                else {
                    $row += ['username' => strtolower(trim($p['username'])), 'password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'created_at' => date('Y-m-d H:i:s'), 'must_change_password' => empty($p['must_change']) ? 0 : 1];
                    $uid = DB::insert('users', $row);
                    $ids = array_intersect(array_map('intval', (array)($p['groups'] ?? [])), array_map('intval', array_column($groups, 'id'))) ?: [(int)DB::val("SELECT id FROM user_groups WHERE journal_id=? AND role_key='reader'", [$jid])];
                    foreach ($ids as $g) DB::exec('INSERT IGNORE INTO user_user_groups (user_id,user_group_id) VALUES (?,?)', [$uid, $g]);
                    if (!empty($p['send_welcome'])) EmailTemplateService::send('USER_REGISTER', $row['email'], ['recipientName' => trim($row['given_name'] . ' ' . $row['family_name']), 'username' => $row['username']]);
                    flash(t('users.created', $row['username']));
                }
                redirect(url('users'));
            }
            $u = array_merge($u ?: [], $p);
        }
        View::render('backend/user_form', ['title' => $u && !empty($u['id']) ? t('users.edit') : t('users.add'), 'menu' => 'settings', 'sub' => 'users', 'u' => $u ?: [], 'errors' => $errors, 'groups' => $groups, 'isEdit' => $id !== '', 'chosen' => array_map('intval', (array)($_POST['groups'] ?? []))], 'backend');
    }
    public function editRoles(string $id = ''): void {
        $u = $this->user($id); if ($this->protectedAdmin($u)) WorkflowController::deny(403); $groups = $this->assignable(); $gids = array_map('intval', array_column($groups, 'id'));
        if (Request::isPost()) {
            $new = array_intersect(array_map('intval', (array)($_POST['groups'] ?? [])), $gids);
            $sa = (int)DB::val("SELECT id FROM user_groups WHERE role_key='site_admin'");
            if ($this->isLastSiteAdmin((int)$u['id']) && $sa && !in_array($sa, $new, true)) { flash(t('users.err_last_admin'), 'error'); redirect(url('users', 'editRoles', [$u['id']])); }
            if ((int)$u['id'] === Auth::id() && !array_intersect($new, array_map('intval', array_column(DB::all("SELECT id FROM user_groups WHERE role_key IN ('manager','site_admin') AND (journal_id=? OR journal_id IS NULL)", [$this->jid()]), 'id')))) { flash(t('users.err_self_roles'), 'error'); redirect(url('users', 'editRoles', [$u['id']])); }
            DB::exec('DELETE FROM user_user_groups WHERE user_id=? AND user_group_id IN (' . implode(',', $gids ?: [0]) . ')', [$u['id']]);
            foreach ($new as $g) DB::exec('INSERT IGNORE INTO user_user_groups (user_id,user_group_id) VALUES (?,?)', [$u['id'], $g]);
            flash(t('users.roles_saved')); redirect(url('users'));
        }
        View::render('backend/user_roles', ['title' => t('users.edit_roles'), 'menu' => 'settings', 'sub' => 'users', 'u' => $u, 'groups' => $groups, 'chosen' => array_map('intval', array_column(DB::all('SELECT user_group_id FROM user_user_groups WHERE user_id=?', [$u['id']]), 'user_group_id'))], 'backend');
    }
    public function email(string $id = ''): void {
        $u = $this->user($id); $errors = [];
        if (Request::isPost()) {
            $sub = trim((string)($_POST['subject'] ?? '')); $body = trim((string)($_POST['body'] ?? ''));
            if ($sub === '') $errors['subject'] = t('form.required_field'); if ($body === '') $errors['body'] = t('form.required_field');
            if (!$errors) { Mailer::send($u['email'], mb_substr($sub, 0, 255), $body); flash(t('users.emailed', $u['email'])); redirect(url('users')); }
        }
        View::render('backend/user_email', ['title' => t('users.email'), 'menu' => 'settings', 'sub' => 'users', 'u' => $u, 'errors' => $errors], 'backend');
    }
    public function toggle(string $id = ''): void {
        $u = $this->user($id);
        if (Request::isPost()) {
            if ((int)$u['id'] === Auth::id()) flash(t('users.err_self'), 'error');
            elseif ($this->protectedAdmin($u)) WorkflowController::deny(403);
            elseif (!$u['disabled'] && $this->isLastSiteAdmin((int)$u['id'])) flash(t('users.err_last_admin'), 'error');
            else {
                DB::exec('UPDATE users SET disabled=?, disabled_reason=? WHERE id=?', [$u['disabled'] ? 0 : 1, $u['disabled'] ? null : (mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 255) ?: null), $u['id']]);
                if (!$u['disabled']) DB::exec('DELETE FROM sessions_remember WHERE user_id=?', [$u['id']]);
                flash($u['disabled'] ? t('users.enabled') : t('users.disabled_done'));
            }
        }
        redirect(url('users'));
    }
    /** Removes the user's roles in this journal (the account stays). */
    public function remove(string $id = ''): void {
        $u = $this->user($id);
        if (Request::isPost()) {
            if ((int)$u['id'] === Auth::id()) flash(t('users.err_self'), 'error'); elseif ($this->protectedAdmin($u)) WorkflowController::deny(403);
            else { DB::exec('DELETE FROM user_user_groups WHERE user_id=? AND user_group_id IN (SELECT id FROM user_groups WHERE journal_id=?)', [$u['id'], $this->jid()]); flash(t('users.removed')); }
        }
        redirect(url('users'));
    }
    public function roles(): void {
        $this->guard(); $jid = $this->jid();
        if (Request::isPost()) {
            foreach (DB::all('SELECT id,role_key FROM user_groups WHERE journal_id=?', [$jid]) as $g) {
                if (in_array($g['role_key'], ['manager', 'site_admin'], true)) continue;
                DB::exec('DELETE FROM user_group_stages WHERE user_group_id=?', [$g['id']]);
                foreach ([1, 3, 4, 5] as $s) if (!empty($_POST['stage'][$g['id']][$s])) DB::exec('INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (?,?)', [$g['id'], $s]);
                if (in_array($g['role_key'], ['author', 'reviewer', 'reader'], true)) DB::exec('UPDATE user_groups SET permit_self_registration=? WHERE id=?', [empty($_POST['selfreg'][$g['id']]) ? 0 : 1, $g['id']]);
            }
            flash(t('profile.saved')); redirect(url('users', 'roles'));
        }
        $groups = DB::all('SELECT * FROM user_groups WHERE journal_id=? ORDER BY id', [$jid]); $st = [];
        foreach (DB::all('SELECT * FROM user_group_stages') as $r) $st[$r['user_group_id']][$r['stage_id']] = true;
        View::render('backend/roles_grid', ['title' => t('users.roles'), 'menu' => 'settings', 'sub' => 'roles', 'groups' => $groups, 'st' => $st], 'backend');
    }
    public function access(): void {
        $this->guard();
        if (Request::isPost()) { $j = $this->jid(); Settings::set('restrictSiteAccess', empty($_POST['restrictSiteAccess']) ? '0' : '1', $j); Settings::set('restrictArticleAccess', empty($_POST['restrictArticleAccess']) ? '0' : '1', $j); Settings::set('disableUserReg', ($_POST['disableUserReg'] ?? '0') === '1' ? '1' : '0', $j); flash(t('profile.saved')); redirect(url('users', 'access')); }
        View::render('backend/site_access', ['title' => t('users.access'), 'menu' => 'settings', 'sub' => 'access', 'restrictSite' => Settings::get('restrictSiteAccess', '0') === '1', 'restrictArticle' => Settings::get('restrictArticleAccess', '0') === '1', 'disableReg' => Settings::get('disableUserReg', '0') === '1'], 'backend');
    }
}
