<?php use App\Core\Settings; ?>
<div class="page page_contact"><?= breadcrumb([[t('about.contact'), null]]) ?>

	<h1>
		<?= e(t('about.contact')) ?>
	</h1>
	
<div class="contact_section">
<?php foreach ([['primary', 'contact.principal', 'contactName', 'contactEmail'], ['support', 'contact.support', 'supportName', 'supportEmail']] as [$c, $h, $n, $em]): if (Settings::get($n, '') === '') continue; ?>
	<div class="contact <?= $c ?>"><h3><?= e(t($h)) ?></h3><div class="name"><?= e(Settings::get($n)) ?></div>
	<?php if ($e_ = Settings::get($em, '')): ?>
	<div class="email">
		<script type="text/javascript">document.write(unescape('<?= e(obfuscate_mailto($e_)) ?>'))</script>
	</div>
	<?php endif; ?></div>
<?php endforeach; ?>
</div></div>
