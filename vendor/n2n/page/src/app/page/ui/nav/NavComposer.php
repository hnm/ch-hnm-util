<?php
namespace page\ui\nav;

use page\model\nav\NavBranch;
use n2n\util\ex\IllegalStateException;
use page\model\NavBranchCriteria;
use n2n\web\ui\UiComponent;
use n2n\util\type\ArgUtils;
use page\ui\nav\impl\CommonNavItemBuilder;
use n2n\web\ui\UiException;
use n2n\impl\web\ui\view\html\HtmlView;
use page\model\PageState;
use n2n\util\type\CastUtils;

/**
 * Created by {@link Nav} and used to describe navigations in a fluid way. It is usually passed to 
 * {@link \page\ui\PageHtmlBuilder::navigation()} to build the navigation.
 */
class NavComposer {
	private $navBranchCriteria;
	
	private $parentIncluded = false;
	private $relStartLevelNo;
	private $absStartlevelNo;
	private $numLevels;
	private $numOpenLevels = 0;
	private $navItemBuilder;
	
	/**
	 * Use {@link Nav} to create a NavComposer. Don't call this constructor manually.
	 * @param NavBranchCriteria $navBranchCriteria Will be used as base.
	 */
	public function __construct(NavBranchCriteria $navBranchCriteria) {
		$this->navBranchCriteria = $navBranchCriteria;
	}
	
	/**
	 * Default is false.
	 * @param bool $parentIncluded
	 * @return \page\ui\nav\NavComposer
	 */
	public function includeParent(bool $parentIncluded = true): NavComposer {
		$this->parentIncluded = $parentIncluded;
		return $this;
	}
	
	/**
	 * Specifies the first navigation level absolute to the root page
	 * (e.g. <code>Nav::root()->absStartLevel(2)</code>).
	 * 
	 * @param int $absStartLevelNo
	 * @return \page\ui\nav\NavComposer
	 */
	public function absStartLevel(int $absStartLevelNo = null) {
		if ($this->relStartLevelNo !== null) {
			throw new IllegalStateException('Relative start level already defined.');
		}
		
		$this->absStartlevelNo = $absStartLevelNo;
		return $this;
	}
	
	/**
	 * Specifies the first navigation level relative to the base page 
	 * (e.g. <code>Nav::current()->relStartLevel(-1)</code>).
	 * 
	 * @param int $relStartLevelNo
	 * @return \page\ui\nav\NavComposer
	 */
	public function relStartLevel(int $relStartLevel = null) {
		if ($this->absStartlevelNo !== null) {
			throw new IllegalStateException('Absolute start level already defined.');
		}
			
		$this->relStartLevelNo = $relStartLevel;
		return $this;
		
	}
	
	/**
	 * Restricts the maximum number of navigation levels.
	 * 
	 * @param int $numLevels or null for no maximum. 
	 * @return \page\ui\nav\NavComposer
	 */
	public function levels(int $numLevels = null): NavComposer {
		$this->numLevels = $numLevels;
		return $this;
	}
	
	/**
	 * This method is used in combination with {@link NavComposer::levels()} and advises the NavComposer to include 
	 * active and open navigation items even if they are outside of the range defined be {@link NavComposer::levels()}.
	 * 
	 * Default is 0
	 * 
	 * @param mixed $numOpenLevels num open level or null if active or open navigation items should be included.
	 * @return \page\ui\nav\NavComposer
	 */
	public function openLevels(int $numOpenLevels = null): NavComposer {
		$this->numOpenLevels = $numOpenLevels;
		return $this;
	}
	
