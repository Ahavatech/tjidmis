<?php
namespace App\Core;
use PDO;
class DB {
    private static ?PDO $pdo = null;
    public static function pdo(): PDO {
        if (!self::$pdo) {
            $c = config('db');
            self::$pdo = new PDO("mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}", $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$pdo;
    }
    public static function q(string $sql, array $p = []): \PDOStatement { $s = self::pdo()->prepare($sql); $s->execute($p); return $s; }
    public static function all(string $sql, array $p = []): array { return self::q($sql, $p)->fetchAll(); }
    public static function one(string $sql, array $p = []): ?array { $r = self::q($sql, $p)->fetch(); return $r === false ? null : $r; }
    public static function val(string $sql, array $p = []) { $r = self::q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }
    public static function exec(string $sql, array $p = []): int { return self::q($sql, $p)->rowCount(); }
    public static function insert(string $table, array $row): int {
        $cols = array_keys($row);
        self::q('INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($row));
        return (int)self::pdo()->lastInsertId();
    }
}
