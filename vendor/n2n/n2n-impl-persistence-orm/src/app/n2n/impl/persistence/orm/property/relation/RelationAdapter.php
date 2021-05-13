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
namespace n2n\impl\persistence\orm\property\relation;

use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\FetchType;
use n2n\persistence\orm\CascadeType;
use n2n\persistence\orm\query\from\TreePath;
use n2n\persistence\orm\property\EntityProperty;
use n2n\persistence\orm\property\BasicEntityProperty;
use n2n\impl\persistence\orm\property\relation\util\ActionMarker;
use n2n\persistence\orm\store\action\RemoveAction;
use n2n\persistence\orm\store\ValueHash;

abstract class RelationAdapter implements Relation {
	protected $entityProperty;
	protected $entityModel;
	protected $targetEntityModel;
	protected $actionMarker;
	protected $fetchType = FetchType::LAZY;
	protected $cascadeType = CascadeType::NONE;
	protected $orphanRemoval = false;
	/**
	 * @param EntityModel $entityModel
	 * @param string $fetchType
	 * @param string $cascadeType
	 */
	public function __construct(EntityProperty $entityProperty, EntityModel $targetEntityModel) {
		$this->entityProperty = $entityProperty;
		$this->entityModel = $entityProperty->getEntityModel();
		$this->targetEntityModel = $targetEntityModel;
		$this->actionMarker = new ActionMarker($this->entityProperty);
	}
	
	public function getEntityProperty() {
		return $this->entityProperty;
	}
		
	public function getTargetEntityModel() {
		return $this->targetEntityModel;
	}
	
	public function getActionMarker() {
		return $this->actionMarker;
	}
	
	public function getFetchType() {
		return $this->fetchType;
	}
	
	public function setFetchType($fetchType) {
		$this->fetchType = $fetchType;
	}
	
	public function getCascadeType() {
		return $this->cascadeType;
	}
	
	public function setCascadeType($cascadeType) {
		$this->cascadeType = $cascadeType;
	}
	
	public function isOrphanRemoval() {
		return $this->orphanRemoval;
	}
	
	public function setOrphanRemoval($orphanRemoval) {
		return $this->orphanRemoval = $orphanRemoval;
	}

	protected function createIdTreePath() {
		return new TreePath(array($this->entityModel->getIdDef()->getPropertyName()));
	}

	protected function createTargetIdTreePath() {
		return new TreePath(array($this->entityProperty->getName(),
				$this->targetEntityModel->getIdDef()->getPropertyName()));
	}
	/**
	 * @return BasicEntityProperty 
	 */
	protected function getTargetIdEntityProperty() {
		return $this->targetEntityModel->getIdDef()->getEntityProperty();
	}
	
	public function supplyRemoveAction(RemoveAction $removeAction, $value, ValueHash $oldValueHash) {
	}
}
