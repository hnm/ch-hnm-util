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
use n2n\persistence\orm\store\action\PersistAction;
use n2n\impl\persistence\orm\property\relation\selection\JoinColumnToManyLoader;
use n2n\impl\persistence\orm\property\relation\selection\ToManyRelationSelection;
use n2n\persistence\orm\store\SimpleLoaderUtils;
use n2n\persistence\orm\FetchType;
use n2n\util\ex\UnsupportedOperationException;
use n2n\persistence\orm\query\from\meta\TreePointMeta;
use n2n\impl\persistence\orm\property\relation\util\OrphanRemover;
use n2n\impl\persistence\orm\property\relation\util\ToManyValueHasher;
use n2n\impl\persistence\orm\property\relation\util\ToManyAnalyzer;
use n2n\persistence\orm\store\action\supply\SupplyJob;
use n2n\util\type\ArgUtils;
use n2n\util\col\ArrayUtils;
use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\store\ValueHash;
use n2n\impl\persistence\orm\property\relation\util\ToManyValueHash;
use n2n\impl\persistence\orm\property\relation\compare\ToManyCustomComparable;
use n2n\impl\persistence\orm\property\relation\compare\InverseJoinColumnToManyQueryItemFactory;

class InverseJoinColumnOneToManyRelation extends MasterRelation implements ToManyRelation {
	private $inverseJoinColumnName;
	private $orderDirectives = array();

	public function getInverseJoinColumnName() {
		return $this->inverseJoinColumnName;
	}
	
	public function setInverseJoinColumnName($inverseJoinColumnName) {
		$this->inverseJoinColumnName = $inverseJoinColumnName;
	}
	
	public function getOrderDirectives() {
		return $this->orderDirectives;
	}
	
	public function setOrderDirectives(array $orderDirectives) {
		$this->orderDirectives = $orderDirectives;
	}
	
