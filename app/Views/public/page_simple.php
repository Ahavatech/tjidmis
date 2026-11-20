<?php /** $crumb, $html, $cls, optional $wrap (extra wrapper class) */ ?>
<div class="page <?= e($cls ?? '') ?>"><?= breadcrumb([[$crumb, null]]) ?>

	<h1>
		<?= e($crumb) ?>
	</h1>
	
<?= !empty($wrap) ? '<div class="' . e($wrap) . '">' . rich($html) . '</div>' : rich($html) ?></div>
