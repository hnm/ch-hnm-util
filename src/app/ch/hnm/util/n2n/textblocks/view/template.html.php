<?php
	use n2n\core\N2N;
	use n2n\impl\web\ui\view\html\HtmlView;
	use elob\core\model\ElobState;
	use n2nutil\jquery\JQueryLibrary;
	use tmpl\ui\TemplateHtmlBuilder;
	use elob\core\model\TemplateModel;
use elob\core\model\MandatorCssGenerator;
	
	/**
	 * @var HtmlView $view
	 */
	$view = HtmlView::view($view);
	$html = HtmlView::html($view);
	$request = HtmlView::request($view);
	$httpContext = HtmlView::httpContext($view);
	
	$html->meta()->addMeta(array('charset' => N2N::CHARSET));
	$html->meta()->addMeta(array('name' => 'author', 'content' => N2N::getAppConfig()->general()->getPageName()));
	$html->meta()->addMeta(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
	$html->meta()->addMeta(['http-equiv' =>  'x-ua-compatible', 'content' => 'ie=edge']);
	$html->meta()->addMeta(array('name' => 'msapplication-TileImage', 'content' => $httpContext->getAssetsUrl('tmpl')->pathExt('img/touch-icon-192x192.png')));
	$html->meta()->addMeta(array('name' => 'msapplication-TileColor', 'content' => '#44A411'));
	
	$n2nLocale = $view->getN2nLocale();
?>
<!doctype html>
<html class="no-js" lang="<?php $html->out($n2nLocale->getLanguageId()) ?>">
	<?php $html->headStart() ?>
		<!-- internet page created by hnm.ch -->
	<?php $html->headEnd() ?>
	<?php $html->bodyStart() ?>
	
	<?php $view->importContentView() ?>
				
	<?php $html->bodyEnd() ?>
</html>