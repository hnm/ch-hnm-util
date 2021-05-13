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
namespace n2n\impl\web\dispatch\mag\model;

use n2n\impl\web\dispatch\map\val\ValNotEmpty;
use n2n\web\dispatch\map\PropertyPath;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\dispatch\map\val\ValMaxLength;
use n2n\web\ui\UiComponent;
use n2n\reflection\property\AccessProxy;
use n2n\impl\web\dispatch\property\ScalarProperty;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\impl\web\ui\view\html\HtmlSnippet;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\impl\web\ui\view\html\HtmlUtils;

class SecretStringMag extends MagAdapter {
	private $maxlength;
	private $required;
	private $inputAttrs;
	
	public function __construct($labelStr, $value = null, bool $required = false, $maxlength = null, 
			array $attrs = null, array $inputAttrs = null) {
		parent::__construct($labelStr, $value, $attrs);
		$this->maxlength = $maxlength;
		$this->required = $required;
		$this->inputAttrs = $inputAttrs;
	}
	
	public function setMaxlength($maxlength) {
		$this->maxlength = $maxlength;
	}
	
	public function getMaxlength() {
		return $this->maxlength;
	}
	
	public function isRequired(): bool {
		return $this->required;
	}
	
	public function setRequired(bool $required) {
		$this->required = $required;
	}
	
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ScalarProperty($accessProxy, false);
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->isRequired()) {
			$bd->val($this->propertyName, new ValNotEmpty());
		}
		
		if (isset($this->maxlength)) {
			$bd->val($this->propertyName, new ValMaxLength((int) $this->maxlength));
		}
	}
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uo): UiComponent {
		$uiC =  new HtmlSnippet();
		
		
		$attrs = HtmlUtils::mergeAttrs(
				$uo->createAttrs(UiOutfitter::NATURE_TEXT|UiOutfitter::NATURE_MAIN_CONTROL), $this->inputAttrs);
		
		$uiC->append($view->getFormHtmlBuilder()->getInput($propertyPath, $attrs));
			
		
		if (null !== $this->helpTextLstr) {
			$uiC->append($uo->createElement(UiOutfitter::EL_NATURE_HELP_TEXT, null,
					$this->getHelpText($view->getN2nLocale())));
		}
		
		return $uiC;
	}
}
