<?php $op = 'users'; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('stat.menu_users')) ?></h1></div>
<form class="be_filters" method="get" action="<?= e(url('statistics', $op)) ?>"><label><?= e(t('stat.from')) ?> <input type="date" name="from" value="<?= e($from) ?>"></label><label><?= e(t('stat.to')) ?> <input type="date" name="to" value="<?= e($to) ?>"></label><button class="be_btn" type="submit"><?= e(t('dash.filter')) ?></button></form>
<div class="be_cards"><div class="be_stat"><span><?= (int)$total ?></span><?= e(t('stat.users_total')) ?></div><div class="be_stat"><span><?= (int)$new ?></span><?= e(t('stat.users_new')) ?></div></div>
<div class="be_card"><?php $gr = []; foreach ($roles as $r) $gr[] = ['cells' => [e($r['name']), (int)$r['n']]]; ?>
<?= grid(t('stat.users_by_role'), [], [[t('users.roles'), 70], [t('menu.users'), 30]], $gr) ?></div></div>
