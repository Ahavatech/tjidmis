<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Upload, Sanitizer};

/** Settings > Journal / Website / Workflow / Distribution. Plain forms are driven by SCHEMA; special tabs have their own screens. */
class SettingsController {
    const ROLES = ['manager', 'site_admin'];
    /** area => tab => list of [key, type, options]. Types: text, email, number, textarea, html, checkbox, select, color, image, css, lines. */
    const SCHEMA = [
        'journal' => [
            'masthead' => [['name', 'text', ['required' => 1]], ['initials', 'text'], ['abbreviation', 'text'], ['publisher', 'text'], ['eissn', 'text'], ['issn', 'text'], ['description', 'html'], ['editorialTeam', 'html'], ['about', 'html']],
            'contact' => [['contactName', 'text'], ['contactEmail', 'email'], ['contactPhone', 'text'], ['contactAffiliation', 'text'], ['contactMailingAddress', 'textarea'], ['supportName', 'text'], ['supportEmail', 'email'], ['supportPhone', 'text']],
            'sections' => [],
        ],
        'website' => [
            'appearance' => [['themeColor', 'color'], ['logo', 'image'], ['favicon', 'image'], ['homepageImage', 'image'], ['additionalHomeContent', 'html'], ['pageFooter', 'html'], ['journalStyleSheet', 'css']],
            'information' => [['readerInfo', 'html'], ['authorInfo', 'html'], ['librarianInfo', 'html']],
            'navigation' => [], 'staticPages' => [], 'blocks' => [], 'plugins' => [],
            'announcements' => [['announcementsEnabled', 'checkbox'], ['announcementsIntro', 'html'], ['numAnnouncementsHomepage', 'number', ['min' => 0, 'max' => 10]]],
            'lists' => [['itemsPerPage', 'number', ['min' => 5, 'max' => 100]]],
            'privacy' => [['privacyStatement', 'html']],
            'datetime' => [['dateFormat', 'select', ['options' => ['Y-m-d' => '2026-01-15', 'd/m/Y' => '15/01/2026', 'm/d/Y' => '01/15/2026', 'j F Y' => '15 January 2026', 'F j, Y' => 'January 15, 2026']]]],
        ],
        'workflow' => [
            'submission' => [['submissionsDisabled', 'checkbox'], ['authorGuidelines', 'html'], ['submissionChecklist', 'lines'], ['metaKeywords', 'checkbox'], ['metaReferences', 'checkbox'], ['components', 'lines']],
            'review' => [['defaultReviewMode', 'select', ['options' => ['anonymous' => 'review.method_anonymous', 'double_anonymous' => 'review.method_double', 'open' => 'review.method_open'], 'translate' => 1]],
                ['numWeeksPerResponse', 'number', ['min' => 1, 'max' => 26]], ['numWeeksPerReview', 'number', ['min' => 1, 'max' => 52]], ['numDaysBeforeInviteReminder', 'number', ['min' => 0, 'max' => 90]], ['numDaysBeforeSubmitReminder', 'number', ['min' => 0, 'max' => 90]], ['reviewGuidelines', 'html'], ['competingInterests', 'html'], ['reviewerUploads', 'checkbox']],
            'emails' => [['emailSignature', 'textarea'], ['mailFrom', 'email']],
        ],
        'distribution' => [
            'license' => [['copyrightHolderType', 'select', ['options' => ['author' => 'set.holder_author', 'journal' => 'set.holder_journal', 'other' => 'set.holder_other'], 'translate' => 1]], ['copyrightHolderOther', 'text'],
                ['licenseUrl', 'text'], ['copyrightYearBasis', 'select', ['options' => ['issue' => 'set.year_issue', 'submission' => 'set.year_submission'], 'translate' => 1]], ['copyrightNotice', 'html'], ['licenseTerms', 'html']],
            'indexing' => [['searchDescription', 'textarea'], ['customHeaders', 'textarea']],
            'doi' => [['doiPrefix', 'text'], ['doiPattern', 'text'], ['doiAutoAssign', 'checkbox']],
            'payments' => [['apcEnabled', 'checkbox'], ['apcFee', 'number', ['min' => 0, 'max' => 1000000]], ['apcCurrency', 'text'], ['apcInstructions', 'html']], 'access' => [],
        ],
    ];
    const DEFAULT_ON = ['announcementsEnabled', 'metaKeywords', 'metaReferences', 'reviewerUploads'];
    const FEATURES = ['staticPages', 'customBlocks', 'webFeeds', 'googleScholar', 'usageStats', 'orcid'];
    private function jid(): int { return (int)Settings::journal()['id']; }
    private function guard(): void { Auth::require(self::ROLES); }
    public static function tabsFor(string $area): array { return array_keys(self::SCHEMA[$area] ?? []); }

