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
namespace n2n\impl\persistence\orm\property\relation\selection;

use n2n\persistence\orm\property\EntityProperty;
use n2n\util\ex\IllegalStateException;

class ArrayObjectProxy extends \ArrayObject {
	private $loadClosure;
	private $targetIdEntityProperty;
	private $id;
// 	private $loadedValueHash;
	private $whenInitializedClosures = array();
	
	public function __construct(\Closure $loadClosure, EntityProperty $targetIdEntityProperty) {
		$this->loadClosure = new \ReflectionFunction($loadClosure);
		$this->targetIdEntityProperty = $targetIdEntityProperty;
		$this->id = uniqid();
	}

	public function getId() {
		return $this->id;
	}
	
// 	public function getLoadedValueHash() {
// 		IllegalStateException::assertTrue($this->loadedValueHash !== null);
// 		return $this->loadedValueHash;
// 	}
	
	public function isInitialized() {
		return $this->loadClosure === null;
	}

	public function initialize() {
		if ($this->isInitialized()) return;
		
		$entities = $this->loadClosure->invoke();
// 		$hasher = new ToManyValueHasher($this->targetIdEntityProperty);
// 		$this->loadedValueHash = $hasher->createValueHash($entities);
		parent::exchangeArray($entities);
		$this->loadClosure = null;
		
		foreach ($this->whenInitializedClosures as $closure) {
			$closure($this);
		}
		$this->whenInitializedClosures = array();
	}
	
	public function whenInitialized(\Closure $whenInitiliazedClosure) {
		IllegalStateException::assertTrue(!$this->isInitialized());
		$this->whenInitializedClosures[] = $whenInitiliazedClosure;
	}

	public function offsetExists ($index) {
		$this->initialize();
		return parent::offsetExists($index);
	}

	public function offsetGet ($index) {
		$this->initialize();
		return parent::offsetGet($index);
	}

	public function offsetSet ($index, $newval) {
		$this->initialize();
		return parent::offsetSet($index, $newval);
	}

	public function offsetUnset ($index) {
		$this->initialize();
		return parent::offsetUnset($index);
	}

	public function append ($value) {
		$this->initialize();
		return parent::append($value);
	}

	public function getArrayCopy () {
		$this->initialize();
		return parent::getArrayCopy();
	}

	public function count () {
		$this->initialize();
		return parent::count();
	}

	public function asort () {
		$this->initialize();
		return parent::asort();
	}

	public function ksort () {
		$this->initialize();
		return parent::ksort();
	}

	public function uasort ($cmp_function) {
		$this->initialize();
		return parent::uasort($cmp_function);
	}

	public function uksort ($cmp_function) {
		$this->initialize();
		return parent::uksort($cmp_function);
	}

	public function natsort () {
		$this->initialize();
		return parent::natsort();
	}

	public function natcasesort () {
		$this->initialize();
		return parent::natcasesort();
	}

	public function serialize () {
		$this->initialize();
		return parent::serialize();
	}

	public function getIterator () {
		$this->initialize();
		return parent::getIterator();
	}

	public function exchangeArray ($input) {
		$this->initialize();
		return parent::exchangeArray($input);
	}

	public function setIteratorClass ($iterator_class) {
		$this->initialize();
		return parent::setIteratorClass($iterator_class);
	}

	public function getIteratorClass () {
		$this->initialize();
		return parent::getIteratorClass();
	}
	/* (non-PHPdoc)
	 * @see Serializable::unserialize()
	 */
// 	public function unserialize($serialized) {
// 		$this->initialize();
// 		return parent::unserialize($serialized);
// 	}

}
