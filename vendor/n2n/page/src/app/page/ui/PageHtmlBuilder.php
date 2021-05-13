<?php
namespace page\ui;

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\l10n\N2nLocale;
use n2n\impl\web\ui\view\html\HtmlSnippet;
use page\ui\nav\NavComposer;
use page\ui\nav\Nav;
use n2n\impl\web\ui\view\html\HtmlElement;
use page\model\nav\murl\MurlPage;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\web\ui\UiComponent;

/**
 * PageHtmlBuilder provides methods for simple html output in views dependent on the state of the site.
 * It looks up {@link \page\model\PageState} to determine the current page.
 */
class PageHtmlBuilder {
	private $view;
	private $meta;
	private $pageState;
	
	/**
	 * <p>You can instance PageHtmlBuilder in every {@link https://support.n2n.rocks/en/n2n/docs/html HtmlView} by 
	 * using <code>$pageHtml = new PageHtmlBuilder($view);</code></p>
	 * 
	 * @param HtmlView $view
	 */
	public function __construct(HtmlView $view) {
		$this->view = $view;
		$this->meta = new PageHtmlBuilderMeta($view);
	}
	
	/**
	 * @return \page\ui\PageHtmlBuilderMeta
	 */
	public function meta() {
		return $this->meta;
	}
	
	/**
	 * Prints the html encoded title of the current page or the page name specified in app.ini if there is no 
	 * current page.
	 * @param string|UiComponent|null $overwriteTitle prints just passed value if not null 
	 */
	public function title($overwriteTitle = null) {
		$this->view->out($this->getTitle($overwriteTitle));
	}
	
	/**
	 * Same as {@link PageHtmlBuilder::title()} but returns the output.
	 * @return \n2n\web\ui\UiComponent
	 */
	public function getTitle($overwriteTitle = null) {
		if (null !== $overwriteTitle) {
			return $this->view->getHtmlBuilder()->getOut($overwriteTitle);
		}
		return $this->view->getHtmlBuilder()->getEsc($this->meta->getTitle());
	}
	
	/**
	 * <p>Prints the content items of the current page which are assigned to the panel with passed name.</p>
	 *
	 * <pre>
	 * &lt;div&gt;&lt;?php $pageHtml-&gt;contentItems('main') ?&gt;&lt;/div&gt;
	 * </pre>
	 * 
	 * @see PageHtmlBuilderMeta::getContentItems()
	 * 
	 * @param string $panelName
	 */
	public function contentItems(string $panelName) {
		foreach ($this->meta()->getContentItems($panelName) as $contentItem) {
			$this->view->out($contentItem->createUiComponent($this->view));
		}		
	}
	
/**
	 * Same as {@link PageHtmlBuilder::contentItems()} but returns the output.
	 * @return \n2n\web\ui\UiComponent|null
	 */
	public function getContentItems(string $panelName) {
		$contentItems = $this->meta()->getContentItems($panelName);
		if (empty($contentItems)) return null;
		
		$htmlSnippet = new HtmlSnippet();
		foreach ($contentItems as $contentItem) {
			$htmlSnippet->appendLn($contentItem->createUiComponent($this->view));
		}
		return $htmlSnippet;
	}
	
	/**
	 * <p>Prints a customized navigation.</p>
	 * 
	 * <p>
	 * <strong>Usage example</strong>
	 * <pre>
	 * &lt;?php $pageHtml-&gt;navigation(Nav::root()-&gt;levels(2), array('id' =&gt; 'main-navigation')) ?&gt;
	 * </pre>
	 * </p>
	 * 
	 * @param NavComposer $navComposer <p>Use {@link \page\ui\nav\Nav} to build a suitable {@link NavComposer}. 
	 * If you pass null, the default {@link NavComposer} will be used (<code>Nav::root()</code>).</p>
	 * 
	 * <p>See {@link \page\ui\nav\Nav} for further information.</p>
	 * 
	 * @param array $attrs html attributes of the outer ul
	 * @param array $ulAttrs html attributes of every inner ul
	 * @param array $liAttrs html attributes of every li
	 */
	public function navigation(NavComposer $navComposer = null, array $attrs = null, array $ulAttrs = null, 
			array $liAttrs = null, array $aAttrs = null) {
		$this->view->out($this->getNavigation($navComposer, $attrs, $ulAttrs, $liAttrs, $aAttrs));
	}
	
