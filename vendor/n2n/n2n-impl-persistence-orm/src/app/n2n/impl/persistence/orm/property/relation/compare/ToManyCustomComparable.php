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

use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\query\from\TreePath;
use n2n\persistence\orm\criteria\compare\CriteriaComparator;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\criteria\compare\ComparisonStrategy;
use n2n\persistence\meta\data\QueryPlaceMarker;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\query\QueryState;
use n2n\util\type\TypeConstraint;
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\criteria\compare\CustomComparable;
use n2n\persistence\meta\data\QueryComparator;
use n2n\persistence\orm\criteria\CriteriaConflictException;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\persistence\orm\criteria\compare\QueryComparatorBuilder;
use n2n\persistence\orm\query\QueryModel;
use n2n\persistence\orm\query\from\Tree;
use n2n\persistence\orm\query\QueryItemSelect;
use n2n\persistence\orm\criteria\item\ConstantQueryPoint;
use n2n\persistence\meta\data\QueryItem;

class ToManyCustomComparable implements CustomComparable {
	private $metaTreePoint;
	private $targetIdTreePath;
	private $toManyQueryItemFactory;
	private $queryState;
	private $typeConstraint;

	private $entityColumnComparable;
	private $toManyQueryItem;

	public function __construct(MetaTreePoint $metaTreePoint, EntityModel $targetEntityModel, 
			TreePath $targetIdTreePath, ToManyQueryItemFactory $toManyQueryItemFactory, 
			QueryState $queryState) {
		$this->metaTreePoint = $metaTreePoint;
		$this->targetIdTreePath = $targetIdTreePath;
		$this->targetEntityModel = $targetEntityModel;
		$this->toManyQueryItemFactory = $toManyQueryItemFactory;
		$this->queryState = $queryState;
		$this->typeConstraint = TypeConstraint::createSimple($this->targetEntityModel->getClass()->getName(), true);
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\ColumnComparable::getAvailableOperators()
	*/
	public function getAvailableOperators() {
		return array(CriteriaComparator::OPERATOR_CONTAINS, CriteriaComparator::OPERATOR_CONTAINS_NOT,
				CriteriaComparator::OPERATOR_CONTAINS_ANY, CriteriaComparator::OPERATOR_CONTAINS_NONE);
	}
		
	private function requestEntityColumnComparable() {
		if ($this->entityColumnComparable !== null) {
			return $this->entityColumnComparable;
		}
		
		$targetIdComparisonStrategy = $this->metaTreePoint->requestPropertyComparisonStrategy(
				$this->targetIdTreePath->copy());
		IllegalStateException::assertTrue($targetIdComparisonStrategy->getType() == ComparisonStrategy::TYPE_COLUMN);
		
		return $this->entityColumnComparable = new IdColumnComparableDecorator(
				$targetIdComparisonStrategy->getColumnComparable(),
				$this->targetEntityModel);
	}
	
// 	/* (non-PHPdoc)
// 	 * @see \n2n\persistence\orm\criteria\compare\ColumnComparable::buildQueryItem($operator)
// 	*/
// 	public function buildQueryItem($operator) {
		
// 	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\ColumnComparable::buildCounterpartQueryItemFromValue()
	 */
	public function buildCounterpartQueryItemFromValue($operator, $value) {
		if ($operator == CriteriaComparator::OPERATOR_CONTAINS) {
			return $this->requestEntityColumnComparable()
					->buildCounterpartQueryItemFromValue($operator, $value);
		}
		
		return new QueryPlaceMarker($this->queryState->registerPlaceholderValue(
				$this->parseFieldValue($value)));
	}
	
	private function validateOperator($operator) {
		if ($operator == CriteriaComparator::OPERATOR_CONTAINS 
				|| $operator == CriteriaComparator::OPERATOR_CONTAINS_NOT
				|| $operator == CriteriaComparator::OPERATOR_CONTAINS_ANY
				|| $operator == CriteriaComparator::OPERATOR_CONTAINS_NONE) return;
		
		throw new CriteriaConflictException('Invalid operator \'' . $operator 
				. '\' for comparison. Available operators: ' . CriteriaComparator::OPERATOR_CONTAINS 
				. ', ' . CriteriaComparator::OPERATOR_CONTAINS_NOT);
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\CustomComparable::compareWithValue()
	 */
	public function compareWithValue(QueryComparator $queryComparator, $operator, $value) {
		$this->validateOperator($operator);
		try {
			switch ($operator) {
				case CriteriaComparator::OPERATOR_CONTAINS:
				case CriteriaComparator::OPERATOR_CONTAINS_NOT:
					$this->typeConstraint->validate($value);
					break;
				case CriteriaComparator::OPERATOR_CONTAINS_ANY:
				case CriteriaComparator::OPERATOR_CONTAINS_NONE:
					TypeConstraint::createArrayLike(null, true, $this->typeConstraint)->validate($value);
			}
		} catch (ValueIncompatibleWithConstraintsException $e) {
			throw new CriteriaConflictException('Value can not be compared with property.', 0, $e);
		}
		
		if ($operator == CriteriaComparator::OPERATOR_CONTAINS) {
			$entityColumnComparable = $this->requestEntityColumnComparable();
			$queryComparator->match(
					$entityColumnComparable->buildQueryItem(CriteriaComparator::OPERATOR_EQUAL), 
					QueryComparator::OPERATOR_EQUAL,
					$entityColumnComparable->buildCounterpartQueryItemFromValue(
							CriteriaComparator::OPERATOR_EQUAL, $value));
			return;
		}
		
		if ($operator == CriteriaComparator::OPERATOR_CONTAINS_NOT) {
			$queryComparator->match(
					new QueryPlaceMarker($this->queryState->registerPlaceholderValue(
							$this->parseFieldValue($value)),
							QueryComparator::OPERATOR_IN, $this->requestToManyQueryItem()));
			return;
		}
		
		$entityColumnComparable = $this->requestEntityColumnComparable();
		$testQueryResult = $this->createTestQueryResult(
				$entityColumnComparable->buildCounterpartQueryItemFromValue(
						QueryComparator::OPERATOR_IN, $value));
		
		if ($operator == CriteriaComparator::OPERATOR_CONTAINS_ANY) {
			$queryComparator->test(QueryComparator::OPERATOR_EXISTS, $testQueryResult);
			return;
		}
		
		if ($operator == CriteriaComparator::OPERATOR_CONTAINS_NONE) {
			$queryComparator->test(QueryComparator::OPERATOR_NOT_EXISTS, $testQueryResult);
			return;
		}
	}
	
	private function requestIdCc(MetaTreePoint $metaTreePoint, TreePath $treePath) {
		$idComparisonStrategy = $metaTreePoint->requestPropertyComparisonStrategy($treePath);
		IllegalStateException::assertTrue($idComparisonStrategy->getType() == ComparisonStrategy::TYPE_COLUMN);
		
		return $idComparisonStrategy->getColumnComparable();
	}
	
	/**
	 * @param object $entity
	 * @return string
	 */
	private function parseTargetIdRaw($entity) {
		$targetIdProperty = $this->targetEntityModel->getIdDef()->getEntityProperty();
	
		$id = null;
		if ($entity !== null) {
			ArgUtils::assertTrue(is_object($entity));
			$id = $targetIdProperty->readValue($entity);
		}
	
		return $targetIdProperty->buildRaw($id, $this->queryState->getEntityManager()->getPdo());
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\criteria\compare\CustomComparable::compareWith()
	 */
	public function compareWith(QueryComparator $queryComparator, $operator, ComparisonStrategy $comparisonStrategy) {
		$this->validateOperator($operator);
		
		if ($comparisonStrategy->getType() != ComparisonStrategy::TYPE_COLUMN) {
			throw new CriteriaConflictException('Incompatible comparison');
		}
		
		$columnComparable = $comparisonStrategy->getColumnComparable();
		
		$oppositeOperator = null;
		$testTypeConstraint = null;
		switch ($operator) {
			case CriteriaComparator::OPERATOR_CONTAINS_ANY:
			case CriteriaComparator::OPERATOR_CONTAINS_NONE:
				$oppositeOperator = CriteriaComparator::OPERATOR_CONTAINS;
				$testTypeConstraint = TypeConstraint::createArrayLike(null, false, $this->typeConstraint);
				break;
			default:
				$oppositeOperator = QueryComparatorBuilder::oppositeOperator($operator);
				$testTypeConstraint = $this->typeConstraint;
			
		}
		
		if (!$testTypeConstraint->isPassableBy($columnComparable->getTypeConstraint($oppositeOperator))) {
			$arrayTypeConstraint = TypeConstraint::createArrayLike(null, false, $this->typeConstraint);
			throw new CriteriaConflictException('Incompatible comparison: ' 
					. $arrayTypeConstraint->__toString() . ' ' . $operator . ' ' 
					. $columnComparable->getTypeConstraint($oppositeOperator) );
		}
		
		if ($operator == CriteriaComparator::OPERATOR_CONTAINS) {
			$entityColumnComparable = $this->requestEntityColumnComparable();
			$queryComparator->match(
					$entityColumnComparable->buildQueryItem(CriteriaComparator::OPERATOR_EQUAL),
					QueryComparator::OPERATOR_EQUAL,
					$columnComparable->buildQueryItem(CriteriaComparator::OPERATOR_EQUAL));
			return;
		}
		
		if ($operator == CriteriaComparator::OPERATOR_CONTAINS_NOT) {
			$queryComparator->match(
					$columnComparable->buildQueryItem(CriteriaComparator::OPERATOR_NOT_IN),
					QueryComparator::OPERATOR_NOT_IN, $this->requestToManyQueryItem());
			return;
		}
		
		
		$testQueryResult = $this->createTestQueryResult($columnComparable->buildQueryItem($oppositeOperator));
		
		if ($operator == CriteriaComparator::OPERATOR_CONTAINS_ANY) {
			$queryComparator->test(QueryComparator::OPERATOR_EXISTS, $testQueryResult);
			return;
		}
		
		if ($operator == CriteriaComparator::OPERATOR_CONTAINS_NONE) {
			$queryComparator->test(QueryComparator::OPERATOR_NOT_EXISTS, $testQueryResult);
			return;
		}
	}
	
	private function requestToManyQueryItem() {
		if ($this->toManyQueryItem !== null) {
			return $this->toManyQueryItem;
		} 
		
		$entityModel = $this->metaTreePoint->getMeta()->getEntityModel();
		$idComparisonStrategy = $this->metaTreePoint->requestPropertyComparisonStrategy(
				$entityModel->getIdDef()->getPropertyName());
		
		return $this->toManyQueryItem = $this->toManyQueryItemFactory->createQueryItem(
				$idComparisonStrategy->getColumnComparable(), $this->queryState);
	}
	
	/**
	 * 
	 * @return \n2n\persistence\orm\query\from\TreePath
	 */
	private function createIdTreePath() {
		return new TreePath([$this->metaTreePoint->getMeta()->getEntityModel()->getIdDef()->getPropertyName()]);
	}
	
	private function createTestQueryResult(QueryItem $counterQueryItem) {
		$entityModel = $this->metaTreePoint->getMeta()->getEntityModel();
		$idCc = $this->requestIdCc($this->metaTreePoint, $this->createIdTreePath());
		
		$tree = new Tree($this->queryState);
// @todo support inherit access
// 		$tree->setInheritedQueryPointResolver($inheritedQueryPointResolver);
		$subMetaTreePoint = $tree->createBaseTreePoint($entityModel);
		
		$subIdCc = $this->requestIdCc($subMetaTreePoint, $this->createIdTreePath());
		$subTargetIdCc = $this->requestIdCc($subMetaTreePoint, $this->targetIdTreePath->copy());
		
		$subQueryComparator = new QueryComparator();
		$subQueryComparator->match(
				$idCc->buildQueryItem(CriteriaComparator::OPERATOR_EQUAL),
				QueryComparator::OPERATOR_EQUAL,
				$subIdCc->buildQueryItem(CriteriaComparator::OPERATOR_EQUAL));
		$subQueryComparator->match(
				$subTargetIdCc->buildQueryItem(CriteriaComparator::OPERATOR_IN),
				QueryComparator::OPERATOR_IN,
				$counterQueryItem);
		
		$subQueryModel = new QueryModel($tree, new QueryItemSelect($this->queryState));
		$subQueryModel->addUnnamedSelectQueryPoint(new ConstantQueryPoint(1, $this->queryState));
		$subQueryModel->setWhereQueryComparator($subQueryComparator);
		
		
		$selectBuilder = $this->queryState->getPdo()->getMetaData()->createSelectStatementBuilder();
		$subQueryModel->apply($selectBuilder);
		
		return $selectBuilder->toQueryResult();
		
	}

}
