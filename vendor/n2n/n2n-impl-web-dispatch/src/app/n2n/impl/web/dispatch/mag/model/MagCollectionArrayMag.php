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
use n2n\web\dispatch\mag\Mag;
use n2n\web\dispatch\map\PropertyPath;
use n2n\util\type\ArgUtils;
use n2n\impl\web\dispatch\map\val\ValArraySize;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\impl\web\dispatch\property\ObjectProperty;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\mag\MagDispatchable;
use n2n\web\dispatch\mag\UiOutfitter;

/**
 * Class MagCollectionArrayMag
 * @package n2n\impl\web\dispatch\mag\model
 */
class MagCollectionArrayMag extends MagAdapter {

	const DEFAULT_INC = 10;

	private $creator;
	private $min;
	private $max;

	/**
	 * MagCollectionArrayMag constructor.
	 * @param $label
	 * @param \Closure $creator
	 * @param bool $mandatory
	 * @param array|null $containerAttrs
	 */
	public function __construct($label, \Closure $creator,
			$mandatory = false, array $containerAttrs = null) {
		parent::__construct($label, array(), $containerAttrs);
		$this->creator = $creator;

		if ($mandatory) {
			$this->min = 1;
		}
	}

	/**
	 * @return int
	 */
	public function getNum() {
		return $this->num;
	}

	/**
	 * @param int $num
	 */
	public function setNum(int $num) {
		$this->num = $num;
	}

	/**
	 * @return int
	 */
	public function getMin() {
		return $this->min;
	}

	/**
	 * @param int $min
	 */
	public function setMin(int $min) {
		$this->min = $min;
	}

	/**
	 * @return int
	 */
	public function getMax() {
		return $this->max;
	}

	/**
	 * @param int $max
	 */
	public function setMax(int $max) {
		$this->max = $max;
	}

	/**
	 * @return \Closure
	 */
	public function getCreator() {
		return $this->creator;
	}

	/**
	 * @param AccessProxy $accessProxy
	 * @return ManagedProperty
	 */
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		$property = new ObjectProperty($accessProxy, true);
		$property->setCreator($this->creator);
		return $property;
	}

	/**
	 * @param BindingDefinition $bd
	 */
	public function setupBindingDefinition(BindingDefinition $bd) {
		$bd->val($this->propertyName, new ValArraySize($this->min, null, $this->max));		
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value) {
		ArgUtils::valArray($value, 'array');
		$this->value = $value;
	}

	/**
	 * @param mixed $value
	 */
	public function setFormValue($value) {
		$this->value = array();
		foreach ((array) $value as $magDispatchable) {
			$this->value[] = $magDispatchable->getMagCollection()->readValues(); 
		}
	}

	/**
	 * @return array
	 */
	public function getFormValue() {
		$magDispatchables = array();
		foreach ($this->value as $fieldValues) {
			$magDispatchable = $this->createFieldMagDispatchable();
			$magDispatchable->getMagCollection()->writeValues($fieldValues);
			$magDispatchables[] = $magDispatchable;
		}
		return $magDispatchables;
	}

	/**
	 * @return MagDispatchable
	 */
	private function createFieldMagDispatchable(): MagDispatchable {
		$magDispatchable = $this->creator->__invoke();
		ArgUtils::valTypeReturn($magDispatchable, MagDispatchable::class, null, $this->creator);
		return $magDispatchable;
	}

	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $view
	 * @return UiComponent
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uiOutfitter): UiComponent {
		$numExisting = sizeof($view->getFormHtmlBuilder()->meta()->getMapValue($propertyPath));
		$attrs = HtmlUtils::mergeAttrs($this->getContainerAttrs($view));
		$this->setAttrs($attrs);

		$num = $numExisting;
		if (isset($this->max) && $this->max > $num) {
			$num = $this->max;
		} else {
			$num += self::DEFAULT_INC;
		}
    
		return $view->getImport('\n2n\impl\web\dispatch\mag\view\magCollectionArrayMag.html',
				array('propertyPath' => $propertyPath, 'uiOutfitter' => $uiOutfitter, 'numExisting' => $numExisting, 'num' => $num));
	}

	public function getNature(): int{
		return Mag::NATURE_GROUP;
	}
}
