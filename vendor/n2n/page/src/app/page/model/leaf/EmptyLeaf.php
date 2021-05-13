<?php
namespace page\model\leaf;

use n2n\core\container\N2nContext;
use page\bo\Page;
use n2n\util\uri\Path;
use page\model\nav\LeafContent;
use page\model\nav\impl\CommonLeafContent;
use n2n\web\http\controller\Controller;
use n2n\web\http\controller\ControllerContext;
use n2n\web\http\StatusException;
use n2n\web\http\Response;

class EmptyLeaf extends LeafAdapter {
	/**
	 * {@inheritDoc}
	 * @see \page\model\nav\Leaf::createLeafContent($n2nContext, $cmdPath, $cmdContextPath)
	 */
	public function createLeafContent(N2nContext $n2nContext, Path $cmdPath, Path $cmdContextPath): LeafContent {
		return new CommonLeafContent($this, $cmdPath, $cmdContextPath, new EmptyController());
	}
}

class EmptyController implements Controller {
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\controller\Controller::execute($controllerContext)
	 */
	public function execute(ControllerContext $controllerContext): bool {
		throw new StatusException(Response::STATUS_500_INTERNAL_SERVER_ERROR, 'No content available for this page.');
	}
}