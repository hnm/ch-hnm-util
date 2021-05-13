<?php
namespace page\model;

use page\model\nav\ObjAffiliationTester;
use page\bo\PageController;
use page\bo\PageT;
use page\bo\PageContent;
use page\bo\Page;

class PageObjAffiliationTester implements ObjAffiliationTester {
	private $pageId;
	
	public function __construct($pageId) {
		$this->pageId = $pageId;
	}
	
	public function isAffiliatedWith($obj): bool {
		if ($obj instanceof PageController) {
			$obj = $obj->getPageContent();
		}
		
		if ($obj instanceof PageContent) {
			$obj = $obj->getPage();
		}
		
		if ($obj instanceof PageT) {
			$obj = $obj->getPage();
		}
		
		return ($obj instanceof Page && $obj->getId() === $this->pageId);
	}
}