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
namespace n2n\impl\persistence\orm\property;

use n2n\impl\persistence\orm\property\relation\Relation;
use n2n\persistence\orm\property\JoinableEntityProperty;

interface RelationEntityProperty extends JoinableEntityProperty {
	const TYPE_ONE_TO_ONE = 'OneToOne';
	const TYPE_ONE_TO_MANY = 'OneToMany';
	const TYPE_MANY_TO_ONE = 'ManyToOne';
	const TYPE_MANY_TO_MANY = 'ManyToMany';
	
	/**
	 * @return Relation
	 */
	public function getRelation(): Relation;
	
	/**
	 * @return string 
	 */
	public function getType(): string;
	
	/**
	 * @return bool 
	 */
	public function isMaster(): bool;
	
	/**
	 * @return bool
	 */
	public function isToMany(): bool;
}
