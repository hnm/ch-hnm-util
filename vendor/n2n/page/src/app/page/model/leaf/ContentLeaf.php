<?php
namespace page\model\leaf;

use n2n\core\container\N2nContext;
use n2n\persistence\orm\EntityManager;
use n2n\util\type\CastUtils;
use page\bo\Page;
use n2n\l10n\N2nLocale;
use n2n\util\uri\Path;
use page\model\nav\LeafContent;
use page\model\PageControllerAnalyzer;
use page\model\nav\impl\CommonLeafContent;
use page\model\PageMethod;
use n2n\util\ex\IllegalStateException;
use page\model\nav\UnknownContentItemPanelException;
use page\model\IllegalPageStateException;
use page\model\nav\SitemapItem;
use page\model\nav\murl\MurlPage;
use page\model\PageDao;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\uri\Url;
use n2n\util\type\ArgUtils;
use n2n\util\uri\UnavailableUrlException;
use n2n\util\type\TypeUtils;

class ContentLeaf extends LeafAdapter {
	private $pageId;
	
	public function __construct(N2nLocale $n2nLocale, string $name, int $pageId) {
		parent::__construct($n2nLocale, $name);
		$this->pageId = $pageId;
	}
		
	private function lookupPageContent(N2nContext $n2nContext) {
		$em = $n2nContext->lookup(EntityManager::class);
		CastUtils::assertTrue($em instanceof EntityManager);
		
		if (null !== ($page = $em->find(Page::getClass(), $this->pageId))) {
			return $page->getPageContent();
		}
		
		$pageDao = $n2nContext->lookup(PageDao::class);
		CastUtils::assertTrue($pageDao instanceof PageDao);
		$pageDao->clearCache();
		throw new IllegalStateException('Old cache conflict. Auto clean up executed. Try again.');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\Leaf::createLeafContent($n2nContext, $cmdPath, $cmdContextPath)
	 */
	public function createLeafContent(N2nContext $n2nContext, Path $cmdPath, Path $cmdContextPath): LeafContent {
		$pageContent = $this->lookupPageContent($n2nContext);
		$pageController = $pageContent->getPageController();
		
		$analyzer = new PageControllerAnalyzer(new \ReflectionClass($pageController));
		$leafContent = new PageLeafContent($this, $cmdPath, $cmdContextPath, $pageController);
		
		$pageMethod = $analyzer->analyzeMethod($pageController->getMethodName());
		if ($pageMethod === null) {
			throw new IllegalPageStateException('Page method '
					. TypeUtils::prettyMethName(get_class($pageController), $pageController->getMethodName())
					. ' does not exist. Used in: ' . get_class($pageController) . '#' . $pageController->getId());
		}
		$leafContent->setPageMethod($pageMethod);
		
		if (null !== ($pageContentT = $pageContent->t($this->getN2nLocale()))) {
			$leafContent->setSeTitle($pageContentT->getSeTitle());
			$leafContent->setSeDescription($pageContentT->getSeDescription());
			$leafContent->setSeKeywords($pageContentT->getSeKeywords());
		}
		
		if (null !== ($pageControllerT = $pageController->pageControllerT($this->getN2nLocale()))) {
			$leafContent->setContentItems($pageControllerT->getContentItems()->getArrayCopy());
		}
		
		return $leafContent;
	}
	
	const MAGIC_SITEMAP_METHOD = '_createSitemapItems';
	
	public function createSitemapItems(N2nContext $n2nContext): array {
		$sitemapItem = null;
		$baseUrl = null;
		
		try {
			$sitemapItem = new SitemapItem(MurlPage::obj($this->navBranch)->locale($this->n2nLocale)->absolute()->toUrl($n2nContext));
		} catch (UnavailableUrlException $e) {
			return array();
		}
		
		$pageContent = $this->lookupPageContent($n2nContext);
		$pageController = $pageContent->getPageController();
		
		$class = new \ReflectionClass($pageController);
		if (!$class->hasMethod(self::MAGIC_SITEMAP_METHOD)) {
			return array($sitemapItem);
		}
		
		try {
			$baseUrl = MurlPage::obj($this->navBranch)->locale($this->n2nLocale)->absolute()->pathExt('some-path')->toUrl($n2nContext);
			$pathParts = $baseUrl->getPath()->getPathParts();
			array_pop($pathParts);
			$baseUrl = $baseUrl->chPath($baseUrl->getPath()->chPathParts($pathParts));
		} catch (UnavailableUrlException $e) {
			return array($sitemapItem);
		}
		
		$method = $class->getMethod(self::MAGIC_SITEMAP_METHOD);
		$method->setAccessible(true);
		
		$invoker = new MagicMethodInvoker($n2nContext);
		$invoker->setClassParamObject(Url::class, $baseUrl);
		$invoker->setClassParamObject(N2nLocale::class, $this->n2nLocale);
		$sitemapItems = $invoker->invoke($pageController, $method);
		ArgUtils::valArrayReturn($sitemapItems, $pageController, $method, SitemapItem::class);
		
		array_unshift($sitemapItems, $sitemapItem);
		return $sitemapItems;
	}
	

// 	private function determinePriority() {
// 		$negativeScore = 0;
		
// 		if (!$this->isHome()) {
// 			$negativeScore++;
// 		}
		
// 		$negativeScore += $this->navBranch->getLevel();
// 		$negativeScore = ($negativeScore > 9) ? 9 : $negativeScore;
				 
// 		return 1 - ($negativeScore / 10);
// 	}
	
// 	private function determineChangeFreq() {
// 		$negativeScore = 1;
    	
//     	if (!$this->isHome()) {
//     		$negativeScore++;
//     	}
    	
//     	$negativeScore += $this->navBranch->getLevel();
    	
// 		switch (ceil($negativeScore)) {
//         	case 1:
//             	return SitemapItem::CHANGE_FREQ_HOURLY;
//         	case 2:
//             	return SitemapItem::CHANGE_FREQ_DAILY;
//         	case 3:
//             	return SitemapItem::CHANGE_FREQ_WEEKLY;
//         	case 4:
//             	return SitemapItem::CHANGE_FREQ_MONTHLY;
//         	default:
//             	return SitemapItem::CHANGE_FREQ_YEARLY;
//         }
//     }
	    
}

class PageLeafContent extends CommonLeafContent {
	private $pageMethod;
	private $contentItems = array();
	
