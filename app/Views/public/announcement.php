<div class="page page_announcement"><?= breadcrumb([[t('nav.announcements'), url('announcement')], [$a['title'], null]]) ?>
<article class="obj_announcement_full"><h1><?= e($a['title']) ?></h1><div class="date"><?= e(fmt_date($a['date_posted'])) ?></div>
<div class="description"><?= $a['description'] ?></div></article></div>
