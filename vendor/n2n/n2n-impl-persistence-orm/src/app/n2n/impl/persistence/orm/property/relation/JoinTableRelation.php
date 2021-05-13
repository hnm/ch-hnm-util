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

use n2n\persistence\orm\query\QueryState;
use n2n\impl\persistence\orm\property\relation\tree\JoinTableTreePoint;
use n2n\impl\persistence\orm\property\relation\selection\JoinTableToManyLoader;
use n2n\persistence\orm\store\SimpleLoaderUtils;
use n2n\persistence\orm\store\action\RemoveAction;
use n2n\persistence\Pdo;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\persistence\orm\query\from\meta\TreePointMeta;
use n2n\impl\persistence\orm\property\relation\util\JoinTableAction;
use n2n\impl\persistence\orm\property\relation\compare\JoinTableToManyQueryItemFactory;
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\store\ValueHash;

abstract class JoinTableRelation extends MasterRelation {
	protected $joinTableName;
	protected $joinColumnName;
	protected $inverseJoinColumnName;
	protected $orphanRemoval = false;
	
	public function getJoinTableName() {
		return $this->joinTableName;
	}
	
	public function setJoinTableName($joinTableName) {
		$this->joinTableName = $joinTableName;
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
	
	public function isOrphanRemoval() {
		return $this->orphanRemoval;
	}
	
	public function setOrphanRemoval($orphanRemoval) {
		return $this->orphanRemoval = $orphanRemoval;
	}
	
	public function createJoinTreePoint(TreePointMeta $treePointMeta, QueryState $queryState) {
		$idColumn = $this->entityModel->getIdDef()->getEntityProperty()
				->createQueryColumn($treePointMeta);
		
		$joinTableAlias = $queryState->createTableAlias($this->joinTableName);
		
		$targetEntityModel = $this->getTargetEntityModel();
		$targetTreePointMeta = $targetEntityModel->createTreePointMeta($queryState);
		$targetIdColumn = $targetEntityModel->getIdDef()->getEntityProperty()->createQueryColumn($targetTreePointMeta);
	
		$treePoint = new JoinTableTreePoint($queryState, $targetTreePointMeta);
		$treePoint->setIdColumn($idColumn);
		$treePoint->setJoinTableName($this->joinTableName);
		$treePoint->setJoinTableAlias($joinTableAlias);
		$treePoint->setJoinColumnName($this->joinColumnName); 
		$treePoint->setInverseJoinColumnName($this->inverseJoinColumnName);
		$treePoint->setTargetIdColumn($targetIdColumn);
		return $treePoint;
	}
	
	public function createInverseJoinTreePoint(EntityModel $entityModel, TreePointMeta $targetTreePointMeta, QueryState $queryState) {
		$targetIdColumn = $this->getTargetEntityModel()->getIdDef()->getEntityProperty()
				->createQueryColumn($targetTreePointMeta);
		
		$joinTableAlias = $queryState->createTableAlias($this->joinTableName);
		
		$treePointMeta = $entityModel->createTreePointMeta($queryState);
		$idColumn = $this->entityModel->getIdDef()->getEntityProperty()->createQueryColumn($treePointMeta);
		
		$treePoint = new JoinTableTreePoint($queryState, $treePointMeta);
		$treePoint->setIdColumn($targetIdColumn);
		$treePoint->setJoinTableName($this->joinTableName);
		$treePoint->setJoinTableAlias($joinTableAlias);
		$treePoint->setJoinColumnName($this->inverseJoinColumnName);
		$treePoint->setInverseJoinColumnName($this->joinColumnName);
		$treePoint->setTargetIdColumn($idColumn);
		
		return $treePoint;
	}
	
	public function createInverseToManyLoader(EntityModel $entityModel, QueryState $queryState) {
		return new JoinTableToManyLoader(
				new SimpleLoaderUtils($queryState->getEntityManager(), $entityModel),
				$this->targetEntityModel->getIdDef()->getEntityProperty(),
				$this->joinTableName, $this->joinColumnName, $this->inverseJoinColumnName);
	}
	
	protected function createJoinTableAction(Pdo $pdo) {
		return new JoinTableAction($pdo, $this->joinTableName, $this->joinColumnName, 
				$this->inverseJoinColumnName);
	}
	/**
	 * @param PersistAction $persistAction
	 * @return JoinTableAction
	 */
	protected function createJoinTableActionFromPersistAction(PersistAction $persistAction) {
		$actionQueue = $persistAction->getActionQueue();
		$idProperty = $this->entityModel->getIdDef()->getEntityProperty();
		
		$pdo = $actionQueue->getEntityManager()->getPdo();
		$joinTableAction = $this->createJoinTableAction($pdo);
		$actionQueue->add($joinTableAction);
		
		if ($persistAction->hasId()) {
			$joinTableAction->setJoinIdRaw($idProperty->buildRaw($persistAction->getId(), $pdo));
			return $joinTableAction;
		}
		
		$joinTableAction->addDependent($persistAction);
		$persistAction->executeAtEnd(function () use ($joinTableAction, $idProperty, $persistAction, $pdo) {
			$joinTableAction->setJoinIdRaw($idProperty->buildRaw($persistAction->getId(), $pdo));
		});
		
		return $joinTableAction;	
	}

	public function supplyRemoveAction(RemoveAction $removeAction, $value, ValueHash $oldValueHash) {
		$actionQueue = $removeAction->getActionQueue();
		
		$pdo = $actionQueue->getEntityManager()->getPdo();
		$joinTableAction = $this->createJoinTableAction($pdo);
		$joinTableAction->setJoinIdRaw($this->entityModel->getIdDef()->getEntityProperty()
				->buildRaw($removeAction->getId(), $pdo));
		
		$actionQueue->add($joinTableAction);
	}
	
// 	public function supplyInverseToOneRemoveAction($targetValue, $targetValueHash, RemoveAction $targetRemoveAction) {
// 		if ($targetValueHash === null) return;
		
// 		$persistenceContext = $targetRemoveAction->getActionQueue()->getEntityManager()->getPersistenceContext();
		
// 		$entity = $persistenceContext->getEntityByIdRep($this->entityModel, $targetValueHash);
// 		if ($persistenceContext->containsRemovedEntity($entity)) continue;

// 		$this->createJoinTableActionFromTargetRemoveAction($targetRemoveAction);
		
// 	}
	
// 	public function supplyInverseToManyRemoveAction($targetValue, $targetValueHash, RemoveAction $targetRemoveAction) {
// 		$this->createJoinTableActionFromTargetRemoveAction($targetRemoveAction);
// 	}
	
	
	public function createInverseJoinTableToManyQueryItemFactory(EntityModel $entityModel) {
		return new JoinTableToManyQueryItemFactory($this->joinTableName,
				$this->inverseJoinColumnName, $this->joinColumnName);
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\model\ActionDependency::removeActionSupplied()
	 */
	public function removeActionSupplied(RemoveAction $targetRemoveAction) {
		if ($this->actionMarker->isConstraintReleased($targetRemoveAction)) return;
	
		$actionQueue = $targetRemoveAction->getActionQueue();
		$pdo = $actionQueue->getEntityManager()->getPdo();
		$targetIdProperty = $this->targetEntityModel->getIdDef()->getEntityProperty();
		
		$joinTableAction = new JoinTableAction($pdo, $this->joinTableName,
				$this->inverseJoinColumnName, $this->joinColumnName);
		$joinTableAction->setJoinIdRaw($targetIdProperty->buildRaw($targetRemoveAction->getId(), $pdo));
		
		$actionQueue->add($joinTableAction);
	
		$targetRemoveAction->executeAtEnd(function ($actionQueue, $resetAction) {
			$actionQueue->add($joinTableAction);
		});
	}
}


// public function createSelectionQueryColumn(QueryState $queryState, TreePointMeta $queryPoint) {
// 	return $queryPoint->registerColumn($this->entityModel, $this->getReferencedColumnName());
// }

// public function createInverseToManyLoader(EntityManager $em, $id, ToMany $toMany) {
// 	return new JoinTableToManyLoader($em, $id, $this->entityModel, $this->getJoinTableName(),
// 			$this->getInverseJoinColumnName(), $this->getJoinColumnName(), $toMany);
// }

// public function lookupInverseOneToOneEntity(EntityManager $em, $id) {
// 	$toOneLoader = new JoinTableToOneLoader($em, $id, $this->entityModel, $this->getJoinTableName(),
// 			$this->getInverseJoinColumnName(), $this->getJoinColumnName());
// 	return $toOneLoader->load();
// }

// public function supplyNonCascadeInverseRemoveAction($mappedValue, RemoveAction $deletingJob) {
// 	if (!$deletingJob->getRemoveMeta()->hasObjectId()) return;

// 	$relationAction = new JoinTableRelationAction($deletingJob->getActionQueue(), $this->getJoinTableName(),
// 			$this->getInverseJoinColumnName(), $this->getJoinColumnName());
// 	$relationAction->setId($deletingJob->getRemoveMeta()->getObjectId());
// 	$relationAction->addDependent($deletingJob);

// 	$deletingJob->getActionQueue()->add($relationAction);
// }