	/**
	 * Same as {@link PageHtmlBuilder::navigation()} but returns the output.
	 *  
	 * @return \n2n\web\ui\UiComponent
	 */
	public function getNavigation(NavComposer $navComposer = null, array $attrs = null, array $ulAttrs = null, 
			array $liAttrs = null, array $aAttrs = null) {
		if ($navComposer === null) {
			$navComposer = Nav::root();
		}
		
		return $navComposer->build($this->view, $attrs, $ulAttrs, $liAttrs, $aAttrs);
	}	
	
	/**
	 * <p>Prints a breadcrumb navigation of the current page in form of a ul-/li-list.</p> 
	 * 
	 * <p>Also see {@link PageHtmlBuilderMeta::getBreadcrumbNavBranches()} to find out how to build a breadcrumb navigation.</p>
	 * 
	 * @param array $attrs Html attributes of the ul element.
	 * @param array $liAttrs Html attributes of each li element 
	 * @param string $divider Pass a {@link \n2n\web\ui\UiComponent} or string if a divider span element should be printed
	 * in each li element. 
	 */
	public function breadcrumbs(array $attrs = null, array $liAttrs = null, array $aAttrs = null, $divider = null) {
		$this->view->out($this->getBreadcrumbs($attrs, $liAttrs, $aAttrs, $divider));
	}
	
	/**
	 * Same as {@link PageHtmlBuilder::breadcrumbs()} but returns the output.
	 * @return \n2n\web\ui\UiComponent
	 */
	public function getBreadcrumbs(array $attrs = null, array $liAttrs = null, array $aAttrs = null, $divider = null) {
		$navBranches = $this->meta->getBreadcrumbNavBranches();
		if (empty($navBranches)) return null;
		
		$html = $this->view->getHtmlBuilder();
		
		$lis = array();
		$lastNavBranch = array_pop($navBranches);
		foreach ($navBranches as $navBranch) {
			$lis[] = $li = new HtmlElement('li', $liAttrs, $html->getLink(MurlPage::obj($navBranch), null, $aAttrs));
			if ($divider !== null) {
				$li->appendContent(new HtmlElement('span', array('class' => 'divider'), $divider));
			}
		}
		
		$lis[] = new HtmlElement('li', HtmlUtils::mergeAttrs(array('class' => 'active'), $liAttrs), 
				$html->getLink(MurlPage::obj($lastNavBranch), null, $aAttrs));
		
		return new HtmlElement('ul', $attrs, $lis);		
	}
	
	/**
	 * <p>Prints a locale switch navigation of the current page.</p> 
	 * 
	 * <p>Also see {@link PageHtmlBuilderMeta::getN2nLocaleSwitchUrls()} to find out how to customize the output.</p>
	 * 
	 * @param array $ulAttrs
	 * @param array $liAttrs
	 */
	public function localeSwitch(array $ulAttrs = null, array $liAttrs = null, array $aAttrs = null) {
		$this->view->out($this->getN2nLocaleSwitch($ulAttrs, $liAttrs, $aAttrs));
	}
	
