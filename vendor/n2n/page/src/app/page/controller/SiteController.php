<?php
namespace page\controller;

use n2n\web\http\controller\ControllerAdapter;
use page\model\PageState;
use n2n\util\type\CastUtils;
use page\config\PageConfig;
use n2n\util\uri\Path;
use n2n\l10n\IllegalN2nLocaleFormatException;
use n2n\web\http\PageNotFoundException;
use n2n\reflection\annotation\AnnoInit;
use n2n\context\RequestScoped;
use n2n\web\http\annotation\AnnoPath;

class SiteController extends ControllerAdapter implements RequestScoped {
	private static function _annos(AnnoInit $ai) {
// 		$ai->p('n2nLocaleRedirected', new AnnoSessionScoped());
		$ai->m('sitemap', new AnnoPath('/sitemap.xml'));
	}
	
// 	private $n2nLocaleRedirected = false;
	
	private $pageState;
	private $pageConfig;
	
	public function prepare(PageState $pageState) {
		$this->pageState = $pageState;
		$this->pageConfig = $this->getN2nContext()->getModuleConfig('page');
		CastUtils::assertTrue($this->pageConfig instanceof PageConfig);
	}
	
	public function index(Path $cmdPath, Path $cmdContextPath, array $params = null) {
		$leafContents = $this->determineLeafContents($cmdPath, $cmdContextPath);
		if ($leafContents === null) return;
		
		$controllingPlan = $this->getControllingPlan();
		foreach ($leafContents as $leafContent) {
			$controllingPlan->addMain($leafContent->getControllerContext(), true);
			$this->pageState->setCurrentLeafContent($leafContent);
			if ($controllingPlan->executeNextMain(true)) {
				return;
			}
			$this->pageState->setCurrentLeafContent(null);
		}
		
		throw new PageNotFoundException();
	}
	
	private function determineLeafContents(Path $cmdPath, Path $cmdContextPath) {
		if (!$this->pageConfig->areN2nLocaleUrlsActive()) {
			return $this->createLeafResults($cmdPath, $cmdContextPath, false);
		}

		if ($cmdPath->isEmpty()) {
// 			if ($this->n2nLocaleRedirect()) return null;
			
			$this->getRequest()->setN2nLocale($this->getHttpContext()->getMainN2nLocale());
			return $this->createLeafResults($cmdPath, $cmdContextPath, true);
		}
		
		$n2nLocale = null;
		try {
			$n2nLocale = $this->getHttpContext()->httpIdToN2nLocale($cmdPath->getFirstPathPart(), false);
		} catch (IllegalN2nLocaleFormatException $e) {
			throw new PageNotFoundException(null, 0, $e);
		}
		
		if ($n2nLocale->equals($this->getHttpContext()->getMainN2nLocale()) && $cmdPath->size() <= 1) {
			throw new PageNotFoundException();
		}
		
		if ($this->getHttpContext()->containsContextN2nLocale($n2nLocale)) {
			$this->getRequest()->setN2nLocale($n2nLocale);
			return $this->createLeafResults($cmdPath->sub(1), $cmdContextPath->ext($cmdPath->sub(0, 1)), false);
		}
		
		throw new PageNotFoundException();
	}
		
	private function createLeafResults(Path $cmdPath, Path $cmdContextPath, bool $homeOnly) {
		$n2nLocale = $this->getRequest()->getN2nLocale();
		
		$subsystemName = null;
		if (null !== ($subsystem = $this->getRequest()->getSubsystem())) {
			$subsystemName = $subsystem->getName();
		}
		
		return $this->pageState->getNavTree()->createLeafContents(
				$this->getN2nContext(), $cmdPath, $cmdContextPath, $n2nLocale, $subsystemName, $homeOnly);
	}
	
// 	private function n2nLocaleRedirect() {
// 		if ($this->pageConfig->isAutoN2nLocaleRedirectAllowed() || $this->n2nLocaleRedirected
// 				|| $this->getHttpContext()->getMainN2nLocale()->equals($this->getRequest()->getN2nLocale())) {
// 			return false;
// 		}
		
// 		$n2nLocale = $this->getRequest()->getN2nLocale();
// 		if ($this->pageState->getNavTree()->containsHomeLeafN2nLocale($n2nLocale)) {
// 			$this->redirectToController($this->getHttpContext()->n2nLocaleToHttpId());
// 			return true;
// 		}
		
// 		return false;
// 	}
	
	public function sitemap() {
		$this->forward('..\view\sitemap.xml', array('sitemapItems' => $this->pageState->getNavTree()
				->createSitemapItems($this->getN2nContext(),  $this->getRequest()->getSubsystem())));
	}
}