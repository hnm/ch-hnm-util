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

use n2n\web\dispatch\mag\MagCollection;
use n2n\web\dispatch\mag\MagDispatchable;
use n2n\web\dispatch\map\bind\MappingDefinition;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\web\dispatch\model\DispatchModel;
use n2n\web\dispatch\property\DynamicAccessProxy;

/**
 * Class MagForm
 * @package n2n\impl\web\dispatch\mag\model
 */
class MagForm implements MagDispatchable {
	private $magCollection;

	/**
	 * MagForm constructor.
	 * @param MagCollection $magCollection
	 */
	public function __construct(MagCollection $magCollection) {
		$this->magCollection = $magCollection;
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\mag\MagDispatchable::getMagCollection()
	 */
	public function getMagCollection(): MagCollection {
		return $this->magCollection;
	}

	/**
	 * @return array
	 */
	public function getPropertyNames() {
		return array_keys($this->magCollection->getMags());
	}

	/**
	 * @param $propertyName
	 * @return bool
	 */
	public function containsPropertyName(string $propertyName) {
		return $this->magCollection->containsPropertyName($propertyName);
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function getPropertyValue(string $name) {
		return $this->magCollection->getMagWrapperByPropertyName($name)->getMag()->getFormValue();
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function setPropertyValue(string $name, $value) {
		$this->magCollection->getMagWrapperByPropertyName($name)->getMag()->setFormValue($value);
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\dispatch\DynamicDispatchable::setup()
	 */
	public function setup(DispatchModel $dispatchModel) {
		foreach ($this->magCollection->getMagWrappers() as $name => $magWrapper) {
			$dispatchModel->addProperty($magWrapper->getMag()->createManagedProperty(new DynamicAccessProxy($name)));
		}
	}

	/**
	 * @param MappingDefinition $mappingDefinition
	 */
	private function _mapping(MappingDefinition $mappingDefinition) {
		$this->magCollection->setupMappingDefinition($mappingDefinition);
	}

	/**
	 * @param BindingDefinition $bindingDefinition
	 */
	private function _validation(BindingDefinition $bindingDefinition) {
		$this->magCollection->setupBindingDefinition($bindingDefinition);
	}
}