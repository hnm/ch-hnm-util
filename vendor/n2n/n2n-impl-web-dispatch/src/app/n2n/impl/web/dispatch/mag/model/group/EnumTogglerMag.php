<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\impl\web\dispatch\mag\model\group;

use n2n\util\type\ArgUtils;
use n2n\web\dispatch\map\PropertyPath;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\mag\MagWrapper;
use n2n\impl\web\dispatch\mag\model\EnumMag;
use n2n\util\type\TypeConstraint;
use n2n\web\dispatch\map\bind\MappingDefinition;
use n2n\web\dispatch\mag\UiOutfitter;

class EnumTogglerMag extends EnumMag {
	private $associatedMagWrapperMap;
	private $htmlId;
	private $disabledIgnored = true;
	
	public function __construct($labelLstr, array $options, $value = null, bool $mandatory = false, 
			array $associatedMagWrapperMap = null) {
		parent::__construct($labelLstr, $options, $value, $mandatory);
		
		$this->setAssociatedMagWrapperMap((array) $associatedMagWrapperMap);
		$this->htmlId = HtmlUtils::buildUniqueId('n2n-impl-web-dispatch-enum-toggler-group');
		$this->setInputAttrs(array());
	}
	
	public function setInputAttrs(array $inputAttrs) {
		parent::setInputAttrs(HtmlUtils::mergeAttrs( array('class' => 'n2n-impl-web-dispatch-enum-toggler',
				'data-n2n-impl-web-dispatch-toggler-class' => $this->htmlId), $inputAttrs), $inputAttrs);
	}
	
	/**
	 * @param MagWrapper[][] $associatedMagWrapperMap
	 */
	public function setAssociatedMagWrapperMap(array $associatedMagWrapperMap) {
		ArgUtils::valArray($associatedMagWrapperMap, TypeConstraint::createArrayLike('array', false, 
				TypeConstraint::createSimple(MagWrapper::class)), false, 'associatedMagWrapperMap');
		$this->associatedMagWrapperMap = $associatedMagWrapperMap;
		
		foreach ($this->associatedMagWrapperMap as $value => $associatedMagWrappers) {
			foreach ($associatedMagWrappers as $associatedMagWrapper) {
				$associatedMagWrapper->addMarkAttrs(array('class' => $this->htmlId . ' ' . $this->htmlId . '-'
						. $value));
			}
		}
	}
	
// 	/**
// 	 * @param MagWrapper[] $associatedMagWrappers
// 	 */
// 	public function setAssociatedMagWrappers($value, array $associatedMagWrappers) {
// 		ArgUtils::valArray($associatedMagWrappers, MagWrapper::class, false, 'associatedMagWrappers');
// 		$this->associatedMagWrapperMap[$value] = $associatedMagWrappers;
// 	}
	
	/**
	 * @return MagWrapper[][] 
	 */
	public function getAssociatedMagWrapperMap() {
		return $this->associatedMagWrapperMap;
	}
	
	public function setupMappingDefinition(MappingDefinition $md) {
		parent::setupMappingDefinition($md);
		
		if (!$this->disabledIgnored || !$md->isDispatched()) return;
		
		$dispValue = $md->getDispatchedValue($this->propertyName);
		
		$notIgnoredMagWrappers = array();
		
		foreach ($this->associatedMagWrapperMap as $value => $associatedMagWrappers) {
			if ($dispValue == $value) {
				$notIgnoredMagWrappers = $associatedMagWrappers;
				continue;
			}
			
			foreach ($associatedMagWrappers as $associatedMagWrapper) {
				$associatedMagWrapper->setIgnored(true);
			}
		}

		foreach ($notIgnoredMagWrappers as $notIgnoredMagWrapper) {
			$notIgnoredMagWrapper->setIgnored(false);
		}
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uiOutfitter): UiComponent {
// 		$view->getHtmlBuilder()->meta()->addLibrary(new JQueryLibrary(3, true));
// 		$view->getHtmlBuilder()->meta()->bodyEnd()->addJs('js/ajah.js', 'n2n\impl\web\ui');
		$view->getHtmlBuilder()->meta()->addJs('js/group.js', 'n2n\impl\web\dispatch');
		
		return parent::createUiField($propertyPath, $view, $uiOutfitter);
	}
}
