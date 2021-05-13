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
use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\impl\persistence\orm\property\relation\tree\JoinColumnTreePoint;
use n2n\persistence\orm\FetchType;
use n2n\impl\persistence\orm\property\relation\selection\ToOneRelationSelection;
use n2n\impl\persistence\orm\property\relation\selection\JoinColumnToManyLoader;
use n2n\persistence\orm\store\SimpleLoaderUtils;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\persistence\orm\store\action\RemoveAction;
use n2n\persistence\orm\query\from\meta\TreePointMeta;
use n2n\impl\persistence\orm\property\relation\util\ToOneValueHasher;
use n2n\impl\persistence\orm\property\relation\compare\InverseJoinColumnToManyQueryItemFactory;
use n2n\impl\persistence\orm\property\relation\compare\IdColumnComparableDecorator;
use n2n\persistence\orm\property\EntityProperty;
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\model\ActionDependency;
use n2n\impl\persistence\orm\property\relation\util\JoinColumnResetAction;
use n2n\persistence\orm\store\action\supply\SupplyJob;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\store\ValueHash;
use n2n\impl\persistence\orm\property\relation\util\ToOneUtils;
use n2n\persistence\orm\store\PersistenceOperationException;
use n2n\persistence\orm\store\EntityInfo;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\relation\util\ToOneValueHash;

class JoinColumnToOneRelation extends MasterRelation implements ToOneRelation, ActionDependency {
	private $joinColumnName;
	private $toOneUtils;
	
	public function __construct(EntityProperty $entityProperty, EntityModel $targetEntityModel) {
		parent::__construct($entityProperty, $targetEntityModel);
		$targetEntityModel->registerActionDependency($this);
		
		$this->toOneUtils = new ToOneUtils($this, true);
	}
	
	public function setJoinColumnName($joinColumnName) {
		$this->joinColumnName = $joinColumnName;
	}
	
	public function getJoinColumnName() {
		return $this->joinColumnName;
	}
	
