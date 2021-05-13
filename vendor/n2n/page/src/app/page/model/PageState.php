<?php
namespace page\model;

use n2n\context\RequestScoped;
use page\model\nav\NavTree;
use n2n\core\container\N2nContext;
use page\model\nav\LeafContent;
use n2n\core\N2N;

/**
 * state
 *
 */
class PageState implements RequestScoped {
	private $pageDao;
	private $n2nContext;
	private $navTree;
	private $leafContent;
	
	private function _init(PageDao $pageDao, N2nContext $n2nContext) {
		$this->pageDao = $pageDao;
		$this->n2nContext = $n2nContext;
	}
	
	/**
	 * @return NavTree
	 */
	public function getNavTree(): NavTree {
		if ($this->navTree === null) {
			if (!N2N::isDevelopmentModeOn()) {
				$this->navTree = $this->pageDao->getCachedNavTree();
			} else {
				$this->navTree = $this->pageDao->lookupNavTree();
			}
						
		}
		
		return $this->navTree;
	}	
	
	public function hasCurrent() {
		return $this->leafContent !== null;
	}
	
	public function setCurrentLeafContent(LeafContent $leafContent = null) {
		$this->leafContent = $leafContent;
	}
	
	/**
	 * @return \page\model\nav\LeafContent|null
	 * @throws IllegalPageStateException
	 */
	public function getCurrentLeafContent(bool $required = true) {
		if ($this->leafContent !== null) {
			return $this->leafContent;
		}
		
		if (!$required) return null;
		
		throw new IllegalPageStateException('No current LeafContent assigned.');
	}
	
	/**
	 * @param bool $required
	 * @return \page\model\nav\Leaf
	 * @throws IllegalPageStateException
	 */
	public function getCurrentLeaf(bool $required = true) {
		if (null !== ($leafContent = $this->getCurrentLeafContent($required))) {
			return $leafContent->getLeaf();
		}
	
		return null;
	}
	
	/**
	 * @param bool $required
	 * @return \page\model\nav\NavBranch
	 * @throws IllegalPageStateException
	 */
	public function getCurrentNavBranch(bool $required = true) {
		if (null !== ($leaf = $this->getCurrentLeaf($required))) {
			return $leaf->getNavBranch();
		}
		
		return null;
	}
}
