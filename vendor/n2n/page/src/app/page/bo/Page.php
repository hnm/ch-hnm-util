<?php
namespace page\bo;

use n2n\reflection\ObjectAdapter;
use n2n\reflection\annotation\AnnoInit;
use n2n\persistence\orm\annotation\AnnoOneToOne;
use n2n\persistence\orm\annotation\AnnoManyToOne;
use n2n\persistence\orm\annotation\AnnoOneToMany;
use n2n\persistence\orm\CascadeType;
use n2n\l10n\N2nLocale;
use rocket\impl\ei\component\prop\translation\Translator;
use page\model\leaf\ExternalLeaf;
use page\model\nav\NavTree;
use page\model\leaf\InternalLeaf;
use page\model\IllegalPageStateException;
use page\model\leaf\ContentLeaf;
use page\model\nav\NavBranch;
use page\model\nav\UnknownNavBranchException;
use page\model\NavInitProcess;
use n2n\util\type\ArgUtils;
use page\model\PageMonitor;
use n2n\persistence\orm\annotation\AnnoEntityListeners;
use n2n\util\type\CastUtils;
use page\model\leaf\EmptyLeaf;
use page\model\PageObjAffiliationTester;

class Page extends ObjectAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->c(new AnnoEntityListeners(PageEntityListener::getClass()));
		$ai->p('pageContent', new AnnoOneToOne(PageContent::getClass(), null, CascadeType::ALL, null, true));
		$ai->p('internalPage', new AnnoManyToOne(Page::getClass()));
		$ai->p('pageTs', new AnnoOneToMany(PageT::getClass(), 'page', CascadeType::ALL, null, true));
	}

	const NS = 'page';
	
	private $id;
// 	private $type;
	private $internalPage;
	private $externalUrl;
	private $pageContent;
	private $subsystemName;
	private $online = true;
	private $inPath = true;
	private $hookKey;
	private $inNavigation = true;
	private $navTargetNewWindow = false;
	private $lft;
	private $rgt;
	private $lastMod;
