<?php
namespace page\model;

use n2n\context\RequestScoped;
use rocket\impl\ei\component\prop\string\cke\model\CkeLinkProviderAdapter;
use n2n\l10n\N2nLocale;
use n2n\core\container\N2nContext;
use n2n\util\type\CastUtils;
use page\model\nav\NavBranch;
use n2n\web\ui\view\View;
use page\model\nav\murl\MurlPage;

class PageCkeLinkProvider extends CkeLinkProviderAdapter implements RequestScoped {
	private $pageState;
	private $n2nContext;
	
	private function _init(PageState $pageState, N2nContext $n2nContext) {
		$this->pageState = $pageState;
		$this->n2nContext = $n2nContext;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \rocket\impl\ei\component\prop\string\cke\model\CkeLinkProvider::getTitle()
	 */
	public function getTitle(): string {
		return 'Page Links';
	}

	/**
	 * {@inheritDoc}
	 * @see \rocket\impl\ei\component\prop\string\cke\model\CkeLinkProvider::getLinkUrls()
	 */
	public function getLinkOptions(N2nLocale $n2nLocale): array {
		$options = array();
		$this->buildLinkOptions($this->pageState->getNavTree()->getRootNavBranches(), $options);
		return $options;
	}
	
	/**
	 * @param NavBranch[] $navBranches
	 */
	private function buildLinkOptions(array $navBranches, array &$options) {
		$localeId = $this->n2nContext->getN2nLocale()->getId();
		$langId = $this->n2nContext->getN2nLocale()->getLanguageId();
		
		foreach ($navBranches as $navBranch) {
			CastUtils::assertTrue($navBranch instanceof NavBranch);
			if ($navBranch->getId() === null) continue;
			
			$leafs = $navBranch->getLeafs();
			
			$name = null;
			if (isset($leafs[$localeId]) && $leafs[$localeId]->isAccessible()) {
				$name = $leafs[$localeId]->getName();
			} else if (isset($leafs[$langId]) && $leafs[$langId]->isAccessible()) {
				$name = $leafs[$localeId]->getName();
			} else {
				foreach ($leafs as $leaf) {
					if (!$leaf->isAccessible()) continue;
					
					$name = $leaf->getName();
					break;
				}
			}
			
			if ($name !== null) {
				$options[$navBranch->getId()] = str_repeat('.', $navBranch->getLevel()) . ' ' . $name;
			}
			
			$this->buildLinkOptions($navBranch->getChildren(), $options);
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \rocket\impl\ei\component\prop\string\cke\model\CkeLinkProviderAdapter::buildUrl()
	 */
	public function buildUrl(string $key, View $view, N2nLocale $n2nLocale) {
		return $view->buildUrlStr(MurlPage::id($key)->locale($n2nLocale));
	}
	
// 	/**
// 	 * {@inheritDoc}
// 	 * @see \rocket\impl\ei\component\prop\string\cke\model\CkeLinkProvider::buildUrl()
// 	 */
// 	public function buildUrl(string $key) {
// 		return null;
// 	}
}