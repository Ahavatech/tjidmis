<?php
namespace App\Core;
/** Validates by extension AND real MIME, renames to a random name, stores outside the web root. */
class Upload {
    const ALLOWED = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'odt' => ['application/vnd.oasis.opendocument.text', 'application/zip'],
        'rtf' => ['application/rtf', 'text/rtf'],
        'txt' => ['text/plain'],
        'xls' => ['application/vnd.ms-excel'], 'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'png' => ['image/png'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'gif' => ['image/gif'],
        'zip' => ['application/zip'],
    ];
    public static function baseDir(): string { return rtrim(config('files_dir'), '/\\'); }
    /** @return array{stored_name:string,original_name:string,mime:string,size:int,path:string} */
    public static function store(array $file, string $subdir, array $exts = null, int $maxBytes = 20971520): array {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new \RuntimeException(t('upload.error'));
        if ($file['size'] > $maxBytes) throw new \RuntimeException(t('upload.too_big'));
        $orig = basename($file['name']);
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = $exts ? array_intersect_key(self::ALLOWED, array_flip($exts)) : self::ALLOWED;
        if (!isset($allowed[$ext])) throw new \RuntimeException(t('upload.bad_type'));
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, $allowed[$ext], true)) throw new \RuntimeException(t('upload.bad_type'));
        if (strpos($mime, 'image/') === 0 && preg_match('/<\?(php|=)|<script|<%/i', (string)file_get_contents($file['tmp_name']))) throw new \RuntimeException(t('upload.bad_type'));   // image polyglots
        $dir = self::baseDir() . '/' . trim($subdir, '/');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) throw new \RuntimeException(t('upload.error'));
        return ['stored_name' => $stored, 'original_name' => $orig, 'mime' => $mime, 'size' => (int)$file['size'], 'path' => trim($subdir, '/') . '/' . $stored];
    }

    /** Public images (profile photos, logos): validated by extension + real MIME, random name, saved under /public/. */
    public static function storePublic(array $file, string $subdir, int $maxBytes = 2097152): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new \RuntimeException(t('upload.error'));
        if ($file['size'] > $maxBytes) throw new \RuntimeException(t('upload.too_big'));
        $map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset($map[$ext]) || (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) !== $map[$ext] || !@getimagesize($file['tmp_name'])) throw new \RuntimeException(t('upload.bad_type'));
        $raw = (string)file_get_contents($file['tmp_name']);
        if (preg_match('/<\?(php|=)|<script|<%/i', $raw)) throw new \RuntimeException(t('upload.bad_type'));   // polyglot images carrying code
        $dir = ROOT . '/public/' . trim($subdir, '/');
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) throw new \RuntimeException(t('upload.error'));
        $name = bin2hex(random_bytes(16)) . '.' . $ext; $dest = $dir . '/' . $name;
        if (extension_loaded('gd') && ($im = @imagecreatefromstring($raw)) !== false) {   // re-encode: drops metadata and any appended payload
            imagealphablending($im, false); imagesavealpha($im, true);
            $ok = match ($map[$ext]) { 'image/png' => imagepng($im, $dest), 'image/gif' => imagegif($im, $dest), default => imagejpeg($im, $dest, 90) };
            imagedestroy($im); if (!$ok) throw new \RuntimeException(t('upload.error'));
        } elseif (!move_uploaded_file($file['tmp_name'], $dest)) throw new \RuntimeException(t('upload.error'));
        return trim($subdir, '/') . '/' . $name;
    }
}
