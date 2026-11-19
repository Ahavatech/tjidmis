<?php
namespace App\Controllers\Public;
/** /libraryFiles/downloadPublic/{id}: public template files (Word template, copyright form) stored in public/library/{id}/. */
class LibraryController {
    public function downloadPublic(string $id = ''): void {
        $dir = ROOT . '/public/library/' . (int)$id;
        $files = is_dir($dir) ? array_values(array_filter(scandir($dir), fn($f) => $f[0] !== '.')) : [];
        if (!$files) { http_response_code(404); echo 'Not found'; return; }
        $path = $dir . '/' . $files[0];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime); header('Content-Length: ' . filesize($path)); header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._ -]/', '_', $files[0]) . '"');
        readfile($path);
    }
}