//	private $lastModBy;
	private $pageTs;
	private $indexable = true;

	public function __construct() {
		$this->lastMod = new \DateTime();
		$this->pageContent = new PageContent();
	}

	private function _prePersist(PageMonitor $pageMonitor) {
		$pageMonitor->registerInsert($this);
	}
	
	private function _preUpdate(PageMonitor $pageMonitor) {
		$this->lastMod = new \DateTime();
		$pageMonitor->registerUpdate($this);
	}
	
	private function _preRemove(PageMonitor $pageMonitor) {
		$pageMonitor->registerRemove($this);
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	const TYPE_EXTERNAL = 'external';
	const TYPE_INTERNAL = 'internal';
	const TYPE_CONTENT = 'content';
	
	public function getType() {
		if ($this->externalUrl !== null) {
			return self::TYPE_EXTERNAL;
		}
		
		if ($this->internalPage !== null) {
			return self::TYPE_INTERNAL;
		}
		
		return self::TYPE_CONTENT;
	}
	
	public function setType(string $type) {
		ArgUtils::valEnum($type, self::getTypes());
		
		switch ($type) {
			case self::TYPE_EXTERNAL:
				$this->internalPage = null;
				$this->pageContent = null;
				break;
			case self::TYPE_INTERNAL:
				$this->externalUrl = null;
				$this->pageContent = null;
				break;
			case self::TYPE_CONTENT:
				$this->externalUrl = null;
				$this->internalPage = null;
				break;
		}
	}
	
	public static function getTypes() {
		return array(self::TYPE_EXTERNAL, self::TYPE_INTERNAL, self::TYPE_CONTENT);
	}
	
	/**
	 * @return Page|null
	 */
	public function getInternalPage() {
		return $this->internalPage;
	}
	
	public function setInternalPage($internalPage) {
		$this->internalPage = $internalPage;
	}
	
	public function getExternalUrl() {
		return $this->externalUrl;
	}
	
	public function setExternalUrl($externalUrl) {
		$this->externalUrl = $externalUrl;
	}
	
	public function getPageContent() {
		return $this->pageContent;
	}
	
	public function setPageContent(PageContent $pageContent = null) {
		$this->pageContent = $pageContent;
	}
	
	public function getSubsystemName() {
		return $this->subsystemName;
	}
	
	public function setSubsystemName(string $subsystemName = null) {
		$this->subsystemName = $subsystemName;
	}
	
	public function isOnline(): bool {
		return $this->online;
	}
	
	public function setOnline(bool $online) {
		$this->online = $online;
	}
	
	public function isInPath(): bool {
		return $this->inPath;
	}
	
	public function setInPath(bool $inPath) {
		$this->inPath = $inPath;
	}
	
	public function isInNavigation(): bool {
		return $this->inNavigation;
	}
	
	public function setInNavigation(bool $inNavigation) {
		$this->inNavigation = $inNavigation;
	}
	
	public function isNavTargetNewWindow(): bool {
		return $this->navTargetNewWindow;
	}
	
	public function setNavTargetNewWindow(bool $targetNewWindow) {
		$this->navTargetNewWindow = $targetNewWindow;
	}
	
	public function getHookKey() {
		return $this->hookKey;
	}
	
	public function setHookKey($hookKey) {
		$this->hookKey = $hookKey;
	}
	
	public function getLft() {
		return $this->lft;
	}
	
	public function setLft($lft) {
		$this->lft = $lft;
	}
	
	public function getRgt() {
		return $this->rgt;
	}
	
	public function setRgt($rgt) {
		$this->rgt = $rgt;
	}
	/**
	 *
	 * @return \DateTime
	 */
	public function getLastMod() {
		return $this->lastMod;
	}
	
	public function setLastMod(\DateTime $lastMod = null) {
		$this->lastMod = $lastMod;
	}
	/**
	 *
	 * @return \rocket\user\bo\RocketUser
	 */
// 	public function getLastModBy() {
// 		return $this->lastModBy;
// 	}
	
// 	public function setLastModBy(RocketUser $lastModBy) {
// 		$this->lastModBy = $lastModBy;
// 	}
	
	public function getPageTs() {
		return $this->pageTs;
	}
	
	public function setPageTs(\ArrayObject $pageTs) {
		$this->pageTs = $pageTs;
	}

	public function setIndexable(bool $indexable) {
		$this->indexable = $indexable;
	}

	public function getIndexable() {
		return $this->indexable;
	}

	public function equals($obj) {
		return $obj instanceof Page && $this->id == $obj->getId();
	}
	
	/**
	 *
	 * @param N2nLocale ...$n2nLocales        	
	 * @return PageT
	 */
	public function t(N2nLocale ...$n2nLocales) {
		return Translator::findAny($this->pageTs, ...$n2nLocales);
	}
	
	/**
	 * @param NavInitProcess $navInitProcess
	 * @throws IllegalPageStateException
	 */
	public function createNavBranch(NavInitProcess $navInitProcess) {
		$navBranch = new NavBranch($navInitProcess->getNavTree(), $this->id);
		
		if ($this->hookKey !== null) {
			$navBranch->setHookKeys(array($this->hookKey));
		}
		
		$pageId = $this->getId();
		$navBranch->setObjAffiliationTester(new PageObjAffiliationTester($pageId));
		$navBranch->setInPath($this->isInPath());
		
		if ($this->externalUrl !== null) {
			$this->applyExternalLeafs($navBranch);
		} else if ($this->internalPage !== null) {
			$this->applyInternalLeafs($navBranch, $navInitProcess);
		} else if ($this->pageContent !== null) {
			$this->applyContentLeafs($navBranch);
		} else {
			$this->applyEmptyLeafs($navBranch);
		}
		
		return $navBranch; 
	}
	
	private function applyExternalLeafs(NavBranch $navBranch) {
		foreach ($this->pageTs as $pageT) {
			CastUtils::assertTrue($pageT instanceof PageT);
			
			$leaf = new ExternalLeaf($pageT->getN2nLocale(), $pageT->getName(), $this->externalUrl);
			$leaf->setAccessible($this->online && $pageT->isActive());
			$leaf->setPathPart($pageT->getPathPart());
			$leaf->setSubsystemName($pageT->getPage()->getSubsystemName());
			$leaf->setTitle($pageT->getTitle());
			$leaf->setInNavigation($leaf->isAccessible() && $this->inNavigation);
			$leaf->setTargetNewWindow($this->navTargetNewWindow);
			$navBranch->addLeaf($leaf);
			$leaf->setIndexable($this->indexable);
		}
	}
	
	private function applyInternalLeafs(NavBranch $navBranch, NavInitProcess $navInitProcess) {
		$leafs = array();
		foreach ($this->pageTs as $pageT) {
			CastUtils::assertTrue($pageT instanceof PageT);
			
			$leafs[] = $leaf = new InternalLeaf($pageT->getN2nLocale(), $pageT->getName());
			$leaf->setAccessible($this->online && $pageT->isActive());
			$leaf->setPathPart($pageT->getPathPart());
			$leaf->setSubsystemName($pageT->getPage()->getSubsystemName());
			$leaf->setTitle($pageT->getTitle());
			$leaf->setInNavigation($leaf->isAccessible() && $this->inNavigation);
			$leaf->setTargetNewWindow($this->navTargetNewWindow);
			$navBranch->addLeaf($leaf);
			$leaf->setIndexable($this->indexable);
		}
			
		$that = $this;
		$navInitProcess->onInitialized(function (NavTree $navTree) use ($that, $leafs) {
			try {
				$targetNavBranch = $navTree->get($that->internalPage);
				foreach ($leafs as $leaf) {
					$leaf->setTargetNavBranch($targetNavBranch);
				}
			} catch (UnknownNavBranchException $e) {
				throw new IllegalPageStateException('Internal link page (id: ' . $that->id 
						. ') contains invalid target.', 0, $e);
			}
		});
	}
	
	private function applyContentLeafs(NavBranch $navBranch) {
		$pageController = $this->pageContent->getPageController();
		$tagNames = $pageController->getTagNames();
		ArgUtils::valArrayReturn($tagNames, $pageController, 'getTagNames', array('scalar', null));
		$navBranch->setTagNames($tagNames);
		
		foreach ($this->pageTs as $pageT) {
			CastUtils::assertTrue($pageT instanceof PageT);
			
			$leafs[] = $leaf = new ContentLeaf($pageT->getN2nLocale(), $pageT->getName(), $this->id);
			$leaf->setAccessible($this->online && $pageT->isActive());
			$leaf->setPathPart($pageT->getPathPart());
			$leaf->setSubsystemName($pageT->getPage()->getSubsystemName());
			$leaf->setTitle($pageT->getTitle());
			$leaf->setInNavigation($leaf->isAccessible() && $this->inNavigation);
			$leaf->setTargetNewWindow($this->navTargetNewWindow);
			$leaf->setIndexable($this->indexable);
			$navBranch->addLeaf($leaf);
		}
		
		$pageController->navBranchCreated($navBranch);
	}
	
	private function applyEmptyLeafs(NavBranch $navBranch) {
		foreach ($this->pageTs as $pageT) {
			CastUtils::assertTrue($pageT instanceof PageT);
			
			$leaf = new EmptyLeaf($pageT->getN2nLocale(), $pageT->getName());
			$leaf->setAccessible($this->online && $pageT->isActive());
			$leaf->setPathPart($pageT->getPathPart());
			$leaf->setSubsystemName($pageT->getPage()->getSubsystemName());
			$leaf->setTitle($pageT->getTitle());
			$leaf->setInNavigation($leaf->isAccessible() && $this->inNavigation);
			$leaf->setTargetNewWindow($this->navTargetNewWindow);
			$leaf->setIndexable($this->indexable);
			$navBranch->addLeaf($leaf);
		}
	}
}