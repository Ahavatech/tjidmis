<?php
namespace App\Controllers\Public;
use App\Core\{DB, Settings, Upload};
/** /libraryFiles/downloadPublic/{id}: publisher library files that are marked "Public Access" (the Word template, the copyright form, ...). */
class LibraryController {
    public function downloadPublic(string $id = ''): void {
        $f = DB::one('SELECT * FROM library_files WHERE id=? AND journal_id=? AND public_access=1', [(int)$id, (int)Settings::journal()['id']]);
        if (!$f) { http_response_code(404); echo 'Not found'; return; }
        self::send($f);
    }
    public static function send(array $f): void {
        $path = Upload::baseDir() . '/library/' . (int)$f['id'] . '/' . $f['stored_name'];
        if (!is_file($path)) { http_response_code(404); echo 'Not found'; return; }
        header('Content-Type: ' . ($f['mime'] ?: 'application/octet-stream')); header('Content-Length: ' . filesize($path)); header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._ -]/', '_', $f['original_name']) . '"');
        readfile($path);
    }
}
