<?php
// namespace page\model;

// use n2n\core\container\N2nContext;
// use n2n\reflection\ObjectAdapter;
// use rocket\impl\ei\component\prop\string\wysiwyg\DynamicUrlBuilder;
// use n2n\web\http\HttpContext;
// use page\model\nav\murl\MurlPage;
// use n2n\util\uri\UnavailableUrlException;

// class PageWysiwygLinkBuilder extends ObjectAdapter implements DynamicUrlBuilder {

// 	const CHARACTERISTICS_KEY_ID = 'id';
	
// 	private $pageState;
// 	private $pageDao;
// 	private $n2nContext;

// 	private function _init(PageDao $pageDao, N2nContext $n2nContext, PageState $pageState) {
// 		$this->pageDao = $pageDao;
// 		$this->n2nContext = $n2nContext;
// 		$this->pageState = $pageState;
// 	}

// 	public function buildUrl(HttpContext $httpContext, $characteristics) {
// 		$page = $this->pageDao->getPageById($characteristics[self::CHARACTERISTICS_KEY_ID]);
// 		if (null === $page) return null;
		
// 		try {
// 			return MurlPage::obj($page)->toUrl($httpContext->getN2nContext());
// 		} catch (UnavailableUrlException $e) {
// 			return null;
// 		}
// 	}
// }