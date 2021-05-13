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
namespace n2n\impl\persistence\orm\property\relation\selection;

use n2n\persistence\orm\store\SimpleLoaderUtils;
use n2n\persistence\orm\query\select\EntityObjSelection;
use n2n\persistence\meta\data\QueryColumn;
use n2n\persistence\meta\data\JoinType;
use n2n\persistence\meta\data\QueryTable;
use n2n\persistence\meta\data\QueryComparator;
use n2n\persistence\orm\property\BasicEntityProperty;

class JoinTableToManyLoader extends ToManyLoaderAdapter {
	private $utils;
	private $relatedIdEntityProperty; 
	private $joinTableName;
	private $joinColumnName;
	private $relatedIdJoinColumnName;
	
	public function __construct(SimpleLoaderUtils $simpleLoaderUtils, BasicEntityProperty $relatedIdEntityProperty, 
			$joinTableName, $joinColumnName, $relatedIdJoinColumnName) {
		$this->utils = $simpleLoaderUtils;
		$this->relatedIdEntityProperty = $relatedIdEntityProperty; 
		$this->joinTableName = $joinTableName;
		$this->joinColumnName = $joinColumnName;
		$this->relatedIdJoinColumnName = $relatedIdJoinColumnName;
	}
	
	public function loadEntities($relatedId) {
		$this->utils->initialize();
		$this->utils->setSelection(new EntityObjSelection($this->utils->entityModel, 
				$this->utils->queryState, $this->utils->metaTreePoint));
		
		$idQueryColumn = $this->utils->entityModel->getIdDef()->getEntityProperty()->createQueryColumn(
				$this->utils->metaTreePoint->getMeta(), $this->utils->queryState);
		
		$joinTableAlias = $this->utils->queryState->createTableAlias($this->joinTableName);
		$joinQueryColumn = new QueryColumn($this->joinColumnName, $joinTableAlias);
		
		$relatedIdColumnComparator = $this->relatedIdEntityProperty->createColumnComparableFromQueryItem(
				new QueryColumn($this->relatedIdJoinColumnName, $joinTableAlias), $this->utils->queryState);
		
		
		$orderQueryDirectives = $this->applyOrderDirectives($this->utils->metaTreePoint);
		
		$selectBuilder = $this->utils->build();
		$selectBuilder->addJoin(JoinType::INNER, new QueryTable($this->joinTableName), $joinTableAlias)
				->match($idQueryColumn, QueryComparator::OPERATOR_EQUAL, $joinQueryColumn);
		$selectBuilder->getWhereComparator()->match($relatedIdColumnComparator->buildQueryItem(QueryComparator::OPERATOR_EQUAL), 
				QueryComparator::OPERATOR_EQUAL,
				$relatedIdColumnComparator->buildCounterpartQueryItemFromValue(
						QueryComparator::OPERATOR_EQUAL, $relatedId));
		
		foreach ($orderQueryDirectives as $orderQueryDirective) {
			$orderQueryDirective->apply($selectBuilder);
		}
		
		return $this->fetchArray($this->utils);
	}
}