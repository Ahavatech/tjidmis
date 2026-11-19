<?php
namespace App\Controllers\Public;
use App\Core\{View, Settings, Upload};
use App\Models\Catalog;
use App\Services\{CitationService, MetricsService};
class ArticleController {
    private function notFound(): void { http_response_code(404); View::render('public/error', ['code' => 404, 'message' => t('error.404'), 'title' => t('error.404.title')]); }
    public function view(string $id = '', string $galleyId = '', string $fileId = ''): void {
        $jid = (int)Settings::journal()['id'];
        $a = Catalog::article($jid, (int)$id);
        if (!$a) { $this->notFound(); return; }
        if ($galleyId !== '') {
            $g = Catalog::galley((int)$a['pid'], (int)$galleyId);
            if (!$g) { $this->notFound(); return; }
            if ($g['remote_url']) redirect($g['remote_url']);
            MetricsService::hit($jid, (int)$a['sid'], 'galley_download', (int)$g['id']);
            View::render('public/galley', ['a' => $a, 'g' => $g], null);
            return;
        }
        MetricsService::hit($jid, (int)$a['sid'], 'abstract_view');
        $j = ['name' => Settings::get('name'), 'issn' => Settings::get('eissn'), 'abbrev' => Settings::get('abbreviation')];
        View::render('public/article', ['a' => $a, 'title' => $a['title'], 'bodyClass' => 'article', 'j' => $j,
            'cites' => CitationService::all($a, $j), 'metrics' => Catalog::metrics((int)$a['sid']), 'headExtra' => feature('googleScholar') ? View::partial('partials/article_meta', ['a' => $a, 'j' => $j]) : '']);
    }
    /** Streams a published galley file. Permission rule: only galleys of published articles are public. */
    public function download(string $id = '', string $galleyId = '', string $fileId = ''): void {
        $jid = (int)Settings::journal()['id'];
        $a = Catalog::article($jid, (int)$id);
        $g = $a ? Catalog::galley((int)$a['pid'], (int)$galleyId) : null;
        if (!$g || empty($g['stored_name'])) { $this->notFound(); return; }
        $path = Upload::baseDir() . "/journals/$jid/submissions/{$g['submission_id']}/{$g['file_stage']}/{$g['stored_name']}";
        if (!is_file($path)) { $this->notFound(); return; }
        $inline = !empty($_GET['inline']);
        if (!$inline) MetricsService::hit($jid, (int)$a['sid'], 'galley_download', (int)$g['id']);
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $g['original_name']);
        header('Content-Type: ' . $g['mime']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    }
}
