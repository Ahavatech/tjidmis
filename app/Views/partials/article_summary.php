<?php /** @var array $a article row, $h heading level for title */ $h = $h ?? 3; ?>
<div class="obj_article_summary">
  <h<?= $h ?> class="title"><a id="article-<?= (int)$a['sid'] ?>" href="<?= e(url('article', 'view', [$a['sid']])) ?>"><?= e($a['title']) ?></a></h<?= $h ?>>
  <div class="meta">
    <div class="authors"><?= e($a['authors_str']) ?></div>
    <?php if (!empty($a['pages'])): ?><div class="pages"><?= e($a['pages']) ?></div><?php endif; ?>
  </div>
  <?php if (!empty($a['galleys'])): ?><ul class="galleys_links"><?php foreach ($a['galleys'] as $g): ?>
    <?php $rs = !\App\Services\SubscriptionService::hasAccess((int)$a['sid']); ?><li><a class="obj_galley_link <?= e(strtolower($g['label'])) ?><?= $rs ? ' restricted' : '' ?>" href="<?= e(url('article', 'view', [$a['sid'], $g['id']])) ?>" aria-labelledby="article-<?= (int)$a['sid'] ?>">
	<?= $rs ? '<span class="pkp_screen_reader">' . e(t('sub.restricted')) . '</span>' : '' ?><?= e($g['label']) ?>
	</a></li>
  <?php endforeach; ?></ul><?php endif; ?>
</div>
