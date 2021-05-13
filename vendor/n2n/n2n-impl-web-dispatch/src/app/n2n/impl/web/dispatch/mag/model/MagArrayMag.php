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

use n2n\web\dispatch\mag\Mag;
use n2n\web\ui\UiComponent;
use n2n\util\type\ArgUtils;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\dispatch\mag\MagCollection;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\impl\web\ui\view\html\HtmlUtils;

class MagArrayMag extends MagAdapter {
	const PROPERTY_NAME = 'field';
	
	private $decorated;

	/**
	 * MagArrayMag constructor.
	 * @param $label
	 * @param \Closure $magCreator
	 * @param bool $required
	 * @param array|null $containerAttrs
	 */
	public function __construct($label, \Closure $magCreator, bool $required = false, array $containerAttrs = null) {
		$this->decorated = new MagCollectionArrayMag($label, function () use ($magCreator) {
			$mag = $magCreator();
			ArgUtils::valTypeReturn($mag, Mag::class, null, $magCreator);
			$magCollection = new MagCollection();
			$magCollection->addMag(MagArrayMag::PROPERTY_NAME, $mag);
			return new MagForm($magCollection);
		});
	}

	public function setPropertyName(string $propertyName) {
		$this->decorated->setPropertyName($propertyName);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::getPropertyName()
	 */
	public function getPropertyName(): string {
		return $this->decorated->getPropertyName();
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::getLabel()
	 */
	public function getLabel(\n2n\l10n\N2nLocale $n2nLocale): string {
		return $this->decorated->getLabel($n2nLocale);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::createManagedProperty()
	 */
	public function createManagedProperty(\n2n\reflection\property\AccessProxy $accessProxy): ManagedProperty {
		return $this->decorated->createManagedProperty($accessProxy);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::setupMappingDefinition()
	 */
	public function setupMappingDefinition(\n2n\web\dispatch\map\bind\MappingDefinition $md) {
		$this->decorated->setupMappingDefinition($md);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::setupBindingDefinition()
	 */
	public function setupBindingDefinition(\n2n\web\dispatch\map\bind\BindingDefinition $bd) {
		$this->decorated->setupBindingDefinition($bd);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::getFormValue()
	 */
	public function getFormValue() {
		return $this->decorated->getFormValue();
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::setFormValue()
	 */
	public function setFormValue($value) {
		$this->decorated->setFormValue($value);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::getContainerAttrs()
	 */
	public function getContainerAttrs(\n2n\impl\web\ui\view\html\HtmlView $view): array {
		return $this->decorated->getContainerAttrs($view);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::createUiField()
	 */
	public function createUiField(\n2n\web\dispatch\map\PropertyPath $propertyPath, \n2n\impl\web\ui\view\html\HtmlView $view,
			UiOutfitter $uiOutfitter): UiComponent {
		$numExisting = sizeof($view->getFormHtmlBuilder()->meta()->getMapValue($propertyPath));
		$attrs = HtmlUtils::mergeAttrs($this->getContainerAttrs($view));
		$this->setAttrs($attrs);
		
		$num = $numExisting;
		if (isset($this->max) && $this->max > $num) {
			$num = $this->max;
		} else {
			$num += $this->decorated::DEFAULT_INC;
		}
		
		return $view->getImport('\n2n\impl\web\dispatch\mag\view\magArrayMag.html',
				array('propertyPath' => $propertyPath, 'uiOutfitter' => $uiOutfitter, 'numExisting' => $numExisting, 'num' => $num));
				
		//return $this->decorated->createUiField($propertyPath, $view, $uiOutfitter);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::getValue()
	 */
	public function getValue() {
		$value = array();
		foreach ($this->decorated->getValue() as $key => $decoratedFieldValue) {
			if (array_key_exists(self::PROPERTY_NAME, $decoratedFieldValue)) {
				$value[$key] = $decoratedFieldValue[self::PROPERTY_NAME];
			}
		}
		return $value;
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::setValue()
	 */
	public function setValue($value) {
		ArgUtils::valType($value, 'array');
		
		$decoratedValue = array();
		foreach ($value as $key => $fieldValue) {
			$decoratedValue[$key] = array(self::PROPERTY_NAME => $fieldValue);
		}
		$this->decorated->setValue($decoratedValue);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::whenAssigned()
	 */
	public function whenAssigned(\n2n\web\dispatch\mag\MagCollection $magCollection) {
		$this->decorated->whenAssigned($magCollection);
	}

	public function isGroup(): bool {
		return true;
	}
}
