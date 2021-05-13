<?php
namespace page\ui\nav\impl;

use page\ui\nav\NavItemBuilder;
use n2n\impl\web\ui\view\html\HtmlElement;
use n2n\impl\web\ui\view\html\HtmlUtils;
use page\model\nav\Leaf;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\ui\UiComponent;
use page\model\nav\murl\MurlPage;

abstract class NavItemBuilderAdapter implements NavItemBuilder {
	protected $classPrefix = '';
	protected $rootLevel = 0;
	
	public function setRootLevel(int $rootLevel) {
		$this->rootLevel = $rootLevel;
	}
	
	public function buildRootUl(HtmlView $view, array $attrs): HtmlElement {
		$className = $this->classPrefix . 'level-' . $this->rootLevel 
				. ' ' . $this->classPrefix . 'level-rel-0';
		$attrs = HtmlUtils::mergeAttrs(array('class' => $className), $attrs);
		return new HtmlElement('ul', $attrs, '');
	}
	
	public function buildUl(HtmlView $view, Leaf $parentLeaf, array $attrs, int $infos): HtmlElement {
		$level = ($parentLeaf->getNavBranch()->getLevel() + 1);
		$relLevel = $level - $this->rootLevel;
		$className = $this->classPrefix . 'level-' . $level . ' ' . $this->classPrefix . 'level-rel-' . $relLevel;
		
		$attrs = HtmlUtils::mergeAttrs(array('class' => $className), $attrs);
		return new HtmlElement('ul', $attrs, '');
	}
	
	public function buildLi(HtmlView $view, Leaf $leaf, array $attrs, array $aAttrs, int $infos): HtmlElement {
		return new HtmlElement('li', $this->buildLiAttrs($view, $leaf, $attrs, $infos), 
				$view->getHtmlBuilder()->getLink(MurlPage::obj($leaf), 
						$this->buildLiLabel($view, $leaf, $attrs, $infos),
						$this->buildAAttrs($view, $leaf, $aAttrs, $infos)));
	}
	
	protected function buildAAttrs(HtmlView $view, Leaf $leaf, array $attrs, int $infos) {
		if ($leaf->isTargetNewWindow() && !($infos & self::INFO_OPEN || $infos & self::INFO_CURRENT)) {
			$attrs = HtmlUtils::mergeAttrs(array('target' => '_blank'), $attrs);
		}
		
		$attrs = HtmlUtils::mergeAttrs($this->buildAdditionalAAttrs($view, $leaf, $attrs, $infos), $attrs);
		
		if (!array_key_exists('title', $attrs)) {
			$attrs['title'] = $leaf->getTitle();
		}
		
		return $attrs;
	}
	
	protected function buildAdditionalAAttrs(HtmlView $view, Leaf $leaf, array $attrs, int $infos) {
		return array();
	}
	
	protected function buildLiAttrs(HtmlView $view, Leaf $leaf, array $attrs, int $infos): array {
		$attrs = HtmlUtils::mergeAttrs($this->buildAdditionalLiAttrs($view, $leaf, $attrs, $infos), $attrs);
		
		$level = $leaf->getNavBranch()->getLevel();
		$relLevel = $level - $this->rootLevel;
		$classNames = array($this->classPrefix . 'level-' . $level, 
				$this->classPrefix . 'level-rel-' . $relLevel);
		
		if ($infos & self::INFO_HAS_CHILDREN) {
			$classNames[] = $this->classPrefix . 'has-children';
		}
		
		if ($infos & self::INFO_CURRENT) {
			$classNames[] = $this->classPrefix . 'active';
		}
		
		if ($infos & self::INFO_OPEN) {
			$classNames[] = $this->classPrefix . 'open';
		}
		
		return HtmlUtils::mergeAttrs(array('class' => implode(' ', $classNames)), $attrs);
	}
	
	protected function buildAdditionalLiAttrs(HtmlView $view, Leaf $leaf, array $attrs, int $infos): array {
		return array();
	}
	
	/**
	 * @param HtmlView $view
	 * @param Leaf $leaf
	 * @param array $attrs
	 * @param int $infos
	 * @return UiComponent|string
	 */
	protected function buildLiLabel(HtmlView $view, Leaf $leaf, array $attrs, int $infos) {
		return $leaf->getName();
	}
}