    public function index(string $area = 'journal', string $tab = ''): void {
        $this->guard();
        if (!isset(self::SCHEMA[$area])) $area = 'journal';
        $tab = isset(self::SCHEMA[$area][$tab]) ? $tab : array_key_first(self::SCHEMA[$area]);
        $data = ['title' => t('menu.settings'), 'menu' => 'settings', 'area' => $area, 'tab' => $tab, 'fields' => self::SCHEMA[$area][$tab], 'values' => $this->values(self::SCHEMA[$area][$tab]), 'errors' => $_SESSION['set_errors'] ?? []];
        unset($_SESSION['set_errors']);
        $jid = $this->jid();
        switch ("$area/$tab") {
            case 'journal/sections': $data['sections'] = DB::all('SELECT s.*, (SELECT COUNT(*) FROM submissions x WHERE x.section_id=s.id) used FROM sections s WHERE s.journal_id=? ORDER BY s.seq,s.id', [$jid]); break;
            case 'website/appearance': $data += $this->sidebarData($jid); break;
            case 'website/navigation': $data['items'] = $this->navTree($jid); $data['pages'] = DB::all('SELECT id,title FROM static_pages WHERE journal_id=? ORDER BY title', [$jid]); break;
            case 'website/staticPages': $data['pages'] = DB::all('SELECT * FROM static_pages WHERE journal_id=? ORDER BY title', [$jid]); break;
            case 'website/blocks': $data['blocks'] = DB::all('SELECT * FROM custom_blocks WHERE journal_id=? ORDER BY title', [$jid]); break;
            case 'website/plugins': $data['features'] = array_merge(array_fill_keys(self::FEATURES, true), Settings::json('enabledFeatures', [])); break;
            case 'workflow/emails': $data['templates'] = DB::all('SELECT id,email_key,subject,enabled,is_custom FROM email_templates WHERE journal_id=? ORDER BY email_key', [$jid]); break;
        }
        if (in_array($tab, ['submission'], true) && $area === 'workflow') { $data['values']['submissionChecklist'] = implode("\n", Settings::json('submissionChecklist', [])); $data['values']['components'] = implode("\n", Settings::json('components', \App\Controllers\Backend\SubmissionWizardController::COMPONENTS)); }
        View::render('backend/settings', $data, 'backend');
    }
    private function values(array $fields): array {
        $v = [];
        foreach ($fields as [$k, $type]) {
            $val = Settings::get($k, in_array($k, self::DEFAULT_ON, true) ? '1' : ($k === 'dateFormat' ? 'Y-m-d' : ''));
            $v[$k] = $val;
        }
        return $v;
    }

