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
namespace n2n\core\container;

use n2n\l10n\N2nLocale;
use n2n\util\magic\MagicContext;
use n2n\core\VarStore;
use n2n\core\module\ModuleManager;
use n2n\web\http\HttpContext;
use n2n\web\http\HttpContextNotAvailableException;
use n2n\context\LookupManager;

interface N2nContext extends MagicContext {
	
	/**
	 * @return \n2n\core\container\TransactionManager
	 */
	public function getTransactionManager(): TransactionManager;

	/**
	 * @return \n2n\core\module\ModuleManager
	 */
	public function getModuleManager(): ModuleManager;

	/**
	 * @param string $namespace
	 * @return mixed
	 */
	public function getModuleConfig(string $namespace);
	
	/**
	 * @return \n2n\core\VarStore
	 */
	public function getVarStore(): VarStore;
	
	/**
	 * @return bool
	 */
	public function isHttpContextAvailable(): bool;
	 
	/**
	 * @return \n2n\web\http\HttpContext
	 * @throws HttpContextNotAvailableException
	 */
	public function getHttpContext(): HttpContext;
	
	/**
	 * @return \n2n\core\container\AppCache
	 */
	public function getAppCache(): AppCache;
	
	/**
	 * @return \n2n\l10n\N2nLocale
	 */
	public function getN2nLocale(): N2nLocale;
	
	/**
	 * @param N2nLocale $n2nLocale
	 */
	public function setN2nLocale(N2nLocale $n2nLocale);
	
	/**
	 * @return LookupManager
	 */
	public function getLookupManager(): LookupManager;
	
	/**
	 * @param object $object
	 */
	public function magicInit($object);
}
