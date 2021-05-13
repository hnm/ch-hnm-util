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

use n2n\core\config\AppConfig;
use n2n\core\container\N2nContext;
use n2n\core\container\AppCache;
use n2n\util\cache\CacheStore;

interface N2nCache {
	/**
	 * @param VarStore $varStore
	 */
	public function varStoreInitilaized(VarStore $varStore);
	
	/**
	 * @return null|\n2n\util\cache\CacheStore 
	 */
	public function getStartupCacheStore(): ?CacheStore;
	
	/**
	 * @param AppConfig $appConfig
	 */
	public function appConfigInitilaized(AppConfig $appConfig);
	
	/**
	 * @param N2nContext $n2nContext
	 */
	public function n2nContextInitialized(N2nContext $n2nContext);
	
	/**
	 * @return \n2n\core\container\AppCache
	 */
	public function getAppCache(): AppCache;
}
