// <?php
// namespace page\model\nav;

// use n2n\core\container\N2nContext;
// use page\model\PageState;
// use n2n\util\type\CastUtils;
// use n2n\util\type\ArgUtils;

// class NavBranchCriteria {
// 	const NAMED_ROOT = 'root';
// 	const NAMED_CURRENT = 'current';
	
// 	protected $name;
// 	protected $affiliatedObj;
// 	protected $tagNames;
// 	protected $hookKeys;
	
// 	public static function createNamed(string $name) {
// 		ArgUtils::valEnum($name, array(self::NAMED_ROOT, self::NAMED_CURRENT), null, true);
// 		$navBranchCriteria = new NavBranchCriteria();
// 		$navBranchCriteria->name = $name;
// 		return $navBranchCriteria;
// 	}
	
// 	public static function create($affiliatedObj, array $tagNames, array $hookKeys) {
// 		ArgUtils::valObject($affiliatedObj, true);
// 		$navBranchCriteria = new NavBranchCriteria();
// 		$navBranchCriteria->affiliatedObj = $affiliatedObj;
// 		$navBranchCriteria->tagNames = $tagNames;
// 		$navBranchCriteria->hookKeys = $hookKeys;
// 		return $navBranchCriteria;
// 	}
	
// 	/**
// 	 * @param N2nContext $n2nContext
// 	 * @return \page\model\nav\NavBranch
// 	 * @throws UnknownNavBranchException
// 	 */
// 	public function determine(N2nContext $n2nContext) {
// 		$pageState = $n2nContext->lookup(PageState::class);
// 		CastUtils::assertTrue($pageState instanceof PageState);
		
// 		if ($this->name !== null) {
// 			if ($pageState->hasCurrent()) {
// 				return $pageState->getNavTree()->getClosest($pageState->getCurrentNavBranch(), 
// 						$this->affiliatedObj, $this->tagNames, $this->hookKeys);
// 			} else {
// 				return $pageState->getNavTree()->get($this->affiliatedObj, $this->tagNames, $this->hookKeys);
// 			}
// 		}
		
// 		if ($this->name == self::NAMED_CURRENT) {
// 			return $pageState->getCurrentNavBranch();
// 		}
		
// 		if (!$pageState->hasCurrent()) {
// 			return $pageState->getNavTree()->get();
// 		}
		
// 		return $pageState->getCurrentNavBranch()->getRoot();	
// 	}
// }