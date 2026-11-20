<?php /** $cat, $parent, $subs, $articles: OJS 3.3 catalogCategory.tpl */
$crumbs = [];
if ($parent) $crumbs[] = [$parent['title'], url('catalog', 'category', [$parent['path']])];
$crumbs[] = [$cat['title'], null]; ?>
<div class="page page_catalog_category">
	<?= breadcrumb($crumbs) ?>
	<h1>
		<?= e($cat['title']) ?>
	</h1>

	<div class="article_count">
		<?= e(t('cat.items', count($articles))) ?>
	</div>

	<div class="about_section<?= trim((string)$cat['description']) !== '' ? ' has_description' : '' ?>">
		<div class="description">
			<?= rich((string)$cat['description']) ?>
		</div>
	</div>

	<?php if ($subs): ?>
	<nav class="subcategories" role="navigation">
		<h2>
			<?= e(t('cat.subcategories')) ?>
		</h2>
		<ul>
			<?php foreach ($subs as $s): ?><li>
				<a href="<?= e(url('catalog', 'category', [$s['path']])) ?>">
					<?= e($s['title']) ?>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php endif; ?>

	<h2 class="title">
		<?= e(t('cat.all_items')) ?>
	</h2>

	<?php if (!$articles): ?>
		<p><?= e(t('cat.no_items')) ?></p>
	<?php else: ?>
		<ul class="cmp_article_list articles">
			<?php foreach ($articles as $a): $a['galleys'] = []; ?><li>
				<?= \App\Core\View::partial('partials/article_summary', ['a' => $a, 'h' => 3]) ?>
			</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
