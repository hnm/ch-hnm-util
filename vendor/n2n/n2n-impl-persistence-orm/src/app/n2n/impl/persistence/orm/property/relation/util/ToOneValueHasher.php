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

use n2n\util\type\ArgUtils;
use n2n\persistence\orm\property\BasicEntityProperty;
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\store\ValueHash;
use n2n\util\ex\IllegalStateException;

class ToOneValueHasher {
	private $idEntityProperty;
	
	public function __construct(BasicEntityProperty $idEntityProperty) {
		$this->idEntityProperty = $idEntityProperty;
	}
	
	public function createValueHash($value) {
		if ($value === null) return new ToOneValueHash(false, null);
		ArgUtils::assertTrue(is_object($value));
		
		$id = $this->idEntityProperty->readValue($value);
		if ($id === null) return new ToOneValueHash(true, null);
		
		return new ToOneValueHash(true, $this->idEntityProperty->valueToRep($id));
	}
	
	public function reportId($id, ToOneValueHash $toOneValueHash) {
		$toOneValueHash->reportIdRep($this->idEntityProperty->valueToRep($id));
	}
	
	public static function createFromEntityModel(EntityModel $entityModel) {
		return new ToOneValueHasher($entityModel->getIdDef()->getEntityProperty());
	}
}

class ToOneValueHash implements ValueHash {
	/**
	 * @var bool
	 */
	private $objectExisting;
	/**
	 * @var string|null
	 */
	private $idRep;
	
	/**
	 * @param string|null $idRep
	 */
	public function __construct(bool $objectExisting, ?string $idRep) {
		$this->objectExisting = $objectExisting;
		$this->idRep = $idRep;
	}
	
	/**
	 * @param string $idRep
	 */
	public function reportIdRep(string $idRep) {
		IllegalStateException::assertTrue($this->objectExisting, 'Hash is for non-existing object.');
		IllegalStateException::assertTrue($this->idRep === null, 'IdRep already known.');
		$this->idRep = $idRep;
	}
	
	/**
	 * @return boolean
	 */
	public function isObjectExisting() {
		return $this->objectExisting;
	}
	
	/**
	 * @return string|null
	 */
	public function getIdRep() {
		return $this->idRep;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\persistence\orm\store\ValueHash::matches()
	 */
	public function matches(ValueHash $valueHash): bool {
		ArgUtils::assertTrue($valueHash instanceof ToOneValueHash);
		
		return $this->objectExisting === $valueHash->isObjectExisting() 
				&& $this->idRep === $valueHash->getIdRep();
	}
	
// 	public function checkForUntouchedProxy($entityObj) {
// 		if ($entityObj instanceof EntityProxy) {
			
// 		}
// 	}
}