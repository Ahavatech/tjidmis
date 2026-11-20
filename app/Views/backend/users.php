<?php $tabs = ['users' => 'users.users_tab', 'roles' => 'users.roles', 'access' => 'users.access']; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.users')) ?></h1></div>
<ul class="be_tabs"><li class="active"><a href="<?= e(url('users')) ?>"><?= e(t('users.users_tab')) ?></a></li><li><a href="<?= e(url('users', 'roles')) ?>"><?= e(t('users.roles')) ?></a></li><li><a href="<?= e(url('users', 'access')) ?>"><?= e(t('users.access')) ?></a></li></ul>
<div class="be_card"><form class="be_filters" method="get" action="<?= e(url('users')) ?>"><select name="field" aria-label="<?= e(t('users.search_in')) ?>"><option value=""><?= e(t('users.search_all')) ?></option><?php foreach (['given' => 'users.f_given', 'family' => 'users.f_family', 'username' => 'users.f_username', 'email' => 'users.f_email'] as $fk => $fl): ?><option value="<?= $fk ?>"<?= ($field ?? '') === $fk ? ' selected' : '' ?>><?= e(t($fl)) ?></option><?php endforeach; ?></select>
<input type="search" name="q" value="<?= e($q) ?>" aria-label="<?= e(t('users.search_btn')) ?>">
<select name="role" aria-label="<?= e(t('users.roles')) ?>"><option value=""><?= e(t('users.all_roles')) ?></option><?php foreach ($groups as $g): ?><option value="<?= (int)$g['id'] ?>"<?= $role === (int)$g['id'] ? ' selected' : '' ?>><?= e($g['name']) ?></option><?php endforeach; ?></select><button class="be_btn" type="submit"><?= e(t('users.search_btn')) ?></button></form>
<?php $gr = []; foreach ($rows as $r) { $act = [[t('users.edit_user'), url('users', 'form', [$r['id']])], [t('users.edit_roles'), url('users', 'editRoles', [$r['id']])], [t('users.email'), url('users', 'email', [$r['id']]), ['class' => 'preview']]];
  if ((int)$r['id'] !== $me) {
    if (!$r['disabled']) $act[] = [t('users.login_as'), url('user', 'loginAs', [$r['id']]), ['post' => 1, 'class' => 'copy']];
    $act[] = [$r['disabled'] ? t('users.enable') : t('users.disable'), url('users', 'toggle', [$r['id']]), ['post' => 1, 'class' => 'copy']];
    $act[] = [t('users.remove'), url('users', 'remove', [$r['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => t('users.confirm_remove')]]; }
  $gr[] = ['class' => $r['disabled'] ? 'be_muted_row' : '', 'cells' => [e($r['given_name']) . ($r['disabled'] ? ' <em>(' . e(t('users.disabled')) . ')</em>' : ''), e($r['family_name']), e($r['username']), e($r['email'])], 'actions' => $act]; } ?>
<?= grid(t('users.current'), [[t('users.add'), url('users', 'form')]], [[t('users.f_given'), 22], [t('users.f_family'), 22], [t('users.f_username'), 22], [t('users.f_email'), 34]], $gr, t('grid.no_items')) ?>
<?= paginate($total, $page, $per, fn($n) => url('users', 'index', [], array_filter(['q' => $q, 'field' => $field ?? null, 'role' => $role ?: null, 'page' => $n]))) ?></div></div>
