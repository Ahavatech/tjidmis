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
