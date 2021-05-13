<?php
namespace page\bo;

use n2n\persistence\orm\annotation\AnnoEntityListeners;
use rocket\impl\ei\component\prop\translation\Translatable;
use n2n\l10n\N2nLocale;
use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\ObjectAdapter;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use page\model\PageMonitor;

class PageContentT extends ObjectAdapter implements Translatable {
	private static function _annos(AnnoInit $ai) {
		$ai->c(new AnnoEntityListeners(PageEntityListener::getClass()));
		$ai->p('pageContent', new AnnoManyToOne(PageContent::getClass()));
	}
	
	private $id;
	private $n2nLocale;
	private $pageContent;
	private $seTitle;
	private $seDescription;
	private $seKeywords;
	
	public function __construct() {
		$this->contentItems = new \ArrayObject();
	}
	
	private function _prePersist(PageMonitor $pageMonitor) {
		$pageMonitor->registerRelatedChange($this->pageContent->getPage());
	}
	
	private function _preUpdate(PageMonitor $pageMonitor) {
		$pageMonitor->registerRelatedChange($this->pageContent->getPage());
	}
	
	private function _preRemove(PageMonitor $pageMonitor) {
		$pageMonitor->registerRelatedChange($this->pageContent->getPage());
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
	
	public function getPageContent() {
		return $this->pageContent;
	}
	
	public function setPageContent(PageContent $pageContent) {
		$this->pageContent = $pageContent;
	}
	
	public function getSeTitle() {
		return $this->seTitle;
	}

	public function setSeTitle(string $seTitle = null) {
		$this->seTitle = $seTitle;
	}

	public function getSeDescription() {
		return $this->seDescription;
	}

	public function setSeDescription(string $seDescription = null) {
		$this->seDescription = $seDescription;
	}

	public function getSeKeywords() {
		return $this->seKeywords;
	}

	public function setSeKeywords(string $seKeywords = null) {
		$this->seKeywords = $seKeywords;
	}

	public function getContentItems() {
		return $this->contentItems;
	}

	public function setContentItems(\ArrayObject $contentItems) {
		$this->contentItems = $contentItems;
	}
	
	public function hasPanel($panel) {
		foreach ($this->contentItems as $contentItem) {
			if ($contentItem->getPanel() === $panel) return true;
		}
		
		return false;
	}
}