	public function createJoinTreePoint(TreePointMeta $treePointMeta, QueryState $queryState) {
		$idQueryColumn = $this->entityModel->getIdDef()->getEntityProperty()
				->createQueryColumn($treePointMeta);
	
		$targetTreePointMeta = $this->targetEntityModel->createTreePointMeta($queryState);
		$targetJoinQueryColumn = $targetTreePointMeta->registerColumn($this->targetEntityModel, 
				$this->inverseJoinColumnName);
		
		$treePoint = new JoinColumnTreePoint($queryState, $targetTreePointMeta);
		$treePoint->setJoinColumn($idQueryColumn);
		$treePoint->setTargetJoinColumn($targetJoinQueryColumn);
		return $treePoint;
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\MasterRelation::createInverseJoinTreePoint()
	 */
	public function createInverseJoinTreePoint(EntityModel $entityModel, TreePointMeta $targetTreePointMeta, QueryState $queryState) {
		$targetJoinQueryColumn = $targetTreePointMeta->registerColumn($this->targetEntityModel, 
				$this->inverseJoinColumnName);
		
		$treePointMeta = $entityModel->createTreePointMeta($queryState);
		$idQueryColumn = $this->targetEntityModel->getIdDef()->getEntityProperty()
				->createColumn($treePointMeta);
				
		$treePoint = new JoinColumnTreePoint($queryState, $treePointMeta);
		$treePoint->setJoinColumn($targetJoinQueryColumn);
		$treePoint->setTargetJoinColumn($idQueryColumn);
		return $treePoint;
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::createColumnComparable()
	 */
	public function createCustomComparable(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		$toManyQueryItemFactory = new InverseJoinColumnToManyQueryItemFactory($this->targetEntityModel,
				$this->inverseJoinColumnName);
		
		return new ToManyCustomComparable($metaTreePoint, $this->targetEntityModel,
				$this->createTargetIdTreePath(), $toManyQueryItemFactory, $queryState);
	}

	public function createInverseJoinTableToManyQueryItemFactory(EntityModel $entityModel) {
		throw new UnsupportedOperationException();
	}
	
	public function prepareSupplyJob(SupplyJob $supplyJob, $value, ?ValueHash $oldValueHash) {
		if (!$this->orphanRemoval || $oldValueHash === null || $supplyJob->isInsert()) return;

		ArgUtils::assertTrue($oldValueHash instanceof ToManyValueHash);
		if ($oldValueHash->checkForUntouchedProxy($value)) return;
	
		$orphanRemover = new OrphanRemover($supplyJob, $this->targetEntityModel, $this->actionMarker);
		
		if (!$supplyJob->isRemove()) {
			ArgUtils::assertTrue(ArrayUtils::isArrayLike($value));
			foreach ($value as $entity) {
				$orphanRemover->releaseCandiate($entity);
			}
		}
			
		$orphanRemover->reportCandidateByIdReps($oldValueHash->getIdReps(true));
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::supplyPersistAction()
	 */
	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash, 
			?ValueHash $oldValueHash) {
		ArgUtils::assertTrue($oldValueHash === null || $oldValueHash instanceof ToManyValueHash);
		if ($oldValueHash !== null && $oldValueHash->checkForUntouchedProxy($value)) return;
		
		$toManyAnalyzer = new ToManyAnalyzer($persistAction->getActionQueue());
		$toManyAnalyzer->analyze($value);
		
		$hasher = new ToManyValueHasher($this->targetEntityModel->getIdDef()->getEntityProperty());
		
		if ($oldValueHash !== null && !$toManyAnalyzer->hasPendingPersistActions()
				&& $hasher->matches($toManyAnalyzer->getEntityIds(), $oldValueHash)) {
			return;
		}
		
		$this->checkValueHash($toManyAnalyzer, $hasher, $valueHash);
		
		$targetPersistActions = $toManyAnalyzer->getAllPersistActions();
		
		if ($persistAction->hasId()) {
			$this->applyPersistId($persistAction, $targetPersistActions);
			return;
		}
				
		foreach ($targetPersistActions as $targetPersistAction) {
			$targetPersistAction->addDependent($persistAction);
		}
		
		$persistAction->executeAtEnd(function () use ($persistAction, $targetPersistActions) {
			$this->applyPersistId($persistAction, $targetPersistActions);
		});
	}
	
	/**
	 * @param PersistAction[] $targetPersistActions
	 * @param ToManyValueHasher $hasher
	 * @param ToManyValueHash $valueHash
	 */
	private function checkValueHash(ToManyAnalyzer $toManyAnalyzer, ToManyValueHasher $hasher, ToManyValueHash $valueHash) {
		foreach ($toManyAnalyzer->getPendingPersistActions() as $key => $targetPersistAction) {
			$targetPersistAction->executeAtEnd(function () use ($targetPersistAction, $hasher, $key, $valueHash) {
				$hasher->reportId($key, $targetPersistAction->getId(), $valueHash);
			});
		}	
	}
	
	private function applyPersistId(PersistAction $persistAction, array $targetPersistActions) {
		$idProperty = $this->entityModel->getIdDef()->getEntityProperty();
		$idRaw = $idProperty->valueToRep($persistAction->getId());
		foreach ($targetPersistActions as $targetPersistAction) {
			$targetPersistAction->getMeta()->setRawValue($this->targetEntityModel,
					$this->inverseJoinColumnName, $idRaw);
		}
	}
// 	/* (non-PHPdoc)
// 	 * @see \n2n\impl\persistence\orm\property\relation\Relation::supplyInverseToManyRemoveAction()
// 	 */
// 	public function supplyInverseToManyRemoveAction($targetValue, $targetValueHash, RemoveAction $targetRemoveAction) {
// 		throw new UnsupportedOperationException();
// 	}
// 	/* (non-PHPdoc)
// 	 * @see \n2n\impl\persistence\orm\property\relation\Relation::supplyInverseToOneRemoveAction()
// 	 */
// 	public function supplyInverseToOneRemoveAction($targetValue, $targetValueHash, RemoveAction $targetRemoveAction) {
// 	}
	
	public function createValueHash($value, EntityManager $em): ValueHash {
		$analyzer = new ToManyValueHasher($this->targetEntityModel->getIdDef()
				->getEntityProperty());
		return $analyzer->createValueHash($value);
	}

	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::createSelection()
	 */
	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		$idSelection = $metaTreePoint->requestPropertySelection($this->createIdTreePath());
		$idProperty = $this->entityModel->getIdDef()->getEntityProperty();
		
		$toManyLoader = new JoinColumnToManyLoader(
				new SimpleLoaderUtils($queryState->getEntityManager(), $this->targetEntityModel),
				$idProperty, $this->inverseJoinColumnName);
		$toManyLoader->setOrderDirectives($this->orderDirectives);
		
		$toManySelection = new ToManyRelationSelection($idSelection, $toManyLoader, 
				$this->getTargetIdEntityProperty());
		$toManySelection->setLazy($this->fetchType == FetchType::LAZY);
		return $toManySelection;
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\MasterRelation::createInverseToManyLoader()
	 */
	public function createInverseToManyLoader(EntityModel $entityModel, QueryState $queryState) {
		throw new UnsupportedOperationException();
	}
}
