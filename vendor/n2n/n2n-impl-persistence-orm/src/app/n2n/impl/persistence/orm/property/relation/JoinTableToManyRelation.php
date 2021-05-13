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
use n2n\impl\persistence\orm\property\relation\selection\JoinTableToManyLoader;
use n2n\persistence\orm\store\SimpleLoaderUtils;
use n2n\persistence\orm\FetchType;
use n2n\impl\persistence\orm\property\relation\selection\ToManyRelationSelection;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\impl\persistence\orm\property\relation\util\ToManyValueHasher;
use n2n\impl\persistence\orm\property\relation\util\ToManyAnalyzer;
use n2n\impl\persistence\orm\property\relation\compare\JoinTableToManyQueryItemFactory;
use n2n\impl\persistence\orm\property\relation\compare\ToManyCustomComparable;
use n2n\persistence\orm\store\action\supply\SupplyJob;
use n2n\persistence\orm\property\EntityProperty;
use n2n\persistence\orm\model\EntityModel;
use n2n\impl\persistence\orm\property\relation\util\ToManyUtils;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\store\ValueHash;
use n2n\util\type\ArgUtils;
use n2n\impl\persistence\orm\property\relation\util\ToManyValueHash;

class JoinTableToManyRelation extends JoinTableRelation implements ToManyRelation {
	private $toManyUtils;
	private $orderDirectives = array();

	public function __construct(EntityProperty $entityProperty, EntityModel $targetEntityModel) {
		parent::__construct($entityProperty, $targetEntityModel);
		$this->toManyUtils = new ToManyUtils($this, true);
	}
	
	public function getOrderDirectives() {
		return $this->orderDirectives;
	}
	
	public function setOrderDirectives(array $orderDirectives) {
		$this->orderDirectives = $orderDirectives;
	}

	public function createCustomComparable(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		$toManyQueryItemFactory = new JoinTableToManyQueryItemFactory($this->joinTableName,
				$this->joinColumnName, $this->inverseJoinColumnName);
		
		return new ToManyCustomComparable($metaTreePoint, $this->targetEntityModel, 
				$this->createTargetIdTreePath(), $toManyQueryItemFactory, $queryState);
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::createSelection()
	 */
	public function createSelection(MetaTreePoint $metaTreePoint, QueryState $queryState) {
		$idSelection = $metaTreePoint->requestPropertySelection($this->createIdTreePath());
		
		$toManyLoader = new JoinTableToManyLoader(
				new SimpleLoaderUtils($queryState->getEntityManager(), $this->targetEntityModel),
				$this->entityModel->getIdDef()->getEntityProperty(), $this->joinTableName, 
				$this->inverseJoinColumnName, $this->joinColumnName);
		$toManyLoader->setOrderDirectives($this->orderDirectives);
		
		$toManySelection = new ToManyRelationSelection($idSelection, $toManyLoader, 
				$this->getTargetIdEntityProperty());
		$toManySelection->setLazy($this->fetchType == FetchType::LAZY);
		return $toManySelection;
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::prepareSupplyJob()
	 */
	public function prepareSupplyJob(SupplyJob $supplyJob, $value, ?ValueHash $oldValueHash) {
		$this->toManyUtils->prepareSupplyJob($supplyJob, $value, $oldValueHash);
	}
	/* (non-PHPdoc)
	 * @see \n2n\impl\persistence\orm\property\relation\Relation::supplyPersistAction()
	 */
	public function supplyPersistAction(PersistAction $persistAction, $value, ValueHash $valueHash, 
			?ValueHash $oldValueHash) {
		ArgUtils::assertTrue($oldValueHash === null || $oldValueHash instanceof ToManyValueHash);
		if ($oldValueHash !== null && $oldValueHash->checkForUntouchedProxy($value)) return;
		
		$targetIdProperty = $this->targetEntityModel->getIdDef()->getEntityProperty();
				
		$toManyAnalyzer = new ToManyAnalyzer($persistAction->getActionQueue());
		$toManyAnalyzer->analyze($value);

		$hasher = new ToManyValueHasher($targetIdProperty);
		
		if ($oldValueHash !== null && !$toManyAnalyzer->hasPendingPersistActions() 
				&& $hasher->matches($toManyAnalyzer->getEntityIds(), $oldValueHash)) {
			return;
		}
		
		$joinTableAction = $this->createJoinTableActionFromPersistAction($persistAction);
		
		foreach ($toManyAnalyzer->getEntityIds() as $targetEntityId) {
			$joinTableAction->addInverseJoinIdRaw($targetIdProperty->buildRaw($targetEntityId, $joinTableAction->getPdo()));
		}
		
		foreach ($toManyAnalyzer->getPendingPersistActions() as $key => $targetPersistAction) {
			$joinTableAction->addDependent($targetPersistAction);
			$targetPersistAction->executeAtEnd(function () use ($joinTableAction, $targetPersistAction, $targetIdProperty, $hasher, $key, $valueHash) {
				$targetId = $targetPersistAction->getId();
				$joinTableAction->addInverseJoinIdRaw($targetIdProperty->buildRaw($targetId, $joinTableAction->getPdo()));
				$hasher->reportId($key, $targetId, $valueHash);
			});
		}
	}
	
	public function createValueHash($value, EntityManager $em): ValueHash {
		$analyzer = new ToManyValueHasher($this->targetEntityModel->getIdDef()->getEntityProperty());
		return $analyzer->createValueHash($value);
	}
}
