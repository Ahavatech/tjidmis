<?php use App\Models\Catalog; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.issues')) ?></h1><a class="be_btn be_btn_primary" href="<?= e(url('manageIssues', 'form')) ?>"><?= e(t('iss.create')) ?></a></div>
<ul class="be_tabs"><?php foreach (['future' => 'iss.future', 'back' => 'iss.back'] as $k => $l): ?><li class="<?= $k === $tab ? 'active' : '' ?>"><a href="<?= e(url('manageIssues', 'index', [], ['tab' => $k])) ?>"><?= e(t($l)) ?> <span class="be_count"><?= (int)$counts[$k] ?></span></a></li><?php endforeach; ?></ul>
<div class="be_card"><?php if (!$rows): ?><p class="be_empty muted"><?= e(t($tab === 'back' ? 'iss.none_back' : 'iss.none_future')) ?></p><?php else: ?>
<table class="be_table"><thead><tr><th><?= e(t('menu.issues')) ?></th><th><?= e(t('iss.articles')) ?></th><th><?= e(t('iss.date')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><a href="<?= e(url('manageIssues', 'edit', [$r['id']])) ?>"><?= e(Catalog::issueLabel($r)) ?></a><?= $r['is_current'] ? ' <span class="be_badge_stage be_badge_published">' . e(t('iss.current')) . '</span>' : '' ?></td><td><?= (int)$r['articles'] ?></td><td><?= e(fmt_date($r['date_published'])) ?></td>
<td class="be_actions"><a class="be_btn" href="<?= e(url('manageIssues', 'edit', [$r['id']])) ?>"><?= e(t('wizard.edit')) ?></a>
<?php if ($r['published']): ?><a class="be_btn" href="<?= e(url('issue', 'view', [$r['id']])) ?>" target="_blank"><?= e(t('iss.view')) ?></a>
<?php if (!$r['is_current']): ?><form class="be_inline" method="post" action="<?= e(url('manageIssues', 'makeCurrent', [$r['id']])) ?>"><?= csrf_field() ?><button class="be_btn" type="submit"><?= e(t('iss.make_current')) ?></button></form><?php endif; ?>
<?php else: ?><form class="be_inline" method="post" action="<?= e(url('manageIssues', 'delete', [$r['id']])) ?>" onsubmit="return confirm('<?= e(t('iss.confirm_delete')) ?>')"><?= csrf_field() ?><button class="be_btn be_btn_danger" type="submit"><?= e(t('wizard.delete')) ?></button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?></div></div>
