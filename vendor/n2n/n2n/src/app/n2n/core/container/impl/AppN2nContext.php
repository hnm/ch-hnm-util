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
namespace n2n\core\container\impl;

use n2n\web\http\Request;
use n2n\web\http\Response;
use n2n\l10n\N2nLocale;
use n2n\context\LookupManager;
use n2n\reflection\ReflectionUtils;
use n2n\web\http\HttpContextNotAvailableException;
use n2n\core\module\UnknownModuleException;
use n2n\l10n\DynamicTextCollection;
use n2n\reflection\magic\MagicUtils;
use n2n\context\LookupFailedException;
use n2n\core\config\AppConfig;
use n2n\core\VarStore;
use n2n\core\container\N2nContext;
use n2n\core\container\TransactionManager;
use n2n\core\module\ModuleManager;
use n2n\web\http\HttpContext;
use n2n\core\container\AppCache;
use n2n\util\ex\IllegalStateException;
use n2n\core\config\GeneralConfig;
use n2n\core\config\WebConfig;
use n2n\core\config\MailConfig;
use n2n\core\config\IoConfig;
use n2n\core\config\FilesConfig;
use n2n\core\config\ErrorConfig;
use n2n\core\config\DbConfig;
use n2n\core\config\OrmConfig;
use n2n\core\config\N2nLocaleConfig;
use n2n\persistence\orm\EntityManagerFactory;
use n2n\persistence\orm\EntityManager;
use n2n\core\container\PdoPool;
use n2n\web\http\Session;
use n2n\util\magic\MagicObjectUnavailableException;

class AppN2nContext implements N2nContext {
	private $transactionManager;
	private $moduleManager;
	private $appCache;
	private $varStore;
	private $appConfig;
	private $moduleConfigs = array();
	private $httpContext;
	private $n2nLocale;
	private $lookupManager;
	
	public function __construct(TransactionManager $transactionManager, ModuleManager $moduleManager, AppCache $appCache, 
			VarStore $varStore, AppConfig $appConfig) {
		$this->transactionManager = $transactionManager;
		$this->moduleManager = $moduleManager;
		$this->appCache = $appCache;
		$this->varStore = $varStore;
		$this->appConfig = $appConfig;
		$this->n2nLocale = N2nLocale::getDefault();
	}
	
	/**
	 * @param LookupManager $lookupManager
	 */
	public function setLookupManager(LookupManager $lookupManager) {
		$this->lookupManager = $lookupManager;
	}
	