	public function createJoinTreePoint(TreePointMeta $treePointMeta, QueryState $queryState) {
		$joinColumn = $treePointMeta->registerColumn($this->entityModel, 
				$this->joinColumnName);
		
		$targetTreePointMeta = $this->targetEntityModel->createTreePointMeta($queryState);
		$targetIdColumn = $this->targetEntityModel->getIdDef()->getEntityProperty()
				->createQueryColumn($targetTreePointMeta);
		
		$treePoint = new JoinColumnTreePoint($queryState, $targetTreePointMeta);
		$treePoint->setJoinColumn($joinColumn);
		$treePoint->setTargetJoinColumn($targetIdColumn);
		return $treePoint;
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\MasterRelation::createInverseJoinTreePoint()
	 */
	public function createInverseJoinTreePoint(EntityModel $entityModel, TreePointMeta $targetTreePointMeta, QueryState $queryState) {
		$treePointMeta = $entityModel->createTreePointMeta($queryState);
		$joinColumn = $treePointMeta->registerColumn($this->entityModel, $this->joinColumnName);
		
		$targetIdColumn = $this->targetEntityModel->getIdDef()->getEntityProperty()
				->createQueryColumn($targetTreePointMeta);
		
		$treePoint = new JoinColumnTreePoint($queryState, $treePointMeta);
		$treePoint->setJoinColumn($targetIdColumn);
		$treePoint->setTargetJoinColumn($joinColumn);
		return $treePoint;
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::createColumnComparable()
	 */
	public function createColumnComparable(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		$meta = $metaTreePoint->getMeta();
		$targetIdProperty = $this->targetEntityModel->getIdDef()->getEntityProperty();
		
		return new IdColumnComparableDecorator(
				$targetIdProperty->createColumnComparableFromQueryItem(
						$meta->registerColumn($this->entityModel, $this->joinColumnName), $queryState),
				$this->targetEntityModel);
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\ToOneRelation::createRepresentingQueryItem()
	 */
	public function createRepresentingQueryItem(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		return $metaTreePoint->getMeta()->registerColumn($this->entityModel, $this->joinColumnName);
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::createSelection()
	 */
	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		$targetIdProperty = $this->targetEntityModel->getIdDef()->getEntityProperty();
		$idSelection = $targetIdProperty->createSelectionFromQueryItem($metaTreePoint->getMeta()
				->registerColumn($this->entityModel, $this->joinColumnName), $queryState);
		
		$toOneRelationSelection = new ToOneRelationSelection($this->targetEntityModel, $idSelection, $queryState);
		$toOneRelationSelection->setLazy($this->fetchType == FetchType::LAZY);
		return $toOneRelationSelection;
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\MasterRelation::createInverseToManyLoader()
	 */
	public function createInverseToManyLoader(EntityModel $entityModel, QueryState $queryState) {
		return new JoinColumnToManyLoader(
				new SimpleLoaderUtils($queryState->getEntityManager(), $entityModel),
				$this->targetEntityModel->getIdDef()->getEntityProperty(), $this->joinColumnName);
	}
	
	public function prepareSupplyJob(SupplyJob $supplyJob, $value, ?ValueHash $oldValueHash) {
		$this->toOneUtils->prepareSupplyJob($supplyJob, $value, $oldValueHash);
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::supplyPersistAction()
	 */
	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash, 
				?ValueHash $oldValueHash) {
		if ($value === null) {
			$persistAction->getMeta()->setRawValue($this->entityModel, $this->joinColumnName, null);
			return;
		}
		
		$actionQueue = $persistAction->getActionQueue();
		$pdo = $actionQueue->getEntityManager()->getPdo();
		$targetPersistAction = $persistAction->getActionQueue()->getPersistAction($value);

		try {
			$targetPersistAction = $persistAction->getActionQueue()->getPersistAction($value);
		} catch (PersistenceOperationException $e) {
			throw new PersistenceOperationException($this->entityProperty->toPropertyString() . ' of entity obj '
				. EntityInfo::buildEntityString($persistAction->getEntityModel(), $persistAction->getId())
				. ' contains value that can not be persisted.', 0, $e, 3);
		}

		if ($targetPersistAction->hasId()) {
			$persistAction->getMeta()->setRawValue($this->entityModel, $this->joinColumnName, 
					$this->getTargetIdEntityProperty()->buildRaw($targetPersistAction->getId(), $pdo));
			return;
		}
		
		$persistAction->addDependent($targetPersistAction);
		
		ArgUtils::assertTrue($valueHash instanceof ToOneValueHash);
		$targetPersistAction->executeAtEnd(function () use ($persistAction, $valueHash, $targetPersistAction, $pdo) {
			$targetId = $targetPersistAction->getId();
			
			$persistAction->getMeta()->setRawValue($this->entityModel, $this->joinColumnName, 
					$this->getTargetIdEntityProperty()->buildRaw($targetId, $pdo));
			
			$hasher = new ToOneValueHasher($this->getTargetIdEntityProperty());
			$hasher->reportId($targetId, $valueHash);
		});
	}
// 	/* (non-PHPdoc)
// 	 * @see \n2n\impl\persistence\orm\property\relation\Relation::supplyRemoveAction()
// 	 */
// 	public function supplyRemoveAction($value, $valueHash, RemoveAction $removeAction) {
		
// 	}
	
// 	public function supplyInverseToManyRemoveAction($targetValue, $targetValueHash, RemoveAction $targetRemoveAction) {
// 		$actionQueue = $targetRemoveAction->getActionQueue();
// 		$pdo = $actionQueue->getEntityManager()->getPdo();
// 		$persistenceContext = $actionQueue->getEntityManager()->getPersistenceContext();
// 		$idProperty = $this->entityModel->getIdDef()->getEntityProperty;
		
// 		foreach (ToManyValueHasher::extractIdReps($targetValueHash) as $idRep) {
// 			$entity = $persistenceContext->getEntityByIdRep($this->entityModel, $idRep);
// 			if ($persistenceContext->containsRemovedEntity($entity)) continue;
			
// 			$persistActionMeta = $actionQueue->getOrCreatePersistAction($entity)->getMeta();
// 			if (!$persistActionMeta->containsRawValue($this->entityModel, $this->joinColumnName)) {
// 				$persistActionMeta->setRawValue($this->entityModel, $this->joinColumnName, 
// 						$idProperty->buildRaw($idProperty->repToValue($idRep), $pdo));
// 			}
// 		}
// 	}
	
// 	public function supplyInverseToOneRemoveAction($targetValue, $targetValueHash, RemoveAction $targetRemoveAction) {
// 		if ($targetValueHash === null) return;
		
// 		$actionQueue = $targetRemoveAction->getActionQueue();
// 		$persistenceContext = $actionQueue->getEntityManager()->getPersistenceContext();
		
// 		$entity = $persistenceContext->getEntityByIdRep($this->entityModel, $targetValueHash);
// 		if ($persistenceContext->containsRemovedEntity($entity)) return;
			
// 		$persistActionMeta = $actionQueue->getOrCreatePersistAction($entity)->getMeta();
// 		if (!$persistActionMeta->containsRawValue($this->entityModel, $this->joinColumnName)) {
// 			$idProperty = $this->entityModel->getIdDef()->getEntityProperty();
// 			$pdo = $actionQueue->getEntityManager()->getPdo();
// 			$persistActionMeta->setRawValue($this->entityModel, $this->joinColumnName,
// 					$idProperty->buildRaw($idProperty->repToValue($targetValueHash), $pdo));
// 		}
// 	}

	
	public function createValueHash($value, EntityManager $em): ValueHash {
		return ToOneValueHasher::createFromEntityModel($this->targetEntityModel)
				->createValueHash($value);
	}
	
	public function createInverseJoinTableToManyQueryItemFactory(EntityModel $entityModel) {
		return new InverseJoinColumnToManyQueryItemFactory($entityModel, $this->joinColumnName);
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\model\ActionDependency::persistActionSupplied()
	 */
	public function persistActionSupplied(PersistAction $targetPersistAction) {
		
	}
	
	private function markRemoveAction(RemoveAction $removeAction) {
		$removeAction->setAttribute(get_class($this), true);
	}
	
	private function isRemoveActionMarked(RemoveAction $removeAction) {
		return (boolean) $removeAction->getAttribute(get_class($this));
	}
	/* (non-PHPdoc)
	 * @see \n2n\persistence\orm\model\ActionDependency::removeActionSupplied()
	 */
	public function removeActionSupplied(RemoveAction $targetRemoveAction) {
		if ($this->actionMarker->isConstraintReleased($targetRemoveAction)) return;
		
		$actionQueue = $targetRemoveAction->getActionQueue();
		$pdo = $actionQueue->getEntityManager()->getPdo();
		$idRaw = $this->getTargetIdEntityProperty()->buildRaw($targetRemoveAction->getId(), $pdo);
		
		$resetAction = new JoinColumnResetAction($pdo, 
				$this->entityModel->getTableName(), $this->joinColumnName);
		$resetAction->setJoinIdRaw($idRaw);
		
		$targetRemoveAction->executeAtEnd(function ($actionQueue, $resetAction) {
			$actionQueue->add($resetAction);
		});
	}
}
