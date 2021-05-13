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

use n2n\persistence\orm\store\action\ActionQueue;
use n2n\util\type\ArgUtils;
use n2n\util\col\ArrayUtils;

class ToManyAnalyzer {
	private $actionQueue;
	private $persistActions = array();
	private $pendingPersistActions = array();
	private $entityIds = array();
	
	public function __construct(ActionQueue $actionQueue) {
		$this->actionQueue = $actionQueue;
	}
	/**
	 * @param mixed $value
	 */
	public function analyze($value) {
		if ($value === null) return;
		ArgUtils::assertTrue(ArrayUtils::isArrayLike($value));
		
		$persistenceContext = $this->actionQueue->getEntityManager()->getPersistenceContext();
		
		foreach ($value as $key => $entity) {
			if (!$persistenceContext->containsManagedEntityObj($entity)) {
				continue;
			}
						
			$persistAction = $this->actionQueue->getPersistAction($entity);
			if ($persistAction->hasId()) {
				$this->entityIds[] = $persistAction->getId();
				$this->persistActions[] = $persistAction;
			} else {
				$this->pendingPersistActions[$key] = $persistAction;
			}
		}
	}
	
	public function getEntityIds() {
		return $this->entityIds;
	}
	
	public function getPersistActions() {
		return $this->persistActions;
	}
	
	public function hasPendingPersistActions() {
		return !empty($this->pendingPersistActions);
	}
	
	public function getPendingPersistActions() {
		return $this->pendingPersistActions;
	}
	
	public function getAllPersistActions() {
		return array_merge($this->persistActions, $this->pendingPersistActions);
	}
}
