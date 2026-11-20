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
        if ($res === 'ok') { if (Auth::user()['must_change_password']) { flash(t('login.must_change')); redirect(url('user', 'profile', ['password'])); } redirect($source ?: url('submissions')); }
        // repeated failures are throttled silently: OJS shows its normal error and has no lock-out message
        View::render('public/login', ['title' => t('nav.login'), 'bodyClass' => 'login', 'source' => $source, 'username' => $user,
            'error' => t(['invalid' => 'login.invalid', 'disabled' => 'login.disabled', 'locked' => 'login.invalid'][$res])]);
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
        $u = DB::one('SELECT * FROM users WHERE email=?', [$email]);
        if ($u && (int)$u['disabled']) {   // OJS tells the user why the reset cannot be completed
            View::render('public/lost_password', ['title' => t('lost.title'), 'bodyClass' => 'lost_password', 'error' => t('reset.failed', t('reset.disabled'))]); return;
        }
        if ($u) {
            DB::exec('DELETE FROM password_resets WHERE user_id=? OR expires_at<NOW()', [$u['id']]);
            $tok = bin2hex(random_bytes(32));
            DB::insert('password_resets', ['user_id' => $u['id'], 'token_hash' => hash('sha256', $tok), 'expires_at' => date('Y-m-d H:i:s', time() + 3600)]);
            EmailTemplateService::send('PASSWORD_RESET_CONFIRM', $u['email'], ['recipientName' => trim($u['given_name'] . ' ' . $u['family_name']), 'username' => $u['username'],
                'passwordResetUrl' => abs_url(url('login', 'resetPassword', [$u['username']], ['confirm' => $tok]))]);
        }
        // same answer whether or not the address exists, so the form cannot be used to discover accounts
        View::render('public/message', ['title' => t('lost.title'), 'bodyClass' => 'lost_password', 'heading' => t('lost.title'), 'message' => t('lost.sent'), 'backUrl' => url('login', null, [], $u ? ['username' => $u['username']] : []), 'backLabel' => t('nav.login')]);
    }
    /** OJS 3.3: the emailed link is /login/resetPassword/{username}?confirm={hash}; it shows a form to choose a new password. */
    private function resetUser(string $username, string $tok): ?array {
        return DB::one('SELECT u.* FROM users u JOIN password_resets r ON r.user_id=u.id WHERE u.username=? AND r.token_hash=? AND r.expires_at>NOW() AND u.disabled=0', [$username, hash('sha256', $tok)]);
    }
    private function resetMessage(string $message, ?string $backUrl, string $backLabel, string $cls = 'page_message'): void {
        View::render('public/message', ['title' => t('lost.title'), 'bodyClass' => 'lost_password', 'heading' => t('lost.title'), 'message' => $message, 'backUrl' => $backUrl, 'backLabel' => $backLabel, 'cls' => $cls]);
    }
    public function resetPassword(string $username = ''): void {
        if (Auth::user()) redirect(url('submissions'));
        $row = $username === '' ? null : DB::one('SELECT id, disabled FROM users WHERE username=?', [$username]);
        if (!$row) redirect(url('login', 'lostPassword'));
        if ((int)$row['disabled']) { $this->resetMessage(t('reset.failed', t('reset.disabled')), url('login'), t('nav.login')); return; }
        $tok = (string)($_GET['confirm'] ?? '');
        if ($tok === '' || !$this->resetUser($username, $tok)) { $this->resetMessage(t('reset.invalid'), url('login', 'lostPassword'), t('lost.title'), 'page_error'); return; }
        View::render('public/reset_password', ['title' => t('lost.title'), 'username' => $username, 'hash' => $tok, 'errors' => []], 'backend_anon');
    }
    public function updateResetPassword(): void {
        if (!Request::isPost()) redirect(url('login', 'lostPassword'));
        $username = (string)($_POST['username'] ?? ''); $tok = (string)($_POST['hash'] ?? '');
        if ($username === '' || !DB::one('SELECT id FROM users WHERE username=?', [$username])) redirect(url('login', 'lostPassword'));
        $u = $this->resetUser($username, $tok);
        if (!$u) { $this->resetMessage(t('reset.invalid'), url('login', 'lostPassword'), t('lost.title'), 'page_error'); return; }
        $min = (int)Settings::site('minPasswordLength', '8'); $p = (string)($_POST['password'] ?? ''); $errors = [];
        // OJS 3.3 ResetPasswordForm checks, in order: length, required, match. All are on the "password" field and the first failure is shown.
        if (strlen($p) < $min) $errors['password'] = t('form.password_short', $min);
        elseif ($p === '') $errors['password'] = t('reset.err_required');
        elseif ($p !== (string)($_POST['password2'] ?? '')) $errors['password'] = t('form.password_mismatch');
        if ($errors) { View::render('public/reset_password', ['title' => t('lost.title'), 'username' => $username, 'hash' => $tok, 'errors' => $errors], 'backend_anon'); return; }
        DB::exec('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?', [password_hash($p, PASSWORD_DEFAULT), $u['id']]);
        DB::exec('DELETE FROM password_resets WHERE user_id=?', [$u['id']]);
        DB::exec('DELETE FROM sessions_remember WHERE user_id=?', [$u['id']]);   // sign out remembered devices
        $this->resetMessage(t('reset.done'), url('login', null, [], ['username' => $username]), t('nav.login'));
    }
}
