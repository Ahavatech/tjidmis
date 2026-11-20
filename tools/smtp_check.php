<?php
// Developer/deploy tool: sends one test e-mail through the SMTP settings in config.php and prints the result.
//   php tools/smtp_check.php you@example.org
require __DIR__ . '/../app/bootstrap.php';
use App\Core\{Mailer, DB};

$to = $argv[1] ?? '';
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { fwrite(STDERR, "Usage: php tools/smtp_check.php recipient@example.org\n"); exit(2); }
$smtp = $GLOBALS['config']['smtp'] ?? [];
echo 'PHPMailer class: ' . (class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? 'available' : 'MISSING (upload the vendor/ folder)') . "\n";
echo 'SMTP host: ' . ($smtp['host'] ?: '(not set in config.php, the message will only be logged)') . "\n";
$_SERVER['PATH_INFO'] = '/' . ($GLOBALS['config']['default_journal'] ?? 'jidmis');
App\Core\Request::parse();
$ok = Mailer::send($to, 'SMTP test from the journal site', "If you can read this, outgoing mail works.\n");
$row = DB::one('SELECT sent, error FROM email_log ORDER BY id DESC LIMIT 1');
echo 'Result: ' . ($ok ? 'SENT' : 'NOT SENT') . ($row['error'] ? ' - ' . $row['error'] : '') . "\n";
exit($ok ? 0 : 1);
