<?php
// namespace page\model;

// use n2n\core\container\N2nContext;
// use n2n\util\StringUtils;
// use rocket\impl\ei\component\prop\string\wysiwyg\WysiwygLinkConfigAdapter;
// use rocket\impl\ei\component\prop\string\wysiwyg\DynamicWysiwygLinkConfig;
// use n2n\l10n\N2nLocale;
// use page\model\nav\NavBranch;
// use n2n\util\col\ArrayUtils;

// class PageWysiwygLinkConfig extends WysiwygLinkConfigAdapter implements DynamicWysiwygLinkConfig {
	
// 	private $pageState;
// 	private $n2nContext;
	
// 	private function _init(PageState $pageState, N2nContext $n2nContext) {
// 		$this->pageState = $pageState;
// 		$this->n2nContext = $n2nContext;
// 	}
	
// 	public function getTitle() {
// 		return 'Page';
// 	}
	
// 	public function getLinkPaths(N2nLocale $n2nLocale) {
// 		$linkPaths = array();
// 		$navTree = $this->pageState->getNavTree();
		
// 		$fallbackLocale = N2nLocale::getDefault();
		
// 		foreach ($navTree->getRootNavBranches() as $rootNavBranches) {
// 			$linkPaths = array_merge($linkPaths, $this->getLinkPathsForTreeItem($rootNavBranches, 
// 					$n2nLocale, $fallbackLocale));
// 		}
		
// 		return $linkPaths;
// 	}
	
// 	private function getLinkPathsForTreeItem(NavBranch $navBranch, N2nLocale $locale, N2nLocale $fallbackLocale) {
// 		$linkPaths = array();
// 		$label = $this->buildLabel($navBranch, $locale, $fallbackLocale);
// 		$url = $this->buildUrl($navBranch);
		
// 		if (null !== $label && null !== $url) {
// 			$linkPaths[$label] = $url;
// 		}
		
// 		foreach ($navBranch->getChildren() as $childTreeItem) {
// 			$linkPaths = array_merge($linkPaths, 
// 					$this->getLinkPathsForTreeItem($childTreeItem, $locale, $fallbackLocale));
// 		}
		
// 		return $linkPaths;
// 	}
	
// 	private function buildLabel(NavBranch $navBranch, N2nLocale $n2nLocale, N2nLocale $fallbackN2nLocale) {
// 		$leaf = null;
// 		if ($navBranch->containsLeafN2nLocale($n2nLocale)) {
// 			$leaf = $navBranch->getLeafByN2nLocale($n2nLocale);
// 		} else if ($navBranch->containsLeafN2nLocale($fallbackN2nLocale)) {
// 			$leaf = $navBranch->getLeafByN2nLocale($fallbackN2nLocale);
// 		} else {
// 			$leaf = ArrayUtils::first($navBranch->getLeafs());
// 		}
		
// 		return str_repeat('.', $navBranch->getLevel()) . ($leaf !== null ? $leaf->getName() : '');
// 	}
	
// 	private function buildUrl(NavBranch $navBranch) {
// 		return StringUtils::jsonEncode(array(
// 				PageWysiwygLinkBuilder::CHARACTERISTICS_KEY_ID => $navBranch->getId()));
// 	}
	
// 	public function getLinkBuilderClass() {
// 		return PageWysiwygLinkBuilder::getClass();
// 	}
// }