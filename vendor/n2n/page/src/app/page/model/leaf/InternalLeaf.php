<?php
namespace page\model\leaf;

use n2n\web\http\controller\Controller;
use n2n\core\container\N2nContext;
use n2n\web\http\controller\ControllerContext;
use n2n\util\uri\Path;
use page\model\nav\LeafContent;
use page\model\nav\NavBranch;
use page\model\nav\impl\CommonLeafContent;
use n2n\util\ex\IllegalStateException;
use page\model\nav\murl\MurlPage;
use n2n\web\http\Response;
use page\model\nav\UrlBuildTask;
use n2n\web\http\payload\impl\Redirect;
use n2n\web\http\PageNotFoundException;
use n2n\util\uri\UnavailableUrlException;

class InternalLeaf extends LeafAdapter {
	private $targetNavBranch;
	
	public function getTragetNavBranch() {
		return $this->targetNavBranch;
	}
	
	public function setTargetNavBranch(NavBranch $targetNavBranch) {
		$this->targetNavBranch = $targetNavBranch;
	}
	
	public function prepareUrl(UrlBuildTask $urlBuildTask) {
		$urlBuildTask->overwriteNavBranch($this->targetNavBranch);
	}
	
	public function createLeafContent(N2nContext $n2nContext, Path $cmdPath, Path $cmdContextPath): LeafContent {
		IllegalStateException::assertTrue($this->targetNavBranch !== null);
		
		return new CommonLeafContent($this, $cmdPath, $cmdContextPath, 
				new InternalController($this->targetNavBranch, $n2nContext));
	}
}

class InternalController implements Controller {
	private $targetNavBranch;
	private $n2nContext;
	
	public function __construct(NavBranch $targetNavBranch, N2nContext $n2nContext) {
		$this->targetNavBranch = $targetNavBranch;
		$this->n2nContext = $n2nContext;
	}
	
	public function execute(ControllerContext $controllerContext): bool {
		$targetUrl = null;
		try {
			$targetUrl = MurlPage::obj($this->targetNavBranch)->toUrl($this->n2nContext, $controllerContext);
		} catch (UnavailableUrlException $e) {
			throw new PageNotFoundException($e->getMessage(), 0, $e);
		}
		
		$this->n2nContext->getHttpContext()->getResponse()->send(
				new Redirect((string) $targetUrl, Response::STATUS_301_MOVED_PERMANENTLY));
		
		return true;
	}
}