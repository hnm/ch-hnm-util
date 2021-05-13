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

use n2n\persistence\orm\criteria\compare\CustomComparable;
use n2n\persistence\meta\data\QueryComparator;
use n2n\persistence\orm\criteria\compare\ComparisonStrategy;
use n2n\persistence\orm\criteria\CriteriaConflictException;
use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\query\from\TreePath;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\persistence\orm\property\EntityProperty;
use n2n\persistence\orm\criteria\compare\QueryComparatorBuilder;
use n2n\util\ex\IllegalStateException;
use n2n\util\type\TypeUtils;

class EmbeddedCustomComparable implements CustomComparable {
	private $metaTreePoint;
	private $embeddedEntityProperty;
	
	public function __construct(MetaTreePoint $metaTreePoint, EmbeddedEntityProperty $embeddedEntityProperty) {
		$this->metaTreePoint = $metaTreePoint;
		$this->embeddedEntityProperty = $embeddedEntityProperty;
	}
	/**
	 * @return MetaTreePoint
	 */
	public function getMetaTreePoint() {
		return $this->metaTreePoint;
	}
	/**
	 * @return EmbeddedEntityProperty
	 */
	public function getEmbeddedEntityProperty() {
		return $this->embeddedEntityProperty;
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\CustomComparable::compareWithValue()
	 */
	public function compareWithValue(QueryComparator $queryComparator, $operator, $object) {
		if ($object !== null && !is_a($object, $this->embeddedEntityProperty->getTargetClass()->getName())) {
			throw new CriteriaConflictException('Type ' . $this->embeddedEntityProperty->getTargetClass()->getName() 
					. ' required. Given: ' . TypeUtils::getTypeInfo($object));
		}
		
		$queryComparator = $queryComparator->andGroup();
		foreach ($this->embeddedEntityProperty->getEntityProperties() as $entityProperty) {
			$this->applyComparisonWithValue($queryComparator, $object, $entityProperty, $operator);
		}
	}
	
	private function applyComparisonWithValue(QueryComparator $queryComparator, $object, EntityProperty $entityProperty, $operator) {
		$propertyComparisonStrategy = $this->metaTreePoint->requestPropertyComparisonStrategy(new TreePath(array($entityProperty->getName())));
		$propertyValue = null;
		if ($object !== null) {
			$propertyValue = $entityProperty->readValue($object);
		}
		
		$previousE = null;
		try {
			QueryComparatorBuilder::applyPropertyValueComparison($queryComparator, $propertyComparisonStrategy, $operator, $propertyValue, true);
			return;
		} catch (ValueIncompatibleWithConstraintsException $e) {
			$previousE = $e;
		} catch (CriteriaConflictException $e) {
			$previousE = $e;
		}
		
		throw new CriteriaConflictException('Property ' . $entityProperty->toPropertyString() . ' can not be compared with value: <' 
				. TypeUtils::getTypeInfo($propertyValue) . '>');
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\CustomComparable::compareWith()
	 */
	public function compareWith(QueryComparator $queryComparator, $operator, ComparisonStrategy $comparisonStrategy) {
		$customComparable = null;
		$previousE = null;
		try {
			$customComparable = $comparisonStrategy->getCustomComparable();
		} catch (IllegalStateException $e) {
			$previousE = $e;
		} 
		
		if (!($customComparable instanceof EmbeddedCustomComparable) 
				|| $this->embeddedEntityProperty->getTargetClass() != $customComparable->getEmbeddedEntityProperty()->getTargetClass()) {
			$customComparable = null;
		}

		if ($customComparable === null) {
			throw new CriteriaConflictException($this->embeddedEntityProperty->toPropertyString()
					. ' can only be compared to other embedded properties of the same type.', 0, $previousE);
		}
		
		foreach ($this->embeddedEntityProperty->getEntityProperties() as $entityProperty) {
			$this->applyComparison($queryComparator, $entityProperty, $operator, $customComparable);
		}
	}

	private function applyComparison(QueryComparator $queryComparator, 
			EntityProperty $entityProperty, $operator, EmbeddedCustomComparable $customComparable) {
		$propertyComparisonStrategy1 = $this->metaTreePoint->requestPropertyComparisonStrategy(
				new TreePath(array($entityProperty->getName())));
		$propertyComparisonStrategy2 = $customComparable->getMetaTreePoint()->requestPropertyComparisonStrategy(
				new TreePath(array($entityProperty->getName())));
		
		$previousE = null;
		try {
			QueryComparatorBuilder::applyComparison($queryComparator, $propertyComparisonStrategy1, $operator, $propertyComparisonStrategy2, true);
			return;
		} catch (CriteriaConflictException $e) {
			// @todo
			throw new CriteriaConflictException('Property ' . $entityProperty->toPropertyString() 
					. ' can not be compared... make better exception here');
		}		
	}
}
