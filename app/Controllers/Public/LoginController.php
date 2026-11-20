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
            EmailTemplateService::send('PASSWORD_RESET_CONFIRM', $u['email'], ['recipientName' => trim($u['given_name'] . ' ' . $u['family_name']), 'username' => $u['username'],
                'passwordResetUrl' => abs_url(url('login', 'resetPassword', [$u['username']], ['confirm' => $tok]))]);
        }
        // same answer whether or not the address exists, so the form cannot be used to discover accounts
        View::render('public/message', ['title' => t('lost.title'), 'bodyClass' => 'lost_password', 'heading' => t('lost.title'), 'message' => t('lost.sent'), 'backUrl' => url('login'), 'backLabel' => t('nav.login')]);
    }
    /** OJS 3.3 flow: the emailed link is /login/resetPassword/{username}?confirm={hash}. A valid link resets the password to a random one and emails it. */
    public function resetPassword(string $username = ''): void {
        $tok = (string)($_GET['confirm'] ?? '');
        $u = ($username === '' || $tok === '') ? null : DB::one('SELECT u.* FROM users u JOIN password_resets r ON r.user_id=u.id WHERE u.username=? AND r.token_hash=? AND r.expires_at>NOW() AND u.disabled=0', [$username, hash('sha256', $tok)]);
        if (!$u) redirect(url('login', 'lostPassword'));
        $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $pw = '';
        for ($i = 0; $i < 10; $i++) $pw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        DB::exec('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $u['id']]);
        DB::exec('DELETE FROM password_resets WHERE user_id=?', [$u['id']]);
        DB::exec('DELETE FROM sessions_remember WHERE user_id=?', [$u['id']]);   // sign out remembered devices
        EmailTemplateService::send('PASSWORD_RESET', $u['email'], ['recipientName' => trim($u['given_name'] . ' ' . $u['family_name']), 'username' => $u['username'], 'password' => $pw]);
        View::render('public/message', ['title' => t('lost.title'), 'bodyClass' => 'lost_password', 'heading' => t('lost.title'), 'message' => t('reset.done'), 'backUrl' => url('login'), 'backLabel' => t('nav.login')]);
    }
}
