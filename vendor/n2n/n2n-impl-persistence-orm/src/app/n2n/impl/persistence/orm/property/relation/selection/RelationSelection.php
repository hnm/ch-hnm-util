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

use n2n\persistence\orm\query\select\Selection;
use n2n\persistence\PdoStatement;

abstract class RelationSelection implements Selection{
	protected $idSelection;
	protected $lazy = true;
	
	public function __construct(Selection $idSelection) {
		$this->idSelection = $idSelection;
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\query\select\Selection::getSelectQueryItems()
	 */
	public function getSelectQueryItems() {
		return $this->idSelection->getSelectQueryItems();
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\query\select\Selection::bindColumns()
	 */
	public function bindColumns(PdoStatement $stmt, array $columnAliases) {
		$this->idSelection->bindColumns($stmt, $columnAliases);
	}
	
	public function setLazy($lazy) {
		$this->lazy = (boolean) $lazy;
	}
	
	public function isLazy() {
		return $this->lazy;
	}
}
