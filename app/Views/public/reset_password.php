<h1 class="app__pageHeading">
		<?= e(t('lost.title')) ?>
	</h1>

	<div class="app__contentPanel">
		<form class="pkp_form" id="updateResetPassword" method="post" action="<?= e(url('login', 'updateResetPassword')) ?>">
			<?= csrf_field() ?>
			<?php if ($errors): ?><div id="formErrors">
				<span class="pkp_form_error"><?= e(t('form.errors_occurred')) ?>:</span>
				<ul class="pkp_form_error_list">
				<?php foreach ($errors as $field => $message): ?>
					<li><a href="#<?= e($field) ?>"><?= e($message) ?></a></li>
				<?php endforeach; ?>
				</ul>
			</div>
			<script>window.location.hash="formErrors";</script><?php endif; ?>

			<p><span class="instruct"><?= e(t('reset.instructions')) ?></span></p>

			<div id="loginFields" class="pkp_form_area">
				<div class="pkp_form_section">
					<label for="password"><?= e(t('reset.new')) ?> <span class="req">*</span></label>
					<input type="password" id="password" name="password" required maxlength="32">
					<label class="field_hint" for="password"><?= e(t('form.password_short', (int)\App\Core\Settings::site('minPasswordLength', '8'))) ?></label>
				</div>
				<div class="pkp_form_section">
					<label for="password2"><?= e(t('reset.new2')) ?> <span class="req">*</span></label>
					<input type="password" id="password2" name="password2" required maxlength="32">
				</div>

				<input type="hidden" id="username" name="username" value="<?= e($username) ?>">
				<input type="hidden" id="hash" name="hash" value="<?= e($hash) ?>">

				<p>
					<?= t('reset.privacy_link', e(url('about', 'privacy'))) ?>
				</p>

				<p><span class="formRequired"><?= t('reset.required_note') ?></span></p>
				<div class="formButtons"><button class="submit be_btn be_btn_primary" type="submit"><?= e(t('reset.save')) ?></button></div>
			</div>
		</form>
	</div>
