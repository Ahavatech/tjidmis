<?php
namespace App\Controllers\Public;
use App\Core\{View, Auth, DB, Settings, Request};
use App\Services\EmailTemplateService;
class LoginController {
    public function index(): void {
        if (Auth::user()) redirect(url('submissions'));
        View::render('public/login', ['title' => t('nav.login'), 'bodyClass' => 'login', 'source' => safe_source($_GET['source'] ?? ''), 'error' => null, 'username' => '']);
    }
    public function signIn(): void {
        if (!Request::isPost()) redirect(url('login'));
        $source = safe_source($_POST['source'] ?? '');
        $user = trim((string)($_POST['username'] ?? ''));
        $res = Auth::attempt($user, (string)($_POST['password'] ?? ''), !empty($_POST['remember']));
        if ($res === 'ok') redirect($source ?: url('submissions'));
        http_response_code($res === 'locked' ? 429 : 200);
        View::render('public/login', ['title' => t('nav.login'), 'bodyClass' => 'login', 'source' => $source, 'username' => $user,
            'error' => t(['invalid' => 'login.invalid', 'disabled' => 'login.disabled', 'locked' => 'login.locked'][$res])]);
    }
    public function signOut(): void { Auth::logout(); flash(t('login.signed_out')); redirect(url()); }
    public function signOutAsUser(): void { Auth::signOutAs(); redirect(url('submissions')); }

    public function lostPassword(): void {
        View::render('public/lost_password', ['title' => t('lost.title'), 'bodyClass' => 'lost_password', 'error' => null]);
    }
    public function requestResetPassword(): void {
        if (!Request::isPost()) redirect(url('login', 'lostPassword'));
        $email = trim((string)($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            View::render('public/lost_password', ['title' => t('lost.title'), 'bodyClass' => 'lost_password', 'error' => t('form.email_invalid')]); return;
        }
        $u = DB::one('SELECT * FROM users WHERE email=? AND disabled=0', [$email]);
        if ($u) {
            DB::exec('DELETE FROM password_resets WHERE user_id=? OR expires_at<NOW()', [$u['id']]);
            $tok = bin2hex(random_bytes(32));
            DB::insert('password_resets', ['user_id' => $u['id'], 'token_hash' => hash('sha256', $tok), 'expires_at' => date('Y-m-d H:i:s', time() + 3600)]);
            EmailTemplateService::send('PASSWORD_RESET', $u['email'], ['recipientName' => trim($u['given_name'] . ' ' . $u['family_name']), 'username' => $u['username'],
                'passwordResetUrl' => abs_url(url('login', 'resetPassword', [$tok]))]);
        }
        // same answer whether or not the address exists, so the form cannot be used to discover accounts
        flash(t('lost.sent')); redirect(url('login'));
    }
    private function validReset(string $tok): ?array {
        return $tok === '' ? null : DB::one('SELECT user_id FROM password_resets WHERE token_hash=? AND expires_at>NOW()', [hash('sha256', $tok)]);
    }
    public function resetPassword(string $token = ''): void {
        $r = $this->validReset($token); $errors = [];
        if (!$r) { flash(t('reset.invalid'), 'error'); redirect(url('login', 'lostPassword')); }
        if (Request::isPost()) {
            $min = (int)Settings::site('minPasswordLength', '8'); $p = (string)($_POST['password'] ?? '');
            if (strlen($p) < $min) $errors['password'] = t('form.password_short', $min);
            elseif (strlen($p) > 72) $errors['password'] = t('form.password_long');
            elseif ($p !== (string)($_POST['password2'] ?? '')) $errors['password2'] = t('form.password_mismatch');
            if (!$errors) {
                DB::exec('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?', [password_hash($p, PASSWORD_DEFAULT), $r['user_id']]);
                DB::exec('DELETE FROM password_resets WHERE user_id=?', [$r['user_id']]);
                DB::exec('DELETE FROM sessions_remember WHERE user_id=?', [$r['user_id']]);   // sign out remembered devices
                flash(t('reset.done')); redirect(url('login'));
            }
        }
        View::render('public/reset_password', ['title' => t('reset.title'), 'bodyClass' => 'lost_password', 'token' => $token, 'errors' => $errors]);
    }
}