    /** POST /settings/save/{area}/{tab} */
    public function save(string $area = '', string $tab = ''): void {
        $this->guard(); $fields = self::SCHEMA[$area][$tab] ?? null;
        if ($fields === null || !Request::isPost()) redirect(url('settings', 'index', [$area ?: 'journal', $tab]));
        if ($area === 'website' && $tab === 'plugins') { $this->savePlugins(); return; }
        $jid = $this->jid(); $errors = []; $write = [];
        foreach ($fields as $f) {
            [$k, $type] = $f; $o = $f[2] ?? []; $raw = $_POST[$k] ?? '';
            switch ($type) {
                case 'text': $v = mb_substr(trim((string)$raw), 0, 255); if (!empty($o['required']) && $v === '') $errors[$k] = t('form.required_field'); $write[$k] = $v; break;
                case 'email': $v = trim((string)$raw); if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) $errors[$k] = t('form.email_invalid'); $write[$k] = $v; break;
                case 'number': $n = (int)$raw; if (!is_numeric($raw) || $n < ($o['min'] ?? 0) || $n > ($o['max'] ?? 9999)) $errors[$k] = t('set.err_number', $o['min'] ?? 0, $o['max'] ?? 9999); $write[$k] = (string)$n; break;
                case 'textarea': $write[$k] = trim((string)$raw); break;
                case 'html': $write[$k] = Sanitizer::clean((string)$raw); break;
                case 'checkbox': $write[$k] = empty($_POST[$k]) ? '0' : '1'; break;
                case 'select': if (!array_key_exists((string)$raw, $o['options'])) $errors[$k] = t('set.err_choice'); $write[$k] = (string)$raw; break;
                case 'color': $c = strtolower(trim((string)$raw)); if (!preg_match('/^#[0-9a-f]{6}$/', $c)) $errors[$k] = t('set.err_color'); $write[$k] = $c; break;
                case 'lines':
                    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$raw)), fn($x) => $x !== ''));
                    if ($k === 'submissionChecklist' || $k === 'components') $write[$k] = json_encode($lines, JSON_UNESCAPED_UNICODE);
                    if ($k === 'components' && !$lines) $errors[$k] = t('set.err_components'); break;
                case 'image':
                    if (!empty($_POST[$k . '_remove'])) { $this->dropFile(Settings::get($k, '')); $write[$k] = ''; if ($k === 'logo') { $write['logoWidth'] = ''; $write['logoHeight'] = ''; } break; }
                    if (!empty($_FILES[$k]['name'])) {
                        try { $path = Upload::storePublic($_FILES[$k], "journals/$jid", 5242880); $this->dropFile(Settings::get($k, ''));
                            $write[$k] = $path; if ($k === 'logo' && ($sz = @getimagesize(ROOT . '/public/' . $path))) { $write['logoWidth'] = (string)$sz[0]; $write['logoHeight'] = (string)$sz[1]; } }
                        catch (\RuntimeException $x) { $errors[$k] = $x->getMessage(); }
                    } break;
                case 'css':
                    if (!empty($_POST[$k . '_remove'])) { $this->dropFile(Settings::get($k, '')); $write[$k] = ''; break; }
                    if (!empty($_FILES[$k]['name'])) {
                        $f2 = $_FILES[$k]; $css = ($f2['error'] === UPLOAD_ERR_OK && $f2['size'] < 262144 && strtolower(pathinfo($f2['name'], PATHINFO_EXTENSION)) === 'css') ? file_get_contents($f2['tmp_name']) : false;
                        if ($css === false || !mb_check_encoding($css, 'UTF-8')) { $errors[$k] = t('set.err_css'); break; }
                        $css = preg_replace(['#@import[^;]*;#i', '#expression\s*\(#i', '#javascript:#i', '#</?style#i'], '', $css);
                        $dir = ROOT . '/public/journals/' . $jid; if (!is_dir($dir)) @mkdir($dir, 0775, true);
                        if (file_put_contents($dir . '/styleSheet.css', $css) === false) { $errors[$k] = t('upload.error'); break; }
                        $write[$k] = "journals/$jid/styleSheet.css";
                    } break;
            }
        }
        if ($area === 'workflow' && $tab === 'emails' && ($write['mailFrom'] ?? '') === '') $errors['mailFrom'] = t('form.email_invalid');
        if ($area === 'distribution' && $tab === 'license' && isset($write['licenseUrl']) && $write['licenseUrl'] !== '' && !preg_match('#^https?://#i', $write['licenseUrl'])) $errors['licenseUrl'] = t('profile.url_invalid');
        if ($tab === 'indexing' && isset($write['customHeaders'])) $write['customHeaders'] = implode("\n", array_filter(preg_split('/\R/', $write['customHeaders']), fn($l) => preg_match('/^\s*<meta\s[^<>]*>\s*$/i', $l) && !preg_match('/http-equiv|javascript:|on\w+\s*=/i', $l)));
        if ($errors) { $_SESSION['set_errors'] = $errors; flash(t('set.fix'), 'error'); }
        else { foreach ($write as $k => $v) Settings::set($k, $v, $jid); flash(t('profile.saved')); }
        redirect(url('settings', 'index', [$area, $tab]));
    }
    private function dropFile(?string $rel): void { if ($rel && preg_match('#^journals/\d+/#', $rel) && is_file(ROOT . '/public/' . $rel)) @unlink(ROOT . '/public/' . $rel); }
    private function savePlugins(): void {
        $f = []; foreach (self::FEATURES as $k) $f[$k] = !empty($_POST[$k]);
        Settings::set('enabledFeatures', json_encode($f), $this->jid()); flash(t('profile.saved')); redirect(url('settings', 'index', ['website', 'plugins']));
    }

    // ---------------------------------------------------------------- sidebar manager
    public static function allBlockKeys(int $jid): array {
        $keys = ['information' => t('sidebar.information'), 'web_feed' => t('sidebar.current_issue') . ' (' . t('set.web_feed') . ')', 'make_submission' => t('subm.make_block')];
        foreach (DB::all('SELECT name,title FROM custom_blocks WHERE journal_id=? ORDER BY title', [$jid]) as $b) $keys['custom:' . $b['name']] = $b['title'];
        return $keys;
    }
    private function sidebarData(int $jid): array {
        $all = self::allBlockKeys($jid); $on = array_values(array_filter(Settings::json('sidebarBlocks', []), fn($k) => isset($all[$k])));
        return ['sidebarOn' => array_map(fn($k) => [$k, $all[$k]], $on), 'sidebarOff' => array_map(fn($k) => [$k, $all[$k]], array_values(array_diff(array_keys($all), $on)))];
    }
    /** POST /settings/sidebar/{op}/{key}: add, remove, up, down */
    public function sidebar(string $op = '', string $key = ''): void {
        $this->guard(); $jid = $this->jid();
        if (Request::isPost()) {
            $key = rawurldecode($key); $all = self::allBlockKeys($jid); $list = array_values(array_filter(Settings::json('sidebarBlocks', []), fn($k) => isset($all[$k])));
            $i = array_search($key, $list, true);
            if ($op === 'add' && isset($all[$key]) && $i === false) $list[] = $key;
            elseif ($op === 'remove' && $i !== false) array_splice($list, $i, 1);
            elseif (in_array($op, ['up', 'down'], true) && $i !== false) { $j = $op === 'up' ? $i - 1 : $i + 1; if ($j >= 0 && $j < count($list)) [$list[$i], $list[$j]] = [$list[$j], $list[$i]]; }
            Settings::set('sidebarBlocks', json_encode(array_values($list)), $jid);
        }
        redirect(url('settings', 'index', ['website', 'appearance']) . '#sidebar');
    }
    // ---------------------------------------------------------------- navigation helpers
    private function navTree(int $jid): array {
        $rows = DB::all("SELECT n.*, sp.title sp_title FROM navigation_menu_items n LEFT JOIN static_pages sp ON sp.id=n.static_page_id WHERE n.journal_id=? AND n.menu='primary' ORDER BY n.seq,n.id", [$jid]);
        $top = []; $kids = []; foreach ($rows as $r) { if ($r['parent_id']) $kids[$r['parent_id']][] = $r; else $top[] = $r; }
        foreach ($top as &$t) $t['children'] = $kids[$t['id']] ?? []; return $top;
    }
}
