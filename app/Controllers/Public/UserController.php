<?php
namespace App\Controllers\Public;
use App\Controllers\Backend\ProfileController;
use App\Core\{View, Auth, DB, Settings, Request, Countries};
use App\Services\{EmailTemplateService, NotificationService};
class UserController {
    public function register(): void {
        if (Auth::user()) { View::render('public/registration_complete', ['title' => t('reg.complete'), 'bodyClass' => 'register']); return; }   // OJS shows the success page to anyone logged in
        if (Settings::get('disableUserReg', '0') === '1') { View::render('public/page_simple', ['title' => t('nav.register'), 'crumb' => t('nav.register'), 'html' => '<p>' . e(t('reg.disabled')) . '</p>', 'cls' => 'page_register']); return; }
        $errors = []; $v = $_POST;
        if (Request::isPost()) {
            $given = trim($v['givenName'] ?? ''); $aff = trim($v['affiliation'] ?? ''); $country = (string)($v['country'] ?? '');
            $email = strtolower(trim($v['email'] ?? '')); $uname = trim($v['username'] ?? ''); $pw = (string)($v['password'] ?? '');
            $min = (int)Settings::site('minPasswordLength', '8');
            // Same checks, same order and same messages as OJS 3.3's RegistrationForm; the first failing check of a field is the one shown.
            $add = function (string $f, string $m) use (&$errors) { if (!isset($errors[$f])) $errors[$f] = $m; };
            $nameTaken = $uname !== '' && DB::val('SELECT id FROM users WHERE username=?', [$uname]);
            if ($nameTaken) $add('username', t('reg.err_username_exists'));
            if ($uname === '') $add('username', t('reg.err_username_required'));
            if ($pw === '') $add('password', t('reg.err_password_required'));
            if (!preg_match('/^[a-z0-9]+([\-_][a-z0-9]+)*$/', $uname)) $add('username', t('reg.err_username_format'));
            if (strlen($pw) < $min) $add('password', t('form.password_short', $min));
            if ($pw !== (string)($v['password2'] ?? '')) $add('password', t('form.password_mismatch'));
            if ($given === '') $add('givenName', t('reg.err_given_required'));
            if (!isset(Countries::all()[$country])) $add('country', t('reg.err_country_required'));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $add('email', t('reg.err_email_required'));
            elseif (DB::val('SELECT id FROM users WHERE email=?', [$email])) $add('email', t('reg.err_email_exists'));
            if (empty($v['privacyConsent']) && trim(strip_tags((string)Settings::get('privacyStatement', ''))) !== '') $add('privacyConsent', t('reg.err_consent_required'));
            if (!$errors) {
                $jid = (int)Settings::journal()['id'];
                $roles = ['reader', 'author']; if (!empty($v['reviewerGroup'])) $roles[] = 'reviewer';
                $uid = DB::insert('users', ['username' => $uname, 'email' => $email, 'password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'given_name' => $given,
                    'family_name' => trim($v['familyName'] ?? ''), 'affiliation' => $aff, 'country' => $country, 'created_at' => date('Y-m-d H:i:s'),
                    'reviewing_interests' => !empty($v['reviewerGroup']) ? trim($v['interests'] ?? '') : null]);
                foreach ($roles as $rk) {
                    $g = DB::val('SELECT id FROM user_groups WHERE journal_id=? AND role_key=? AND permit_self_registration=1', [$jid, $rk]);
                    if ($g) DB::exec('INSERT IGNORE INTO user_user_groups (user_id,user_group_id) VALUES (?,?)', [$uid, $g]);
                }
                DB::exec('INSERT INTO user_settings (user_id,journal_id,name,value) VALUES (?,?,?,?),(?,?,?,?)',
                    [$uid, $jid, 'privacyConsent', date('c'), $uid, $jid, 'emailConsent', !empty($v['emailConsent']) ? '1' : '0']);
                NotificationService::add($uid, $jid, 'welcome', t('reg.welcome_note', Settings::get('name')));
                EmailTemplateService::send('USER_REGISTER', $email, ['recipientName' => trim($given . ' ' . ($v['familyName'] ?? '')), 'username' => $uname]);
                Auth::loginId($uid);
                redirect(url('user', 'register'));   // like OJS: the success page is shown at /user/register
            }
        }
        View::render('public/register', ['title' => t('nav.register'), 'errors' => $errors, 'v' => $v,
            'reviewerGroupId' => (int)DB::val("SELECT id FROM user_groups WHERE journal_id=? AND role_key='reviewer'", [(int)Settings::journal()['id']])]);
    }
    public function registrationComplete(): void {
        Auth::requireLogin();
        View::render('public/registration_complete', ['title' => t('reg.complete'), 'bodyClass' => 'register']);
    }
    public function profile(string $tab = 'identity'): void { (new ProfileController())->index($tab); }
    /** POST /user/loginAs/{id} (managers and site admins only; see Auth::loginAs) */
    public function loginAs(string $id = ''): void {
        Auth::requireLogin();
        if (!Request::isPost() || !Auth::loginAs((int)$id)) { http_response_code(403); View::render('public/error', ['code' => 403, 'message' => t('error.403'), 'title' => '403']); return; }
        redirect(url('submissions'));
    }
}
