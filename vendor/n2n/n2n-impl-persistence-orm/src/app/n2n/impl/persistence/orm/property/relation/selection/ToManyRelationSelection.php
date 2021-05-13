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
use n2n\persistence\orm\query\select\ValueBuilder;
use n2n\persistence\orm\query\select\LazyValueBuilder;
use n2n\persistence\orm\property\BasicEntityProperty;

class ToManyRelationSelection extends RelationSelection implements Selection {
	private $toManyLoader;
	private $targetIdEntityProperty;
	
	public function __construct(Selection $idSelection, ToManyLoader $toManyLoader, 
			BasicEntityProperty $targetIdEntityProperty) {
		parent::__construct($idSelection);
		$this->toManyLoader = $toManyLoader;
		$this->targetIdEntityProperty = $targetIdEntityProperty;
	}
	
	public function createValueBuilder() {
		$idValueBuilder = $this->idSelection->createValueBuilder();
		
		if ($this->lazy) {
			return $this->createLazyFetchValueBuilder($idValueBuilder);
		}
		
		return $this->createEagerFetchValueBuilder($idValueBuilder);
	}
	
	private function createEagerFetchValueBuilder(ValueBuilder $idValueBuilder) {
		return new LazyValueBuilder(function () use ($idValueBuilder) {
			return new \ArrayObject($this->toManyLoader->loadEntities($idValueBuilder->buildValue()));
		});
	}
	
	private function createLazyFetchValueBuilder(ValueBuilder $idValueBuilder) {
		return new LazyValueBuilder(function () use ($idValueBuilder) {
			$that = $this;
			$id = $idValueBuilder->buildValue();
			return new ArrayObjectProxy(function () use ($that, $id) {
				return $that->toManyLoader->loadEntities($id);
			}, $that->targetIdEntityProperty); 
		}); 
	}
}
