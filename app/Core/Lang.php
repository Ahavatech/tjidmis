<?php
namespace App\Core;
class Lang {
    private static ?array $s = null;
    public static function get(string $key, ...$args): string {
        self::$s ??= require APP . '/lang/en.php';
        $v = self::$s[$key] ?? $key;
        return $args ? vsprintf($v, $args) : $v;
    }
}
