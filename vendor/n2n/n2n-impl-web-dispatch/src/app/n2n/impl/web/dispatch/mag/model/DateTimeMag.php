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
use n2n\l10n\Lstr;
use n2n\web\dispatch\map\PropertyPath;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\dispatch\property\DateTimeProperty;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\impl\web\ui\view\html\HtmlSnippet;

/**
 * Class DateTimeMag
 * @package n2n\impl\web\dispatch\mag\model
 */
class DateTimeMag extends MagAdapter {
	private $mandatory;
	private $dateStyle;
	private $timeStyle;
	private $icuPattern;
	protected $inputAttrs;

	/**
	 * DateTimeMag constructor.
	 * @param string|Lstr $label
	 * @param string $dateStyle
	 * @param string $timeStyle
	 * @param string $icuPattern
	 * @param \DateTime|null $value
	 * @param bool $mandatory
	 * @param array|null $inputAttrs
	 */
	public function __construct($label, string $dateStyle = null, string $timeStyle = null,
			string $icuPattern = null, \DateTime $value = null, bool $mandatory = false, array $inputAttrs = null) {
		parent::__construct($label, $value);
		$this->mandatory = $mandatory;
		$this->dateStyle = $dateStyle;
		$this->timeStyle = $timeStyle;
		$this->icuPattern = $icuPattern;
		$this->inputAttrs = $inputAttrs;
	}

	/**
	 * @return string
	 */
	public function getDateStyle(): string {
		return $this->dateStyle;
	}

	/**
	 * @param string $dateStyle
	 */
	public function setDateStyle(string $dateStyle) {
		$this->dateStyle = $dateStyle;
	}

	/**
	 * @return string
	 */
	public function getTimeStyle(): string {
		return $this->timeStyle;
	}

	/**
	 * @param string $timeStyle
	 */
	public function setTimeStyle(string $timeStyle) {
		$this->timeStyle = $timeStyle;
	}

	/**
	 * @return string
	 */
	public function getIcuPattern(): string {
		return $this->icuPattern;
	}

	/**
	 * @param string $icuPattern
	 */
	public function setIcuPattern(string $icuPattern) {
		$this->icuPattern = $icuPattern;
	}

	/**
	 * @param AccessProxy $accessProxy
	 * @return ManagedProperty
	 */
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		$dateTimeProperty = new DateTimeProperty($accessProxy, false);
		
		if (isset($this->dateStyle)) {
			$dateTimeProperty->setDateStyle($this->dateStyle);
		}
		if (isset($this->timeStyle)) {
			$dateTimeProperty->setTimeStyle($this->timeStyle);
		}
		if (isset($this->icuPattern)) {
			$dateTimeProperty->setIcuPattern($this->icuPattern);
		}
		
		return $dateTimeProperty;
	}

	/**
	 * @param BindingDefinition $bd
	 */
	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->mandatory) {
			$bd->val($this->propertyName, new ValIsset());
		}
	}

	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $view
	 * @return UiComponent
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uo): UiComponent {
		$attrs = HtmlUtils::mergeAttrs($uo->createAttrs(UiOutfitter::NATURE_TEXT|UiOutfitter::NATURE_MAIN_CONTROL),
				$this->inputAttrs);
		
		$uiC = new HtmlSnippet($view->getFormHtmlBuilder()->getInput($propertyPath, $attrs));
		
		if (null !== $this->helpTextLstr) {
			$uiC->append($uo->createElement(UiOutfitter::EL_NATURE_HELP_TEXT, null,
					$this->getHelpText($view->getN2nLocale())));
		}
		
		return $uiC;
	}
}
