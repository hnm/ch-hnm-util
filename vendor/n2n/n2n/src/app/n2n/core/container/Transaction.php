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
namespace n2n\core\container;

class Transaction {
	private $transactionalContext;
	private $level;
	private $tcRef;
	private $readOnly;

	public function __construct(TransactionManager $transactionalContext, $level, $tcRef, $readOnly) {
		$this->transactionalContext = $transactionalContext;
		$this->level = $level;
		$this->tcRef = $tcRef;
		$this->readOnly = (boolean) $readOnly;
	}

	public function getLevel() {
		return $this->level;
	}

	public function getTransactionManager() {
		return $this->transactionalContext;
	}

	public function isRoot() {
		return $this->level == 1;
	}
	
	public function isReadOnly(): bool {
		return $this->readOnly;
	}

	public function commit() {
		$this->transactionalContext->closeLevel($this->level, $this->tcRef, true);
	}

	public function rollBack() {
		$this->transactionalContext->closeLevel($this->level, $this->tcRef, false);
	}
}
