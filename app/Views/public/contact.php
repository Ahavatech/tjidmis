<?php use App\Core\Settings; ?>
<div class="page page_contact"><?= breadcrumb([[t('about.contact'), null]]) ?><h1><?= e(t('about.contact')) ?></h1>
<div class="contact_section">
<?php foreach ([['primary', 'contact.principal', 'contactName', 'contactEmail'], ['support', 'contact.support', 'supportName', 'supportEmail']] as [$c, $h, $n, $em]): if (Settings::get($n, '') === '') continue; ?>
	<div class="contact <?= $c ?>"><h3><?= e(t($h)) ?></h3><div class="name"><?= e(Settings::get($n)) ?></div>
	<div class="email"><?php if ($e_ = Settings::get($em, '')): ?><a href="mailto:<?= e($e_) ?>"><?= e($e_) ?></a><?php endif; ?></div></div>
<?php endforeach; ?>
</div></div>
