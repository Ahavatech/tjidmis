<?php use App\Core\Auth; $staff = Auth::hasRole(['manager', 'section_editor', 'editor', 'assistant', 'reviewer', 'copyeditor', 'layout_editor', 'proofreader']); ?>
<div class="page page_register_complete"><?= breadcrumb([[t('reg.complete'), null]]) ?>

	<h1>
		<?= e(t('reg.complete')) ?>
	</h1>
	<p>
		<?= e(t('reg.complete_text')) ?>
	</p>
	<ul class="registration_complete_actions">
		<?php if ($staff): ?><li class="view_submissions">
			<a href="<?= e(url('submissions')) ?>">
				<?= e(t('reg.view_submissions')) ?>
			</a>
		</li>
		<?php endif; ?><li class="new_submission">
			<a href="<?= e(url('submission', 'wizard')) ?>">
				<?= e(t('reg.new_submission')) ?>
			</a>
		</li>
		<li class="edit_profile">
			<a href="<?= e(url('user', 'profile')) ?>">
				<?= e(t('reg.edit_profile')) ?>
			</a>
		</li>
		<li class="browse">
			<a href="<?= e(url('index')) ?>">
				<?= e(t('reg.continue')) ?>
			</a>
		</li>
	</ul>
</div>
