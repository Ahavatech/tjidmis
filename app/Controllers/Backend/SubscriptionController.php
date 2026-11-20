<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Sanitizer};
use App\Services\SubscriptionService as SS;

/** OJS 3.3 "Subscriptions" (shown when the publishing mode is Subscription): individual and institutional subscriptions, subscription types, policies. */
class SubscriptionController {
    const TABS = ['individual' => 'sub.individual', 'institutional' => 'sub.institutional', 'types' => 'sub.types', 'policies' => 'sub.policies'];
    private function jid(): int { return (int)Settings::journal()['id']; }
    private function guard(): void { Auth::require(SettingsController::ROLES); if (!SS::enabled()) WorkflowController::deny(404); }
    private function back(string $tab): never { redirect(url('subscription', 'index', [$tab])); }

    public function index(string $tab = 'individual'): void {
        $this->guard(); $jid = $this->jid(); if (!isset(self::TABS[$tab])) $tab = 'individual'; $d = ['title' => t('sub.menu'), 'menu' => 'subscriptions', 'tab' => $tab, 'tabs' => self::TABS];
        if ($tab === 'individual' || $tab === 'institutional') {
            $d['rows'] = DB::all('SELECT s.*, t.name type_name, u.given_name, u.family_name, u.username FROM subscriptions s JOIN subscription_types t ON t.id=s.type_id LEFT JOIN users u ON u.id=s.user_id WHERE s.journal_id=? AND t.institutional=? ORDER BY s.id', [$jid, $tab === 'institutional' ? 1 : 0]);
        } elseif ($tab === 'types') $d['rows'] = SS::types($jid);
        else $d['values'] = ['subscriptionExpiryPartial' => Settings::get('subscriptionExpiryPartial', '0'), 'subscriptionContactName' => Settings::get('subscriptionContactName', ''), 'subscriptionContactEmail' => Settings::get('subscriptionContactEmail', ''), 'subscriptionAdditionalInformation' => Settings::get('subscriptionAdditionalInformation', '')];
        View::render('backend/subscriptions', $d, 'backend');
    }
    public function savePolicies(): void {
        $this->guard();
        if (Request::isPost()) {
            $j = $this->jid(); Settings::set('subscriptionExpiryPartial', empty($_POST['subscriptionExpiryPartial']) ? '0' : '1', $j);
            Settings::set('subscriptionContactName', mb_substr(trim((string)($_POST['subscriptionContactName'] ?? '')), 0, 255), $j); Settings::set('subscriptionContactEmail', mb_substr(trim((string)($_POST['subscriptionContactEmail'] ?? '')), 0, 255), $j);
            Settings::set('subscriptionAdditionalInformation', Sanitizer::clean((string)($_POST['subscriptionAdditionalInformation'] ?? '')), $j); flash(t('profile.saved'));
        }
        $this->back('policies');
    }
    // ------------------------------------------------------------------ subscription types
    public function type(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $ty = $id !== '' ? DB::one('SELECT * FROM subscription_types WHERE id=? AND journal_id=?', [(int)$id, $jid]) : null; $errors = [];
        if ($id !== '' && !$ty) WorkflowController::deny(404);
        if (Request::isPost()) {
            $p = $_POST; $name = trim((string)($p['name'] ?? '')); $cost = str_replace(',', '.', trim((string)($p['cost'] ?? ''))); $dur = trim((string)($p['duration'] ?? '')); $cur = strtoupper(trim((string)($p['currency'] ?? 'USD')));
            if ($name === '') $errors['name'] = t('sub.err_type_name');
            if ($cost === '' || !is_numeric($cost) || (float)$cost < 0) $errors['cost'] = t('sub.err_cost');
            if ($dur !== '' && !ctype_digit($dur)) $errors['duration'] = t('sub.err_duration');
            if (!preg_match('/^[A-Z]{3}$/', $cur)) $errors['currency'] = t('sub.err_currency');
            if (!isset(SS::FORMATS[(int)($p['format'] ?? 0)])) $errors['format'] = t('sub.err_format');
            if (!$errors) {
                $row = ['name' => mb_substr($name, 0, 255), 'description' => Sanitizer::clean((string)($p['description'] ?? '')) ?: null, 'cost' => round((float)$cost, 2), 'currency' => $cur, 'duration' => $dur === '' ? null : (int)$dur, 'format' => (int)$p['format'],
                    'institutional' => empty($p['institutional']) ? 0 : 1, 'membership' => empty($p['membership']) ? 0 : 1, 'disable_public_display' => empty($p['disable_public_display']) ? 0 : 1];
                if ($ty) DB::exec('UPDATE subscription_types SET ' . implode(',', array_map(fn($k) => "`$k`=?", array_keys($row))) . ' WHERE id=?', array_merge(array_values($row), [$ty['id']]));
                else DB::insert('subscription_types', $row + ['journal_id' => $jid, 'seq' => 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM subscription_types WHERE journal_id=?', [$jid])]);
                flash(t('profile.saved')); $this->back('types');
            }
            $ty = array_merge($ty ?: [], $p);
        }
        View::render('backend/subscription_type_form', ['title' => t(!empty($ty['id']) ? 'sub.type_edit' : 'sub.type_create'), 'menu' => 'subscriptions', 'tab' => 'types', 'tabs' => self::TABS, 'ty' => $ty ?: ['format' => 1, 'currency' => 'USD'], 'errors' => $errors], 'backend');
    }
    public function typeDelete(string $id = ''): void {
        $this->guard(); if (Request::isPost()) { DB::exec('DELETE FROM subscription_types WHERE id=? AND journal_id=?', [(int)$id, $this->jid()]); flash(t('set.deleted')); } $this->back('types');
    }
    // ------------------------------------------------------------------ subscriptions
    public function edit(string $kind = 'individual', string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $inst = $kind === 'institutional'; $kind = $inst ? 'institutional' : 'individual'; $errors = [];
        $sub = $id !== '' ? DB::one('SELECT s.* FROM subscriptions s JOIN subscription_types t ON t.id=s.type_id WHERE s.id=? AND s.journal_id=? AND t.institutional=?', [(int)$id, $jid, $inst ? 1 : 0]) : null;
        if ($id !== '' && !$sub) WorkflowController::deny(404);
        $types = SS::types($jid, $inst);
        if (Request::isPost()) {
            $p = $_POST; $typeId = (int)($p['type_id'] ?? 0); $ty = DB::one('SELECT * FROM subscription_types WHERE id=? AND journal_id=? AND institutional=?', [$typeId, $jid, $inst ? 1 : 0]);
            if (!$ty) $errors['type_id'] = t('sub.err_type');
            $uid = null;
            if (!$inst) { $u = DB::one('SELECT id FROM users WHERE username=?', [trim((string)($p['username'] ?? ''))]); if (!$u) $errors['username'] = t('sub.err_user'); else $uid = (int)$u['id']; }
            else {
                if (trim((string)($p['institution_name'] ?? '')) === '') $errors['institution_name'] = t('sub.err_institution');
                if (trim((string)($p['domain'] ?? '')) === '' && trim((string)($p['ip_ranges'] ?? '')) === '') $errors['domain'] = t('sub.err_domain_or_ip');
            }
            $ds = trim((string)($p['date_start'] ?? '')); $de = trim((string)($p['date_end'] ?? ''));
            foreach (['date_start' => $ds, 'date_end' => $de] as $k => $dv) if ($dv !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dv)) $errors[$k] = t('rev.err_date');
            if ($ds === '') $errors['date_start'] = t('sub.err_start');
            if ($ty && $ty['duration'] !== null && $de === '' && !$errors) $de = date('Y-m-d', strtotime("$ds +" . (int)$ty['duration'] . ' months'));
            if (!$errors && $de !== '' && $de < $ds) $errors['date_end'] = t('sub.err_end');
            if (!isset(SS::STATUSES[(int)($p['status'] ?? 0)])) $errors['status'] = t('sub.err_status');
            if (!$errors) {
                $row = ['type_id' => $typeId, 'user_id' => $uid, 'institution_name' => $inst ? mb_substr(trim($p['institution_name']), 0, 255) : null, 'institution_mailing' => $inst ? (trim((string)($p['institution_mailing'] ?? '')) ?: null) : null,
                    'domain' => $inst ? (trim((string)($p['domain'] ?? '')) ?: null) : null, 'ip_ranges' => $inst ? (trim((string)($p['ip_ranges'] ?? '')) ?: null) : null, 'status' => (int)$p['status'], 'date_start' => $ds, 'date_end' => $de ?: null,
                    'membership' => mb_substr(trim((string)($p['membership'] ?? '')), 0, 40) ?: null, 'reference_number' => mb_substr(trim((string)($p['reference_number'] ?? '')), 0, 40) ?: null, 'notes' => trim((string)($p['notes'] ?? '')) ?: null];
                if ($sub) DB::exec('UPDATE subscriptions SET ' . implode(',', array_map(fn($k) => "`$k`=?", array_keys($row))) . ' WHERE id=?', array_merge(array_values($row), [$sub['id']]));
                else DB::insert('subscriptions', $row + ['journal_id' => $jid]);
                flash(t('profile.saved')); $this->back($kind);
            }
            $sub = array_merge($sub ?: [], $p);
        }
        $uname = ''; if (!empty($sub['user_id'])) $uname = (string)DB::val('SELECT username FROM users WHERE id=?', [$sub['user_id']]); if (isset($sub['username'])) $uname = (string)$sub['username'];
        View::render('backend/subscription_form', ['title' => t(!empty($sub['id']) ? 'sub.edit' : 'sub.create'), 'menu' => 'subscriptions', 'tab' => $kind, 'tabs' => self::TABS, 'inst' => $inst, 'sub' => $sub ?: ['status' => 1, 'date_start' => date('Y-m-d')], 'types' => $types, 'uname' => $uname, 'errors' => $errors], 'backend');
    }
    public function delete(string $id = ''): void {
        $this->guard(); $kind = 'individual';
        if (Request::isPost()) { $s = DB::one('SELECT s.id, t.institutional FROM subscriptions s JOIN subscription_types t ON t.id=s.type_id WHERE s.id=? AND s.journal_id=?', [(int)$id, $this->jid()]);
            if ($s) { $kind = $s['institutional'] ? 'institutional' : 'individual'; DB::exec('DELETE FROM subscriptions WHERE id=?', [$s['id']]); flash(t('set.deleted')); } }
        $this->back($kind);
    }
}
