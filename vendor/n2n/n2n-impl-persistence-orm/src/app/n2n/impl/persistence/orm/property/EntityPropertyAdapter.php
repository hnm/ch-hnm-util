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
namespace n2n\impl\persistence\orm\property;

use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\property\EntityProperty;
use n2n\reflection\property\AccessProxy;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\model\EntityPropertyCollection;
use n2n\persistence\orm\EntityDataException;
use n2n\reflection\ReflectionException;
use n2n\util\type\TypeUtils;

abstract class EntityPropertyAdapter implements EntityProperty {
	private $entityModel;
	private $parent;
	protected $accessProxy;
	
	/**
	 * @param AccessProxy $accessProxy
	 */
	public function __construct(AccessProxy $accessProxy) {
		$this->accessProxy = $accessProxy;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::setEntityModel()
	 */
	public function setEntityModel(EntityModel $entityModel) {
		$this->entityModel = $entityModel;	
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::getEntityModel()
	 */
	public function getEntityModel() {
		if ($this->entityModel === null) {
			throw new IllegalStateException('No EntityModel assigned.');
		}
		
		return $this->entityModel;
	}
	
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::setParent()
	 */
	public function setParent(EntityProperty $parent) {
		$this->parent = $parent;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::getParent()
	 */
	public function getParent() {
		return $this->parent;
	}
	
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::getName()
	 */
	public function getName() {
		return $this->accessProxy->getPropertyName();
	}
	
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\property\EntityProperty::writeValue()
	 */
	public function writeValue($object, $value) {
		$this->accessProxy->setValue($object, $value);
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\property\EntityProperty::readValue()
	 */
	public function readValue($object) {
		try {
			return $this->accessProxy->getValue($object);
		} catch (ReflectionException $e) {
			throw new EntityDataException('Failed to read value of ' . $this, 0, $e);
		}
	}
	
	public function toPropertyString() {
		return TypeUtils::prettyReflPropName($this->accessProxy->getProperty());
	}
	
	public function copy($value) {
		return $value;
	}
	
	public function equals($obj) {
		return $obj instanceof EntityProperty
				&& $obj->getEntityModel()->equals($this->entityModel)
				&& $obj->getName() == $this->getName(); 
	}
	
	public function hasTargetEntityModel(): bool {
		return false;
	}
	
	public function getTargetEntityModel(): EntityModel {
		throw new IllegalStateException('EntityProperty contains no target EntityModel: ' . $this);
	}
	
	public function hasEmbeddedEntityPropertyCollection(): bool {
		return false;
	}
	
	public function getEmbeddedEntityPropertyCollection(): EntityPropertyCollection {
		throw new IllegalStateException('EntityProperty contains no target EntityPropertyCollection: ' . $this);
	}
	
	public function __toString(): string {
		return (new \ReflectionClass($this))->getShortName() . ' [' . $this->accessProxy . ']';
	}
}
