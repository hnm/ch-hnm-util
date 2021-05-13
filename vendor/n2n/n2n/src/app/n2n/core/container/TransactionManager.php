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
	  
use n2n\reflection\ObjectAdapter;

class TransactionManager extends ObjectAdapter {
	private $transactionalResources = array();
	private $commitListeners = array();
	private $tRef = 1;
	
	private $rootTransaction = null;
	private $currentLevel = 0;
	private $readOnly = null;
	private $rollingBack = false;
	private $transactions = array();

	public function createTransaction($readOnly = false) {
		$this->currentLevel++;
		
		$transaction = new Transaction($this, $this->currentLevel, $this->tRef, $readOnly);
		if ($this->currentLevel == 1) {
			$this->readOnly = $readOnly;
			$this->begin($transaction);
		} else if ($this->readOnly && !$readOnly) {
			throw new TransactionStateException(
					'Cannot create non readonly transaction in readonly transaction.');
		}
		
		return $this->transactions[$this->currentLevel] = $transaction;
	}
	
	/**
	 * Returns true if there is an open transaction 
	 * @return bool
	 */
	public function hasOpenTransaction() {
		return $this->rootTransaction !== null;
	}
	
	/**
	 * Returns true if there is an open read only transaction.
	 * @return bool true or false if a transaction is open, otherwise null.
	 */
	public function isReadyOnly() {
		return $this->readOnly;
	}
	
	/**
	 * @return \n2n\core\container\Transaction
	 * @throws TransactionStateException if no transaction is open.
	 */
	public function getRootTransaction() {
		if ($this->rootTransaction !== null) {
			return $this->rootTransaction;
		}
		
		throw new TransactionStateException('No active transaction.');
	}
	
	/**
	 * @return \n2n\core\container\Transaction
	 * @throws TransactionStateException if no transaction is open.
	 */
	public function getCurrentTransaction() { 
		if (false !== ($transaction = end($this->transactions))) {
			return $transaction;
		}
		
		if ($this->rootTransaction !== null) {
			return $this->rootTransaction;
		}

		throw new TransactionStateException('No active transaction.');
	}
	
	public function closeLevel($level, $tRef, bool $commit) {
		if ($this->tRef != $tRef || $level > $this->currentLevel) {
			throw new TransactionStateException('Transaction is already closed.');
		}
		
		if (!$commit) {
			$this->rollingBack = true;
		} else if ($this->rollingBack === true) {
			throw new TransactionStateException(
					'Transaction cannot be commited because sub transaction was rolled back');
		}

		foreach (array_keys($this->transactions) as $tlevel) {
			if ($level > $tlevel) continue;
			
			unset($this->transactions[$tlevel]);
			$this->currentLevel = $level - 1;
		}
		
		if (!empty($this->transactions)) return;
			
		try {
			if (!$this->rollingBack) { 
				$this->rollingBack = !$this->prepareCommit();
			}
			
			if ($this->rollingBack) {
				$this->rollBack();
			} else {
				$this->commit();
			}
		} finally {
			$this->reset();
		}
	}
	
	private function reset() {
		$this->rootTransaction = null;
		$this->rollingBack = false;
		$this->readOnly = null;
	}
	
	private function begin(Transaction $transaction) {
		$this->rootTransaction = $transaction;

		foreach ($this->transactionalResources as $resource) {
			$resource->beginTransaction($transaction);
		}
	}
	
	private function prepareCommit() {
		$this->tRef++;
		
		foreach ($this->transactionalResources as $resource) {
			if ($resource->prepareCommit($this->rootTransaction)) continue;

			return false;
		}
		
		return true;
	}

	private function commit() {
		$this->tRef++;
		
		foreach ($this->commitListeners as $commitListener) {
			$commitListener->preCommit($this->rootTransaction);
		}
		
		try {
			foreach ($this->transactionalResources as $resource) {
				$resource->commit($this->rootTransaction);
			}
		} catch (CommitFailedException $e) {
			$tsm = array();
			foreach ($this->commitListeners as $commitListener) {
				try {
					$commitListener->commitFailed($this->rootTransaction, $e);
				} catch (\Throwable $t) {
					$tsm[] = get_class($t) . ': ' . $t->getMessage();
				}
			}
			
			if (empty($tsm)) {
				throw $e;
			}
			
			throw new CommitFailedException('Commit failed with CommitListener exceptions: ' . implode(', ', $tsm), 
					0, $e);
		}
		
		foreach ($this->commitListeners as $commitListener) {
			$commitListener->postCommit($this->rootTransaction);
		}
	}

	private function rollBack() {
		$this->tRef++;
		
		foreach ($this->transactionalResources as $listener) {
			$listener->rollBack($this->rootTransaction);
		}
	}

	public function registerResource(TransactionalResource $resource) {
		$this->transactionalResources[spl_object_hash($resource)] = $resource;
		
		if ($this->hasOpenTransaction()) {
			$resource->beginTransaction($this->rootTransaction);
		}
	}

	public function unregisterResource(TransactionalResource $resource) {
		unset($this->transactionalResources[spl_object_hash($resource)]);
	}
	
	public function registerCommitListener(CommitListener $commitListener) {
		$this->commitListeners[spl_object_hash($commitListener)] = $commitListener;
	}
	
	public function unregisterCommitListener(CommitListener $commitListener) {
		unset($this->commitListeners[spl_object_hash($commitListener)]);
	}
}
