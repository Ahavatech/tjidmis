<?php
// Minimal autoloader (no Composer needed on shared hosting): PHPMailer\PHPMailer\* -> vendor/phpmailer/phpmailer/src/*
spl_autoload_register(function (string $c) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    if (strpos($c, $prefix) === 0) {
        $f = __DIR__ . '/phpmailer/phpmailer/src/' . substr($c, strlen($prefix)) . '.php';
        if (is_file($f)) require $f;
    }
});
