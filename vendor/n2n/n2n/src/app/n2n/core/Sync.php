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
namespace n2n\core;

use n2n\io\fs\FsPath;
use n2n\util\HashUtils;
use n2n\io\IoUtils;
use n2n\util\ex\IllegalStateException;

// @todo accept for same thread
/**
 * provides thread safety
 *
 */
class Sync {
	const FILE_SUFFIX = '.lock';
	
	private static $fsPath;
	/**
	 * @param FsPath $fsPath
	 */
	public static function init(FsPath $fsPath) {
		self::$fsPath = $fsPath;
	} 
	/**
	 * @param string|\ReflectionClass $class
	 * @param string $key
	 * @return \n2n\core\Lock
	 */
	public static function ex($class, $key = null) {
		return self::createLock($class, $key, LOCK_EX);			
	}
	/**
	 * @param string|\ReflectionClass $class
	 * @param string $key
	 * @return \n2n\core\Lock or null
	 */
	public static function exNb($class, $key = null) {
		return self::createLock($class, $key, LOCK_EX|LOCK_NB);
	}
	/**
	 * @param string|\ReflectionClass $class
	 * @param string $key
	 * @return \n2n\core\Lock
	 */
	public static function sh($class, $key = null) {
		return self::createLock($class, $key, LOCK_SH);
	}
	/**
	 * @param string|\ReflectionClass $class
	 * @param string $key
	 * @return \n2n\core\Lock or null
	 */
	public static function shNb($class, $key = null) {
		return self::createLock($class, $key, LOCK_SH|LOCK_NB);
	}
	/**
	 * @param string|\ReflectionClass $class
	 * @param string $key
	 * @param int $operation
	 * @throws IllegalStateException
	 * @return \n2n\core\Lock
	 */
	private static function createLock($class, $key, $operation) {
		if (self::$fsPath === null) {
			throw new IllegalStateException('Sync not initialized.');
		}
		
		$id = '';
		if ($class !== null) {
			$id .= 'c:' . self::determineClassName($class);
		}
		if ($key !== null) {
			$id .= '-k:' . $key;
		}

		$fileName = (string) self::$fsPath->ext(HashUtils::base36Md5Hash($id) . self::FILE_SUFFIX);
		$resource = IoUtils::fopen($fileName, 'c');
		$blocked = null;
		if ($operation & LOCK_NB) {
			if (!flock($resource, $operation, $blocked)) return null;
		} else {
			IoUtils::flock($resource, $operation, $blocked);
		}
		
		return new Lock($resource, $operation & LOCK_EX, $blocked, $fileName);
	}
	
	private static function determineClassName($class) {
		$className = null;
		
		if (is_object($class)) {
			$className = get_class($class);
		} else if ($class instanceof \ReflectionClass) {
			$className = $class->getName();
		} else {
			$className = (string) $class;
		}
		
		while (false !== ($superClassName = get_parent_class($className))) {
			$className = $superClassName;
		}
		
		return $className;
	}
}

class Lock {
	private $resource;
	private $exclusive;
	private $blocked;
	private $fileName;
	
	public function __construct($resource, $exclusive, $blocked, $fileName) {
		$this->resource = $resource;
		$this->exclusive = (boolean) $exclusive;
		$this->blocked = (boolean) $blocked;
		$this->fileName = $fileName;
	}

	public function release() {
		if ($this->resource === null) {
			return;
		}
		
		$resource = $this->resource;
		$this->resource = null;
		
		IoUtils::flock($resource, LOCK_UN);
		if (!$this->exclusive && !flock($resource, LOCK_EX|LOCK_NB)) {
			fclose($resource);
			return;
		}
		
		fclose($resource);
		// file could already be deleted by another sync lock
		@unlink($this->fileName);
	}
	
	public function isExclusive() {
		return $this->exclusive;
	}
	
	public function hasBlocked() {
		return $this->blocked;
	}
	
	public function __destruct() {
		$this->release();
	}
}
