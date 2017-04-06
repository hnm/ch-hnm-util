<?php
namespace ch\hnm\util\n2n\pagination\model;

class SimplePagination extends PaginationAdapter {

	private $currentPageNum;
	private $numPages;
	
	public function __construct($currentPageNum, $numPages, $numVisiblePaginationEntries = null) {
		if ($numPages > 0 && $currentPageNum > $numPages) {
			throw new InvalidPageException('Invalid Page Num: ' . $currentPageNum . '. Total Pages: ' . $numPages);
		} 
		parent::__construct($numVisiblePaginationEntries);
		$this->currentPageNum = $currentPageNum;
		$this->numPages = $numPages;
	}
	
	public function getCurrentPageNum() {
		return $this->currentPageNum;
	}

	public function setCurrentPageNum($currentPageNum) {
		$this->currentPageNum = $currentPageNum;
	}

	public function getNumPages() {
		return $this->numPages;
	}

	public function setNumPages($numPages) {
		$this->numPages = $numPages;
	}
}

