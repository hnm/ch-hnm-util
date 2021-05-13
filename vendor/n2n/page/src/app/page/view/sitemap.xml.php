<?php 
	use n2n\impl\web\ui\view\xml\XmlView;
	use page\model\nav\SitemapItem;

	$view = XmlView::view($this);
	$xml = XmlView::xml($this);

	$sitemapItems = $view->getParam('sitemapItems');
	$view->assert(is_array($sitemapItems));
?>
<?php $xml->xmlHeader() ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
	<?php foreach ($sitemapItems as $sitemapItem): $view->assert($sitemapItem instanceof SitemapItem) ?>
		<url>
			<loc><?php $xml->esc($sitemapItem->getLoc()) ?></loc>
			<?php if (null !== ($lastMod = $sitemapItem->getLastMod())): ?>
				<lastmod><?php $xml->esc($lastMod->format(DateTime::W3C)) ?></lastmod>
			<?php endif ?>
			<?php if (null !== ($changeFreq = $sitemapItem->getChangeFreq())): ?>
				<changefreq><?php $xml->esc($changeFreq) ?></changefreq>
			<?php endif ?>
			<?php if (null !== ($priority = $sitemapItem->getPriority())): ?>
				<priority><?php $xml->esc($priority) ?></priority>
			<?php endif ?>
			
		</url>
	<?php endforeach ?>
</urlset>