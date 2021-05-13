<?php
namespace page\model;

use n2n\core\container\N2nContext;
use n2n\util\type\ArgUtils;
use page\model\nav\Leaf;
use page\model\nav\NavBranch;
use n2n\l10n\N2nLocale;
use page\model\nav\UnavailableLeafException;
use page\model\nav\UnknownNavBranchException;

class NavBranchCriteria {
	const NAMED_ROOT = 'root';
	const NAMED_NAV_ROOT = 'navRoot';
	const NAMED_CURRENT = 'current';
	const NAMED_HOME = 'home';
	const NAMED_SUBHOME = 'subhome';
	
	protected $name;
	protected $affiliatedObj;
	protected $tagNames;
	protected $hookKeys;
	protected $id;
	protected $subsystemName;
	
	public static function createNamed(string $name) {
		ArgUtils::valEnum($name, array(self::NAMED_ROOT, self::NAMED_NAV_ROOT, self::NAMED_CURRENT, self::NAMED_HOME), null, true);
		$navBranchCriteria = new NavBranchCriteria();
		$navBranchCriteria->name = $name;
		return $navBranchCriteria;
	}
	
	public static function createSubHome(string $subsystemName = null) {
		$navBranchCriteria = new NavBranchCriteria();
		$navBranchCriteria->name = self::NAMED_SUBHOME;
		$navBranchCriteria->subsystemName = $subsystemName;
		return $navBranchCriteria;
	}
	
	public static function create($affiliatedObj = null, array $tagNames = null, array $hookKeys = null, string $id = null) {
		ArgUtils::valObject($affiliatedObj, true);
		$navBranchCriteria = new NavBranchCriteria();
		$navBranchCriteria->affiliatedObj = $affiliatedObj;
		$navBranchCriteria->tagNames = $tagNames;
		$navBranchCriteria->hookKeys = $hookKeys;
		$navBranchCriteria->id = $id;
		return $navBranchCriteria;
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @return \page\model\nav\NavBranch
	 * @throws UnknownNavBranchException
	 */
	public function determine(PageState $pageState, N2nLocale &$n2nLocale, N2nContext $n2nContext) {
		if ($this->name === null) {
			if ($this->affiliatedObj instanceof NavBranch) {
				return $this->affiliatedObj;
			} else if ($this->affiliatedObj instanceof Leaf) {
				$n2nLocale = $this->affiliatedObj->getN2nLocale();
				return $this->affiliatedObj->getNavBranch();
			} else if ($pageState->hasCurrent()) {
				return $pageState->getNavTree()->getClosest($pageState->getCurrentNavBranch(), 
						$this->affiliatedObj, $this->tagNames, $this->hookKeys, $this->id);
			} else {
				return $pageState->getNavTree()->get($this->affiliatedObj, $this->tagNames, $this->hookKeys, $this->id);
			}
		}
		
		try {
			switch ($this->name) {
				case self::NAMED_CURRENT:
					try {
						return $pageState->getCurrentNavBranch();
					} catch (IllegalPageStateException $e) {
						throw new UnknownNavBranchException('No nav branche active.', 0, $e);
					}
				case self::NAMED_HOME:
					$subsystemName = null;
					if (null !== ($subsystem = $n2nContext->getHttpContext()->getRequest()->getSubsystem())) {
						$subsystemName = $subsystem->getName();
					}
					return $pageState->getNavTree()->getHomeLeaf($n2nLocale, $subsystemName)->getNavBranch();
				case self::NAMED_SUBHOME:
					return $pageState->getNavTree()->getHomeLeaf($n2nLocale, $this->subsystemName)->getNavBranch();
				case self::NAMED_NAV_ROOT:
					if (null !== ($navBranch = $this->determineNavRoot($pageState->getCurrentNavBranch(), 
							$n2nLocale))) {
						return $navBranch;
					}
					
					throw new UnknownNavBranchException('No nav root found for NavBranch: ' 
							. $pageState->getCurrentNavBranch());
			}
		} catch (UnavailableLeafException $e) {
			throw new UnknownNavBranchException(null, 0, $e);
		}
		
		if (!$pageState->hasCurrent()) {
			return $pageState->getNavTree()->get();
		}
		
		return $pageState->getCurrentNavBranch()->getRoot();	
	}
	
	/**
	 * @param NavBranch $navBranch
	 * @param N2nLocale $n2nLocale
	 * @return NULL|\page\model\nav\NavBranch
	 */
	private function determineNavRoot(NavBranch $navBranch, N2nLocale $n2nLocale) {
		$navRootNavBranch = null;
		do {
			if (!$navBranch->containsLeafN2nLocale($n2nLocale)) continue;
			
			if ($navBranch->getLeafByN2nLocale($n2nLocale)->isInNavigation()) {
				$navRootNavBranch = $navBranch;
			}
		} while (null !== ($navBranch = $navBranch->getParent()));
		
		return $navRootNavBranch;
	}
}