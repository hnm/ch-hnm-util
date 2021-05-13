<?php

namespace n2n\impl\web\dispatch\mag\model;

use n2n\impl\web\ui\view\html\HtmlElement;
use n2n\impl\web\ui\view\html\HtmlSnippet;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\dispatch\mag\MagCollection;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\ui\UiComponent;

class BasicUiOutfitter implements UiOutfitter {

	/**
	 * @param string $nature
	 * @return array
	 */
	public function createAttrs(int $nature): array {
		return array();
	}

	public function createElement(int $elemNature, array $attrs = null, $contents = ''): UiComponent {
		if ($elemNature & self::EL_NATRUE_CONTROL_ADDON_SUFFIX_WRAPPER) {
			return new HtmlElement('div', $attrs, $contents);
		}

		if ($elemNature & self::EL_NATURE_CONTROL_ADDON_WRAPPER) {
			return new HtmlElement('span', $attrs, $contents);
		}

		if ($elemNature & self::EL_NATURE_ARRAY_ITEM_CONTROL) {
			$summary = new HtmlElement('div',null, '');
			$container = new HtmlElement('div', HtmlUtils::mergeAttrs($attrs, 
					$this->createAttrs(UiOutfitter::NATURE_MASSIVE_ARRAY_ITEM_STRUCTURE)), $summary);

			$summary->appendLn(new HtmlElement('div', null, ''));
			$summary->appendLn(new HtmlElement('div', null, $contents));
			$summary->appendLn(new HtmlElement('div', array('class' => MagCollection::CONTROL_WRAPPER_CLASS),
					$this->createElement(UiOutfitter::EL_NATURE_CONTROL_REMOVE, 
							array('class' => MagCollection::CONTROL_REMOVE_CLASS), '')));

			return $container;
		}
		
		if ($elemNature & self::EL_NATURE_CONTROL_LIST) {
			return new HtmlElement('div', $attrs, $contents);
		}
		
		if ($elemNature & self::EL_NATURE_CONTROL_LIST_ITEM) {
			return new HtmlElement('div', $attrs, $contents);
		}

		return new HtmlSnippet($contents);
	}

	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $contextView
	 * @return UiComponent
	 */
	public function createMagDispatchableView(PropertyPath $propertyPath = null, HtmlView $contextView): UiComponent {
		return $contextView->getImport('\n2n\impl\web\dispatch\mag\view\magForm.html', array('propertyPath' => null));
	}
}