	/**
	 * Same as {@link PageHtmlBuilder::breadcrumbs()} but returns the output.
	 * @return \n2n\web\ui\UiComponent
	 */
	public function getN2nLocaleSwitch(array $ulAttrs = null, array $liAttrs = null, array $aAttrs = null) {
		$urls = $this->meta->getN2nLocaleSwitchUrls();
		if (empty($urls)) {
			return null;
		}
		
		$n2nLocales = array();
		$langUsages = array();
		foreach (array_keys($urls) as $n2nLocaleId) {
			$n2nLocale = new N2nLocale($n2nLocaleId);
			$n2nLocales[$n2nLocaleId] = $n2nLocale;
			$langId = $n2nLocale->getLanguageId();
			if (!isset($langUsages[$langId])) $langUsages[$langId] = 1;
			else $langUsages[$langId]++;
		}
		
		$ul = new HtmlElement('ul', $ulAttrs);
		
		$html = $this->view->getHtmlBuilder();
		foreach ($urls as $n2nLocaleId => $navUrl) {
			$label = null;
			if ($langUsages[$n2nLocales[$n2nLocaleId]->getLanguageId()] > 1) {
				$label = $n2nLocales[$n2nLocaleId]->toPrettyId();
			} else {
				$label = mb_strtoupper($n2nLocales[$n2nLocaleId]->getLanguageId());
			}
			
			$elemLiAttrs = $liAttrs;
			if ($this->view->getN2nLocale()->equals($n2nLocales[$n2nLocaleId])) {
				$elemLiAttrs = HtmlUtils::mergeAttrs((array) $elemLiAttrs, array('class' => 'active'));
			}
			
			$ul->appendLn(new HtmlElement('li', $elemLiAttrs, $html->getLink($navUrl, $label, $aAttrs)));
		}
		
		return $ul;
	}
}


// class PageHtmlBuilder {
	
// 	private $view;
// 	private $html;
// 	private $request;
// 	private $meta;
	
// 	public function __construct(HtmlView $view) {
// 		$this->view = $view;
// 		$this->html = $view->getHtmlBuilder();
// 		$this->request = $view->getHttpContext()->getRequest();
// 		$this->meta = new PageHtmlBuilderMeta($view);
// 	}
	
// 	public function meta() {
// 		return $this->meta;
// 	}
	
// 	public function getLink($target, $label = null, array $attrs = null, $n2nLocales = null) {
// 		$n2nLocales = (null !== $n2nLocales) ? ArgUtils::toArray($n2nLocales) : array($this->request->getN2nLocale());
// 		$leaf = $this->meta->determineLeaf($target);
// 		$label = (null !== $label) ? $label : $leaf->getName($n2nLocales);
// 		if (!isset($attrs['title'])) {
// 			$title = $leaf->getTitle($n2nLocales);
// 			if (null === $title) {
// 				$title = $leaf->getName($n2nLocales);
// 			}
// 			$attrs['title'] = $title;
// 		}
		
// 		return $this->html->getLink($this->meta->getUrl($target, $n2nLocales), $label, $attrs);
// 	}
	
// 	public function link($target, $label = null, array $attrs = null, N2nLocale $n2nLocale = null) {
// 		$this->view->out($this->getLink($target, $label, $attrs, $n2nLocale));
// 	}
	
// 	public function getLinkStart($target, array $attrs = null, N2nLocale $n2nLocale = null) {
// 		return $this->html->getLinkStart($this->meta->getUrl($target, $n2nLocale), $attrs);
// 	}
	
// 	public function linkStart($target, array $attrs = null, N2nLocale $n2nLocale = null) {
// 		$this->view->out($this->getLinkStart($target, $attrs, $n2nLocale));
// 	}
	
// 	public function getLinkEnd() {
// 		return $this->html->getLinkEnd();
// 	}
	
// 	public function linkEnd() {
// 		$this->view->out($this->getLinkEnd());
// 	}
	
// 	/**
// 	 * @param N2nLocaleNavConfig $n2nLocaleNavConfig
// 	 * @return \n2n\impl\web\ui\view\html\HtmlElement
// 	 */
// 	public function getN2nLocaleNavigation(N2nLocaleNavConfig $n2nLocaleNavConfig = null) {
// 		$builder = new N2nLocaleNavBuilder($this->view, $this);
// 		return $builder->build($n2nLocaleNavConfig);
// 	}