	/**
	 * @throws IllegalStateException
	 * @return \n2n\context\LookupManager
	 */
	public function getLookupManager(): LookupManager {
		if ($this->lookupManager !== null) {
			return $this->lookupManager;
		}
		
		throw new IllegalStateException('No LookupManager defined.');
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\core\container\N2nContext::getTransactionManager()
	 */
	public function getTransactionManager(): TransactionManager {
		return $this->transactionManager;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\core\container\N2nContext::getModuleManager()
	 */
	public function getModuleManager(): ModuleManager {
		return $this->moduleManager;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\core\container\N2nContext::getModuleConfig($namespace)
	 */
	public function getModuleConfig(string $namespace) {
		if (array_key_exists($namespace, $this->moduleConfigs)) {
			return $this->moduleConfigs[$namespace];
		}
		
		$module = $this->moduleManager->getModuleByNs($namespace);
		if ($module->hasConfigDescriber()) {
			return $this->moduleConfigs[$namespace] = $module->createConfigDescriber($this)->buildCustomConfig();
		}
		
		return $this->moduleConfigs[$namespace] = null;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\core\container\N2nContext::isHttpContextAvailable()
	 */
	public function isHttpContextAvailable(): bool {
		return $this->httpContext !== null;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\core\container\N2nContext::getHttpContext()
	 */
	public function getHttpContext(): HttpContext {
		if ($this->httpContext !== null) {
			return $this->httpContext;
		}
		
		throw new HttpContextNotAvailableException();
	}
	
	/**
	 * @param HttpContext $httpContext
	 */
	public function setHttpContext(HttpContext $httpContext = null) {
		$this->httpContext = $httpContext;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\core\container\N2nContext::getVarStore()
	 */
	public function getVarStore(): VarStore {
		return $this->varStore;
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\core\container\N2nContext::getAppCache()
	 */
	public function getAppCache(): AppCache {
		return $this->appCache;
	}
	
	/**
	 * @return N2nLocale
	 */
	public function getN2nLocale(): N2nLocale {
		return $this->httpContext !== null ? $this->httpContext->getRequest()->getN2nLocale() : $this->n2nLocale;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\core\container\N2nContext::setN2nLocale($n2nLocale)
	 */
	public function setN2nLocale(N2nLocale $n2nLocale) {
		if ($this->httpContext !== null) {
			$this->httpContext->getRequest()->setN2nLocale($n2nLocale);
			return;
		}
		
		$this->n2nLocale = $n2nLocale;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\util\magic\MagicContext::lookup()
	 */
	public function lookup($id, $required = true) {
		if ($id instanceof \ReflectionClass) {
			$id = $id->getName();
		}
		
		// @todo check $required
		switch ($id) {
			case Request::class:
				try {
					return $this->getHttpContext()->getRequest();
				} catch (HttpContextNotAvailableException $e) {
					if (!$required) return null;
					throw new MagicObjectUnavailableException('Request not available.', 0, $e); 
				}
				return $this->request;
			case Response::class:
				try {
					return $this->getHttpContext()->getResponse();
				} catch (HttpContextNotAvailableException $e) {
					if (!$required) return null;
					throw new MagicObjectUnavailableException('Response not available.', 0, $e); 
				}
			case Session::class:
				try {
					return $this->getHttpContext()->getSession();
				} catch (HttpContextNotAvailableException $e) {
					if (!$required) return null;
					throw new MagicObjectUnavailableException('Session not available.', 0, $e);
				}
			case HttpContext::class:
				try {
					return $this->getHttpContext();
				} catch (HttpContextNotAvailableException $e) {
					if (!$required) return null;
					throw new MagicObjectUnavailableException('HttpContext not available.', 0, $e);
				}
			case N2nContext::class:
				return $this;
			case LookupManager::class:
				return $this->getLookupManager();
			case N2nLocale::class:
				return $this->getN2nLocale();
			case EntityManager::class:
				return $this->lookup(PdoPool::class)->getEntityManagerFactory()->getExtended();
			case EntityManagerFactory::class:
				return $this->lookup(PdoPool::class)->getEntityManagerFactory();
			case TransactionManager::class:
				return $this->transactionManager;
			case VarStore::class:
				return $this->varStore;
			case AppCache::class:
				return $this->appCache;
			case AppConfig::class:
				return $this->appConfig;
			case GeneralConfig::class:
				return $this->appConfig->general();
			case WebConfig::class:
				return $this->appConfig->web();
			case MailConfig::class:
				return $this->appConfig->mail();
			case IoConfig::class:
				return $this->appConfig->io();
			case FilesConfig::class:
				return $this->appConfig->files();
			case ErrorConfig::class:
				return $this->appConfig->error();
			case DbConfig::class:
				return $this->appConfig->db();
			case OrmConfig::class:
				return $this->appConfig->orm();
			case N2nLocaleConfig::class:
				return $this->appConfig->locale();
			default:
				try {
					return $this->getLookupManager()->lookup($id);
				} catch (LookupFailedException $e) {
					throw new MagicObjectUnavailableException('Could not lookup object with name: ' . $id, 0, $e);
				}
		}
	}
	
	private function determineNamespaceOfParameter(\ReflectionParameter $parameter) {
		if (null !== ($class = $parameter->getDeclaringClass())) {
			return $class->getNamespaceName();
		}
		
		return $parameter->getDeclaringFunction()->getNamespaceName();
	}
	
	public function lookupParameterValue(\ReflectionParameter $parameter) {
		$parameterClass = ReflectionUtils::extractParameterClass($parameter);
		
		if ($parameterClass === null) {
			throw new MagicObjectUnavailableException();
		}
		
		switch ($parameterClass->getName()) {
			case 'n2n\l10n\DynamicTextCollection':
				$module = null;
				try {
					$module = $this->moduleManager->getModuleOfTypeName($this->determineNamespaceOfParameter($parameter),
							!$parameter->allowsNull());
				} catch (UnknownModuleException $e) {
					throw new MagicObjectUnavailableException('Could not determine module for DynamicTextCollection.', 0, $e);
				}
		
				if ($module === null) return null;
		
				return new DynamicTextCollection($module, $this->getN2nLocale());
				
			case 'n2n\core\module\Module':
				try {
					return $this->moduleManager->getModuleOfTypeName($this->determineNamespaceOfParameter($parameter),
							!$parameter->allowsNull());
				} catch (UnknownModuleException $e) {
					throw new MagicObjectUnavailableException('Could not determine module.', 0, $e);
				}
				
			default:
				if ($parameter->isDefaultValueAvailable()) {
					if (null !== ($value = $this->lookup($parameterClass->getName(), false))) {
						return $value;
					}
					return $parameter->getDefaultValue();
				} else if ($parameter->allowsNull()) {
					return $this->lookup($parameterClass->getName(), false);
				}
				
				return $this->lookup($parameterClass->getName(), true);
		}
	}
	
	public function magicInit($object) {
		MagicUtils::init($object, $this);
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @return \n2n\core\container\impl\AppN2nContext
	 */
	static function createCopy(N2nContext $n2nContext) {
		$appN2nContext = new AppN2nContext($n2nContext->getTransactionManager(), $n2nContext->getModuleManager(), 
				$n2nContext->getAppCache(), $n2nContext->getVarStore(), $n2nContext->lookup(AppConfig::class));
		
		$appN2nContext->setLookupManager(new LookupManager($appN2nContext));
		
		$appN2nContext->setN2nLocale($n2nContext->getN2nLocale());
		
		return $appN2nContext;
	}

}
