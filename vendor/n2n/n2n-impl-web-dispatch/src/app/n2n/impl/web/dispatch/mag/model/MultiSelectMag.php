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

use n2n\impl\web\dispatch\map\val\ValIsset;
use n2n\web\dispatch\map\PropertyPath;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\dispatch\map\val\ValEnum;
use n2n\impl\web\dispatch\map\val\ValArraySize;
use n2n\impl\web\dispatch\property\ScalarProperty;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\impl\web\ui\view\html\HtmlSnippet;

/**
 * Class MultiSelectMag
 * @package n2n\impl\web\dispatch\mag\model
 */
class MultiSelectMag extends MagAdapter {
	private $choicesMap;
	private $min;
	private $max;
	private $inputAttrs;

	/**
	 * MultiSelectMag constructor.
	 * @param $label
	 * @param array $choicesMap
	 * @param array|null $default
	 * @param int $min
	 * @param int|null $max
	 * @param array|null $inputAttrs
	 * @param array|null $containerAttrs
	 */
	public function __construct($label, array $choicesMap, array $default = null, int $min = 0, int $max = null,
			array $inputAttrs = null, array $containerAttrs = null) {
		parent::__construct($label, (array) $default, $containerAttrs);
		$this->choicesMap = $choicesMap;
		$this->min = $min;
		$this->max = $max;
		$this->inputAttrs = $inputAttrs;
	}

	/**
	 * @param AccessProxy $accessProxy
	 * @return ManagedProperty
	 */
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ScalarProperty($accessProxy, true);
	}

	/**
	 * @param BindingDefinition $bd
	 */
	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->min > 0) {
			$bd->val($this->getPropertyName(), new ValIsset());
		}
		
		$bd->val($this->getPropertyName(), new ValArraySize($this->min, null, $this->max, null));
		
		$bd->val($this->getPropertyName(), new ValEnum(array_keys($this->choicesMap)));
	}

	/**
	 * @param $choicesMap
	 */
	public function setChoicesMap($choicesMap) {
		$this->choicesMap = $choicesMap;
	}

	/**
	 * @return array
	 */
	public function getChoicesMap() {
		return $this->choicesMap;
	}

	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $htmlView
	 * @return UiComponent
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uo): UiComponent {
		$uiControls = new HtmlSnippet();
		
		$formHtml = $view->getFormHtmlBuilder();
		foreach ($this->choicesMap as $key => $label) {
			$inputAttrs = $uo->createAttrs(UiOutfitter::NATURE_CHECK|UiOutfitter::NATURE_MAIN_CONTROL);
			
			$snippetUi = new HtmlSnippet();
			$cbxPropertyPath = $propertyPath->fieldExt($key);
			$labelUi = $formHtml->getLabel($cbxPropertyPath, $label,
					$uo->createAttrs(UiOutfitter::NATURE_CHECK_LABEL));
			$snippetUi->appendLn($formHtml->getInputCheckbox($cbxPropertyPath, $key, $inputAttrs));
			$snippetUi->appendLn($labelUi);
			
			$uiControls->append($uo->createElement(UiOutfitter::EL_NATURE_CONTROL_LIST_ITEM, null, 
					$uo->createElement(UiOutfitter::EL_NATURE_CHECK_WRAPPER, null, $snippetUi)));
		}
		
		$uiC = new HtmlSnippet($uo->createElement(UiOutfitter::EL_NATURE_CONTROL_LIST, array('class' => 'n2n-multiselect-option'), $uiControls));
		
		if (null !== $this->helpTextLstr) {
			$uiC->append($uo->createElement(UiOutfitter::EL_NATURE_HELP_TEXT, null,
					$this->getHelpText($view->getN2nLocale())));
		}
		
		return $uiC;
	}

	/**
	 * @param $value
	 * @return array
	 */
	public function attributeValueToOptionValue($value) {
		return array_combine($value, $value);
	}
}
