<?php $op = 'editorial'; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('stat.menu_editorial')) ?></h1></div>
<form class="be_filters" method="get" action="<?= e(url('statistics', $op)) ?>"><label><?= e(t('stat.from')) ?> <input type="date" name="from" value="<?= e($from) ?>"></label><label><?= e(t('stat.to')) ?> <input type="date" name="to" value="<?= e($to) ?>"></label><button class="be_btn" type="submit"><?= e(t('dash.filter')) ?></button></form>
<div class="be_cards">
<div class="be_stat"><span><?= (int)$recv ?></span><?= e(t('stat.received')) ?></div><div class="be_stat"><span><?= (int)$accepted ?></span><?= e(t('stat.accepted')) ?></div><div class="be_stat"><span><?= (int)$declined ?></span><?= e(t('stat.declined')) ?></div>
<div class="be_stat"><span><?= (int)$published ?></span><?= e(t('stat.published')) ?></div><div class="be_stat"><span><?= $rate === null ? '-' : (int)$rate . '%' ?></span><?= e(t('stat.rate')) ?></div><div class="be_stat"><span><?= $days === null ? '-' : e($days) ?></span><?= e(t('stat.days')) ?></div></div></div>
