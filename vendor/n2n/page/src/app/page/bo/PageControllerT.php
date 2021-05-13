<?php
namespace page\bo;

use n2n\reflection\ObjectAdapter;
use n2n\reflection\annotation\AnnoInit;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\persistence\orm\annotation\AnnoOrderBy;
use rocket\impl\ei\component\prop\ci\model\ContentItem;
use n2n\persistence\orm\CascadeType;
use n2n\l10n\N2nLocale;
use rocket\impl\ei\component\prop\translation\Translatable;
use n2n\persistence\orm\FetchType;
use page\model\PageMonitor;
use n2n\persistence\orm\annotation\AnnoEntityListeners;

class PageControllerT extends ObjectAdapter implements Translatable {
	private static function _annos(AnnoInit $ai) {
		$ai->c(new AnnoEntityListeners(PageEntityListener::getClass()));
		$ai->p('pageController', new AnnoManyToOne(PageController::getClass(), null, FetchType::EAGER));
		$ai->p('contentItems', new AnnoOneToMany(ContentItem::getClass(), null, CascadeType::ALL, null, true),
				new AnnoOrderBy(array('orderIndex' => 'ASC')));
	}
	
	private $id;
	private $n2nLocale;
	private $pageController;
	private $contentItems;

	private function _prePersist(PageMonitor $pageMonitor) {
		if (null !== ($pageContent = $this->pageController->getPageContent())) {
			$pageMonitor->registerRelatedChange($pageContent->getPage());
		}
	}
	
	private function _preUpdate(PageMonitor $pageMonitor) {
		if (null !== ($pageContent = $this->pageController->getPageContent())) {
			$pageMonitor->registerRelatedChange($pageContent->getPage());
		}
	}
	
	private function _preRemove(PageMonitor $pageMonitor) {
		if (null !== ($pageContent = $this->pageController->getPageContent())) {
			$pageMonitor->registerRelatedChange($pageContent->getPage());
		}
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function getN2nLocale() {
		return $this->n2nLocale;
	}
	
	public function setN2nLocale(N2nLocale $n2nLocale) {
		$this->n2nLocale = $n2nLocale;
	}
	
	public function getPageController() {
		return $this->pageController;
	}
	
	public function setPageController(PageController $pageController) {
		$this->pageController = $pageController;
	}
	
	public function getContentItems() {
		return $this->contentItems;
	}
	
	public function setContentItems(\ArrayObject $contentItems) {
		$this->contentItems = $contentItems;
	}	
}