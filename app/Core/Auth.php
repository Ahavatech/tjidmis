<?php
namespace App\Core;
class Auth {
    private static ?array $user = null;
    public static function start(): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
        session_name('JIRSSESSID');
        session_start();
    }
    public static function user(): ?array {
        if (self::$user === null && !empty($_SESSION['uid'])) {
            self::$user = DB::one('SELECT * FROM users WHERE id=? AND disabled=0', [$_SESSION['uid']]) ?: [];
        }
        return self::$user ?: null;
    }
    public static function attempt(string $login, string $password): bool {
        $u = DB::one('SELECT * FROM users WHERE (username=? OR email=?) AND disabled=0', [$login, $login]);
        if (!$u || !password_verify($password, $u['password_hash'])) return false;
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        DB::exec('UPDATE users SET last_login_at=NOW() WHERE id=?', [$u['id']]);
        self::$user = $u;
        return true;
    }
    public static function logout(): void { $_SESSION = []; session_regenerate_id(true); self::$user = null; }
    /** Role keys held by the user in a journal (journal 0/NULL groups = site-level). */
    public static function roles(?int $journalId = null): array {
        $u = self::user(); if (!$u) return [];
        return array_column(DB::all('SELECT DISTINCT g.role_key FROM user_user_groups uug JOIN user_groups g ON g.id=uug.user_group_id
            WHERE uug.user_id=? AND (g.journal_id IS NULL OR g.journal_id=?)', [$u['id'], $journalId ?? 0]), 'role_key');
    }
    public static function hasRole(array $keys, ?int $journalId = null): bool { return (bool)array_intersect($keys, self::roles($journalId)); }
    /** Call at the top of every back-end route. */
    public static function require(array $roles, ?int $journalId = null): void {
        if (!self::user()) redirect(url('login', null, [], ['source' => $_SERVER['REQUEST_URI'] ?? '']));
        if (!self::hasRole($roles, $journalId)) { http_response_code(403); View::render('public/error', ['code' => 403, 'message' => t('error.403')]); exit; }
    }
}
