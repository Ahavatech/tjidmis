<?= breadcrumb([[$page['title'], null]]) ?>
<div class="page">
	<h1 class="page_title"><?= e($page['title']) ?></h1>
	<?= rich($page['content']) ?>
</div>
