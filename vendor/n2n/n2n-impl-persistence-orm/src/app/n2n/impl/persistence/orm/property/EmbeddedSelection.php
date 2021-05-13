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

use n2n\persistence\orm\query\select\SelectionGroup;
use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\PdoStatement;
use n2n\persistence\orm\query\select\ValueBuilder;
use n2n\reflection\ReflectionUtils;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\query\select\Selection;

class EmbeddedSelection implements Selection {
	private $embeddedEntityProperty;
	private $selectionGroup;
	
	public function __construct(EmbeddedEntityProperty $embeddedEntityProperty, MetaTreePoint $metaTreePoint) {
		$this->embeddedEntityProperty = $embeddedEntityProperty;
	
		$this->selectionGroup = new SelectionGroup();
		foreach ($embeddedEntityProperty->getEntityProperties() as $entityProperty) {
			$this->selectionGroup->addSelection($entityProperty->toPropertyString(),
					$metaTreePoint->requestCustomPropertySelection($entityProperty));
		}
	}
	
	// 	private function buildKey(EntityProperty $entityProperty) {
	// 		return $entityProperty->getEntityModel()->getClass()->getName()
	// 				. '::$' . $entityProperty->getName();
	// 	}
	/**
	 * @return \n2n\persistence\meta\data\QueryItem[]
	 */
	public function getSelectQueryItems() {
		return $this->selectionGroup->getSelectQueryItems();
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\query\select\Selection::bindColumns()
	*/
	public function bindColumns(PdoStatement $stmt, array $columnAliases) {
		$this->selectionGroup->bindColumns($stmt, $columnAliases);
	}
	
	public function createValueBuilder() {
		$propertyValueBuilders = array();
		foreach ($this->embeddedEntityProperty->getEntityProperties() as $entityProperty) {
			$propertyString = $entityProperty->toPropertyString();
			$selection = $this->selectionGroup->getSelectionByKey($propertyString);
			$propertyValueBuilders[$propertyString] = $selection->createValueBuilder();
		}
		
		return new EmbeddedValueBuilder($this->embeddedEntityProperty, $propertyValueBuilders);
	}
}

class EmbeddedValueBuilder implements ValueBuilder {
	private $embeddedEntityProperty;
	private $propertyValueBuilders;
	
	public function __construct(EmbeddedEntityProperty $embeddedEntityProperty, array $propertyValueBuilders) {
		$this->embeddedEntityProperty = $embeddedEntityProperty;
		$this->propertyValueBuilders = $propertyValueBuilders;
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\query\select\ValueBuilder::buildValue()
	 */
	public function buildValue() {
		$propertyValues = array();
		$notNull = false;
		foreach ($this->propertyValueBuilders as $propertyString => $propertyValueBuilder) {
			if (null !== ($propertyValues[$propertyString] = $propertyValueBuilder->buildValue())) {
				$notNull = true;
			}
		}

		if (!$notNull) return null;
		
		$object = ReflectionUtils::createObject($this->embeddedEntityProperty->getTargetClass());
		foreach ($this->embeddedEntityProperty->getEntityProperties() as $entityProperty) {
			$propertyString = $entityProperty->toPropertyString();
			IllegalStateException::assertTrue(array_key_exists($propertyString, $propertyValues));
			$entityProperty->writeValue($object, $propertyValues[$propertyString]);
		}
		return $object;
	}

	
	
}
