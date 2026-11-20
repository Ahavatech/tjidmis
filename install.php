<?php
declare(strict_types=1);
/** Web installer. Locks itself (install.lock) after a successful run. */
session_start();
$root = __DIR__;
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

if (is_file($root . '/install.lock')) { http_response_code(403); exit('<h1>Already installed</h1><p>Delete install.lock to run the installer again (this does not wipe the database).</p>'); }

/** Split a SQL file into statements, respecting quoted strings and -- comments. */
function split_sql(string $sql): array {
    $out = []; $cur = ''; $q = null; $n = strlen($sql);
    for ($i = 0; $i < $n; $i++) {
        $c = $sql[$i];
        if ($q) {
            $cur .= $c;
            if ($c === '\\') { $cur .= $sql[++$i] ?? ''; }
            elseif ($c === $q) { if (($sql[$i + 1] ?? '') === $q) { $cur .= $sql[++$i]; } else $q = null; }
            continue;
        }
        if ($c === '-' && ($sql[$i + 1] ?? '') === '-') { while ($i < $n && $sql[$i] !== "\n") $i++; continue; }
        if ($c === "'" || $c === '"') { $q = $c; $cur .= $c; continue; }
        if ($c === ';') { if (trim($cur) !== '') $out[] = trim($cur); $cur = ''; continue; }
        $cur .= $c;
    }
    if (trim($cur) !== '') $out[] = trim($cur);
    return $out;
}
function tiny_pdf(string $title): string {
    $t = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $title);
    $stream = "BT /F1 18 Tf 72 720 Td ($t) Tj 0 -28 Td /F1 11 Tf (Placeholder PDF galley. Replace with the real article.) Tj ET";
    $objs = ["<< /Type /Catalog /Pages 2 0 R >>", "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
        "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>",
        "<< /Length " . strlen($stream) . " >>\nstream\n$stream\nendstream", "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>"];
    $pdf = "%PDF-1.4\n"; $off = [];
    foreach ($objs as $i => $o) { $off[] = strlen($pdf); $pdf .= ($i + 1) . " 0 obj\n$o\nendobj\n"; }
    $x = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
    foreach ($off as $o) $pdf .= sprintf("%010d 00000 n \n", $o);
    return $pdf . "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n$x\n%%EOF";
}

$checks = [
    'PHP 8.1 or newer (' . PHP_VERSION . ')' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'pdo_mysql' => extension_loaded('pdo_mysql'), 'mbstring' => extension_loaded('mbstring'), 'fileinfo' => extension_loaded('fileinfo'),
    'gd' => extension_loaded('gd'), 'dom (HTML sanitiser)' => extension_loaded('dom'),
    'Project folder writable (config.php)' => is_writable($root),
];
$required = ['pdo_mysql', 'mbstring', 'fileinfo', 'dom (HTML sanitiser)', 'Project folder writable (config.php)'];
$blocking = array_filter($checks, fn($ok, $k) => !$ok && (str_starts_with($k, 'PHP') || in_array($k, $required, true)), ARRAY_FILTER_USE_BOTH);

$errors = []; $done = false;
$v = $_POST + ['db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'db_pass' => '', 'files_dir' => dirname($root) . '/journal_files',
    'site_title' => 'Journal of Intelligent Decision Making and Information Science', 'admin_user' => 'admin', 'admin_email' => '', 'admin_pass' => '', 'demo' => '1'];
