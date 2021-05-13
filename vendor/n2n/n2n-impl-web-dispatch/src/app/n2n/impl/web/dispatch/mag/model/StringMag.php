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
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\web\dispatch\map\PropertyPath;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\dispatch\map\val\ValMaxLength;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\impl\web\dispatch\property\ScalarProperty;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\ui\UiComponent;
use n2n\impl\web\ui\view\html\HtmlSnippet;

/**
 * Class StringMag
 * @package n2n\impl\web\dispatch\mag\model
 */
class StringMag extends MagAdapter {
	private $mandatory;
	private $maxlength;
	private $multiline;
	private $inputAttrs;

	/**
	 * StringMag constructor.
	 * @param $label
	 * @param null $value
	 * @param bool $mandatory
	 * @param int|null $maxlength
	 * @param bool $multiline
	 * @param array|null $attrs
	 * @param array|null $inputAttrs
	 */
	public function __construct($label, $value = null, bool $mandatory = false,
			int $maxlength = null, bool $multiline = false, array $attrs = null, array $inputAttrs = null) {
		parent::__construct($label, $value, $attrs);
		$this->mandatory = $mandatory;
		$this->maxlength = $maxlength;
		$this->multiline = $multiline;
		$this->inputAttrs = (array) $inputAttrs;
	}

	/**
	 * @param bool $mandatory
	 */
	public function setMandatory(bool $mandatory) {
		$this->mandatory = $mandatory;
	}

	/**
	 * @return bool
	 */
	public function isMandatory(): bool {
		return $this->mandatory;
	}

	/**
	 * @param int|null $maxlength
	 */
	public function setMaxlength(int $maxlength = null) {
		$this->maxlength = $maxlength;
	}

	/**
	 * @return int|null
	 */
	public function getMaxlength() {
		return $this->maxlength;
	}

	/**
	 * @param bool $multiline
	 */
	public function setMultiline(bool $multiline) {
		$this->multiline = $multiline;
	}

	/**
	 * @return bool
	 */
	public function isMultiline() {
		return $this->multiline;
	}

	/**
	 * @param array $inputAttrs
	 */
	public function setInputAttrs(array $inputAttrs) {
		$this->inputAttrs = $inputAttrs;
	}

	/**
	 * @return array
	 */
	public function getInputAttrs() {
		return $this->inputAttrs;
	}

	/**
	 * @param AccessProxy $accessProxy
	 * @return ManagedProperty
	 */
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ScalarProperty($accessProxy, false);
	}

	/**
	 * @param BindingDefinition $bd
	 */
	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->isMandatory()) {
			$bd->val($this->getPropertyName(), new ValNotEmpty());
		}
		
		if ($this->getMaxlength() !== null) {
			$bd->val($this->getPropertyName(), new ValMaxLength((int) $this->maxlength));
		}
	}

	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $htmlView
	 * @return UiComponent
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uo): UiComponent {
		if ($this->maxlength !== null) {
			$this->inputAttrs['maxlength'] = $this->maxlength;
		}
		
		$uiC =  new HtmlSnippet();

		if ($this->isMultiline()) {
			$attrs = HtmlUtils::mergeAttrs(
					$uo->createAttrs(UiOutfitter::NATURE_TEXT_AREA|UiOutfitter::NATURE_MAIN_CONTROL), $this->inputAttrs);
			$uiC->append($view->getFormHtmlBuilder()->getTextarea($propertyPath, $attrs));
		} else {
			$attrs = HtmlUtils::mergeAttrs(
					$uo->createAttrs(UiOutfitter::NATURE_TEXT|UiOutfitter::NATURE_MAIN_CONTROL), $this->inputAttrs);
			
			$uiC->append($view->getFormHtmlBuilder()->getInput($propertyPath, $attrs));
		}

		if (null !== $this->helpTextLstr) {
			$uiC->append($uo->createElement(UiOutfitter::EL_NATURE_HELP_TEXT, null, 
					$this->getHelpText($view->getN2nLocale())));
		}
		
		return $uiC;
	}
}