<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.users')) ?></h1></div>
<p class="muted"><?= e(t('users.note')) ?></p>
<div class="be_card"><table class="be_table"><thead><tr><th><?= e(t('users.name')) ?></th><th><?= e(t('login.username')) ?></th><th><?= e(t('reg.email')) ?></th><th><?= e(t('users.roles')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e(trim($r['given_name'] . ' ' . $r['family_name'])) ?><?= $r['disabled'] ? ' <em>(' . e(t('users.disabled')) . ')</em>' : '' ?></td><td><?= e($r['username']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['roles']) ?></td>
<td><?php if ((int)$r['id'] !== \App\Core\Auth::id() && !$r['disabled']): ?><form method="post" action="<?= e(url('user', 'loginAs', [$r['id']])) ?>"><?= csrf_field() ?><button class="be_btn" type="submit"><?= e(t('users.login_as')) ?></button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
