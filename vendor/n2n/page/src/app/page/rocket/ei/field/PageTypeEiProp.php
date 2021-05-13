<?php
namespace page\rocket\ei\field;

use n2n\impl\web\ui\view\html\HtmlView;
use rocket\ei\util\Eiu;
use rocket\impl\ei\component\prop\adapter\DisplayableEiPropAdapter;
use n2n\util\type\CastUtils;
use page\bo\Page;
use n2n\impl\web\ui\view\html\HtmlSnippet;
use n2n\impl\web\ui\view\html\HtmlElement;

class PageTypeEiProp extends DisplayableEiPropAdapter {
	
	public function createUiComponent(HtmlView $view, Eiu $eiu) {
		$page = $eiu->entry()->getEntityObj();
		CastUtils::assertTrue($page instanceof Page);
		
		$iconType = null;
		$label = null;
		
		switch ($page->getType()) {
			case Page::TYPE_INTERNAL:
				$iconType = 'fa fa-link';
				$label = $page->getInternalPage()->t($view->getN2nLocale())->getName();
				break;
			case Page::TYPE_EXTERNAL:
				$iconType = 'fa fa-link';
				$label = $view->getHtmlBuilder()->getLink($page->getExternalUrl(), null, array('target' => '_blank'));
				break;
			default:
				$eiuMask = $eiu->context()->mask($page->getPageContent()->getPageController());
				$iconType = $eiuMask->getIconType();
				$label = $eiuMask->getLabel();
		}
		
		return new HtmlSnippet(
				new HtmlElement('i', array('class' => $iconType), ''),
				' ',
				new HtmlElement('span', null, $label));
	}

	
}