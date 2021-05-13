<?php
namespace page\model\leaf;

use n2n\core\container\N2nContext;
use n2n\web\http\controller\Controller;
use n2n\web\http\controller\ControllerContext;
use n2n\web\http\PageNotFoundException;
use n2n\web\http\Response;
use n2n\l10n\N2nLocale;
use page\model\nav\LeafContent;
use n2n\util\uri\Path;
use page\model\nav\impl\CommonLeafContent;
use n2n\web\http\payload\impl\Redirect;

class ExternalLeaf extends LeafAdapter {
	private $httpLocation;
	
	public function __construct(N2nLocale $n2nLocale, string $name, string $httpLocation) {
		parent::__construct($n2nLocale, $name);
		$this->httpLocation = $httpLocation;
	}

	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\Leaf::createLeafContent($n2nContext, $cmdPath, $cmdContextPath)
	 */
	public function createLeafContent(N2nContext $n2nContext, Path $cmdPath, Path $cmdContextPath): LeafContent {
		return new CommonLeafContent($this, $cmdPath, $cmdContextPath, 
				new ExternalController($this->httpLocation, $n2nContext));
	}
}

class ExternalController implements Controller {
	private $httpLocation;
	private $n2nContext;
	
	public function __construct(string $httpLocation, N2nContext $n2nContext) {
		$this->httpLocation = $httpLocation;
		$this->n2nContext = $n2nContext;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\controller\Controller::execute($controllerContext)
	 */
	public function execute(ControllerContext $controllerContext): bool {
		if (!$controllerContext->getCmdPath()->isEmpty()) {
			throw new PageNotFoundException();
		}
		
		$this->n2nContext->getHttpContext()->getResponse()->send(
				new Redirect($this->httpLocation, Response::STATUS_301_MOVED_PERMANENTLY));
		return true;
	}
}