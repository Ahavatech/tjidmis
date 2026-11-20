<?php $op = 'users'; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('stat.menu_users')) ?></h1></div>
<form class="be_filters" method="get" action="<?= e(url('statistics', $op)) ?>"><label><?= e(t('stat.from')) ?> <input type="date" name="from" value="<?= e($from) ?>"></label><label><?= e(t('stat.to')) ?> <input type="date" name="to" value="<?= e($to) ?>"></label><button class="be_btn" type="submit"><?= e(t('dash.filter')) ?></button></form>
<div class="be_cards"><div class="be_stat"><span><?= (int)$total ?></span><?= e(t('stat.users_total')) ?></div><div class="be_stat"><span><?= (int)$new ?></span><?= e(t('stat.users_new')) ?></div></div>
<div class="be_card"><h2><?= e(t('stat.users_by_role')) ?></h2><table class="be_table"><thead><tr><th><?= e(t('users.roles')) ?></th><th><?= e(t('menu.users')) ?></th></tr></thead><tbody>
<?php foreach ($roles as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= (int)$r['n'] ?></td></tr><?php endforeach; ?></tbody></table></div></div>
