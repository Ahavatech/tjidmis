<?php use App\Core\{Settings, Auth}; ?>
<div class="page page_submissions"><?= breadcrumb([[t('nav.submissions'), null]]) ?>

	<h1>
		<?= e(t('nav.submissions')) ?>
	</h1>
	
<?php if (Settings::get('submissionsDisabled', '0') === '1'): ?><div class="cmp_notification"><?= e(t('wizard.disabled')) ?></div><?php else: ?><div class="cmp_notification">
	<?php if (Auth::user()): ?><a href="<?= e(url('submissions')) ?>"><?= e(t('subm.make')) ?></a>
	<?php else: ?><a href="<?= e(url('login')) ?>"><?= e(t('nav.login')) ?></a> <?= e(t('subm.or')) ?> <a href="<?= e(url('user', 'register')) ?>"><?= e(t('nav.register')) ?></a> <?= e(t('subm.to_make')) ?><?php endif; ?>
</div><?php endif; ?>
<?php if ($checklist): ?><div class="submission_checklist"><h2><?= e(t('subm.checklist')) ?></h2><?= e(t('subm.checklist_intro')) ?>
<ul><?php foreach ($checklist as $c): ?><li><span class="fa fa-check" aria-hidden="true"></span> <?= e($c) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($g = Settings::get('authorGuidelines', '')): ?><div class="author_guidelines" id="authorGuidelines"><h2><?= e(t('subm.guidelines')) ?></h2><?= rich($g) ?></div><?php endif; ?>
<?php foreach ($sections as $s): ?><div class="section_policy"><h2><?= e($s['title']) ?></h2><?= e($s['policy']) ?></div><?php endforeach; ?>
<?php if ($c = Settings::get('copyrightNotice', '')): ?><div class="copyright_notice"><h2><?= e(t('subm.copyright')) ?></h2><?= rich($c) ?></div><?php endif; ?>
<?php if ($p = Settings::get('privacyStatement', '')): ?><div class="privacy_statement" id="privacyStatement"><h2><?= e(t('about.privacy')) ?></h2><?= rich($p) ?></div><?php endif; ?>
</div>
