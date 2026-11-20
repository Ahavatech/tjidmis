<?php use App\Core\Settings; $name = Settings::get('name', 'Journal'); ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= e(($title ?? '') . ' | ' . $name) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/google-fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>"><link rel="stylesheet" href="<?= e(asset('css/backend.css')) ?>">
<style>:root{--theme-color:<?= e(preg_match('/^#[0-9a-fA-F]{3,8}$/', Settings::get('themeColor', '')) ? Settings::get('themeColor') : '#d79f24') ?>}</style></head>
<body class="backend backend_anon">
<div class="be_shell">
  <div class="be_main">
    <header class="be_topbar">
      <a class="be_brand_inline" href="<?= e(url()) ?>"><span class="be_journal"><?= e($name) ?></span></a>
    </header>
    <main class="be_content" id="main">
      <?= $content ?>
    </main>
  </div>
</div>
</body></html>