if (empty($_SESSION['tok'])) $_SESSION['tok'] = bin2hex(random_bytes(16));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['tok'], (string)($_POST['tok'] ?? ''))) $errors[] = 'Invalid form token. Reload and try again.';
    if ($blocking) $errors[] = 'Fix the failed requirements first.';
    if (!preg_match('/^[A-Za-z0-9._-]{3,}$/', $v['admin_user'])) $errors[] = 'Admin username: at least 3 letters, digits or . _ -';
    if (!filter_var($v['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid admin email.';
    if (strlen($v['admin_pass']) < 8) $errors[] = 'Admin password must be at least 8 characters.';
    if (trim($v['files_dir']) === '') $errors[] = 'Files directory is required.';
    if (!$errors) {
        try {
            $pdo = new PDO("mysql:host={$v['db_host']};dbname={$v['db_name']};charset=utf8mb4", $v['db_user'], $v['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            if ($pdo->query("SHOW TABLES LIKE 'journals'")->fetch()) throw new RuntimeException('The database already contains JIRS tables. Use an empty database.');
            $run = function (string $file, array $repl = []) use ($pdo, $root) {
                $sql = strtr(file_get_contents($root . '/install/' . $file), $repl);
                foreach (split_sql($sql) as $st) $pdo->exec($st);
            };
            $run('schema.sql');
            $run('seed.sql');
            $pdo->prepare("UPDATE journal_settings SET value=? WHERE journal_id=1 AND name='name'")->execute([trim($v['site_title'])]);
            $pdo->prepare("UPDATE journal_settings SET value=? WHERE journal_id=1 AND name='mailFrom'")->execute([$v['admin_email']]);
            // admin: site admin + manager + reader
            $pdo->prepare("INSERT INTO users (username,email,password_hash,given_name,family_name,affiliation,created_at) VALUES (?,?,?,?,?,?,NOW())")
                ->execute([$v['admin_user'], $v['admin_email'], password_hash($v['admin_pass'], PASSWORD_DEFAULT), 'Site', 'Administrator', '']);
            $aid = (int)$pdo->lastInsertId();
            foreach ([1, 2, 10] as $g) $pdo->prepare("INSERT INTO user_user_groups (user_id,user_group_id) VALUES (?,?)")->execute([$aid, $g]);
            // files dir
            $fd = rtrim($v['files_dir'], '/\\');
            foreach (['logs', 'journals/1/submissions', 'tmp'] as $d) if (!is_dir("$fd/$d") && !@mkdir("$fd/$d", 0775, true)) throw new RuntimeException("Cannot create $fd/$d. Check the path and permissions.");
            foreach (glob($root . '/install/seed_library/*', GLOB_ONLYDIR) ?: [] as $ld) {   // publisher library files shipped with the seed
                $dst = "$fd/library/" . basename($ld); if (!is_dir($dst)) mkdir($dst, 0775, true);
                foreach (glob("$ld/*") ?: [] as $lf) copy($lf, "$dst/" . basename($lf));
            }
            if (!empty($v['demo'])) {
                $run('seed_demo.sql', ['{{HASH}}' => password_hash('Demo@12345', PASSWORD_DEFAULT)]);
                foreach ($pdo->query('SELECT f.id,f.submission_id,f.stored_name,p.title FROM submission_files f JOIN galleys g ON g.submission_file_id=f.id JOIN publications p ON p.id=g.publication_id') as $f) {
                    $dir = "$fd/journals/1/submissions/{$f['submission_id']}/galley";
                    if (!is_dir($dir)) mkdir($dir, 0775, true);
                    $gid = (string)preg_replace('/\D/', '', $f['stored_name']);
                    $real = $root . '/install/seed_files/galley-' . $gid . '.pdf';
                    $bytes = is_file($real) ? file_get_contents($real) : tiny_pdf($f['title']);
                    file_put_contents("$dir/{$f['stored_name']}", $bytes);
                    $pdo->prepare('UPDATE submission_files SET size=? WHERE id=?')->execute([strlen($bytes), $f['id']]);
                }
            }
            $cfg = [
                'db' => ['host' => $v['db_host'], 'name' => $v['db_name'], 'user' => $v['db_user'], 'pass' => $v['db_pass'], 'charset' => 'utf8mb4'],
                'files_dir' => $fd, 'base_url' => ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . preg_replace('/[^A-Za-z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')), 'url_mode' => 'path', 'pretty_urls' => false, 'default_journal' => 'jidmis', 'timezone' => 'Africa/Lagos',
                'debug' => false, 'demo_mode' => !empty($v['demo']),
                'smtp' => ['host' => '', 'port' => 587, 'user' => '', 'pass' => '', 'secure' => 'tls', 'from' => ''],
            ];
            if (file_put_contents($root . '/config.php', "<?php\nreturn " . var_export($cfg, true) . ";\n") === false) throw new RuntimeException('Could not write config.php');
            file_put_contents($root . '/install.lock', date('c'));
            $done = true;
        } catch (Throwable $e) { $errors[] = $e->getMessage(); }
    }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install</title>
<style>body{font:14px/1.5 "Noto Serif",Georgia,serif;background:#f4f5f7;color:#1e2430;margin:0}main{max-width:640px;margin:2rem auto;background:#fff;padding:2rem;border-radius:10px;box-shadow:0 1px 3px rgba(16,24,40,.1)}
h1{font-family:Inter,Arial,sans-serif;color:#1f2a44;border-left:4px solid #d79f24;padding-left:.75rem}label{display:block;margin:.8rem 0 .2rem;font-weight:700}input[type=text],input[type=password],input[type=email]{width:100%;padding:.5rem;border:1px solid #ccc;border-radius:3px;box-sizing:border-box}
.ok{color:#080}.bad{color:#a80000;font-weight:700}.err{background:#fde8e8;border:1px solid #a80000;padding:.6rem 1rem;margin:1rem 0}button{margin-top:1.2rem;background:#d79f24;border:0;padding:.7rem 1.4rem;font-weight:700;border-radius:3px;cursor:pointer}</style></head><body><main>
<h1>Journal installer</h1>
<?php if ($done): ?>
  <p class="ok"><strong>Installation complete.</strong></p>
  <p><a href="index.php/jidmis">Open the journal home page</a> or <a href="index.php/jidmis/login">log in</a>.</p>
  <?php if (!empty($v['demo'])): ?><p>Demo accounts (editor, sectioneditor, reviewer1, reviewer2, author, reader) use the password <code>Demo@12345</code>. Change them before going live.</p><?php endif; ?>
  <p>For safety, delete <code>install.php</code> now (it is also locked by <code>install.lock</code>).</p>
<?php else: ?>
  <h2>Requirements</h2><ul><?php foreach ($checks as $k => $ok): ?><li class="<?= $ok ? 'ok' : 'bad' ?>"><?= h($k) ?>: <?= $ok ? 'OK' : (in_array($k, $required, true) || str_starts_with($k, 'PHP') ? 'MISSING' : 'optional, missing') ?></li><?php endforeach; ?></ul>
  <?php foreach ($errors as $e): ?><div class="err"><?= h($e) ?></div><?php endforeach; ?>
  <form method="post"><input type="hidden" name="tok" value="<?= h($_SESSION['tok']) ?>">
    <h2>Database</h2>
    <label>Host</label><input type="text" name="db_host" value="<?= h($v['db_host']) ?>">
    <label>Database name</label><input type="text" name="db_name" value="<?= h($v['db_name']) ?>">
    <label>User</label><input type="text" name="db_user" value="<?= h($v['db_user']) ?>">
    <label>Password</label><input type="password" name="db_pass" value="">
    <h2>Files</h2>
    <label>Files directory (outside the web root)</label><input type="text" name="files_dir" value="<?= h($v['files_dir']) ?>">
    <h2>Site and administrator</h2>
    <label>Journal title</label><input type="text" name="site_title" value="<?= h($v['site_title']) ?>">
    <label>Admin username</label><input type="text" name="admin_user" value="<?= h($v['admin_user']) ?>">
    <label>Admin email</label><input type="email" name="admin_email" value="<?= h($v['admin_email']) ?>">
    <label>Admin password (min 8)</label><input type="password" name="admin_pass" value="">
    <label><input type="checkbox" name="demo" value="1" <?= !empty($v['demo']) ? 'checked' : '' ?>> Load demo data (role accounts, 2 issues, 6 articles)</label>
    <button type="submit">Install</button>
  </form>
<?php endif; ?></main></body></html>
