<?php use App\Services\ReviewFormService as RF; $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $it[$k] ?? ''; $opts = $it['options_list'] ?? []; if (!$opts) $opts = ['']; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t(!empty($it['id']) ? 'rf.item_edit' : 'rf.item_create')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('content', 'reviewFormItem', !empty($it['id']) ? [$f['id'], $it['id']] : [$f['id']])) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('rf.item'), 'question', $v('question'), ['type' => 'textarea', 'rows' => 3, 'required' => true, 'error' => $er('question')]) ?>
<?= field(t('rf.item_description'), 'description', $v('description'), ['type' => 'textarea', 'rows' => 3]) ?>
<?= checkbox('required', e(t('rf.required')), !empty($it['required'])) ?>
<?= checkbox('included', e(t('rf.included')), !empty($it['included'])) ?>
<?= field(t('rf.item_type'), 'element_type', $v('element_type'), ['type' => 'select', 'required' => true, 'error' => $er('element_type'), 'options' => array_map(fn($k) => t($k), RF::TYPES)]) ?>
<div id="rf_opts"><span class="label"><?= e(t('rf.selection')) ?></span>
<?php foreach ($opts as $o): ?><div><input type="text" name="options[]" maxlength="255" value="<?= e($o) ?>"></div><?php endforeach; ?>
<button type="button" class="be_btn" id="rf_add"><?= e(t('rf.add_selection')) ?></button></div>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('content', 'reviewForm', [$f['id']])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
<script>(function(){var t=document.querySelector('[name=element_type]'),b=document.getElementById('rf_opts');function s(){b.style.display=(t.value>=4)?'':'none'}t.addEventListener('change',s);s();
document.getElementById('rf_add').addEventListener('click',function(){var d=document.createElement('div');d.innerHTML='<input type="text" name="options[]" maxlength="255">';b.insertBefore(d,this)})})();</script>