	public function setPageMethod(PageMethod $pageMethod) {
		$this->pageMethod = $pageMethod;
	}
	
	public function getPageMethod() {
		IllegalStateException::assertTrue($this->pageMethod !== null);
		return $this->pageMethod;
	}
	
	public function setContentItems(array $contentItems) {
		$this->contentItems = $contentItems;
	}
	
	/**
	 * 
	 * @return \rocket\impl\ei\component\prop\ci\model\ContentItem[]
	 */
	public function getContentItems() {
		return $this->contentItems;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\impl\CommonLeafContent::getContentItemPanelNames()
	 */
	public function getContentItemPanelNames(): array {
		return $this->getPageMethod()->getCiPanelNames();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\impl\CommonLeafContent::containsContentItemPanelName()
	 */
	public function containsContentItemPanelName(string $panelName): bool {
		return $this->getPageMethod()->containsCiPanelName($panelName);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\impl\CommonLeafContent::getContentItemsByPanelName()
	 */
	public function getContentItemsByPanelName(string $panelName): array {
		if (!$this->containsContentItemPanelName($panelName)) {
			$pageController = $this->getControllerContext()->getController();
			throw new UnknownContentItemPanelException('Undefined ContentItem panel \'' . $panelName . '\' for ' 
					. TypeUtils::prettyMethName(get_class($pageController), 
							$pageController->getMethodName()));
		}
		
		$contentItems = array();
		foreach ($this->contentItems as $contentItem) {
			if ($contentItem->getPanel() === $panelName) {
				$contentItems[] = $contentItem;
			}
		}
		return $contentItems;
	}
	
}


