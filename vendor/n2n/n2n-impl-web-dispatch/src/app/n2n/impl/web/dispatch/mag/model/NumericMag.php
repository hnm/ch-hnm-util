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

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\dispatch\map\PropertyPath;
use n2n\impl\web\dispatch\map\val\ValNumeric;
use n2n\reflection\property\AccessProxy;
use n2n\impl\web\dispatch\property\ScalarProperty;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\impl\web\dispatch\map\val\ValNotEmpty;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\impl\web\ui\view\html\HtmlSnippet;

/**
 * Class NumericMag
 * @package n2n\impl\web\dispatch\mag\model
 */
class NumericMag extends MagAdapter {
	private $mandatory;
	private $minValue;
	private $maxValue;
	private $decimalPlaces;
	private $inputAttrs;

	/**
	 * NumericMag constructor.
	 * @param $label
	 * @param null $value
	 * @param bool $mandatory
	 * @param null $minValue
	 * @param null $maxValue
	 * @param int $decimalPlaces
	 * @param array|null $containerAttrs
	 * @param array|null $inputAttrs
	 */
	public function __construct($label, $value = null, $mandatory = false,
			$minValue = null, $maxValue = null, $decimalPlaces = 0, array $containerAttrs = null, array $inputAttrs = null) {
		parent::__construct($label, $value, $containerAttrs);
		$this->mandatory = (bool) $mandatory;
		$this->value = $value;
		$this->minValue = $minValue;
		$this->maxValue = $maxValue;
		$this->decimalPlaces = (int) $decimalPlaces;
		$this->inputAttrs = $inputAttrs;
	}

	/**
	 * @return bool
	 */
	public function isMandatory(): bool {
		return $this->mandatory;
	}

	/**
	 * @param $mandatory
	 * @return NumericMag
	 */
	public function setMandatory(bool $mandatory) {
		$this->mandatory = $mandatory;
		return $this;
	}

	/**
	 * @param $minValue
	 * @return NumericMag
	 */
	public function setMin(?int $minValue) {
		$this->minValue = $minValue;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getMin() {
		return $this->minValue;
	}

	/**
	 * @param $maxValue
	 * @return NumericMag
	 */
	public function setMax(?int $maxValue) {
		$this->maxValue = $maxValue;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getMaxValue() {
		return $this->maxValue;
	}

	/**
	 * @param $decimalPlace
	 * @return NumericMag
	 */
	public function setDecimalPlaces(?int $decimalPlaces) {
		$this->decimalPlaces = $decimalPlaces;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getDecimalPlaces() {
		return $this->decimalPlaces;
	}

	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $view
	 * @return UiComponent
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uo): UiComponent {
		$attrs = array_merge(array('min' => $this->minValue, 'max' => $this->maxValue),
				$uo->createAttrs(UiOutfitter::NATURE_MAIN_CONTROL));

		$uiC = new HtmlSnippet($view->getFormHtmlBuilder()->getInput($propertyPath, $attrs,
				($this->decimalPlaces > 0 ? null : 'number')));
		
		if (null !== $this->helpTextLstr) {
			$uiC->append($uo->createElement(UiOutfitter::EL_NATURE_HELP_TEXT, null,
					$this->getHelpText($view->getN2nLocale())));
		}
		
		return $uiC;
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
		if ($this->mandatory) {
			$bd->val($this->getPropertyName(), new ValNotEmpty());
		}
		$bd->val($this->getPropertyName(), new ValNumeric(null, $this->minValue, null, 
				$this->maxValue, null, $this->decimalPlaces));
	}
}
