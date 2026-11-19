<?php
namespace App\Core;
class Log {
    public static function write(string $msg): void {
        $dir = (config('files_dir') ?? ROOT . '/journal_files') . '/logs';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        @file_put_contents($dir . '/app.log', '[' . date('c') . '] ' . $msg . "\n", FILE_APPEND);
    }
}
