<?php
namespace page\model\nav;

use n2n\l10n\N2nLocale;
use n2n\util\type\ArgUtils;
use n2n\util\ex\IllegalStateException;

class NavBranch {
	private $navTree;
	private $id;
	
	private $leafs = array();
	private $objAffiliationTester;
	private $tagNames = array();
	private $hookKeys = array();
	private $inPath = true;
	
	private $parent;
	private $children = array();
	private $level;
	
	public function __construct(NavTree $navTree, string $id = null) {
		$this->navTree = $navTree;
		$this->id = $id;
	}
	
	/**
	 * @return \page\model\nav\NavTree
	 */
	public function getNavTree() {
		return $this->navTree;
	}
	
	/**
	 * @param string $id
	 */
	public function setId(string $id = null) {
		$this->id = $id;
	}

	/**
	 * @return string|null
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * @param \Closure $objAffiliationTester
	 */
	public function setObjAffiliationTester(ObjAffiliationTester $objAffiliationTester = null) {
		$this->objAffiliationTester = $objAffiliationTester;
	}
	
	/**
	 * @param object $obj
	 * @return boolean
	 */
	public function isAffiliatedWith($obj) {
		ArgUtils::valObject($obj);
		return $this->objAffiliationTester !== null && $this->objAffiliationTester->isAffiliatedWith($obj);
	}

	/**
	 * @param array $tagNames
	 * @return bool
	 */
	public function containsTagNames(array $tagNames): bool {
		foreach ($tagNames as $tagName) {
			if (!in_array($tagName, $this->tagNames, true)) return false;
		}
	
		return true;
	}
	
	public function setTagNames(array $tagNames) {
		ArgUtils::valArray($tagNames, array('scalar', null));
		$this->tagNames = $tagNames;
	}
	
	public function getTagNames(): array {
		return $this->tagNames;
	}
	
	/**
	 * @param string $hookKey
	 * @return boolean
	 */
	public function containsHookKeys(array $hookKeys): bool {
		foreach ($hookKeys as $hookKey) {
			if (!in_array($hookKey, $this->hookKeys, true)) return false;
		}
		
		return true;
	}
	
	/**
	 * @return string[]
	 */
	public function getHookKeys(): array {
		return $this->hookKeys;
	}
	
	/**
	 * @param string[] $hookKeys
	 */
	public function setHookKeys(array $hookKeys) {
		ArgUtils::valArray($hookKeys, 'string');
		$this->hookKeys = $hookKeys;
	}
	
	public function setInPath(bool $inPath) {
		$this->inPath = $inPath;
	}
	
	public function isInPath(): bool {
		return $this->inPath;
	}
	
	/**
	 * @param Leaf $leaf
	 */
	public function addLeaf(Leaf $leaf) {
		$leaf->setNavBranch($this);
		$this->leafs[$leaf->getN2nLocale()->getId()] = $leaf;
	}
	
	/**
	 * @param N2nLocale $n2nLocale
	 */
	public function removeLeafByN2nLocale(N2nLocale $n2nLocale) {
		unset($this->leafs[$n2nLocale->getId()]);
	}
	
	/**
	 * @param N2nLocale $n2nLocale
	 * @return bool
	 */
	public function containsLeafN2nLocale(N2nLocale $n2nLocale) {
		return isset($this->leafs[$n2nLocale->getId()]);
	}
	
	/**
	 * @param N2nLocale $n2nLocale
	 * @throws UnavailableLeafException
	 * @return Leaf
	 */
	public function getLeafByN2nLocale(N2nLocale $n2nLocale) {
		$id = $n2nLocale->getId();
		if (isset($this->leafs[$id])) {
			return $this->leafs[$id];
		}
		
		throw new UnavailableLeafException($this . ' contains no leaf for locale: ' . $n2nLocale);
	}
	
	/**
	 * @return Leaf[]
	 */
	public function getLeafs() {
		return $this->leafs;
	}
	
	public function getParent() {
		return $this->parent;
	}
	
	public function getRoot() {
		$root = $this;
		while (null !== ($parent = $root->getParent())) {
			$root = $parent;
		}
		return $root;
	}
	
	protected function setParent(NavBranch $navBranch = null) {
		if ($this->parent === null) {
			$this->parent = $navBranch;
			return;
		}
		
		throw new IllegalStateException('NavBranch already assigned.');
	}
	
	public function getLevel() {
		if ($this->level !== null) {
			return $this->level;
		}
		
		if ($this->parent === null) {
			return 0;
		}
		
		throw new IllegalStateException('NavBranch not part of any NavTree.');
	}
	
	protected function setLevel(int $level = null) {
		$this->level = $level;
	}
	
	public function appendChild(NavBranch $navBranch) {
		$this->children[] = $navBranch;
		$navBranch->setParent($this);
		$navBranch->setLevel($this->getLevel() + 1);
	}
	
	public function prependChild(NavBranch $navBranch) {
		array_unshift($this->children, $navBranch);
		$navBranch->setParent($this);
		$navBranch->setLevel($this->getLevel() + 1);
	}
	
	public function removeChild(NavBranch $navBranch) {
		foreach ($this->children as $key => $child) {
			if ($navBranch !== $child) continue;
			
			unset($this->children[$key]);
			return;
		}
	}
	
	public function hasChildren() {
		return !empty($this->children);
	}
	
	public function getChildren() {
		return $this->children;
	}
	/**
	 * @param object $affiliatedObj
	 * @param array $tagNames
	 * @param array $hookKeys
	 * @return NavBranch or null if not found
	 */
	public function findChild($affiliatedObj = null, array $tagNames = null, array $hookKeys = null) {
		$navFilter = new NavBranchFilter($affiliatedObj, $tagNames, $hookKeys);
		return $navFilter->find($this->children);
	}
	
	/**
	 * @param object $affiliatedObj
	 * @param array $tagNames
	 * @param array $hookKeys
	 * @return NavBranch or null if not found
	 */
	public function find($affiliatedObj = null, array $tagNames = null, array $hookKeys = null) {
		$navFilter = new NavBranchFilter($affiliatedObj, $tagNames, $hookKeys);
		return $navFilter->findR($this->children);
	}
	
	/**
	 * @param object $affiliatedObj
	 * @param array $tagNames
	 * @param array $hookKeys
	 * @return \page\model\nav\NavBranch
	 */
	public function findAncestor($affiliatedObj = null, array $tagNames = null, array $hookKeys = null) {
		$navFilter = new NavBranchFilter($affiliatedObj, $tagNames, $hookKeys);
		
		$navBranch = $this;
		while (null !== ($navBranch = $navBranch->getParent())) { 
			if ($navFilter->matches($navBranch)) return $navBranch;
		}
		return null;
	}
	
	/**
	 * @param mixed $obj
	 * @return boolean
	 */
	public function equals($obj) {
		return $this === $obj;
	}
	
	/**
	 * @param NavBranch $navBranch
	 * @return boolean
	 */
	public function containsDescendant(NavBranch $navBranch) {
		foreach ($this->children as $childNavBranch) {
			if ($childNavBranch->equals($navBranch) 
					|| $childNavBranch->containsDescendant($navBranch)) {
				return true;
			}
		}
		
		return false;
	}
	
	public function __toString() {
		return 'Branch (id: ' . $this->id . ') with Leafs [' . implode(', ', $this->leafs) . ']';
	}
}
