<?php
namespace page\model\nav;

use n2n\util\type\ArgUtils;

class NavBranchFilter {
	private $affiliatedObj;
	private $tagNames = array();
	private $hookKeys = array();
	private $id;

	public function __construct($affiliatedObj = null, array $tagNames = null, $hookKeys = null, string $id = null) {
		$this->setAffiliatedObj($affiliatedObj);
		$this->setTagNames($tagNames);
		$this->setHookKeys($hookKeys);
		$this->id = $id;
	}

	public function setAffiliatedObj($affiliatedObj = null) {
		ArgUtils::valObject($affiliatedObj, true, 'affiliatedObj');
		$this->affiliatedObj = $affiliatedObj;
	}

	public function setTagNames(array $tagNames = null) {
		ArgUtils::valArray($tagNames, 'string', true, 'tagNames');
		$this->tagNames = (array) $tagNames;
	}

	public function setHookKeys(array $hookKeys = null) {
		ArgUtils::valArray($hookKeys, 'string', true, 'hookKeys');
		$this->hookKeys = (array) $hookKeys;
	}
	
	public function setId(string $id = null) {
		$this->id = $id;
	}
	
	public function findClosest(NavBranch $navBranch) {
		if (null !== ($foundNavBranch = $this->find($navBranch))) {
			return $foundNavBranch;
		}
		
		while (null !== ($parentNavBranch = $navBranch->getParent())) {
			if ($this->matches($parentNavBranch)) {
				return $parentNavBranch;
			}
			
			foreach ($parentNavBranch->getChildren() as $childNavBranch) {
				if ($childNavBranch === $navBranch) continue;
				
				if (null !== ($foundNavBranch = $this->find($childNavBranch))) {
					return $foundNavBranch;
				}
			}
			
			$navBranch = $parentNavBranch;
		}
		
		$navTree = $navBranch->getNavTree();
		foreach ($navTree->getRootNavBranches() as $rootNavBranch) {
			if ($rootNavBranch === $navBranch) continue;
			
			if (null !== ($foundNavBranch = $this->find($rootNavBranch))) {
				return $foundNavBranch;
			}
		}
		
		return null;
	}
	
	public function find(NavBranch $navBranch) {
		if ($this->matches($navBranch)) {
			return $navBranch;
		}
		
		if (null !== ($foundNavBranch = $this->findR($navBranch->getChildren()))) {
			return $foundNavBranch;
		}
	}
		
	public function matches(NavBranch $navBranch) {
		return ($this->affiliatedObj === null || $navBranch->isAffiliatedWith($this->affiliatedObj))
				&& (empty($this->tagNames) || $navBranch->containsTagNames($this->tagNames))
				&& (empty($this->hookKeys) || $navBranch->containsHookKeys($this->hookKeys))
				&& ($this->id === null || $navBranch->getId() === $this->id);
	}

	public function findR(array $navBranches) {
		foreach ($navBranches as $navBranch) {
			if (null !== ($foundNavBranch = $this->find($navBranch))) {
				return $foundNavBranch;
			}
		}
		
		return null;
	}
}