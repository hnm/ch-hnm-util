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
use n2n\persistence\orm\property\BasicEntityProperty;
use n2n\persistence\orm\query\select\EntityObjSelection;
use n2n\persistence\meta\data\QueryComparator;

class JoinColumnToManyLoader extends ToManyLoaderAdapter {
	private $utils;
	private $relatedIdEntityProperty;
	private $relatedIdJoinColumnName;
	
	public function __construct(SimpleLoaderUtils $simpleLoaderUtils,
			BasicEntityProperty $relatedIdEntityProperty, $relatedIdJoinColumnName) {
		$this->utils = $simpleLoaderUtils;
		$this->relatedIdEntityProperty = $relatedIdEntityProperty; 
		$this->relatedIdJoinColumnName = $relatedIdJoinColumnName;
	}
	
	public function loadEntities($relatedId) {
		$this->utils->initialize();
		$this->utils->setSelection(new EntityObjSelection($this->utils->entityModel, 
				$this->utils->queryState, $this->utils->metaTreePoint));
		
		$relatedIdQueryColumn = $this->utils->metaTreePoint->getMeta()->registerColumn(
				$this->utils->entityModel, $this->relatedIdJoinColumnName);
		$relatedIdColumnComparator = $this->relatedIdEntityProperty->createColumnComparableFromQueryItem(
				$relatedIdQueryColumn, $this->utils->queryState);
		
		$orderQueryDirectives = $this->applyOrderDirectives($this->utils->metaTreePoint);
		
		$selectBuilder = $this->utils->build();
		
		$selectBuilder->getWhereComparator()->match(
				$relatedIdColumnComparator->buildQueryItem(QueryComparator::OPERATOR_EQUAL),
				QueryComparator::OPERATOR_EQUAL,
				$relatedIdColumnComparator->buildCounterpartQueryItemFromValue(
						QueryComparator::OPERATOR_EQUAL, $relatedId));
		
		foreach ($orderQueryDirectives as $orderQueryDirective) {
			$orderQueryDirective->apply($selectBuilder);
		}
		
		return $this->fetchArray($this->utils);
	}
}
