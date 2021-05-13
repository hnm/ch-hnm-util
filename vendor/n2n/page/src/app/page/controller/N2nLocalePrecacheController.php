<?php
namespace page\controller;

use n2n\web\http\controller\ControllerAdapter;
use n2n\util\type\CastUtils;
use page\config\PageConfig;
use page\model\PageState;
use n2n\context\annotation\AnnoSessionScoped;
use n2n\reflection\annotation\AnnoInit;
use n2n\context\RequestScoped;

class N2nLocalePrecacheController extends ControllerAdapter implements RequestScoped {
	private static function _annos(AnnoInit $ai) {
		$ai->p('n2nLocaleRedirected', new AnnoSessionScoped());
	}
	
	private $n2nLocaleRedirected = false;

	function index() {
		$this->pageConfig = $this->getN2nContext()->getModuleConfig('page');
		CastUtils::assertTrue($this->pageConfig instanceof PageConfig);
		
		if (!$this->pageConfig->isAutoN2nLocaleRedirectAllowed() || $this->n2nLocaleRedirected
				|| $this->getHttpContext()->getMainN2nLocale()->equals($this->getRequest()->getN2nLocale())) {
			return;
		}
		
		$pageState = $this->getN2nContext()->lookup(PageState::class);
		CastUtils::assertTrue($pageState instanceof PageState);
		$n2nLocale = $this->getRequest()->getN2nLocale();
		
		$subsystem = $this->getHttpContext()->getRequest()->getSubsystem();
		
		if (null === $pageState->getNavTree()->findHomeLeaf($n2nLocale, 
				($subsystem !== null ? $subsystem->getName() : null))) {
			return;
		}
		
		$this->redirectToController($this->getHttpContext()->n2nLocaleToHttpId($n2nLocale));
		$this->n2nLocaleRedirected = true;
		$this->getControllingPlan()->abort();
	}
}