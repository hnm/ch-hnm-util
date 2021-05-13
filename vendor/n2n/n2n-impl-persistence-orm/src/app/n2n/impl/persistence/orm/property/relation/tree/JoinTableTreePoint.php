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
namespace n2n\impl\persistence\orm\property\relation\tree;

use n2n\persistence\meta\data\QueryColumn;
use n2n\persistence\meta\data\SelectStatementBuilder;
use n2n\persistence\meta\data\QueryTable;
use n2n\persistence\meta\data\QueryComparator;
use n2n\persistence\orm\query\from\JoinedTreePointAdapter;

class JoinTableTreePoint extends JoinedTreePointAdapter {
	private $idColumn;
	private $joinTableName;
	private $joinTableAlias;
	private $joinColumnName;
	private $inverseJoinColumnName;
	private $targetIdColumn;
	/**
	 * @return QueryColumn
	 */
	public function getIdColumn() {
		return $this->idColumn;
	}
	/**
	 * @param QueryColumn $idColumn
	 */
	public function setIdColumn(QueryColumn $idColumn) {
		$this->idColumn = $idColumn;
	}
	
	public function getJoinTableName() {
		return $this->joinTableName;
	}
	
	public function setJoinTableName($joinTableName) {
		$this->joinTableName = $joinTableName;
	}
	
	public function getJoinTableAlias() {
		return $this->joinTableAlias;
	}
	
	public function setJoinTableAlias($joinTableAlias) {
		$this->joinTableAlias = $joinTableAlias;
	}
	
	public function getJoinColumnName() {
		return $this->joinColumnName;
	}
	
	public function setJoinColumnName($joinColumnName) {
		$this->joinColumnName = $joinColumnName;
	}
	
	public function getInverseJoinColumnName() {
		return $this->inverseJoinColumnName;
	}
	
	public function setInverseJoinColumnName($inverseJoinColumnName) {
		$this->inverseJoinColumnName = $inverseJoinColumnName;
	}
	
	public function getTargetIdColumn() {
		return $this->targetIdColumn;
	}
	
	public function setTargetIdColumn(QueryColumn $targetIdColumn) {
		$this->targetIdColumn = $targetIdColumn;
	}
	
	public function apply(SelectStatementBuilder $selectBuilder) {
		$selectBuilder->addJoin($this->joinType, new QueryTable($this->joinTableName), $this->joinTableAlias)
				->match($this->idColumn, QueryComparator::OPERATOR_EQUAL, 
						new QueryColumn($this->joinColumnName, $this->joinTableAlias));

		$this->treePointMeta->applyAsJoin($selectBuilder, $this->joinType, $this->onComparator)
				->match(new QueryColumn($this->inverseJoinColumnName, $this->joinTableAlias),
						QueryComparator::OPERATOR_EQUAL, $this->targetIdColumn);
				
		parent::apply($selectBuilder);
	}
}
