<?php
namespace page\bo;

use n2n\persistence\orm\annotation\AnnoEntityListeners;
use n2n\reflection\ObjectAdapter;
use n2n\reflection\annotation\AnnoInit;
use n2n\persistence\orm\annotation\AnnoOneToOne;
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\persistence\orm\CascadeType;
use n2n\l10n\N2nLocale;
use rocket\impl\ei\component\prop\translation\Translator;
use n2n\persistence\orm\FetchType;
use page\model\PageMonitor;

class PageContent extends ObjectAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->c(new AnnoEntityListeners(PageEntityListener::getClass()));
		$ai->p('page', new AnnoOneToOne(Page::getClass(), 'pageContent', CascadeType::PERSIST));
		$ai->p('pageContentTs', new AnnoOneToMany(PageContentT::getClass(), 'pageContent', CascadeType::ALL, 
				null, true));
		$ai->p('pageController', new AnnoOneToOne(PageController::getClass(), null, CascadeType::ALL, 
				FetchType::EAGER, true));
	}
	
	private $id;
	private $ssl = false;
	private $page;
	private $pageContentTs;
	private $pageController;
	
	private function _prePersist(PageMonitor $pageMonitor) {
		$pageMonitor->registerRelatedChange($this->page);
	}
	
	private function _preUpdate(PageMonitor $pageMonitor) {
		$pageMonitor->registerRelatedChange($this->page);
	}
	
	private function _preRemove(PageMonitor $pageMonitor) {
		$pageMonitor->registerRelatedChange($this->page);
	}
		
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	/**
	 * @return PageController
	 */
	public function getPageController() {
		return $this->pageController;
	}

	public function setPageController(PageController $pageController) {
		$this->pageController = $pageController;
	}

	public function getPage() {
		return $this->page;
	}

	public function setPage(Page $page) {
		$this->page = $page;
	}

	/**
	 * @return PageContentT[]
	 */
	public function getPageContentTs() {
		return $this->pageContentTs;
	}

	public function setPageContentTs(\ArrayObject $pageContentTs) {
		$this->pageContentTs = $pageContentTs;
	}
	
	public function isSsl(): bool {
		return $this->ssl;
	}
	
	public function setSsl(bool $ssl) {
		$this->ssl = $ssl;
	}

	public function equals($obj) {
		return $obj instanceof PageContent && $this->id == $obj->getId();
	}

	/**
	 * @param N2nLocale ...$n2nLocales
	 * @return PageContentT
	 */
	public function t(N2nLocale ...$n2nLocales) {
		return Translator::find($this->pageContentTs, ...$n2nLocales);
	}
}