<?php /** $heading, $message, optional $backUrl/$backLabel: the OJS "message" page */ ?>
<div class="page page_message"><?= breadcrumb([[$heading, null]]) ?>

	<h1>
		<?= e($heading) ?>
	</h1>

	<div class="description">
		<?= e($message) ?>
	</div>
	<?php if (!empty($backUrl)): ?><div class="cmp_back_link">
		<a href="<?= e($backUrl) ?>"><?= e($backLabel ?? '') ?></a>
	</div><?php endif; ?>
</div>
