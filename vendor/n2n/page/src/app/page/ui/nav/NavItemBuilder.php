<?php
namespace page\ui\nav;

use n2n\impl\web\ui\view\html\HtmlElement;
use page\model\nav\Leaf;
use n2n\impl\web\ui\view\html\HtmlView;

/**
 * Used by {@link NavComposer} to build navigation html components.
 *
 */
interface NavItemBuilder {
	const INFO_CURRENT = 1;
	const INFO_OPEN = 2;
	const INFO_HAS_CHILDREN = 4;
	
	/**
	 * @param int $rootLevel
	 */
	public function setRootLevel(int $rootLevel);
	
	/**
	 * @param HtmlView $view
	 * @param array $attrs
	 * @return \n2n\impl\web\ui\view\html\HtmlElement
	 */
	public function buildRootUl(HtmlView $view, array $attrs): HtmlElement;
	
	/**
	 * @param HtmlView $view
	 * @param Leaf $parentLeaf
	 * @param array $attrs
	 * @param int $parentInfos
	 * @return \n2n\impl\web\ui\view\html\HtmlElement
	 */
	public function buildUl(HtmlView $view, Leaf $parentLeaf, array $attrs, int $parentInfos): HtmlElement;
	
	/**
	 * @param HtmlView $view
	 * @param Leaf $leaf
	 * @param array $attrs
	 * @param int $infos
	 * @return \n2n\impl\web\ui\view\html\HtmlElement
	 */
	public function buildLi(HtmlView $view, Leaf $leaf, array $attrs, array $aAttrs, int $infos): HtmlElement;
}