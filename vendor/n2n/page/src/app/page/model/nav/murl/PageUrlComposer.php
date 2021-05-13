<?php
namespace page\model\nav\murl;

use n2n\web\http\nav\UrlComposer;
use n2n\l10n\N2nLocale;
use n2n\core\container\N2nContext;
use n2n\web\http\controller\ControllerContext;
use page\model\nav\UnknownNavBranchException;
use page\model\nav\NavUrlBuilder;
use page\model\nav\BranchUrlBuildException;
use n2n\util\type\CastUtils;
use page\model\PageState;
use n2n\util\uri\Url;
use page\model\NavBranchCriteria;
use n2n\util\uri\Path;
use n2n\util\uri\UnavailableUrlException;

/**
 * A PageUrlComposer is created by {@link MurlPage} and can be used like a 
 * {@link \n2n\web\http\nav\ContextUrlComposer} to build urls to pages in a fluid way.
 */
class PageUrlComposer implements UrlComposer {
	private $navBranchCriteria;
	
	private $fallbackAllowed = false;
	private $n2nLocale;
	private $pathExts = array();
	private $queryExt;
	private $fragment;
	private $ssl;
	private $absolute = false;
	private $accessiblesOnly = true;

	/**
	 * Use {@link MurlPage} to create a PageUrlComposer. Don't call this constructor manually.
	 * 
	 * @param NavBranchCriteria $navBranchCriteria
	 */
	public function __construct(NavBranchCriteria $navBranchCriteria) {
		$this->navBranchCriteria = $navBranchCriteria;
	}

	/**
	 * Specifies to which translation of target page the url will be build.
	 * 
	 * @param mixed $n2nLocale N2nLocale or locale id as string of the desired translation. Resetable with null.
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public function locale($n2nLocale) {
		$this->n2nLocale = N2nLocale::build($n2nLocale);
		return $this;
	}
	
	/**
	 * <p>If true and the target page is not availablethe one of its ancestor pages will be used as fallback.</p>
	 * 
	 * <p>Default is false.</p>
	 * 
	 * @param bool $fallbackAllowed
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public function fallback(bool $fallbackAllowed = true) {
		$this->fallbackAllowed = $fallbackAllowed;
		return $this;
	}

	/**
	 * Extends the url to the target page with passed paths. This method behaves like 
	 * {@link \n2n\util\uri\Path::ext()}.
	 * 
	 * @param mixed $pathExts
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public function pathExt(...$pathPartExts): PageUrlComposer {
		$this->pathExts[] = $pathPartExts;
		return $this;
	}
	
	/**
	 * Extends the url to the target page with passed paths. This method behaves like
	 * {@link \n2n\util\uri\Path::extEnc()}.
	 *
	 * @param mixed $pathExts
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public function pathExtEnc(...$pathExts) {
		$this->pathExts[] = array_merge($this->pathExts, $pathExts);
	}
	
	/**
	 * Extends the url to the target page with passed query. This method behaves like 
	 * {@link \n2n\util\uri\Query::ext()}.
	 * 
	 * @param mixed $queryExt
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public function queryExt($queryExt): PageUrlComposer {
		$this->queryExt = $queryExt;
		return $this;
	}

	/**
	 * Defines the fragment of the url to the target page.
	 * 
	 * @param string $fragment
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public function fragment(string $fragment): PageUrlComposer {
		$this->fragment = $fragment;
		return $this;
	}

	/**
	 * <p>If true and the url will be absolute.</p>
	 * 
	 * <p>Default is false.</p>
	 *
	 * @param string $absolute
	 * @return \page\model\nav\murl\PageUrlComposer
	 */
	public function absolute(bool $absolute = true): PageUrlComposer {
		$this->absolute = $absolute;
		return $this;
	}
	
	public function inaccessibles(bool $includeInaccessibles = true) {
		$this->accessiblesOnly = !$includeInaccessibles;
		return $this;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\nav\UrlComposer::toUrl($n2nContext, $controllerContext)
	 */
	public function toUrl(N2nContext $n2nContext, ControllerContext $controllerContext = null, 
			string &$suggestedLabel = null): Url {
		$pageState = $n2nContext->lookup(PageState::class);
		CastUtils::assertTrue($pageState instanceof PageState);

		$n2nLocale = $this->n2nLocale;
		if ($n2nLocale === null){
			$n2nLocale = $n2nContext->getN2nLocale();
		}
		$navBranch = null;
		try {
			$navBranch = $this->navBranchCriteria->determine($pageState, $n2nLocale, $n2nContext);
		} catch (UnknownNavBranchException $e) {
			throw new UnavailableUrlException(false, null, null, $e);
		}

		$navUrlBuilder = new NavUrlBuilder($n2nContext->getHttpContext());
		$navUrlBuilder->setFallbackAllowed($this->fallbackAllowed);
		$navUrlBuilder->setAbsolute($this->absolute);
		$navUrlBuilder->setAccessiblesOnly($this->accessiblesOnly);
		$navUrlBuilder->setPathExt((new Path(array()))->extEnc(...$this->pathExts));
		$url = null;
		$curNavBranch = null;
		try {
			$url = $navUrlBuilder->build($navBranch, $n2nLocale, true, $curNavBranch);
			$suggestedLabel = $curNavBranch->getLeafByN2nLocale($n2nLocale)->getName();
		} catch (BranchUrlBuildException $e) {
			throw new UnavailableUrlException(false, 'NavBranch not available for locale: ' . $n2nLocale, 0, $e);
		}

		return $url->queryExt($this->queryExt)->chFragment($this->fragment);
	}
}