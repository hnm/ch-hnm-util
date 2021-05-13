<?php
namespace page\bo;

use n2n\reflection\ObjectAdapter;
use page\model\PageMonitor;

class PageEntityListener extends ObjectAdapter {
	
	public function _postInsert(PageMonitor $pageMonitor) {
		$pageMonitor->flush();
	}
	
	public function _postUpdate(PageMonitor $pageMonitor) {
		$pageMonitor->flush();
	}
	
	public function _postRemove(PageMonitor $pageMonitor) {
		$pageMonitor->flush();
	}
}