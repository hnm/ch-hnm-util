<?php

namespace app\ch\hnm\util\n2n\pagination\model;

use n2n\web\http\Request;
interface Pagination {
	public function getCurrentPageNum();
	public function getNumPages();
	public function getNumVisiblePaginationEntries();
	public function showFirst();
	public function showLast();
	public function getDivider();
	public function getPreviousLabel();
	public function getNextLabel();
	public function getPath(Request $request, $pageNum);
	public function getActiveClassName();
	public function getPaginationClassName();
}

