<?php
namespace page\model;

use n2n\context\ThreadScoped;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\util\NestedSetUtils;
use page\bo\Page;
use n2n\persistence\orm\util\NestedSetStrategy;
use n2n\persistence\orm\criteria\item\CrIt;
use n2n\util\type\CastUtils;
use n2n\persistence\orm\util\NestedSetItem;
use page\model\nav\NavTree;
use n2n\core\container\AppCache;
use n2n\l10n\N2nLocale;
use page\bo\PageT;

class PageDao implements ThreadScoped {
	private $em;
	private $cacheStore;
	
	private function _init(EntityManager $em, AppCache $appCache) {
		$this->em = $em;
		$this->cacheStore = $appCache->lookupCacheStore(PageDao::class);
	}
	
	public function getHomePageTExcept(N2nLocale $n2nLocale, ?string $subsystemName, PageT $exceptPageT) {
		$criteria = $this->em->createCriteria();
		$criteria->select('pt')->from(PageT::getClass(), 'pt')
				->where(['pt.n2nLocale' => $n2nLocale, 'pt.pathPart' => null])->match('pt', '!=', $exceptPageT);
		
// 		if ($subsystemName === null) {
			$criteria->where(['pt.page.subsystemName' => $subsystemName]);	
// 		} else {
// 			$criteria->where()->andGroup()->match('pt.page.subsystemName', '=', null)
// 					->orMatch('pt.page.subsystemName', '==', $subsystemName);
// 		}
		
		return $criteria->limit(1)->toQuery()->fetchSingle();
	}
	
	public function getCachedNavTree() {
		if (null !== ($cacheItem = $this->cacheStore->get('navTree', array()))) {
			$navTree = $cacheItem->getData();			
			if ($navTree instanceof NavTree) {
				return $navTree;
			}
		}
		
		$navTree = $this->lookupNavTree();
		$this->cacheStore->store('navTree', array(), $navTree);
		
		return $navTree;
	}
	
	public function clearCache() {
		$this->cacheStore->clear();
	}
	
	/**
	 * @return \page\model\nav\NavTree
	 */
	public function lookupNavTree() {
		$navTree = new NavTree();
		$navInitProcess = new NavInitProcess($navTree);
		
		$nsUtils = new NestedSetUtils($this->em, Page::getClass(), 
				new NestedSetStrategy(CrIt::p('lft'), CrIt::p('rgt')));
		
		$levelNavBranches = array();
		
		foreach ($nsUtils->fetch() as $nestedSetItem) {
			CastUtils::assertTrue($nestedSetItem instanceof NestedSetItem);
			
			$level = $nestedSetItem->getLevel();
			$parentLevel = $level - 1;
			
			$levelNavBranches[$level] = $navBranch = $nestedSetItem->getEntityObj()
					->createNavBranch($navInitProcess);
			
			if (!isset($levelNavBranches[$parentLevel])) {
				$navTree->addRootNavBranch($navBranch);
			} else {
				$levelNavBranches[$parentLevel]->appendChild($navBranch);
			}
		}

		
		$navInitProcess->finish($navTree);
		return $navTree;
	}
	
	public function getPageById(int $id) {
		return $this->em->find(Page::getClass(), $id);
	}
}

class NavInitProcess {
	private $navTree;
	private $onInitializedClosures = array();
	
	public function __construct(NavTree $navTree) {
		$this->navTree = $navTree;
	}
	
	/**
	 * @return \page\model\nav\NavTree
	 */
	public function getNavTree() {
		return $this->navTree;
	}
	
	public function onInitialized(\Closure $closure) {
		$this->onInitializedClosures[] = $closure;
	}
	
	public function finish(NavTree $navTree) {
		while (null !== ($closure = array_shift($this->onInitializedClosures))) {
			$closure($navTree);
		}
	}
}
