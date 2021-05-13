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
namespace n2n\impl\persistence\orm\property\relation\compare;

use n2n\persistence\orm\criteria\compare\ColumnComparable;
use n2n\persistence\orm\model\EntityModel;
use n2n\util\type\TypeConstraint;
use n2n\persistence\orm\criteria\compare\CriteriaComparator;
use n2n\util\type\ArgUtils;

class IdColumnComparableDecorator implements ColumnComparable {
	private $idColumnComparable;	
	private $entityModel;
	private $idEntityProperty;
	
	public function __construct(ColumnComparable $idColumnComparable, EntityModel $entityModel) {
		$this->idColumnComparable = $idColumnComparable;
		$this->entityModel = $entityModel;
		$this->idEntityProperty = $entityModel->getIdDef()->getEntityProperty();
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\ColumnComparable::getAvailableOperators()
	 */
	public function getAvailableOperators() {
		return $this->idColumnComparable->getAvailableOperators();
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\ColumnComparable::getTypeConstraint()
	 */
	public function getTypeConstraint($operator) {
		return TypeConstraint::createSimple($this->entityModel->getClass()->getName());
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\ColumnComparable::isSelectable()
	 */
	public function isSelectable($operator) {
		return $this->idColumnComparable->isSelectable($operator);
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\ColumnComparable::buildQueryItem($operator)
	 */
	public function buildQueryItem($operator) {
		return $this->idColumnComparable->buildQueryItem($operator);	
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\ColumnComparable::buildCounterpartQueryItemFromValue()
	 */	
	public function buildCounterpartQueryItemFromValue($operator, $value) {
		return $this->idColumnComparable->buildCounterpartQueryItemFromValue($operator, 
				$this->parseComparableValue($operator, $value));	
	}
	
	private function parseComparableValue($operator, $value) {
		if ($operator !== CriteriaComparator::OPERATOR_IN
				&& $operator !== CriteriaComparator::OPERATOR_NOT_IN) {
			return $this->parseFieldValue($value);
		}
		
		ArgUtils::valArrayLike($value, 'object');
		$idValues = array();
		foreach ($value as $key => $fieldValue) {
			$idValues[$key] = $this->parseFieldValue($fieldValue);
		}
		return $idValues;
	}
	
	private function parseFieldValue($value) {
		if ($value === null) return null;
		
		ArgUtils::assertTrue(is_object($value));
		return $this->idEntityProperty->readValue($value);
	}
}
