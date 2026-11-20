<?php
namespace App\Controllers\Public;
use App\Core\{View, Settings, DB};
use App\Models\Catalog;
class AboutController {
    public function index(): void {
        View::render('public/about', ['title' => t('about.title'), 'bodyClass' => 'about', 'html' => Settings::get('about', '') . $this->archiving()]);
    }
    /** OJS shows the LOCKSS / CLOCKSS licence under "Archiving" in About the Journal once the archive is enabled. */
    private function archiving(): string {
        $h = '';
        foreach ([['enableLockss', 'lockssLicense', 'arch.default_lockss'], ['enableClockss', 'clockssLicense', 'arch.default_clockss']] as [$on, $lic, $def])
            if (Settings::get($on, '') === '1') $h .= '<p>' . (Settings::get($lic, '') ?: t($def)) . '</p>';
        $h = $h ? '<h2>' . e(t('arch.about_heading')) . '</h2>' . $h : '';
        $mode = Settings::get('publishingMode', 'open') ?: 'open'; $pre = '';
        if ($mode === 'open' && ($oa = trim((string)Settings::get('openAccessPolicy', '')))) $pre .= '<h2>' . e(t('about.oa_policy')) . '</h2>' . $oa;
        if ($mode === 'subscription') {
            if (($m = (int)Settings::get('delayedOpenAccessDuration', '0')) > 0) $pre .= '<h2>' . e(t('about.delayed_oa')) . '</h2><p>' . e(t('about.delayed_oa_text', $m)) . '</p>' . Settings::get('delayedOpenAccessPolicy', '');
            if (trim((string)Settings::get('authorSelfArchivePolicy', ''))) $pre .= '<h2>' . e(t('about.self_archive')) . '</h2>' . Settings::get('authorSelfArchivePolicy', '');
        }
        return $pre . $h;
    }
    /** OJS About > Subscriptions: contact, then the individual and institutional subscription types (only in subscription mode). */
    public function subscriptions(): void {
        if (!\App\Services\SubscriptionService::enabled()) { http_response_code(404); View::render('public/error', ['code' => 404, 'message' => t('error.404'), 'title' => t('error.404.title')]); return; }
        $jid = (int)Settings::journal()['id']; $h = '';
        if (($n = Settings::get('subscriptionContactName', '')) || ($m = Settings::get('subscriptionContactEmail', ''))) $h .= '<p>' . e($n ?: '') . ($m ?? Settings::get('subscriptionContactEmail', '') ? ' <a href="mailto:' . e(Settings::get('subscriptionContactEmail', '')) . '">' . e(Settings::get('subscriptionContactEmail', '')) . '</a>' : '') . '</p>';
        $h .= Settings::get('subscriptionAdditionalInformation', '');
        foreach ([false => ['sub.individual', 'sub.public_individual_desc'], true => ['sub.institutional', 'sub.public_institutional_desc']] as $inst => [$hd, $ds]) {
            $types = \App\Services\SubscriptionService::types($jid, (bool)$inst, true); if (!$types) continue;
            $h .= '<div class="subscriptions_' . ($inst ? 'institutional' : 'individual') . '"><h3>' . e(t($hd)) . '</h3><p>' . e(t($ds)) . '</p><table class="cmp_table"><tr><th>' . e(t('sub.col_name')) . '</th><th>' . e(t('sub.col_format')) . '</th><th>' . e(t('sub.col_duration')) . '</th><th>' . e(t('sub.col_cost')) . '</th></tr>';
            foreach ($types as $ty) $h .= '<tr><td><div class="subscription_name">' . e($ty['name']) . '</div><div class="subscription_description">' . ($ty['description'] ?? '') . '</div></td><td>' . e(t(\App\Services\SubscriptionService::FORMATS[$ty['format']])) . '</td><td>' . e(\App\Services\SubscriptionService::duration($ty['duration'] === null ? null : (int)$ty['duration'])) . '</td><td>' . e(number_format((float)$ty['cost'], 2)) . '&nbsp;(' . e($ty['currency']) . ')</td></tr>';
            $h .= '</table></div>';
        }
        View::render('public/page_simple', ['title' => t('sub.public_title'), 'bodyClass' => 'about', 'crumb' => t('sub.public_title'), 'html' => $h ?: '<p>' . e(t('sub.no_access')) . '</p>', 'cls' => 'page_subscriptions']);
    }
    public function editorialTeam(): void {
        $html = Settings::get('editorialTeam', '');
        View::render('public/page_simple', ['title' => t('about.editorial'), 'bodyClass' => 'about', 'crumb' => t('about.editorial'), 'html' => $html, 'cls' => 'page_editorial_team']);
    }
    public function privacy(): void {
        View::render('public/page_simple', ['title' => t('about.privacy'), 'bodyClass' => 'about', 'crumb' => t('about.privacy'), 'html' => Settings::get('privacyStatement', ''), 'cls' => 'page_privacy']);
    }
    public function contact(): void {
        View::render('public/contact', ['title' => t('about.contact'), 'bodyClass' => 'contact']);
    }
    public function submissions(): void {
        $jid = (int)Settings::journal()['id'];
        View::render('public/submissions', ['title' => t('nav.submissions'), 'bodyClass' => 'about',
            'checklist' => Settings::json('submissionChecklist', []),
            'sections' => DB::all('SELECT title,policy FROM sections WHERE journal_id=? AND policy IS NOT NULL AND policy<>"" ORDER BY seq', [$jid])]);
    }
    public function aboutThisPublishingSystem(): void {
        View::render('public/page_simple', ['title' => t('about.platform'), 'crumb' => t('about.platform'), 'html' => Settings::get('aboutPlatform', '<p>' . e(t('about.platform_text')) . '</p>'), 'cls' => 'page_about_publishing_system']);
    }
}
