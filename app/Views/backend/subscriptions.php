<?php use App\Services\SubscriptionService as SS; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('sub.menu')) ?></h1></div>
<ul class="be_tabs"><?php foreach ($tabs as $k => $l): ?><li class="<?= $k === $tab ? 'active' : '' ?>"><a href="<?= e(url('subscription', 'index', [$k])) ?>"><?= e(t($l)) ?></a></li><?php endforeach; ?></ul><div class="be_card">
<?php if ($tab === 'individual' || $tab === 'institutional'): $inst = $tab === 'institutional'; $gr = [];
  foreach ($rows as $r) $gr[] = ['cells' => [$inst ? e($r['institution_name']) : e(trim($r['given_name'] . ' ' . $r['family_name'])) . ' <span class="muted be_small">' . e($r['username']) . '</span>', e($r['type_name']), e(t(SS::STATUSES[$r['status']] ?? 'sub.status_other')), e($r['date_start'] ?: '-'), e($r['date_end'] ?: t('sub.non_expiring'))], 'actions' => [
    [t('wizard.edit'), url('subscription', 'edit', [$tab, $r['id']])], [t('wizard.delete'), url('subscription', 'delete', [$r['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => t('sub.confirm_delete')]]]]; ?>
<?= grid(t($inst ? 'sub.institutional' : 'sub.individual'), [[t('sub.create'), url('subscription', 'edit', [$tab])]], [[t($inst ? 'sub.institution' : 'sub.user'), 28], [t('sub.type'), 22], [t('sub.status'), 18], [t('sub.start'), 16], [t('sub.end'), 16]], $gr, t('grid.no_items')) ?>
<?php elseif ($tab === 'types'): $gr = [];
  foreach ($rows as $r) $gr[] = ['cells' => [e($r['name']), e(t(SS::FORMATS[$r['format']])), e(SS::duration($r['duration'] === null ? null : (int)$r['duration'])), e(number_format((float)$r['cost'], 2) . ' ' . $r['currency']), $r['institutional'] ? e(t('rf.yes')) : e(t('rf.no'))], 'actions' => [
    [t('wizard.edit'), url('subscription', 'type', [$r['id']])], [t('wizard.delete'), url('subscription', 'typeDelete', [$r['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => t('sub.type_confirm_delete')]]]]; ?>
<?= grid(t('sub.types'), [[t('sub.type_create'), url('subscription', 'type')]], [[t('sub.type_name'), 28], [t('sub.format'), 18], [t('sub.duration'), 18], [t('sub.cost'), 18], [t('sub.institutional_col'), 18]], $gr, t('grid.no_items')) ?>
<?php else: ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('subscription', 'savePolicies')) ?>"><?= csrf_field() ?><h3 class="be_group"><?= e(t('sub.expiry')) ?></h3><p class="muted"><?= e(t('sub.expiry_help')) ?></p>
<div class="fields"><?= checkbox('subscriptionExpiryPartial', e(t('sub.expiry_partial')) . ' <span class="muted">&mdash; ' . e(t('sub.expiry_partial_help')) . '</span>', ($values['subscriptionExpiryPartial'] ?? '0') === '1') ?></div>
<h3 class="be_group"><?= e(t('sub.contact')) ?></h3><div class="fields"><?= field(t('sub.contact_name'), 'subscriptionContactName', $values['subscriptionContactName'] ?? '', ['maxlength' => 255]) ?><?= field(t('sub.contact_email'), 'subscriptionContactEmail', $values['subscriptionContactEmail'] ?? '', ['type' => 'email', 'maxlength' => 255]) ?>
<?= field(t('sub.additional_info'), 'subscriptionAdditionalInformation', $values['subscriptionAdditionalInformation'] ?? '', ['type' => 'textarea', 'rows' => 5]) ?></div>
<p class="muted"><?= e(t('sub.oa_where')) ?> <a href="<?= e(url('settings', 'index', ['distribution', 'access'])) ?>"><?= e(t('set.tab_access')) ?></a></p>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></form>
<?php endif; ?></div></div>
