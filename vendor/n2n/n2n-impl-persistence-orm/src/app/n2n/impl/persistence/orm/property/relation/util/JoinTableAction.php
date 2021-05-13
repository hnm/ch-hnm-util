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
namespace n2n\impl\persistence\orm\property\relation\util;

use n2n\persistence\orm\store\action\ActionAdapter;
use n2n\persistence\Pdo;
use n2n\persistence\meta\data\QueryColumn;
use n2n\persistence\meta\data\QueryPlaceMarker;
use n2n\persistence\meta\data\QueryComparator;

class JoinTableAction extends ActionAdapter {
	private $pdo;
	private $joinTableName;
	private $joinColumnName;
	private $inverseJoinColumnName;

	private $joinIdRaw;
	private $inverseJoinIdRaws = array();

	public function __construct(Pdo $pdo, $joinTableName, $joinColumnName, $inverseJoinColumnName) {
		$this->pdo = $pdo;
		$this->joinTableName = $joinTableName;
		$this->joinColumnName = $joinColumnName;
		$this->inverseJoinColumnName = $inverseJoinColumnName;
	}

	public function getPdo() {
		return $this->pdo;
	}
	
	public function setJoinIdRaw($joinIdRaw) {
		$this->joinIdRaw = $joinIdRaw;
	}

	public function addInverseJoinIdRaw($inverseJoinIdRaw) {
		$this->inverseJoinIdRaws[$inverseJoinIdRaw] = $inverseJoinIdRaw;
	}

	protected function exec() {
		$metaData = $this->pdo->getMetaData();

		$deleteBuilder = $metaData->createDeleteStatementBuilder();
		$deleteBuilder->setTable($this->joinTableName);
		$deleteBuilder->getWhereComparator()->match(new QueryColumn($this->joinColumnName),
				QueryComparator::OPERATOR_EQUAL, new QueryPlaceMarker());
		$deleteStmt = $this->pdo->prepare($deleteBuilder->toSqlString());
		$deleteStmt->execute(array($this->joinIdRaw));

		if (empty($this->inverseJoinIdRaws)) return;

		$insertBuilder = $metaData->createInsertStatementBuilder();
		$insertBuilder->setTable($this->joinTableName);
		$insertBuilder->addColumn(new QueryColumn($this->joinColumnName), new QueryPlaceMarker());
		$insertBuilder->addColumn(new QueryColumn($this->inverseJoinColumnName), new QueryPlaceMarker());

		$insertStmt = $this->pdo->prepare($insertBuilder->toSqlString());
		foreach ($this->inverseJoinIdRaws as $inverseJoinId) {
			$insertStmt->execute(array($this->joinIdRaw, $inverseJoinId));
		}
	}
}
