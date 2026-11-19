<?php
namespace App\Controllers\Public;
use App\Controllers\Backend\ProfileController;
use App\Core\{View, Auth, DB, Settings, Request, Countries};
use App\Services\{EmailTemplateService, NotificationService};
class UserController {
    public function register(): void {
        if (Auth::user()) redirect(url('submissions'));
        $errors = []; $v = $_POST;
        if (Request::isPost()) {
            $given = trim($v['givenName'] ?? ''); $aff = trim($v['affiliation'] ?? ''); $country = (string)($v['country'] ?? '');
            $email = strtolower(trim($v['email'] ?? '')); $uname = strtolower(trim($v['username'] ?? '')); $pw = (string)($v['password'] ?? '');
            $min = (int)Settings::site('minPasswordLength', '8');
            if ($given === '') $errors['givenName'] = t('form.required_field');
            if ($aff === '') $errors['affiliation'] = t('form.required_field');
            if (!isset(Countries::all()[$country])) $errors['country'] = t('form.required_field');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $errors['email'] = t('form.email_invalid');
            elseif (DB::val('SELECT id FROM users WHERE email=?', [$email])) $errors['email'] = t('reg.email_taken');
            if (!preg_match('/^[a-z0-9]+([\-_][a-z0-9]+)*$/', $uname) || strlen($uname) < 3 || strlen($uname) > 32) $errors['username'] = t('reg.username_rule');
            elseif (DB::val('SELECT id FROM users WHERE username=?', [$uname])) $errors['username'] = t('reg.username_taken');
            if (strlen($pw) < $min) $errors['password'] = t('form.password_short', $min);
            elseif (strlen($pw) > 72) $errors['password'] = t('form.password_long');
            elseif ($pw !== (string)($v['password2'] ?? '')) $errors['password2'] = t('form.password_mismatch');
            if (empty($v['privacyConsent'])) $errors['privacyConsent'] = t('reg.consent_required');
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
                redirect(url('user', 'registrationComplete'));
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
