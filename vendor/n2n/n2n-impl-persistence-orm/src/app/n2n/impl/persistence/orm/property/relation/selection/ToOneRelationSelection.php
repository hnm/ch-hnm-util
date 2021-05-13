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
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\query\QueryState;
use n2n\persistence\orm\query\select\LazyValueBuilder;
use n2n\persistence\orm\store\SimpleLoader;
use n2n\persistence\orm\query\select\ValueBuilder;

class ToOneRelationSelection extends RelationSelection implements Selection {
	protected $entityModel;
	protected $queryState;
	protected $lazy = true;
	
	public function __construct(EntityModel $entityModel, Selection $idSelection,
			QueryState $queryState) {
		parent::__construct($idSelection);
		$this->entityModel = $entityModel;
		$this->queryState = $queryState;
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\query\select\Selection::createValueBuilder()
	 */
	public function createValueBuilder() {
		$idValueBuilder = $this->idSelection->createValueBuilder();
		
		if ($this->lazy) {
			return $this->createLazyFetchValueBuilder($idValueBuilder);
		}
		
		return $this->createEagerFetchValueBuilder($idValueBuilder);
	}
	/**
	 * @return \n2n\persistence\orm\query\select\LazyValueBuilder
	 */
	private function createLazyFetchValueBuilder(ValueBuilder $idValueBuilder) {
		return new LazyValueBuilder(function () use ($idValueBuilder) {
			return $this->queryState->getPersistenceContext()->getOrCreateEntityProxy(
					$this->entityModel, $idValueBuilder->buildValue(),
					$this->queryState->getEntityManager());
		});
	}
	/**
	 * @return \n2n\persistence\orm\query\select\LazyValueBuilder
	 */
	private function createEagerFetchValueBuilder(ValueBuilder $idValueBuilder) {
		return new LazyValueBuilder(function () use ($idValueBuilder) {
			$id = $idValueBuilder->buildValue();
			$persistenceContext = $this->queryState->getPersistenceContext();
	
			if (null !== ($entity = $persistenceContext->getManagedEntityObj($this->entityModel, $id))) {
				return $entity;
			}
	
			$simpleLoader = new SimpleLoader($this->queryState->getEntityManager());
			return $simpleLoader->loadEntity($this->entityModel, $id);
		});
	}
}
