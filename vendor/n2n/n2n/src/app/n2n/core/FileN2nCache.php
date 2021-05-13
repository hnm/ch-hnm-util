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

use n2n\util\cache\impl\FileCacheStore;
use n2n\core\config\AppConfig;
use n2n\core\container\N2nContext;
use n2n\io\IoUtils;
use n2n\core\container\AppCache;
use n2n\io\fs\FsPath;
use n2n\util\cache\CacheStore;

class FileN2nCache implements N2nCache {
	const STARTUP_CACHE_DIR = 'startupcache';
	const APP_CACHE_DIR = 'appcache';
	
	private $varStore;
	private $startupCacheStore;
	private $dirPerm;
	private $filePerm;
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\core\N2nCache::varStoreInitilaized()
	 */
	public function varStoreInitilaized(VarStore $varStore) {
		$this->varStore = $varStore;
		$this->startupCacheStore = new FileCacheStore($this->varStore->requestDirFsPath(
				VarStore::CATEGORY_TMP, N2N::NS, self::STARTUP_CACHE_DIR, false, false));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\core\N2nCache::getStartupCacheStore()
	 */
	public function getStartupCacheStore(): ?CacheStore {
		return $this->startupCacheStore;
	}
	
	public function appConfigInitilaized(AppConfig $appConfig) {
		$this->dirPerm = $appConfig->io()->getPrivateDirPermission();
		$this->filePerm = $appConfig->io()->getPrivateFilePermission(); 
		$this->startupCacheStore->setDirPerm($this->dirPerm);
		$this->startupCacheStore->setFilePerm($this->filePerm);
	}
	
	public function n2nContextInitialized(N2nContext $n2nContext) {
	}
	
	public function requestCacheStore($module, $componentName) {
		if (!strlen($componentName) || !IoUtils::hasStrictSpecialChars($componentName)) {
			throw new \InvalidArgumentException('Component name is empty or contains strict special chars: ' . $componentName);
		}
		
		return new FileCacheStore($this->varStore->requestDirFsPath(VarStore::CATEGORY_TMP, $module,
						$componentName), $this->dirPerm, $this->filePerm);
	}
	
	public function getAppCache(): AppCache {
		return new FileAppCache($this->varStore->requestDirFsPath(VarStore::CATEGORY_TMP, N2N::NS, 
				self::APP_CACHE_DIR), $this->dirPerm, $this->filePerm);
	}
}

class FileAppCache implements AppCache {
	private $dirFsPath;
	private $dirPerm;
	private $filePerm;
	
	public function __construct(FsPath $dirFsPath, string $dirPerm, string $filePerm) {
		$this->dirFsPath = $dirFsPath;
		$this->dirPerm = $dirPerm;
		$this->filePerm = $filePerm;
	}
	/**
	 * {@inheritDoc}
	 * @see \n2n\core\container\AppCache::lookupCacheStore($namespace)
	 */
	public function lookupCacheStore(string $namespace): CacheStore {
		$dirFsPath = $this->dirFsPath->ext(VarStore::namespaceToDirName($namespace));
		if (!$dirFsPath->isDir()) {
			$dirFsPath->mkdirs($this->dirPerm);
		}
		
		return new FileCacheStore($dirFsPath, $this->dirPerm, $this->filePerm);
	}
}
