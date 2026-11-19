<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Countries, Upload};
class ProfileController {
    const TABS = ['identity', 'contact', 'roles', 'public', 'password', 'notifications', 'apiKey'];
    const EVENTS = ['newPublications', 'submissionActivity', 'reviewRequests', 'discussions'];
    public function index(string $tab = 'identity'): void {
        Auth::requireLogin();
        if (!in_array($tab, self::TABS, true)) $tab = 'identity';
        $u = Auth::user(); $errors = [];
        if (Request::isPost()) {
            $errors = $this->save($tab, $u);
            if (!$errors) { flash(t('profile.saved')); redirect(url('user', 'profile', [$tab])); }
            $u = array_merge($u, array_intersect_key($_POST, $u));   // keep typed values when validation fails
        }
        $jid = (int)Settings::journal()['id'];
        $prefs = []; foreach (DB::all('SELECT name,value FROM user_settings WHERE user_id=? AND journal_id=?', [$u['id'], $jid]) as $r) $prefs[$r['name']] = $r['value'];
        View::render('backend/profile', ['title' => t('profile.title'), 'menu' => '', 'tab' => $tab, 'u' => $u, 'errors' => $errors, 'prefs' => $prefs,
            'roles' => Auth::roles(), 'isReviewer' => in_array('reviewer', Auth::roles(), true)], 'backend');
    }
    private function save(string $tab, array $u): array {
        $e = []; $p = $_POST; $id = (int)$u['id']; $jid = (int)Settings::journal()['id'];
        switch ($tab) {
            case 'identity':
                if (trim($p['given_name'] ?? '') === '') $e['given_name'] = t('form.required_field');
                if (trim($p['affiliation'] ?? '') === '') $e['affiliation'] = t('form.required_field');
                if (!isset(Countries::all()[$p['country'] ?? ''])) $e['country'] = t('form.required_field');
                if (!$e) DB::exec('UPDATE users SET given_name=?, family_name=?, affiliation=?, country=? WHERE id=?', [trim($p['given_name']), trim($p['family_name'] ?? ''), trim($p['affiliation']), $p['country'], $id]);
                break;
            case 'contact':
                $em = strtolower(trim($p['email'] ?? ''));
                if (!filter_var($em, FILTER_VALIDATE_EMAIL)) $e['email'] = t('form.email_invalid');
                elseif (DB::val('SELECT id FROM users WHERE email=? AND id<>?', [$em, $id])) $e['email'] = t('reg.email_taken');
                if (!$e) DB::exec('UPDATE users SET email=?, phone=?, mailing_address=? WHERE id=?', [$em, trim($p['phone'] ?? '') ?: null, trim($p['mailing_address'] ?? '') ?: null, $id]);
                break;
            case 'roles':
                $g = (int)DB::val("SELECT id FROM user_groups WHERE journal_id=? AND role_key='reviewer' AND permit_self_registration=1", [$jid]);
                if ($g && !empty($p['reviewer'])) DB::exec('INSERT IGNORE INTO user_user_groups (user_id,user_group_id) VALUES (?,?)', [$id, $g]);
                elseif ($g) DB::exec('DELETE FROM user_user_groups WHERE user_id=? AND user_group_id=?', [$id, $g]);
                DB::exec('UPDATE users SET reviewing_interests=? WHERE id=?', [!empty($p['reviewer']) ? (trim($p['reviewing_interests'] ?? '') ?: null) : null, $id]);
                break;
            case 'public':
                $orcid = trim($p['orcid'] ?? '');
                if ($orcid !== '') {
                    if (preg_match('#^(?:https?://orcid\.org/)?(\d{4}-\d{4}-\d{4}-\d{3}[\dX])$#', $orcid, $m)) $orcid = 'https://orcid.org/' . $m[1]; else $e['orcid'] = t('profile.orcid_invalid');
                }
                $url = trim($p['url'] ?? ''); if ($url !== '' && !preg_match('#^https?://#i', $url)) $e['url'] = t('profile.url_invalid');
                $photo = $u['photo'];
                if (!$e && !empty($_FILES['photo']['name'])) {
                    try { $new = Upload::storePublic($_FILES['photo'], 'profiles'); if ($photo) @unlink(ROOT . '/public/' . $photo); $photo = $new; }
                    catch (\RuntimeException $x) { $e['photo'] = $x->getMessage(); }
                }
                if (!$e) DB::exec('UPDATE users SET bio=?, url=?, orcid=?, photo=? WHERE id=?', [\App\Core\Sanitizer::clean($p['bio'] ?? '') ?: null, $url ?: null, $orcid ?: null, $photo, $id]);
                break;
            case 'password':
                $min = (int)Settings::site('minPasswordLength', '8'); $new = (string)($p['password'] ?? '');
                if (!password_verify((string)($p['oldPassword'] ?? ''), $u['password_hash'])) $e['oldPassword'] = t('profile.old_wrong');
                if (strlen($new) < $min) $e['password'] = t('form.password_short', $min);
                elseif (strlen($new) > 72) $e['password'] = t('form.password_long');
                elseif ($new !== (string)($p['password2'] ?? '')) $e['password2'] = t('form.password_mismatch');
                if (!$e) { DB::exec('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $id]); DB::exec('DELETE FROM sessions_remember WHERE user_id=?', [$id]); }
                break;
            case 'notifications':
                foreach (self::EVENTS as $ev) DB::exec('INSERT INTO user_settings (user_id,journal_id,name,value) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$id, $jid, 'emailPref_' . $ev, !empty($p[$ev]) ? '1' : '0']);
                DB::exec('INSERT INTO user_settings (user_id,journal_id,name,value) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$id, $jid, 'emailConsent', !empty($p['newPublications']) ? '1' : '0']);
                break;
        }
        return $e;
    }
}
