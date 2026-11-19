<?php
namespace App\Core;
class Lang {
    private static ?array $s = null;
    public static function get(string $key, ...$args): string {
        if (self::$s === null) { self::$s = []; foreach (glob(APP . '/lang/en*.php') as $f) self::$s = array_merge(self::$s, require $f); }
        $v = self::$s[$key] ?? $key;
        return $args ? vsprintf($v, $args) : $v;
    }
}
