<?php
namespace page\controller;

use rocket\ei\manage\preview\controller\PreviewControllerAdapter;
use n2n\util\type\CastUtils;
use page\bo\Page;
use n2n\l10n\N2nLocale;
use page\model\PageState;
use page\model\NavInitProcess;
use n2n\util\uri\Path;
use page\model\nav\UnavailableLeafException;
use n2n\web\http\PageNotFoundException;
use rocket\ei\util\Eiu;

class PagePreviewController extends PreviewControllerAdapter {

	public function getPreviewTypeOptions(Eiu $eiu): array {
		$page = $eiu->entry()->getEntityObj();
		CastUtils::assertTrue($page instanceof Page);
		
		$pageContent = $page->getPageContent();
		if ($pageContent === null) return array();
		
		$options = array();
		foreach ($page->getPageTs() as $pageT) {
			$n2nLocale = $pageT->getN2nLocale();
			$options[(string) $n2nLocale] = $n2nLocale->getName($eiu->frame()->getN2nLocale());
		}
		return $options;
	}
	
	public function index(PageState $pageState, Path $cmdPath, Path $cmdContextPath, array $params = null) {
		$page = $this->eiu()->object()->getEntityObj();
		CastUtils::assertTrue($page instanceof Page);
		
		$pageContent = $page->getPageContent();
		if ($pageContent === null) {
			return;
		}
		
		$n2nLocale = N2nLocale::create($this->getPreviewType());
		$this->getN2nContext()->setN2nLocale($n2nLocale);
		
		$navInitProcess = new NavInitProcess($pageState->getNavTree());
		$navBranch = $page->createNavBranch($navInitProcess);
		
		$leafContent = null;
		try {
			$leafContent = $navBranch->getLeafByN2nLocale($n2nLocale)
					->createLeafContent($this->getN2nContext(), $cmdPath, $cmdContextPath);
		} catch (UnavailableLeafException $e) {
			throw new PageNotFoundException('Preview unavailable.', null, $e);	
		}
		
		$this->getResponse()->setHttpCachingEnabled(false);
		
		$pageState->setCurrentLeafContent($leafContent);
		$this->delegateToControllerContext($leafContent->getControllerContext());
	}
}