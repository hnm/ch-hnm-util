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

use n2n\persistence\orm\query\from\MetaTreePoint;
use n2n\persistence\orm\query\QueryState;
use n2n\util\ex\IllegalStateException;
use n2n\persistence\orm\criteria\compare\ComparisonStrategy;
use n2n\persistence\orm\FetchType;
use n2n\impl\persistence\orm\property\relation\selection\ToOneRelationSelection;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\impl\persistence\orm\property\relation\util\ToOneValueHasher;
use n2n\impl\persistence\orm\property\relation\compare\IdColumnComparableDecorator;
use n2n\persistence\orm\store\action\supply\SupplyJob;
use n2n\persistence\orm\property\EntityProperty;
use n2n\persistence\orm\model\EntityModel;
use n2n\impl\persistence\orm\property\relation\util\ToOneUtils;
use n2n\persistence\orm\store\ValueHash;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\relation\util\ToOneValueHash;
use n2n\persistence\orm\EntityManager;

class JoinTableToOneRelation extends JoinTableRelation implements ToOneRelation {
	private $toOneUtils;
	
	public function __construct(EntityProperty $entityProperty, EntityModel $targetEntityModel) {
		parent::__construct($entityProperty, $targetEntityModel);
		$this->toOneUtils = new ToOneUtils($this, true);
	}

	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\ToOneRelation::createRepresentingQueryItem()
	 */
	public function createRepresentingQueryItem(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		return $metaTreePoint->requestPropertyRepresentableQueryItem($this->createTargetIdTreePath());
	}

	public function createColumnComparable(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		$comparisonStargegy = $metaTreePoint->requestPropertyComparisonStrategy($this->createTargetIdTreePath())
				->getColumnComparable();
		
		IllegalStateException::assertTrue($comparisonStargegy->getType() == ComparisonStrategy::TYPE_COLUMN);
		
		$meta = $metaTreePoint->getMeta();
		return new IdColumnComparableDecorator($comparisonStargegy->getColumnComparable(), 
				$this->targetEntityModel);
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::createSelection()
	 */
	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		$idSelection = $metaTreePoint->requestPropertySelection($this->createTargetIdTreePath());
	
		$toOneRelationSelection = new ToOneRelationSelection($this->entityModel, $idSelection, $queryState);
		$toOneRelationSelection->setLazy($this->fetchType == FetchType::LAZY);
		return $toOneRelationSelection;
	}
	
	public function prepareSupplyJob(SupplyJob $supplyJob, $value, ?ValueHash $oldValueHash) {
		$this->toOneUtils->prepareSupplyJob($supplyJob, $value, $oldValueHash);
	}
	
	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash, ?ValueHash $oldValueHash) {
		ArgUtils::assertTrue($oldValueHash === null || $oldValueHash instanceof ToOneValueHash);
		
		if ($value === null) {
			if ($oldValueHash === null || $oldValueHash->getEntityIdRep() === null) return;
						
			$this->createJoinTableActionFromPersistAction($persistAction);		
			return;
		}
		
		$targetIdProperty = $this->targetEntityModel->getIdDef()->getEntityProperty();
		$actionQueue = $persistAction->getActionQueue();
		$targetPersistAction = $actionQueue->getPersistAction($value);
		
		if ($targetPersistAction->hasId()) {
			$targetIdRep = $targetIdProperty->valueToRep($targetPersistAction->getId());
			if ($oldValueHash !== null && $targetIdRep === $oldValueHash->getEntityIdRep()) return;

			$this->createJoinTableActionFromPersistAction($persistAction)->addInverseJoinIdRep($targetIdRep);
			return;
		}		
	
		$joinTableAction = $this->createJoinTableActionFromPersistAction($persistAction);
		$joinTableAction->addDependent($targetPersistAction);
		$targetPersistAction->executeAtEnd(function () use ($joinTableAction, $targetPersistAction, $targetIdProperty) {
			$joinTableAction->addInverseJoinIdRep($targetIdProperty->valueToRep($targetPersistAction->getId()));
			
			$hasher = new ToOneValueHasher($this->getTargetIdEntityProperty());
			$hasher->reportId($targetId, $valueHash);
		});
	}
	
	public function createValueHash($value, EntityManager $em): ValueHash {
		return ToOneValueHasher::createFromEntityModel($this->targetEntityModel)
				->createValueHash($value);
	}
}
