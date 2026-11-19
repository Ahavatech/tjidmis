<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.submissions')) ?></h1></div>
<ul class="be_tabs" role="tablist"><?php $first = true; foreach ($tabs as $k => $l): ?><li class="<?= $first ? 'active' : '' ?>"><a href="#tab-<?= e($k) ?>"><?= e($l) ?></a></li><?php $first = false; endforeach; ?></ul>
<div class="be_card be_empty"><p><?= e(t('dash.empty')) ?></p><p class="muted"><?= e(t('dash.phase_note')) ?></p>
<?php if ($canSubmit): ?><p class="muted"><?= e(t('dash.author_note')) ?></p><?php endif; ?></div></div>
