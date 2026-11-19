<div class="page page_announcements"><?= breadcrumb([[t('nav.announcements'), null]]) ?><h1><?= e(t('nav.announcements')) ?></h1>
<p><a href="<?= e(url('user', 'register')) ?>"><?= e(t('ann.signup')) ?></a> <?= e(t('ann.signup_tail')) ?></p>
<ul class="cmp_announcements"><?php foreach ($items as $n): ?><li><article class="obj_announcement_summary">
  <h2><a href="<?= e(url('announcement', 'view', [$n['id']])) ?>"><?= e($n['title']) ?></a></h2>
  <div class="date"><?= e(fmt_date($n['date_posted'])) ?></div>
  <div class="summary"><?= $n['description_short'] ? '<p>' . e($n['description_short']) . '</p>' : '' ?><a class="read_more" href="<?= e(url('announcement', 'view', [$n['id']])) ?>"><?= e(t('home.read_more')) ?></a></div>
</article></li><?php endforeach; ?></ul>
<?php if (!$items): ?><p><?= e(t('ann.none')) ?></p><?php endif; ?>
<?= paginate($total, $page, $per, fn($n) => url('announcement', 'index', [$n])) ?></div>
