<?php /** $issue, $toc, $h (section heading level; article title is $h+1) */ $h = $h ?? 2; ?>
<div class="obj_issue_toc">
  <div class="heading">
    <?php if (!empty($issue['cover_image'])): ?><a class="cover" href="<?= e(url('issue', 'view', [$issue['id']])) ?>"><img src="<?= e(public_url($issue['cover_image'])) ?>" alt="<?= e(\App\Models\Catalog::issueLabel($issue)) ?>"></a><?php endif; ?>
    <?php if (!empty($issue['description'])): ?><div class="description"><?= $issue['description'] ?></div><?php endif; ?>
    <div class="published"><span class="label"><?= e(t('issue.published')) ?></span> <span class="value"><?= e(fmt_date($issue['date_published'])) ?></span></div>
  </div>
  <div class="sections">
    <?php foreach ($toc as $sec): ?><div class="section">
      <?php if (!$sec['hide']): ?><h<?= $h ?>><?= e($sec['title']) ?></h<?= $h ?>><?php endif; ?>
      <ul class="cmp_article_list articles"><?php foreach ($sec['articles'] as $a): ?><li><?= \App\Core\View::partial('partials/article_summary', ['a' => $a, 'h' => $h + 1]) ?></li><?php endforeach; ?></ul>
    </div><?php endforeach; ?>
    <?php if (!$toc): ?><p class="empty"><?= e(t('issue.empty')) ?></p><?php endif; ?>
  </div>
</div>
