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
namespace n2n\impl\web\dispatch;

use n2n\util\type\ArgUtils;
use n2n\web\dispatch\Dispatchable;
use n2n\web\dispatch\DynamicDispatchable;
use n2n\web\dispatch\model\DispatchModel;
use n2n\impl\web\dispatch\property\ObjectProperty;
use n2n\web\dispatch\property\DynamicAccessProxy;
use n2n\web\dispatch\map\bind\MappingDefinition;
use n2n\web\dispatch\map\bind\BindingDefinition;

class DispatchableCollection implements DynamicDispatchable {
	/**
	 * @var Dispatchable[]
	 */
	private $dispatchables = [];
	/**
	 * @var string[]
	 */
	private $ignoredPropertyNames = [];
	
	/**
	 * @param Dispatchable[] $dispatchables
	 */
	public function __construct(array $dispatchables) {
		$this->setDispatchables($dispatchables);
	}
	
	/**
	 * @return Dispatchable[]
	 */
	public function getDispatchables() {
		return $this->dispatchables;
	}
	
	/**
	 * @param Dispatchable[] $dispatchables
	 */
	public function setDispatchables(array $dispatchables) {
		ArgUtils::valArray($dispatchables, Dispatchable::class);
		$this->dispatchables = $dispatchables;
	}
	
	/**
	 * @param string $key
	 * @param Dispatchable $dispatchable
	 */
	public function putDispatchable(string $key, Dispatchable $dispatchable) {
		$this->dispatchables[$key] = $dispatchable;
	}
	
	/**
	 * @param string $key
	 * @return Dispatchable|null
	 */
	public function getDispatchableByKey(string $key) {
		return $this->dispatchables[$key];
	}
	
	/**
	 * @param string $key
	 */
	public function removeDispatchableByKey(string $key) {
		unset($this->dispatchables[$key]);
	}
	
	/**
	 * @return string[]
	 */
	public function getKeys() {
		return array_keys($this->dispatchables);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\DynamicDispatchable::setup()
	 */
	public function setup(DispatchModel $dispatchModel) {
		foreach ($this->dispatchables as $key => $dispatchable) {
			$dispatchModel->addProperty(new ObjectProperty(new DynamicAccessProxy($key), false));
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\DynamicDispatchable::getPropertyValue()
	 */
	public function getPropertyValue(string $name) {
		if (isset($this->dispatchables[$name])) {
			return $this->dispatchables[$name];
		}
		
		return null;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\DynamicDispatchable::setPropertyValue()
	 */
	public function setPropertyValue(string $name, $value) {
		$this->dispatchables[$name] = $value;
	}
	
	/**
	 * @param string[] $ignoredPropertyNames
	 */
	public function setIgnoredPropertyNames(array $ignoredPropertyNames) {
		ArgUtils::valArray($ignoredPropertyNames, 'string');
		$this->ignoredPropertyNames = $ignoredPropertyNames;
	}
	
	/**
	 * @param string $propertyName
	 */
	public function addIgnoredPropertyName(string $propertyName) {
		$this->ignoredPropertyNames[] = $propertyName;
	}
	
	/**
	 * @param string $propertyName
	 */
	public function removeIgnoredPropertyname(string $propertyName) {
		while (false !== ($key = array_search($propertyName, $this->ignoredPropertyNames))) {
			unset($this->ignoredPropertyNames[$key]);
		}
	}
	
	/**
	 * @return string[]
	 */
	public function getIgnoredPropertyNames() {
		return $this->ignoredPropertyNames;
	}
	
	private function _mapping(MappingDefinition $md) {
		foreach ($this->ignoredPropertyNames as $propertyName) {
			$md->ignore($propertyName);
		}
	}
	
	private function _validation(BindingDefinition $bd) { }
}
