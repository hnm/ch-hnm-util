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

use n2n\persistence\orm\property\BasicEntityProperty;
use n2n\persistence\orm\model\EntityModel;
use n2n\impl\persistence\orm\property\relation\selection\ArrayObjectProxy;
use n2n\util\type\ArgUtils;
use n2n\util\col\ArrayUtils;
use n2n\persistence\orm\store\ValueHash;
use n2n\util\ex\IllegalStateException;

class ToManyValueHasher {
	const PROXY_KEYWORD = 'proxy';
	private $targetIdProperty;

	public function __construct(BasicEntityProperty $targetIdProperty) {
		$this->targetIdProperty = $targetIdProperty;
	}

	public static function createFromEntityModel(EntityModel $entityModel) {
		return new ToManyValueHasher($entityModel->getIdDef()->getEntityProperty());
	}

// 	public static function extractIdReps(ValueHash $hash) {
// 		$hash = $hash->getHash();
// 		if (!is_array($hash)) {
// 			throw new \InvalidArgumentException('Ids not extractable from AccessProxy hash.');
// 			return array();
// 		}
		
// 		$idReps = array();
// 		foreach ($hash as $idRep) {
// 			if ($idRep !== null) {
// 				$idReps[$idRep] = $idRep;
// 			}
// 		}
// 		return $idReps;
// 	}

	public function reportId($key, $id, ToManyValueHash $valueHash) {
		$valueHash->reportIdRep($key, $this->targetIdProperty->valueToRep($id));
	}

	public function createValueHash($value) {
// 		if ($value === null) return new CommonValueHash(array());

		if ($value instanceof ArrayObjectProxy) {
			return ToManyValueHash::createFromArrayObjectProxy($value, $this);
		}

		return ToManyValueHash::createFromValue($value, $this);
// 		if ($value instanceof ArrayObjectProxy && $value->getLoadedValueHash() === $valueHash) {
// 			return $value->getId();
// 		}
		
// 		return $valueHash;
	}
	
	public function extractIdRepsMapFromValue($entities) {
		if ($entities === null) return array();
		
		ArgUtils::assertTrue(ArrayUtils::isArrayLike($entities));
		$entityIdReps = array();
		foreach ($entities as $key => $entity) {
			$id = $this->targetIdProperty->readValue($entity);
			if ($id === null) {
				$entityIdReps[$key] = null;
			} else {
				$entityIdReps[$key] = $this->targetIdProperty->valueToRep($id);
			}
		}
		return $entityIdReps;
	}
	
// 	public function createValueHashFromEntities($entities) {
// 		ArgUtils::assertTrue(ArrayUtils::isArrayLike($entities));
// 		$entityIdReps = array();
// 		foreach ($entities as $key => $entity) {
// 			$id = $this->targetIdProperty->readValue($entity);
// 			if ($id === null) {
// 				$entityIdReps[$key] = null;
// 			} else {
// 				$entityIdReps[$key] = $this->targetIdProperty->valueToRep($id);
// 			}
// 		}
// 		return new CommonValueHash($entityIdReps);
// 	}


// 	public static function isUntouchedProxy($value, $valueHash) {
// 		return $value instanceof ArrayObjectProxy && !$value->isInitialized()
// 				&& $valueHash === $value->getId();
// 	}
	
	public function matches(array $entityIds, ToManyValueHash $valueHash) {
		// this means that other array object has been asigned
		if (!$valueHash->isInitialized()) return false;
		
		$vhIdReps = $valueHash->getIdReps(true);
		
		foreach ($entityIds as $entityId) {
			$entityIdRep = $this->targetIdProperty->valueToRep($entityId);
			if (!isset($vhIdReps[$entityIdRep])) return false;
			unset($vhIdReps[$entityIdRep]);
		}

		return empty($vhIdReps);
	}

// 	public function findOrphanIdReps(array $entityIds, $valueHash) {
// 		$vhIdReps = self::extractIdReps($valueHash);
		
// 		foreach ($entityIds as $entityId) {
// 			$entityIdRep = $this->targetIdProperty->valueToRep($entityId);
// 			if (isset($vhIdReps[$entityIdRep])) { 
// 				unset($vhIdReps[$entityIdRep]);
// 			}
// 		}

// 		return $vhIdReps;
// 	}
}


class ToManyValueHash implements ValueHash {
	protected $arrayObjectProxy;
	protected $idRepsMap;
	
	protected function __construct() {
	}
	
	public function isInitialized() {
		return $this->idRepsMap !== null;
	}
	
	public function initialize() {
		if ($this->arrayObjectProxy !== null) {
			$this->arrayObjectProxy->initialize();
			return;
		}
		
		throw new IllegalStateException('No uninitialized ArrayObjectProxy found.');
	}
	
	public function getIdRepsMap(bool $initialize = false) {
		if ($this->isInitialized()) {
			return $this->idRepsMap;
		} else if ($initialize) {
			$this->initialize();
			return $this->idRepsMap;
		}
		
		throw new IllegalStateException('ArrayObjectProxy not initialized.');
	}
	
	public function reportIdRep($key, $idRep) {
		IllegalStateException::assertTrue($this->idRepsMap !== null 
				&& array_key_exists($key, $this->idRepsMap) && $this->idRepsMap[$key] === null);
		$this->idRepsMap[$key] = $idRep;
	}
	
	public function getIdReps(bool $initialize = false) {
		$idReps = array();
		foreach ($this->getIdRepsMap($initialize) as $key => $idRep) {
			if ($idRep === null) continue;
			$idReps[$key] = $idRep;
		}
		return $idReps;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\store\ValueHash::matches()
	 */
	public function matches(ValueHash $valueHash): bool {
		ArgUtils::assertTrue($valueHash instanceof ToManyValueHash);
		
		return $this->idRepsMap === $valueHash->idRepsMap
				&& $this->arrayObjectProxy === $valueHash->arrayObjectProxy;
	}
	
	public static function createFromArrayObjectProxy(ArrayObjectProxy $arrayObjectProxy, ToManyValueHasher $toManyValueHasher) {
		if ($arrayObjectProxy->isInitialized()) {
			return self::createFromValue($arrayObjectProxy, $toManyValueHasher); 		
		}
			
		$toManyValueHash = new ToManyValueHash();
		$toManyValueHash->arrayObjectProxy = $arrayObjectProxy;
		$arrayObjectProxy->whenInitialized(function () use ($arrayObjectProxy, $toManyValueHash, $toManyValueHasher) {
			$toManyValueHash->idRepsMap = $toManyValueHasher->extractIdRepsMapFromValue($arrayObjectProxy);
			$toManyValueHash->arrayObjectProxy = null;
		});
		return $toManyValueHash;
	}
	
	public static function createFromValue($value, ToManyValueHasher $toManyValueHasher) {
		$toManyValueHash = new ToManyValueHash();
		$toManyValueHash->idRepsMap = $toManyValueHasher->extractIdRepsMapFromValue($value);
		return $toManyValueHash;
	}
	

	public function checkForUntouchedProxy($value) {
		return $this->arrayObjectProxy !== null && $this->arrayObjectProxy === $value;
	}
}
