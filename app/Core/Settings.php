<?php
namespace App\Core;
class Settings {
    private static array $j = [];
    private static ?array $site = null;
    public static function journal(): ?array {
        static $row = false, $path = null;
        $p = Request::journalPath();
        if ($row === false || $path !== $p) { $path = $p; $row = DB::one('SELECT * FROM journals WHERE path=? AND enabled=1', [$p]); }
        return $row;
    }
    public static function get(string $name, $default = null, ?int $journalId = null) {
        $jid = $journalId ?? (self::journal()['id'] ?? 0);
        if (!isset(self::$j[$jid])) {
            self::$j[$jid] = [];
            foreach (DB::all('SELECT name,value FROM journal_settings WHERE journal_id=?', [$jid]) as $r) self::$j[$jid][$r['name']] = $r['value'];
        }
        return self::$j[$jid][$name] ?? $default;
    }
    public static function json(string $name, $default = []) { $v = self::get($name); return $v ? (json_decode($v, true) ?? $default) : $default; }
    public static function set(string $name, $value, int $jid): void {
        DB::exec('INSERT INTO journal_settings (journal_id,name,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$jid, $name, (string)$value]);
        unset(self::$j[$jid]);
    }
    public static function setSite(string $name, string $value): void {
        DB::exec('INSERT INTO site_settings (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$name, $value]); self::$site = null;
    }
    public static function site(string $name, $default = null) {
        if (self::$site === null) { self::$site = []; foreach (DB::all('SELECT name,value FROM site_settings') as $r) self::$site[$r['name']] = $r['value']; }
        return self::$site[$name] ?? $default;
    }
}
