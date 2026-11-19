<?php use App\Models\Catalog;
$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$sel = function (string $name, array $opts, $cur) { $h = '<select id="' . e($name) . '" name="' . e($name) . '"><option></option>'; foreach ($opts as $v => $l) $h .= '<option value="' . e($v) . '"' . ((string)$cur === (string)$v ? ' selected' : '') . '>' . e($l) . '</option>'; return $h . '</select>'; };
$years = array_combine($ys = range((int)date('Y'), (int)date('Y') - 15), $ys); $days = array_combine($ds = range(1, 31), $ds); $ms = array_combine(range(1, 12), $months);
$range = function (string $p, string $legend) use ($sel, $g, $years, $ms, $days) { return '<div class="' . ($p === 'dateFrom' ? 'from' : 'to') . '"><fieldset><legend>' . e($legend) . '</legend>'
    . '<label for="' . $p . 'Year">' . e(t('search.year')) . '</label>' . $sel($p . 'Year', $years, $g[$p . 'Year'] ?? '')
    . '<label for="' . $p . 'Month">' . e(t('search.month')) . '</label>' . $sel($p . 'Month', $ms, $g[$p . 'Month'] ?? '')
    . '<label for="' . $p . 'Day">' . e(t('search.day')) . '</label>' . $sel($p . 'Day', $days, $g[$p . 'Day'] ?? '') . '</fieldset></div>'; }; ?>
<div class="page page_search"><?= breadcrumb([[t('nav.search'), null]]) ?><h1><?= e(t('nav.search')) ?></h1>
<form class="cmp_form" method="get" action="<?= e(url('search', 'search')) ?>">
  <div class="search_input"><label class="pkp_screen_reader" for="query"><?= e(t('search.for')) ?></label>
    <input type="text" id="query" name="query" value="<?= e($f['query']) ?>" class="query" placeholder="<?= e(t('nav.search')) ?>"></div>
  <fieldset class="search_advanced"><legend><?= e(t('search.advanced')) ?></legend>
    <div class="date_range"><?= $range('dateFrom', t('search.after')) ?><?= $range('dateTo', t('search.before')) ?></div>
    <div class="author"><label for="authors"><?= e(t('search.authors')) ?></label><input type="text" id="authors" name="authors" value="<?= e($f['authors']) ?>"></div>
  </fieldset>
  <div class="submit"><button class="submit" type="submit"><?= e(t('nav.search')) ?></button></div>
</form>
<?php if ($searched): ?><div class="search_results"><h2><?= e(t('search.results')) ?></h2>
  <?php if (!$rows): ?><p class="no_results"><?= e(t('search.none')) ?></p><?php else: ?>
  <p class="count"><?= e(t('search.count', $total)) ?></p>
  <ul class="search_results_list cmp_article_list"><?php foreach ($rows as $r): ?><li>
    <?= \App\Core\View::partial('partials/article_summary', ['a' => $r, 'h' => 3]) ?>
    <?php if ($r['issue']): ?><div class="search_issue"><?= e(t('art.issue')) ?>: <a href="<?= e(url('issue', 'view', [$r['issue']['id']])) ?>"><?= e(Catalog::issueLabel($r['issue'])) ?></a></div><?php endif; ?></li><?php endforeach; ?></ul>
  <?= paginate($total, $page, $per, fn($n) => url('search', 'search', [], array_merge(array_filter($g, fn($v) => $v !== ''), ['searchPage' => $n]))) ?><?php endif; ?></div><?php endif; ?>
</div>
