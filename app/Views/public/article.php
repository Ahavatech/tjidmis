<?php use App\Models\Catalog; use App\Core\Settings;
$iss = $a['issue']; $crumbs = [[t('nav.archives'), url('issue', 'archive')]];
if ($iss) $crumbs[] = [Catalog::issueLabel($iss), url('issue', 'view', [$iss['id']])];
$crumbs[] = [$a['sectitle'] ?: $a['title'], null];
$kw = json_decode($a['keywords'] ?? '[]', true) ?: []; ?>
<div class="page page_article">
	<?= breadcrumb($crumbs) ?>
	<article class="obj_article_details">
		<h1 class="page_title">
			<?= e($a['title']) ?>
		</h1>
		<?php if ($a['subtitle']): ?><h2 class="subtitle"><?= e($a['subtitle']) ?></h2><?php endif; ?>
		<div class="row">
			<div class="main_entry">
				<?php if ($a['authors']): ?><section class="item authors">
					<h2 class="pkp_screen_reader"><?= e(t('art.authors')) ?></h2>
					<ul class="authors">
						<?php foreach ($a['authors'] as $au): ?><li>
							<span class="name"><?= e(trim($au['given_name'] . ' ' . $au['family_name'])) ?></span>
							<?php if ($au['affiliation']): ?><span class="affiliation"><?= e($au['affiliation']) ?></span><?php endif; ?>
							<?php if ($au['orcid']): ?><span class="orcid"><a href="<?= e($au['orcid']) ?>" target="_blank"><?= e($au['orcid']) ?></a></span><?php endif; ?>
						</li><?php endforeach; ?>
					</ul>
				</section><?php endif; ?>
				<?php if ($a['doi']): ?><section class="item doi">
					<h2 class="label"><?= e(t('art.doi')) ?></h2>
					<span class="value"><a href="https://doi.org/<?= e($a['doi']) ?>">https://doi.org/<?= e($a['doi']) ?></a></span>
				</section><?php endif; ?>
				<?php if ($kw): ?><section class="item keywords">
					<h2 class="label"><?= e(t('art.keywords')) ?></h2>
					<span class="value"><?= e(implode('; ', $kw)) ?></span>
				</section><?php endif; ?>
				<?php if ($a['abstract']): ?><section class="item abstract">
					<h2 class="label"><?= e(t('art.abstract')) ?></h2>
					<?= rich($a['abstract']) ?>
				</section><?php endif; ?>
				<?php if ($a['references_text']): ?><section class="item references">
					<h2 class="label"><?= e(t('art.references')) ?></h2>
					<div class="value"><?= rich($a['references_text']) ?></div>
				</section><?php endif; ?>
			</div><!-- .main_entry -->
			<div class="entry_details">
				<?php if ($a['cover_image']): ?><div class="item cover_image"><div class="sub_item"><img src="<?= e(public_url($a['cover_image'])) ?>" alt=""></div></div><?php endif; ?>
				<?php if ($a['galleys']): ?><div class="item galleys">
					<h2 class="pkp_screen_reader"><?= e(t('art.downloads')) ?></h2>
					<ul class="value galleys_links">
						<?php foreach ($a['galleys'] as $g): ?><li>
							<a class="obj_galley_link <?= e(strtolower($g['label'])) ?>" href="<?= e(url('article', 'view', [$a['sid'], $g['id']])) ?>"><?= e($g['label']) ?></a>
						</li><?php endforeach; ?>
					</ul>
				</div><?php endif; ?>
				<div class="item published">
					<section class="sub_item">
						<h2 class="label"><?= e(t('art.published')) ?></h2>
						<div class="value"><span><?= e(fmt_date($a['date_published'])) ?></span></div>
					</section>
				</div>
				<?php if (Settings::get('showHowToCite', '0') === '1'): ?><div class="item citation"><section class="sub_item citation_display"><h2 class="label"><?= e(t('art.how_to_cite')) ?></h2>
					<div class="value">
						<?php foreach ($cites as $k => $html): ?><div class="csl-bib-body" data-style="<?= e($k) ?>"<?= $k !== 'apa' ? ' hidden' : '' ?>><?= $html ?></div><?php endforeach; ?>
						<label class="cite_style"><span><?= e(t('art.cite_format')) ?></span><select id="citeStyle" aria-label="<?= e(t('art.cite_format')) ?>"><?php foreach (\App\Services\CitationService::STYLES as $k => $lbl): ?><option value="<?= e($k) ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></label>
					</div></section></div><?php endif; ?>
				<div class="item issue">
					<?php if ($iss): ?><section class="sub_item">
						<h2 class="label"><?= e(t('art.issue')) ?></h2>
						<div class="value"><a class="title" href="<?= e(url('issue', 'view', [$iss['id']])) ?>"><?= e(Catalog::issueLabel($iss)) ?></a></div>
					</section><?php endif; ?>
					<?php if ($a['sectitle']): ?><section class="sub_item">
						<h2 class="label"><?= e(t('art.section')) ?></h2>
						<div class="value"><?= e($a['sectitle']) ?></div>
					</section><?php endif; ?>
				</div>
				<?php if ($a['license_url'] && ($lt = Settings::get('licenseTerms', ''))): ?><div class="item copyright">
					<h2 class="label"><?= e(t('art.license')) ?></h2>
					<?= rich($lt) ?>
				</div><?php endif; ?>
				<?php if (Settings::get('showMetrics', '0') === '1'): ?><div class="item metrics"><h2 class="label"><?= e(t('art.metrics')) ?></h2><p><?= e(t('art.views', $metrics['abstract_view'])) ?><br><?= e(t('art.downloads_count', $metrics['galley_download'])) ?></p></div><?php endif; ?>
			</div><!-- .entry_details -->
		</div><!-- .row -->
	</article>
</div><!-- .page -->
