<?php use App\Models\Catalog; ?>
<div class="page page_issue_archive"><?= breadcrumb([[t('nav.archives'), null]]) ?>

	<h1>
		<?= e(t('nav.archives')) ?>
	</h1>
	
<?php if (!$issues): ?><p><?= e(t('archive.empty')) ?></p><?php else: ?>
<ul class="issues_archive"><?php foreach ($issues as $i): ?><li><div class="obj_issue_summary">
  <?php if ($i['cover_image']): ?><a class="cover" href="<?= e(url('issue', 'view', [$i['id']])) ?>"><img src="<?= e(public_url($i['cover_image'])) ?>" alt=""></a><?php endif; ?>
  <h2><a class="title" href="<?= e(url('issue', 'view', [$i['id']])) ?>"><?= e(Catalog::issueLabel($i)) ?></a></h2>
  <div class="description"><?= $i['description'] ?></div></div></li><?php endforeach; ?></ul>
<?= paginate($total, $page, $per, fn($n) => url('issue', 'archive', [$n])) ?><?php endif; ?></div>