// 	public function localeNavigation(N2nLocaleNavConfig $n2nLocaleNavConfig = null) {
// 		$this->view->out($this->getN2nLocaleNavigation($n2nLocaleNavConfig));
// 	}
	
// 	/**
// 	 * @param mexed $target
// 	 * @param BreadcrumbConfig $breadCrumbConfig
// 	 * @param N2nLocale $n2nLocale
// 	 * @return \n2n\impl\web\ui\view\html\HtmlElement
// 	 */
// 	public function getBreadCrumb($target = null, BreadcrumbConfig $breadCrumbConfig = null, 
// 			N2nLocale $n2nLocale = null) {
// 		$builder = new BreadcrumbBuilder($this->view, $this);
// 		return $builder->build($target, $breadCrumbConfig, $n2nLocale);
// 	}
	
// 	public function breadCrumb($target = null, BreadcrumbConfig $breadCrumbConfig = null, 
// 			N2nLocale $n2nLocale = null) {
// 		$this->view->out($this->getBreadCrumb($target, $breadCrumbConfig, $n2nLocale));
// 	}

// 	public function getTitle($title = null, $n2nLocales = null) {
// 		if (null !== $title) return $title;
// 		$currentBranch = $this->meta->getCurrentBranch();
// 		if (null === $currentBranch) return null;
		
// 		$n2nLocales = (null !== $n2nLocales) ? ArgUtils::toArray($n2nLocales) : array($this->request->getN2nLocale());
		
// 		$leaf = $currentBranch->getLeaf();
		
// 		$title = $leaf->getTitle($n2nLocales);
// 		if ($title !== null) return $title;

// 		return $leaf->getName($n2nLocales);
// 	}
	
// 	public function title($title = null, $n2nLocales = null) {
// 		$this->view->getHtmlBuilder()->out($this->getTitle($title, $n2nLocales));
// 	}
	
// 	public function getNavigation($baseTarget, NavConfig $navConfig = null, 
// 			$activeTarget = null, N2nLocale $n2nLocale = null) {
// 		$builder = new NavBuilder($this->view, $this);
		
// 		return $builder->build($baseTarget, $activeTarget, $navConfig, $n2nLocale);
// 	}
	
// 	public function navigation($baseTarget, NavConfig $navConfig = null, $activeTarget = null, N2nLocale $n2nLocale = null) {
// 		$this->view->out($this->getNavigation($baseTarget, $navConfig, $activeTarget));
// 	}
	
// 	public function contentItems($panel = null, $target = null, N2nLocale ...$n2nLocales) {
// 		$pageInfo = null;
// 		if (null !== $target) {
// 			$pageInfo = $this->meta->buildPageInfo($target, ...$n2nLocales);
// 		} else {
// 			$pageInfo = $this->meta->getCurrentPageInfo(...$n2nLocales);
// 		}
		
// 		if (null === $pageInfo) return null;
// 		$page = $pageInfo->getPage();
// 		if (null === $page || !$page instanceof CiContainerPage) return null;
		
// 		$n2nLocales[] = $this->request->getN2nLocale();
		
// 		$this->simpleContentItems($page->getContentItems(... $n2nLocales), $panel);
// 	}
	
// 	private function simpleContentItems($contentItems, $panel = null) {
// 		if (null === $contentItems) return;
// 		ArgUtils::valArrayLike($contentItems, ContentItem::class, false, 'contentItems');
		
// 		foreach ($contentItems as $contentItem) {
// 			$contentItem instanceof ContentItem;
// 			if (null !== $panel && $contentItem->getPanel() !== $panel) continue;
			
// 			$this->html->out($contentItem->createUiComponent($this->view));
// 		}
// 	}
// }