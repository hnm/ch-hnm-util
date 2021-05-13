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

use n2n\persistence\orm\model\EntityModel;
use n2n\persistence\orm\store\action\supply\SupplyJob;

class RemoveConstraintMarker {
	private $supplyJob;
	private $targetEntityModel;
	private $actionMarker;
	
	public function __construct(SupplyJob $supplyJob, EntityModel $targetEntityModel, 
			ActionMarker $actionMarker) {
		$this->supplyJob = $supplyJob;
		$this->targetEntityModel = $targetEntityModel;
		$this->actionMarker = $actionMarker;
	}
	
	public function releaseByIdRep(string $idRep) {
		$removedEntity = $this->supplyJob->getActionQueue()->getEntityManager()->getPersistenceContext()
				->getRemovedEntityByIdRep($this->targetEntityModel, $idRep);
		if ($removedEntity === null) return;
		
		$removeAction = $this->supplyJob->getActionQueue()->getRemoveAction($removedEntity);
		$this->actionMarker->releaseConstraint($removeAction);
		
		$that = $this;
		$this->supplyJob->executeOnReset(function () use ($removeAction, $that) {
			$that->actionMarker->resetConstraint($removeAction);
		});
	}
	
	public function releaseByIdReps(array $idReps) {
		foreach ($idReps as $idRep) {
			$this->releaseByIdRep($idRep);
		}
	}
}
