<div class="be_page"><div class="be_pagehead"><h1><?= e(t('notif.title')) ?></h1>
<?php if ($items): ?><form method="post" action="<?= e(url('notification', 'markRead')) ?>"><?= csrf_field() ?><button class="be_btn" type="submit"><?= e(t('notif.mark_read')) ?></button></form><?php endif; ?></div>
<div class="be_card"><?php if (!$items): ?><p class="muted"><?= e(t('notif.none')) ?></p><?php else: ?>
<ul class="be_list"><?php foreach ($items as $n): ?><li class="<?= $n['read_at'] ? '' : 'unread' ?>"><span><?= e($n['message']) ?></span><span class="muted"><?= e(fmt_date($n['created_at'])) ?></span></li><?php endforeach; ?></ul><?php endif; ?></div></div>
