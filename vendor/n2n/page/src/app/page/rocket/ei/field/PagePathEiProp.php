<?php
namespace page\rocket\ei\field;

use rocket\ei\util\Eiu;
use n2n\impl\web\ui\view\html\HtmlView;
use rocket\impl\ei\component\prop\adapter\DisplayableEiPropAdapter;
use n2n\util\type\CastUtils;
use page\bo\PageT;
use page\model\PageState;
use page\model\nav\NavUrlBuilder;
use n2n\impl\web\ui\view\html\HtmlElement;
use n2n\util\StringUtils;
use page\bo\Page;
use page\model\nav\UnavailableLeafException;

class PagePathEiProp extends DisplayableEiPropAdapter {
	
	public function createUiComponent(HtmlView $view, Eiu $eiu) {
		$pageT = $eiu->entry()->getEntityObj();
		CastUtils::assertTrue($pageT instanceof PageT);
		
		$pageState = $eiu->lookup(PageState::class);
		CastUtils::assertTrue($pageState instanceof PageState);
		
		$navBranch = $pageState->getNavTree()->find($pageT);
		if ($navBranch === null) return null;
		
		$navUrlBuilder = new NavUrlBuilder($eiu->getN2nContext()->getHttpContext());
		$navUrlBuilder->setAccessiblesOnly(false);
		$navUrlBuilder->setFallbackAllowed(false);
		
		$pathStr = null;
		try {
			$pathStr = (string) $navUrlBuilder->buildPath($navBranch, $pageT->getN2nLocale())->chLeadingDelimiter(true);
		} catch (UnavailableLeafException $e) {
			return new HtmlElement('span', ['class' => 'rocket-inactive'], 
					$view->getL10nText('unreachable_err', null, null, null, 'page'));
		}
		
		$cssClass = null;
		if (!$pageT->isActive() || !$pageT->getPage()->isOnline() 
				|| $pageT->getPage()->getType() != Page::TYPE_CONTENT) {
			$cssClass = 'rocket-inactive';
		}
		
		if (mb_strlen($pathStr) <= 30) {
			return new HtmlElement('span', ['class' => $cssClass], $pathStr);
		}
		
		return new HtmlElement('span', ['title' =>  $pathStr, 'class' => $cssClass], StringUtils::reduceFront($pathStr, 30, '...'));
	}
	
	
}

