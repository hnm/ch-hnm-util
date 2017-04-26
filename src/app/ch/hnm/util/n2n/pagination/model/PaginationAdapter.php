<?php
namespace ch\hnm\util\n2n\pagination\model;

use n2n\web\http\Request;
abstract class PaginationAdapter implements Pagination {
	const DEFAULT_NUM_VISIBLE_PAGINATION_ENTRIES = 5;
	const DEFAULT_DIVIDER = '...';
	
	private $numVisiblePaginationEntries = self::DEFAULT_NUM_VISIBLE_PAGINATION_ENTRIES;
	
	public function __construct($numVisiblePaginationEntries = null) {
		if (null !== $numVisiblePaginationEntries) {
			$this->numVisiblePaginationEntries = $numVisiblePaginationEntries;
		}
	}
	
	public function getNumVisiblePaginationEntries() {
		return $this->numVisiblePaginationEntries;
	}
	
	public function showFirst() {
		return true;
	}
	
	public function showLast()  {
		return true;
	}
	
	public function getDivider() {
		return self::DEFAULT_DIVIDER;
	}
	
	public function getPath(Request $request, $pageNum) {
		$cmds = $request->getCmdPath()->getPathParts();
	
		if ($this->getCurrentPageNum() !== 1) {
			array_pop($cmds);
		}
		if (!($pageNum == 1)) {
			array_push($cmds, $pageNum);
		}
		return $request->getContextPath()->ext(array($cmds))->toUrl($_GET);
	}
	
	public function getActiveClassName() {
		return 'active';
	}
	
	public function getPaginationClassName() {
		return 'pagination';
	}
	
	public function getPreviousLabel() {
		return '«';
	}
	
	public function getNextLabel() {
		return '»';
	}
}
