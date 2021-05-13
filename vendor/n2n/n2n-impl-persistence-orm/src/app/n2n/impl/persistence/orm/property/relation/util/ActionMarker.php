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

use n2n\persistence\orm\store\action\RemoveAction;
use n2n\persistence\orm\store\action\PersistAction;
use n2n\persistence\orm\property\EntityProperty;

class ActionMarker {
	const CONSTRAINT_ATTR = 'constraintState';
	const CONSTRAINT_STATE_RELEASED = 'released';
	
	const ORPHAN_ATTR = 'orphanState';
	const ORPHAN_STATE_UNUSED = 'unused';
	const ORPHAN_STATE_USED = 'used';
	
	private $entityProperty;
	
	public function __construct(EntityProperty $entityProperty) {
		$this->entityProperty = $entityProperty;
	}
	
	public function releaseConstraint(RemoveAction $removeAction) {
		$removeAction->setAttr($this->entityProperty, self::CONSTRAINT_ATTR, self::CONSTRAINT_STATE_RELEASED);
	}

	public function isConstraintReleased(RemoveAction $removeAction) {
		return $removeAction->getAttr($this->entityProperty, self::CONSTRAINT_ATTR) === self::STATE_RELEASED;
	}
	
	public function resetConstraint(RemoveAction $persistAction) {
		$persistAction->setAttr($this->entityProperty, self::CONSTRAINT_ATTR, null);
	}
	
	public function reportOrphanCandidate(PersistAction $persistAction) {
		if ($this->isOrphanUsed($persistAction)) return false;
	
		$persistAction->setAttr($this->entityProperty, self::ORPHAN_ATTR, self::ORPHAN_STATE_UNUSED);
		return true;
	}
	
	public function isOrphanUsed(PersistAction $persistAction) {
		return $persistAction->getAttr($this->entityProperty, self::ORPHAN_ATTR) === self::ORPHAN_STATE_USED;
	}
	
	public function useOrphan(PersistAction $persistAction) {
		$persistAction->setAttr($this->entityProperty, self::ORPHAN_ATTR, self::ORPHAN_STATE_USED);
	}
	
	public function resetOrphan(PersistAction $persistAction) {
		$persistAction->setAttr($this->entityProperty, self::ORPHAN_ATTR, null);
	}
}
