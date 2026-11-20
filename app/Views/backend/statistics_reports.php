<div class="be_page"><div class="be_pagehead"><h1><?= e(t('stat.menu_reports')) ?></h1></div>
<p class="muted"><?= e(t('stat.reports_help')) ?></p>
<div class="be_card"><table class="be_table"><tbody>
<?php foreach (['articles', 'reviews', 'users'] as $r): ?><tr><td><strong><?= e(t('stat.report_' . $r)) ?></strong><br><span class="muted be_small"><?= e(t('stat.report_' . $r . '_help')) ?></span></td><td class="be_actions"><a class="be_btn be_btn_primary" href="<?= e(url('statistics', 'report', [$r])) ?>"><?= e(t('stat.download_csv')) ?></a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
