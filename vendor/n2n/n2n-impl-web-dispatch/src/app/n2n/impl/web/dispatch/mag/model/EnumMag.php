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

use n2n\impl\web\dispatch\map\val\ValEnum;
use n2n\web\dispatch\map\PropertyPath;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\dispatch\property\ScalarProperty;
use n2n\l10n\Message;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\l10n\N2nLocale;
use n2n\l10n\Lstr;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\impl\web\ui\view\html\HtmlSnippet;
use n2n\impl\web\dispatch\map\val\ValNotEmpty;

/**
 * Class EnumMag
 * @package n2n\impl\web\dispatch\mag\model
 */
class EnumMag extends MagAdapter {
	private $mandatory;
	private $options;
	private $inputAttrs;
	private $useRadios = false;

	/**
	 * EnumMag constructor.
	 * @param $label
	 * @param array $options
	 * @param null $value
	 * @param bool $mandatory
	 * @param array|null $inputAttrs
	 * @param array|null $containerAttrs
	 */
	public function __construct($label, array $options, $value = null,
			$mandatory = false, array $inputAttrs = null, array $containerAttrs = null) {
		parent::__construct($label, $value, $containerAttrs);
		$this->mandatory = (bool) $mandatory;
		$this->setOptions($options);
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
	 */
	public function setMandatory(bool $mandatory) {
		$this->mandatory = $mandatory;
		
		return $this;
	}
	
	public function isUseRadios() {
		return $this->useRadios;
	}
	
	public function setUseRadios(bool $useRadios) {
		$this->useRadios = $useRadios;
		
		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}

	/**
	 * @param array $options
	 */
	public function setOptions(array $options) {
		if (!$this->mandatory && !isset($options[null])) {
			$this->options = array(null => null) + $options;
		} else {
			$this->options = $options;
		}
		
		return $this;
	}

	/**
	 * @param N2nLocale $n2nLocale
	 * @return array
	 */
	public function buildOptions(N2nLocale $n2nLocale) {
		$options = array();
		foreach ($this->options as $key => $value) {
			$options[$key] = Lstr::create($value)->t($n2nLocale);
		}
		return $options;
	}

	/**
	 * @return array
	 */
	public function getInputAttrs() {
		return $this->inputAttrs;
	}
	
	public function setInputAttrs(array $inputAttrs) {
		$this->inputAttrs = $inputAttrs;
		
		return $this;
	}

	/**
	 * @param mixed $formValue
	 */
	public function setFormValue($formValue) {
		if (!strlen($formValue)) {
			$this->value = null;
			return;
		}
		$this->value = $formValue;
		
		return $this;
	}

	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $view
	 * @return UiComponent
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uo): UiComponent {
		$uiC = new HtmlSnippet();
		$formHtml = $view->getFormHtmlBuilder();
		$options = $this->buildOptions($view->getN2nLocale());
		
		if ($this->useRadios) {
			$uiControls = new HtmlSnippet();
			foreach ($options as $value => $label) {
				$inputAttrs = HtmlUtils::mergeAttrs(
						$uo->createAttrs(UiOutfitter::NATURE_CHECK|UiOutfitter::NATURE_MAIN_CONTROL), $this->inputAttrs);
				
				$snippetUi = new HtmlSnippet();
				$labelUi = $formHtml->getLabel($propertyPath, $label,
						$uo->createAttrs(UiOutfitter::NATURE_CHECK_LABEL));
				$snippetUi->appendLn($formHtml->getInputRadio($propertyPath, $value, $inputAttrs));
				$snippetUi->appendLn($labelUi);
				
				$uiControls->append($uo->createElement(UiOutfitter::EL_NATURE_CONTROL_LIST_ITEM, null, 
						$uo->createElement(UiOutfitter::EL_NATURE_CHECK_WRAPPER, null, $snippetUi)));
			}
			$uiC->append($uo->createElement(UiOutfitter::EL_NATURE_CONTROL_LIST, null, $uiControls));
		} else {
			$attrs = HtmlUtils::mergeAttrs(
					$uo->createAttrs(UiOutfitter::NATURE_SELECT|UiOutfitter::NATURE_MAIN_CONTROL), $this->inputAttrs);
			
			$uiC->append($formHtml->getSelect($propertyPath, $options, $attrs));
		}
		
		
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
		if ($this->isMandatory()) {
			$bd->val($this->getPropertyName(), new ValNotEmpty());
		}
		
		$bd->val($this->getPropertyName(), new ValEnum(array_keys($this->options),
				Message::createCodeArg(ValEnum::DEFAULT_ERROR_TEXT_CODE, array('field' => $this->labelLstr), null, 
						'n2n\impl\web\dispatch')));
	}
}
