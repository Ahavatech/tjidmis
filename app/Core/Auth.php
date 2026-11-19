<?php
namespace App\Core;
class Auth {
    private static ?array $user = null;
    private static bool $loaded = false;
    private static array $rolesCache = [];
    const REMEMBER_COOKIE = 'JIRSREMEMBER', REMEMBER_DAYS = 30, MAX_FAILS = 5, LOCK_SECONDS = 300;

    public static function start(): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => self::https()]);
        session_name('JIRSSESSID');
        session_start();
    }
    private static function https(): bool { return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'; }

    public static function user(): ?array {
        if (!self::$loaded) {
            self::$loaded = true;
            if (!empty($_SESSION['uid']) && (int)($_SESSION['epoch'] ?? 0) < (int)Settings::site('sessionsEpoch', 0)) $_SESSION = [];   // admin expired all sessions
            if (!empty($_SESSION['uid'])) self::$user = DB::one('SELECT * FROM users WHERE id=? AND disabled=0', [$_SESSION['uid']]);
            if (!self::$user && empty($_SESSION['uid']) && !empty($_COOKIE[self::REMEMBER_COOKIE])) self::restoreRemembered();
            if (!self::$user) unset($_SESSION['uid']);
        }
        return self::$user;
    }
    public static function id(): int { return (int)(self::user()['id'] ?? 0); }
    private static function reset(): void { self::$user = null; self::$loaded = false; self::$rolesCache = []; }

    /** @return string ok | invalid | disabled | locked */
    public static function attempt(string $login, string $password, bool $remember = false): string {
        $f = $_SESSION['login_fail'] ?? ['n' => 0, 't' => 0];
        if ($f['n'] >= self::MAX_FAILS && time() - $f['t'] < self::LOCK_SECONDS) return 'locked';
        if ($f['n'] >= self::MAX_FAILS) $f = ['n' => 0, 't' => 0];
        $u = DB::one('SELECT * FROM users WHERE username=? OR email=?', [$login, $login]);
        // always run a hash check so response time does not reveal whether the account exists
        $ok = password_verify($password, $u['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
        if (!$u || !$ok) { $_SESSION['login_fail'] = ['n' => $f['n'] + 1, 't' => time()]; return 'invalid'; }
        if ($u['disabled']) return 'disabled';
        unset($_SESSION['login_fail']);
        self::loginId((int)$u['id']);
        if ($remember) self::remember((int)$u['id']);
        return 'ok';
    }
    /** Log a user in without a password (after registration, password reset or remember-me). */
    public static function loginId(int $id): void {
        session_regenerate_id(true);
        $_SESSION = ['uid' => $id, 'csrf' => bin2hex(random_bytes(32)), 'epoch' => time()];
        DB::exec('UPDATE users SET last_login_at=NOW() WHERE id=?', [$id]);
        self::reset();
    }
    public static function logout(): void {
        if (!empty($_COOKIE[self::REMEMBER_COOKIE])) {
            $p = explode(':', (string)$_COOKIE[self::REMEMBER_COOKIE], 2);
            if (count($p) === 2) DB::exec('DELETE FROM sessions_remember WHERE token_hash=?', [hash('sha256', $p[1])]);
            self::cookie('', time() - 3600);
        }
        $_SESSION = []; session_regenerate_id(true); self::reset();
    }
    private static function cookie(string $v, int $exp): void {
        setcookie(self::REMEMBER_COOKIE, $v, ['expires' => $exp, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => self::https()]);
    }
    private static function remember(int $uid): void {
        $tok = bin2hex(random_bytes(32)); $exp = time() + self::REMEMBER_DAYS * 86400;
        DB::exec('DELETE FROM sessions_remember WHERE expires_at<NOW()');
        DB::insert('sessions_remember', ['user_id' => $uid, 'token_hash' => hash('sha256', $tok), 'expires_at' => date('Y-m-d H:i:s', $exp)]);
        self::cookie($uid . ':' . $tok, $exp);
    }
    private static function restoreRemembered(): void {
        $p = explode(':', (string)$_COOKIE[self::REMEMBER_COOKIE], 2);
        if (count($p) !== 2 || !ctype_digit($p[0])) return;
        $h = hash('sha256', $p[1]);
        $row = DB::one('SELECT r.user_id FROM sessions_remember r JOIN users u ON u.id=r.user_id AND u.disabled=0 WHERE r.token_hash=? AND r.user_id=? AND r.expires_at>NOW()', [$h, (int)$p[0]]);
        if (!$row) { self::cookie('', time() - 3600); return; }
        DB::exec('DELETE FROM sessions_remember WHERE token_hash=?', [$h]);   // rotate: one-time token
        self::loginId((int)$row['user_id']);
        self::remember((int)$row['user_id']);
        self::$user = DB::one('SELECT * FROM users WHERE id=?', [$row['user_id']]);
        self::$loaded = true;
    }

    /** Role keys held in a journal (site-level groups, journal_id NULL, always count). */
    public static function roles(?int $journalId = null): array {
        $u = self::user(); if (!$u) return [];
        $jid = $journalId ?? (int)(Settings::journal()['id'] ?? 0);
        return self::$rolesCache[$u['id']][$jid] ??= array_column(DB::all('SELECT DISTINCT g.role_key FROM user_user_groups uug JOIN user_groups g ON g.id=uug.user_group_id
            WHERE uug.user_id=? AND (g.journal_id IS NULL OR g.journal_id=?)', [$u['id'], $jid]), 'role_key');
    }
    public static function hasRole(array $keys, ?int $journalId = null): bool { return (bool)array_intersect($keys, self::roles($journalId)); }
    public static function requireLogin(): void {
        if (!self::user()) { flash(t('auth.login_required'), 'error'); redirect(url('login', null, [], ['source' => $_SERVER['REQUEST_URI'] ?? ''])); }
    }
    /** Call at the top of every back-end route. */
    public static function require(array $roles, ?int $journalId = null): void {
        self::requireLogin();
        if (!self::hasRole($roles, $journalId)) { http_response_code(403); View::render('public/error', ['code' => 403, 'message' => t('error.403'), 'title' => '403']); exit; }
    }

    // ---- "Login As" (impersonation) ----
    public static function impersonating(): bool { return !empty($_SESSION['uid_real']); }
    public static function realUser(): ?array { return self::impersonating() ? DB::one('SELECT * FROM users WHERE id=?', [$_SESSION['uid_real']]) : self::user(); }
    public static function loginAs(int $targetId): bool {
        $real = self::realUser(); if (!$real || self::impersonating() || $real['id'] === $targetId) return false;
        $rr = array_column(DB::all('SELECT DISTINCT g.role_key FROM user_user_groups x JOIN user_groups g ON g.id=x.user_group_id WHERE x.user_id=?', [$real['id']]), 'role_key');
        if (!array_intersect(['manager', 'site_admin'], $rr)) return false;
        $t = DB::one('SELECT * FROM users WHERE id=? AND disabled=0', [$targetId]); if (!$t) return false;
        $tr = array_column(DB::all('SELECT DISTINCT g.role_key FROM user_user_groups x JOIN user_groups g ON g.id=x.user_group_id WHERE x.user_id=?', [$targetId]), 'role_key');
        if (in_array('site_admin', $tr, true) && !in_array('site_admin', $rr, true)) return false;
        $_SESSION['uid_real'] = (int)$real['id']; $_SESSION['uid'] = $targetId; self::reset(); return true;
    }
    public static function signOutAs(): void {
        if (!self::impersonating()) return;
        $_SESSION['uid'] = (int)$_SESSION['uid_real']; unset($_SESSION['uid_real']); self::reset();
    }
}
