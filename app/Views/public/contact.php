<?php use App\Core\Settings; ?>
<div class="page page_contact"><?= breadcrumb([[t('about.contact'), null]]) ?><h1><?= e(t('about.contact')) ?></h1>
  <div class="contact_section">
    <h2><?= e(t('contact.principal')) ?></h2>
    <div class="contact primary">
      <p class="name"><?= e(Settings::get('contactName', '')) ?></p>
      <p class="address"><?= nl2br(e(Settings::get('contactMailingAddress', ''))) ?></p>
      <?php if ($ph = Settings::get('contactPhone', '')): ?><p class="phone"><span class="label"><?= e(t('contact.phone')) ?></span> <span class="value"><?= e($ph) ?></span></p><?php endif; ?>
      <?php if ($em = Settings::get('contactEmail', '')): ?><p class="email"><a href="mailto:<?= e($em) ?>"><?= e($em) ?></a></p><?php endif; ?>
    </div>
  </div>
</div>
