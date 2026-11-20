<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.users')) ?></h1></div>
<ul class="be_tabs"><li><a href="<?= e(url('users')) ?>"><?= e(t('users.users_tab')) ?></a></li><li class="active"><a href="<?= e(url('users', 'roles')) ?>"><?= e(t('users.roles')) ?></a></li><li><a href="<?= e(url('users', 'access')) ?>"><?= e(t('users.access')) ?></a></li></ul>
<div class="be_card"><p class="muted"><?= e(t('users.roles_desc')) ?></p><p class="muted be_small"><?= e(t('users.roles_help')) ?></p><form method="post" action="<?= e(url('users', 'roles')) ?>"><?= csrf_field() ?>
<?php $cols = [[t('users.role_name'), 22], [t('users.permission_level'), 20]]; foreach ([1, 3, 4, 5] as $s) $cols[] = [t(\App\Models\SubmissionRepo::STAGES[$s]), 11, 'center']; $cols[] = [t('users.selfreg'), 14, 'center'];
$gr = []; foreach ($groups as $g) { $fixed = in_array($g['role_key'], ['manager', 'site_admin'], true); $cells = [e($g['name']), e(t('users.lvl_' . $g['role_key']))];
  foreach ([1, 3, 4, 5] as $s) $cells[] = '<input type="checkbox" name="stage[' . (int)$g['id'] . '][' . $s . ']" value="1"' . (!empty($st[$g['id']][$s]) ? ' checked' : '') . ($fixed ? ' checked disabled' : '') . ' aria-label="' . e($g['name']) . ' ' . $s . '">';
  $cells[] = in_array($g['role_key'], ['author', 'reviewer', 'reader'], true) ? '<input type="checkbox" name="selfreg[' . (int)$g['id'] . ']" value="1"' . ($g['permit_self_registration'] ? ' checked' : '') . '>' : '-';
  $gr[] = ['cells' => $cells]; } ?>
<?= grid(t('users.current_roles'), [], $cols, $gr) ?>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></form></div></div>
