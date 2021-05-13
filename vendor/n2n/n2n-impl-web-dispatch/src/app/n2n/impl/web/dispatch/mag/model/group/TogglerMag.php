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

use n2n\impl\web\dispatch\mag\model\BoolMag;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\util\type\ArgUtils;
use n2n\web\dispatch\mag\MagWrapper;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\map\bind\MappingDefinition;
use n2n\web\ui\UiComponent;

class TogglerMag extends BoolMag {
	private $disabledIgnored = true;
	private $onAssociatedMagWrappers = array();
	private $offAssociatedMagWrappers = array();
	private $onHtmlId;
	private $offHtmlId;
	
	public function __construct($labelLstr, bool $value = false, array $onAssociatedMagWrappers = null, 
			array $offAssociatedMagWrappers = null) {
		
		parent::__construct($labelLstr, $value);
		$this->setOnAssociatedMagWrappers((array) $onAssociatedMagWrappers);
		$this->setOffAssociatedMagWrappers((array) $offAssociatedMagWrappers);
		$this->onHtmlId = HtmlUtils::buildUniqueId('n2n-impl-web-dispatch-toggler-on-');
		$this->offHtmlId = HtmlUtils::buildUniqueId('n2n-impl-web-dispatch-toggler-off-');
		$this->setInputAttrs(array());
	}
	
	public function setInputAttrs(array $inputAttrs) {
		parent::setInputAttrs(HtmlUtils::mergeAttrs(array('class' => 'n2n-impl-web-dispatch-toggler',
				'data-n2n-impl-web-dispatch-toggler-on-class' => $this->onHtmlId,
				'data-n2n-impl-web-dispatch-toggler-off-class' => $this->offHtmlId), $inputAttrs));
	}
	
	/**
	 * @param MagWrapper[] $onAssociatedMagWrappers
	 */
	public function setOnAssociatedMagWrappers(array $onAssociatedMagWrappers) {
		ArgUtils::valArray($onAssociatedMagWrappers, MagWrapper::class, false, 'onAssociatedMagWrappers');
		$this->onAssociatedMagWrappers = $onAssociatedMagWrappers;
		
		foreach ($this->onAssociatedMagWrappers as $associatedMagWrapper) {
			$associatedMagWrapper->addMarkAttrs(array('class' => $this->onHtmlId));
		}
	}
	
	public function setOffAssociatedMagWrappers(array $offAssociatedMagWrappers) {
		ArgUtils::valArray($offAssociatedMagWrappers, MagWrapper::class, false, 'offAssociatedMagWrappers');
		$this->offAssociatedMagWrappers = $offAssociatedMagWrappers;
		
		foreach ($this->offAssociatedMagWrappers as $associatedMagWrapper) {
			$associatedMagWrapper->addMarkAttrs(array('class' => $this->offHtmlId));
		}
	}
	
	/**
	 * @return MagWrapper[] 
	 */
	public function getOnAssociatedMagWrappers() {
		return $this->onAssociatedMagWrappers;
	}
	
	/**
	 * @return MagWrapper[]
	 */
	public function getOffAssociatedMagWrappers() {
		return $this->offAssociatedMagWrappers;
	}
	
	public function setupMappingDefinition(MappingDefinition $md) {
		parent::setupMappingDefinition($md);
		
		if (!$this->disabledIgnored || !$md->isDispatched()) return;
		
		$ignoredMagWrappers = array();
		$notIgnoredMagWrappers = array();
		
		if (!$md->getDispatchedValue($this->propertyName)) {
			$ignoredMagWrappers = $this->onAssociatedMagWrappers;
	  		$notIgnoredMagWrapper = $this->offAssociatedMagWrappers;
		} else {
 			$ignoredMagWrappers = $this->offAssociatedMagWrappers;
			$notIgnoredMagWrapper = $this->onAssociatedMagWrappers;
		}
		
		foreach ($ignoredMagWrappers as $magWrapper)  {
			$magWrapper->setIgnored(true);
		}
		
		foreach ($notIgnoredMagWrapper as $magWrapper)  {
			$magWrapper->setIgnored(false);
		} 
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uiOutfitter): UiComponent {
// 		$view->getHtmlBuilder()->meta()->addLibrary(new JQueryLibrary(3, true));
// 		$view->getHtmlBuilder()->meta()->bodyEnd()->addJs('js/ajah.js', 'n2n\impl\web\ui');
		$view->getHtmlBuilder()->meta()->addJs('js/group.js', 'n2n\impl\web\dispatch');
		
		foreach ($this->offAssociatedMagWrappers as $associatedMagWrapper) {
			$associatedMagWrapper->addMarkAttrs(array('class' => $this->offHtmlId));
		}
		
		return parent::createUiField($propertyPath, $view, $uiOutfitter);
	}
}
