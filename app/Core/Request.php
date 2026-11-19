<?php
namespace App\Core;
class Request {
    private static array $seg = [];
    public static function parse(): void {
        $p = $_SERVER['PATH_INFO'] ?? '';
        if ($p === '' && isset($_GET['route'])) $p = '/' . $_GET['route'];
        self::$seg = array_values(array_filter(explode('/', trim($p, '/')), fn($s) => $s !== ''));
        self::$seg = array_map('rawurldecode', self::$seg);
    }
    public static function segments(): array { return self::$seg; }
    public static function journalPath(): string { return self::$seg[0] ?? config('default_journal', 'jidmis'); }
    public static function method(): string { return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); }
    public static function isPost(): bool { return self::method() === 'POST'; }
    public static function input(string $k, $d = null) { return $_POST[$k] ?? $_GET[$k] ?? $d; }
}