	/**
	 * Specifies the {@link NavItemBuilder} for this navigation. If you want to build a custom navigation, create your
	 * own NavItemBuilder that inherits from {@see NavItemBuilderAdapter}
	 * 
	 * @param mixed $navItemBuilder object of {@link NavItemBuilder} or its lookup id as string. 
	 * @return \page\ui\nav\NavComposer
	 */
	public function builder($navItemBuilder) {
		ArgUtils::valType($navItemBuilder, array(NavItemBuilder::class, 'string'));
		$this->navItemBuilder = $navItemBuilder;
		return $this;
	}
	
	
	/**
	 * Usally invoked by {@link \page\ui\PageHtmlBuilder::navigation()} to build an UiComponent of the 
	 * described navigation.
	 * 
	 * @param HtmlView $view
	 * @param array $attrs
	 * @param array $ulAttrs
	 * @param array $liAttrs
	 * @throws UiException
	 * @return UiComponent
	 */
	public function build(HtmlView $view, array $attrs = null, array $ulAttrs = null, array $liAttrs = null, array $aAttrs = null) {
		$pageState = $view->lookup(PageState::class);
		CastUtils::assertTrue($pageState instanceof PageState);
		
		$navItemBuilder = null;
		if ($this->navItemBuilder === null) {
			$navItemBuilder = new CommonNavItemBuilder();
		} else if ($this->navItemBuilder instanceof NavItemBuilder) {
			$navItemBuilder = $this->navItemBuilder;
		} else {
			$navItemBuilder = $view->lookup($this->navItemBuilder);
			if (!($navItemBuilder instanceof NavItemBuilder)) {
				throw new UiException('Invalid NavItemBuilder: ' . $navItemBuilder);
			}
		}
		
		$navFactory = new NavFactory($navItemBuilder, $view->getN2nLocale(), $this->numLevels, $this->numOpenLevels);
		if ($pageState->hasCurrent()) {
			$navFactory->setCurrentNavBranch($pageState->getCurrentNavBranch());
		}
		$navFactory->setNumLevels($this->numLevels);
		$navFactory->setNumOpenLevels($this->numOpenLevels);
		$navFactory->setRootUlAttrs((array) $attrs);
		$navFactory->setUlAttrs((array) $ulAttrs);
		$navFactory->setLiAttrs((array) $liAttrs);
		$navFactory->setAAttrs((array) $aAttrs);
		
		$n2nLocale = $view->getN2nLocale();
		$navBranch = $this->navBranchCriteria->determine($pageState, $n2nLocale, $view->getN2nContext());
		$startLevel = 1;
		if ($this->absStartlevelNo !== null) {
			$startLevel = $this->absStartlevelNo - $navBranch->getLevel();
		} else if ($this->relStartLevelNo !== null) {
			$startLevel = $this->relStartLevelNo;
		}
		
		return $navFactory->create($view, $this->dertermineBases($navBranch, $startLevel));
	}
	
	/**
	 * @param NavBranch $navBranch
	 * @param int $startLevel
	 * @return \page\model\nav\NavBranch[]
	 */
	private function dertermineBases(NavBranch $navBranch, int $startLevel) {
		if ($startLevel < 0) {
			for ($i = 0; $i >= $startLevel; $i--) {
				$parentNavBranch = $navBranch->getParent();
				if ($parentNavBranch === null) {
					return $navBranch->getNavTree()->getRootNavBranches();
				}
				$navBranch = $parentNavBranch;
			}
			
			if ($this->parentIncluded) {
				return array($navBranch);
			}
			
			return $navBranch->getChildren();
		}
		
		if ($startLevel == 0) {
			$parentNavBranch = $navBranch->getParent();
			if ($parentNavBranch === null) return array($navBranch);
			if ($this->parentIncluded) {
				return array($parentNavBranch);
			}
			return $parentNavBranch->getChildren();
		}
	
	
		if ($this->parentIncluded) {
			$startLevel--;
		}
		
		$baseNavBranches = array($navBranch);
		for ($i = 0; $i < $startLevel; $i++) {
			$nextNavBranches = array();
			foreach ($baseNavBranches as $baseNavBranch) {
				$nextNavBranches = array_merge($nextNavBranches, $baseNavBranch->getChildren());
			}
			$baseNavBranches = $nextNavBranches;
		}
		return $baseNavBranches;
	}
}
