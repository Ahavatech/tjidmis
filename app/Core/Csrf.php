<?php
namespace App\Core;
class Csrf {
    public static function token(): string {
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf'];
    }
    public static function check(): bool {
        $t = $_POST['csrfToken'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
    }
}
