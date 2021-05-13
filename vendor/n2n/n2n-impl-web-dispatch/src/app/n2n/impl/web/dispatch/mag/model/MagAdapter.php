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

use n2n\util\ex\IllegalStateException;
use n2n\web\dispatch\map\bind\MappingDefinition;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\dispatch\mag\Mag;
use n2n\l10n\N2nLocale;
use n2n\l10n\Lstr;
use n2n\web\dispatch\mag\MagCollection;

/**
 * Class MagAdapter
 * @package n2n\impl\web\dispatch\mag\model
 */
abstract class MagAdapter implements Mag {
	protected $propertyName;
	protected $labelLstr;
	protected $helpTextLstr;
	protected $attrs = array();
	protected $value;

	/**
	 * MagAdapter constructor.
	 * @param $labelLstr
	 * @param null $value
	 * @param array|null $attrs
	 */
	public function __construct($labelLstr, $value = null, array $attrs = null) {
		$this->labelLstr = Lstr::create($labelLstr);
		$this->setAttrs((array) $attrs);
		$this->value = $value;
	}

	public function setPropertyName(string $name) {
		$this->propertyName = $name;
	}

	/**
	 * @return string
	 */
	public function getPropertyName(): string {
		if ($this->propertyName === null) {
			throw new IllegalStateException('Property name not set for Mag: ' . get_class($this));
		}
		return $this->propertyName;
	}

	/**
	 * @param N2nLocale $n2nLocale
	 * @return string
	 */
	public function getLabel(N2nLocale $n2nLocale): string {
		return $this->labelLstr->t($n2nLocale);
	}

	/**
	 * @param Lstr $labelL10nStr
	 */
	public function setLabelLstr(Lstr $labelL10nStr) {
		$this->labelLstr = $labelL10nStr;
	}
	
	public function getHelpText(N2nLocale $n2nLocale) {
		return $this->helpTextLstr->t($n2nLocale);
	}
	
	public function setHelpTextLstr(Lstr $helpTextLstr) {
		$this->helpTextLstr = $helpTextLstr;
	}
	
	public function getContainerAttrs(HtmlView $view): array {
		return $this->attrs;
	}
	
	public function getAttrs() {
		return $this->attrs;
	}
	
	public function setAttrs(array $attrs) {
		$this->attrs = $attrs;
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\mag\Mag::setupMappingDefinition()
	 */
	public function setupMappingDefinition(MappingDefinition $md) {
		$md->getMappingResult()->setLabel($this->getPropertyName(), (string) $this->labelLstr);
	}
	
	/**
	 * @return mixed
	 */
	public function getFormValue() {
		return $this->value;
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\mag\Mag::setValue()
	 */
	public function setFormValue($formValue) {
		$this->value = $formValue;
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\mag\Mag::getValue()
	 */
	public function getValue() {
		return $this->value;
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\mag\Mag::setValue()
	 */
	public function setValue($value) {
		$this->value = $value;
	}
	
	public function whenAssigned(MagCollection $magCollection) {
	}

	public function getNature(): int {
		return 0;
	}
}
