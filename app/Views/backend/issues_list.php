<?php use App\Models\Catalog; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.issues')) ?></h1></div>
<ul class="be_tabs"><?php foreach (['future' => 'iss.future', 'back' => 'iss.back'] as $k => $l): ?><li class="<?= $k === $tab ? 'active' : '' ?>"><a href="<?= e(url('manageIssues', 'index', [], ['tab' => $k])) ?>"><?= e(t($l)) ?> <span class="be_count"><?= (int)$counts[$k] ?></span></a></li><?php endforeach; ?></ul>
<div class="be_card"><?php $gr = []; foreach ($rows as $r) { $act = [[t('wizard.edit'), url('manageIssues', 'edit', [$r['id']])]];
  if ($r['published']) { $act[] = [t('iss.view'), url('issue', 'view', [$r['id']]), ['class' => 'preview']]; if (!$r['is_current']) $act[] = [t('iss.make_current'), url('manageIssues', 'makeCurrent', [$r['id']]), ['post' => 1, 'class' => 'copy']]; }
  else $act[] = [t('wizard.delete'), url('manageIssues', 'delete', [$r['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => t('iss.confirm_delete')]];
  $gr[] = ['cells' => ['<a href="' . e(url('manageIssues', 'edit', [$r['id']])) . '">' . e(Catalog::issueLabel($r)) . '</a>' . ($r['is_current'] ? ' <span class="be_badge_stage be_badge_published">' . e(t('iss.current')) . '</span>' : ''), (int)$r['articles'], e(fmt_date($r['date_published']))], 'actions' => $act]; } ?>
<?= grid(t($tab === 'back' ? 'iss.back' : 'iss.future'), [[t('iss.create'), url('manageIssues', 'form')]], [[t('menu.issues'), 50], [t('iss.articles'), 25], [t('iss.date'), 25]], $gr, t($tab === 'back' ? 'iss.none_back' : 'iss.none_future')) ?></div></div>
