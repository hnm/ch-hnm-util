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

use n2n\core\container\TransactionManager;
use n2n\log4php\Logger;
use n2n\core\container\PdoPool;
use n2n\core\module\Module;
use n2n\core\err\ExceptionHandler;
use n2n\l10n\N2nLocale;
use n2n\io\IoUtils;
use n2n\l10n\Language;
use n2n\l10n\Region;
use n2n\batch\BatchJobRegistry;
use n2n\context\LookupManager;
use n2n\web\http\controller\ControllerRegistry;
use n2n\core\config\AppConfig;
use n2n\web\http\VarsRequest;
use n2n\util\uri\Path;
use n2n\core\module\ModuleFactory;
use n2n\core\module\impl\LazyModule;
use n2n\io\fs\FsPath;
use n2n\core\config\build\CombinedConfigSource;
use n2n\core\config\build\AppConfigFactory;
use n2n\l10n\L10n;
use n2n\core\module\ModuleManager;
use n2n\core\container\impl\AppN2nContext;
use n2n\web\http\HttpContext;
use n2n\util\type\CastUtils;
use n2n\core\module\impl\EtcModuleFactory;
use n2n\web\http\Method;
use n2n\web\http\MethodNotAllowedException;
use n2n\l10n\MessageContainer;
use n2n\web\dispatch\DispatchContext;
use n2n\web\http\VarsSession;

define('N2N_CRLF', "\r\n");

class N2N {
	const VERSION = '7.2.33';
	const LOG4PHP_CONFIG_FILE = 'log4php.xml'; 
	const LOG_EXCEPTION_DETAIL_DIR = 'exceptions';
	const LOG_MAIL_BUFFER_DIR = 'log-mail-buffer';
	const LOG_ERR_FILE = 'err.log';
	const SRV_BATCH_DIR = 'batch';
 	const NS = 'n2n';
	const CHARSET = 'utf-8';
	const CHARSET_MIN = 'utf8';
	const CONFIG_CACHE_NAME = 'cache';
	const SYNC_DIR = 'sync';
	
	const STAGE_DEVELOPMENT = 'development';
	const STAGE_TEST = 'test';
	const STAGE_LIVE = 'live';
	
	protected $publicDirPath;
	protected $varStore;
	protected $combinedConfigSource;
	protected $moduleManager;
	protected $appConfig;
	protected $n2nContext;
	
	private static $initialized = false;
	/**
	 * 
	 * @param string $publicDirPath
	 * @param string $varDirPath
	 * @param array $moduleDirPaths
	 */
	private function __construct(FsPath $publicDirPath, FsPath $varDirPath) {
		$this->publicDirPath = $publicDirPath;
		$this->varStore = new VarStore($varDirPath, null, null);
		
		$this->combinedConfigSource = new CombinedConfigSource();
	}
	
	protected function initModules(ModuleFactory $moduleFactory) {
		$moduleFactory->init($this->varStore);
		
		$this->combinedConfigSource->setMain($moduleFactory->getMainAppConfigSource());
		$this->moduleManager = new ModuleManager();
		
		foreach ($moduleFactory->getModules() as $module) {
			$this->moduleManager->registerModule($module);
			if (null !== ($appConfigSource = $module->getAppConfigSource())) {
				$this->combinedConfigSource->putAdditional((string) $module, $appConfigSource);
			}
		}
		
		if (!$this->moduleManager->containsModuleNs(self::NS)) {
			$this->moduleManager->registerModule(new LazyModule(self::NS));
		}
	}

	protected function init(N2nCache $n2nCache) {
		$this->initN2nContext($n2nCache);
		$n2nCache->n2nContextInitialized($this->n2nContext);
	}
	/**
	 * 
	 * @param \n2n\core\config\AppConfig
	 * @throws \n2n\config\InvalidConfigurationException
	 */
	private function initConfiguration(N2nCache $n2nCache) {
		$cacheStore = $n2nCache->getStartupCacheStore();
		$hashCode = null;
		if ($cacheStore === null || null === ($hashCode = $this->combinedConfigSource->hashCode())) {
			$appConfigFactory = new AppConfigFactory($this->publicDirPath);
			$this->appConfig = $appConfigFactory->create($this->combinedConfigSource, N2N::getStage());
			$this->applyConfiguration($n2nCache);
			return;
		}
		
		$characteristics = array('version' => N2N::VERSION, 'stage' => N2N::getStage(),
				'hashCode' => $hashCode, 'publicDir' => (string) $this->publicDirPath);
		if (null !== ($cacheItem = $cacheStore->get(self::CONFIG_CACHE_NAME, $characteristics))) {
			$this->appConfig = $cacheItem->getData();
			if ($this->appConfig instanceof AppConfig) {
				$this->applyConfiguration($n2nCache);
				return;
			} 
			$this->appConfig = null;
		}
		

		$appConfigFactory = new AppConfigFactory($this->publicDirPath);
		$this->appConfig = $appConfigFactory->create($this->combinedConfigSource, N2N::getStage());
		$this->applyConfiguration($n2nCache);
		$cacheStore->removeAll(self::CONFIG_CACHE_NAME);
		$cacheStore->store(self::CONFIG_CACHE_NAME, $characteristics, $this->appConfig);
	}

	private function applyConfiguration(N2nCache $n2nCache) {
		$errorConfig = $this->appConfig->error();
		self::$exceptionHandler->setStrictAttitude($errorConfig->isStrictAttitudeEnabled());
		self::$exceptionHandler->setDetectStartupErrorsEnabled($errorConfig->isDetectStartupErrorsEnabled());
		self::$exceptionHandler->setDetectBadRequestsOnStartupEnabled($errorConfig->isStartupDetectBadRequestsEnabled());
		
		if ($errorConfig->isLogSendMailEnabled()) {
			self::$exceptionHandler->setLogMailRecipient($errorConfig->getLogMailRecipient(), 
					$this->appConfig->mail()->getDefaultAddresser());
		}

		$ioConfig = $this->appConfig->io();
		$this->varStore->setDirPerm($ioConfig->getPrivateDirPermission());
		$this->varStore->setFilePerm($ioConfig->getPrivateFilePermission());
		
		$n2nLocaleConfig = $this->appConfig->locale();
		L10n::setPeclIntlEnabled($this->appConfig->l10n()->isEnabled());
		N2nLocale::setDefault($n2nLocaleConfig->getDefaultN2nLocale());
		N2nLocale::setFallback($n2nLocaleConfig->getFallbackN2nLocale());
		N2nLocale::setAdmin($n2nLocaleConfig->getAdminN2nLocale());
		N2nLocale::setWebAliases($this->appConfig->web()->getAliasN2nLocales());
		
		L10n::setL10nConfig($this->appConfig->l10n());
		L10n::setPseudoL10nConfig($this->appConfig->pseudoL10n());
		
		$n2nCache->appConfigInitilaized($this->appConfig);
	}
	
	private function initN2nContext(N2nCache $n2nCache) {
		$this->n2nContext = new AppN2nContext(new TransactionManager(), $this->moduleManager, $n2nCache->getAppCache(),
				$this->varStore, $this->appConfig);
		
		$lookupManager = new LookupManager($this->n2nContext);
		self::registerShutdownListener($lookupManager);
		$this->n2nContext->setLookupManager($lookupManager);
		
		$this->n2nContext->setHttpContext($this->buildHttpContext());
		
// 		if ($this->n2nContext->isHttpContextAvailable()) {
// 			try {
// 				$this->n2nContext->lookup(DispatchContext::class)->analyzeRequest($this->n2nContext->getHttpContext()->getRequest());
// 			} catch (CorruptedDispatchException $e) {
// 				throw new BadRequestException(null, 0, $e);
// 			}
// 		}
	}
	
	private function buildHttpContext() {
		if (!isset($_SERVER['REQUEST_URI'])) return null;
		
		$generalConfig = $this->appConfig->general();
		$webConfig = $this->appConfig->web();
// 		$filesConfig = $this->appConfig->files();
		
		$request = new VarsRequest($_SERVER, $_GET, $_POST, $_FILES); 
		
		$session = new VarsSession($generalConfig->getApplicationName());
		
		$subsystem = $this->detectSubsystem($request->getHostName(), $request->getContextPath());
		$request->setSubsystem($subsystem);
		
		$n2nLocales = $webConfig->getSupersystem()->getN2nLocales();
		if ($subsystem !== null) {
			$n2nLocales = array_merge($n2nLocales, $subsystem->getN2nLocales());
		}
		$request->setN2nLocale($this->detectN2nLocale($n2nLocales));
		
		return HttpContextFactory::createFromAppConfig($this->appConfig, $request, $session, $this->n2nContext);
	}
	
	
	private function detectN2nLocale(array $n2nLocales) {
		$n2nLocale = null;
		if (!empty($n2nLocales)) {
			$n2nLocale = reset($n2nLocales);
		} else {
			$n2nLocale = N2nLocale::getDefault();
		}
		
		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return $n2nLocale;	
		} 
		
		if (null !== ($n2nLocaleId = N2nLocale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']))) {
			if (isset($n2nLocales[$n2nLocaleId])) {
				return $n2nLocales[$n2nLocaleId];
			}
		
			$n2nLocaleId = \Locale::lookup(array_keys($n2nLocales), $n2nLocaleId);
			if ($n2nLocaleId) return $n2nLocales[$n2nLocaleId];
		}
	
		return $n2nLocale;
	}
	
	private function detectSubsystem($hostName, Path $contextPath) {		
		foreach ($this->appConfig->web()->getSubsystems() as $subsystem) {
			if (null !== ($subsystemHostName = $subsystem->getHostName())) {
				if ($hostName != $subsystemHostName) continue;
			}
				
			if (null !== ($subsystemContextPath = $subsystem->getContextPath())) {
				if (!$contextPath->equals($subsystemContextPath)) continue;
			}
				
			return $subsystem;
		}
		
		return null;
			
	}
	
// 	private function initRegistry() {
// 		$this->batchJobRegistry = new BatchJobRegistry($this->n2nContext->getLookupManager(),
// 				$this->varStore->requestDirFsPath(VarStore::CATEGORY_TMP, self::NS, self::SRV_BATCH_DIR));
		
// 		foreach ($this->appConfig->general()->getBatchControllerClassNames() as $batchJobClassName) {
// 			$this->batchJobRegistry->registerBatchControllerClassName($batchJobClassName);
// 		}
		
// 		if (N2N::isHttpContextAvailable()) {
// 			$webConfig = $this->appConfig->web();
		
// 			$this->contextControllerRegistry = new ControllerRegistry();
// 			foreach ($webConfig->getFilterControllerDefs() as $contextControllerDef) {
// 				$this->contextControllerRegistry->registerFilterControllerDef($contextControllerDef);
// 			}
// 			foreach ($webConfig->getMainControllerDefs() as $contextControllerDef) {
// 				$this->contextControllerRegistry->registerMainControllerDef($contextControllerDef);
// 			}
// 			foreach ($this->appConfig->locale()->getN2nLocales() as $alias => $n2nLocale) {
// 				$this->contextControllerRegistry->setContextN2nLocale($alias, $n2nLocale);
// 			}
// 		}
// 	}
	/*
	 * STATIC
	 */
	private static $exceptionHandler;
	private static $n2n;
	private static $shutdownListeners = array();
	
	public static function setup(string $publicDirPath, string $varDirPath,
			N2nCache $n2nCache, ModuleFactory $moduleFactory = null) {
		mb_internal_encoding(self::CHARSET);
		// 		ini_set('default_charset', self::CHARSET);
		
		self::$exceptionHandler = new ExceptionHandler(N2N::isDevelopmentModeOn());
		register_shutdown_function(array('n2n\core\N2N', 'shutdown'));
		
		self::$n2n = new N2N(new FsPath(IoUtils::realpath($publicDirPath)),
				new FsPath(IoUtils::realpath($varDirPath)));
		
		if ($moduleFactory === null) {
			$moduleFactory = new EtcModuleFactory();
		}
		self::$n2n->initModules($moduleFactory);
		
		$n2nCache->varStoreInitilaized(self::$n2n->varStore);
		self::$n2n->initConfiguration($n2nCache);
		
		Sync::init(self::$n2n->varStore->requestDirFsPath(VarStore::CATEGORY_TMP, self::NS, self::SYNC_DIR));
	}
	
	/**
	 * @param string $publicDirPath
	 * @param string $varDirPath
	 * @param array $moduleDirPaths
	 */
	public static function initialize(string $publicDirPath, string $varDirPath, 
			N2nCache $n2nCache, ModuleFactory $moduleFactory = null) {
		self::setup($publicDirPath, $varDirPath, $n2nCache, $moduleFactory);
		
		self::$n2n->init($n2nCache);
		self::$initialized = true;
		
		// @todo move up so exception will be grouped earlier.
		self::initLogging(self::$n2n);
		
		self::$exceptionHandler->checkForStartupErrors();
	}
	/**
	 * @param \n2n\core\N2N $n2n
	 */
	private static function initLogging(N2N $n2n) {
		$errorConfig = $n2n->appConfig->error();
		
		if ($errorConfig->isLogSaveDetailInfoEnabled()) {
			self::$exceptionHandler->setLogDetailDirPath(
					(string) $n2n->varStore->requestDirFsPath(VarStore::CATEGORY_LOG, self::NS, self::LOG_EXCEPTION_DETAIL_DIR, true),
					$n2n->appConfig->io()->getPrivateFilePermission());
		}
		
		if ($errorConfig->isLogHandleStatusExceptionsEnabled()) {
			self::$exceptionHandler->setLogStatusExceptionsEnabled(true, 
					$errorConfig->getLogExcludedHttpStatus());
		} else {
			self::$exceptionHandler->setLogStatusExceptionsEnabled(false, array());
		}
		
		if ($errorConfig->isLogSendMailEnabled()) {
			self::$exceptionHandler->setLogMailBufferDirPath(
					$n2n->varStore->requestDirFsPath(VarStore::CATEGORY_TMP, self::NS, self::LOG_MAIL_BUFFER_DIR));
		}
		
		Logger::configure((string) $n2n->varStore->requestFileFsPath(
				VarStore::CATEGORY_ETC, null, null, self::LOG4PHP_CONFIG_FILE, true, false));
	
		$logLevel = $n2n->appConfig->general()->getApplicationLogLevel();
		
		if (isset($logLevel)) {
			Logger::getRootLogger()->setLevel($logLevel);
		}
		
		self::$exceptionHandler->setLogger(Logger::getLogger(get_class(self::$exceptionHandler)));
	}
	/**
	 * 
	 * @return bool
	 */
	public static function isInitialized() {
		return self::$initialized;
	}
	
	public static function finalize() {
		foreach(self::$shutdownListeners as $shutdownListener) {
			$shutdownListener->onShutdown();
		}
		
// 		self::shutdown();
	}
	/**
	 * 
	 */
	public static function shutdown() {
		self::$exceptionHandler->checkForFatalErrors();
		if (!self::$exceptionHandler->isStable()) return;
		
		try {
			if (!N2N::isInitialized()) return;
				
			if (N2N::isHttpContextAvailable()) {
				N2N::getCurrentResponse()->flush();
			}
		} catch (\Throwable $t) {
			self::$exceptionHandler->handleThrowable($t);
		}
	}
	/**
	 * 
	 * @param \n2n\core\ShutdownListener $shutdownListener
	 */
	public static function registerShutdownListener(ShutdownListener $shutdownListener) {
		self::$shutdownListeners[spl_object_hash($shutdownListener)] = $shutdownListener;
	}
	/**
	 * 
	 * @param \n2n\core\ShutdownListener $shutdownListener
	 */
	public static function unregisterShutdownListener(ShutdownListener $shutdownListener) {
		unset(self::$shutdownListeners[spl_object_hash($shutdownListener)]);
	}
	/**
	 * @return \n2n\core\N2N
	 * @throws \n2n\core\N2nHasNotYetBeenInitializedException
	 */
	protected static function _i() {
		if(self::$n2n === null) {
			throw new N2nHasNotYetBeenInitializedException('No N2N instance has been initialized for current thread.');
		}
		return self::$n2n;
	}
	/**
	 * @return bool
	 */
	public static function isDevelopmentModeOn() {
		return defined('N2N_STAGE') && N2N_STAGE == self::STAGE_DEVELOPMENT;
	}
	/**
	 * @return bool
	 */
	public static function isLiveStageOn() {
		return !defined('N2N_STAGE') || N2N_STAGE == self::STAGE_LIVE;
	}
	
	public static function getStage() {
		if (defined('N2N_STAGE')) {
			return N2N_STAGE;
		}
		
		return self::STAGE_LIVE;
	}
	/**
	 * 
	 * @return \n2n\core\err\ExceptionHandler
	 */
	public static function getExceptionHandler() {
		return self::$exceptionHandler;
	}
	/**
	 * 
	 * @return \n2n\core\config\AppConfig
	 */
	public static function getAppConfig() {
		return self::_i()->appConfig;
	}
	/**
	 * 
	 * @return string
	 */
	public static function getPublicDirPath() {
		return self::_i()->publicDirPath;
	}
	/**
	 * 
	 * @return \n2n\core\VarStore
	 */
	public static function getVarStore() {
		return self::_i()->varStore;	
	}
	/**
	 * 
	 * @return \n2n\l10n\N2nLocale[]
	 */
	public static function getN2nLocales() {
		return self::_i()->appConfig->web()->getAllN2nLocales();
	}
	/**
	 * 
	 * @param string $n2nLocaleId
	 * @return boolean
	 */
	public static function hasN2nLocale($n2nLocaleId) {
		return isset(self::_i()->n2nLocales[(string) $n2nLocaleId]);
	}
	/**
	 * 
	 * @param string $n2nLocaleId
	 * @throws N2nLocaleNotFoundException
	 * @return \n2n\l10n\N2nLocale
	 */
	public static function getN2nLocaleById($n2nLocaleId) {
		if (isset(self::_i()->n2nLocales[(string) $n2nLocaleId])) {
			return self::_i()->n2nLocales[(string) $n2nLocaleId];
		}
		
		throw new N2nLocaleNotFoundException('N2nLocale not found: ' . $n2nLocaleId);
	}
	/**
	 * 
	 * @return array
	 */
	public static function getN2nLocaleIds() {
		return array_keys(self::_i()->n2nLocales);
	}
	/**
	 * 
	 * @param \n2n\l10n\Language $language
	 * @return array<\n2n\l10n\N2nLocale>
	 */
	public static function getN2nLocalesByLanguage(Language $language) {
		return self::getN2nLocalesByLanguageId($language);
	}
	/**
	 * 
	 * @param string $languageShort
	 * @return array<\n2n\l10n\N2nLocale>
	 */
	public static function getN2nLocalesByLanguageId($languageShort) {
		$languageShort = (string) $languageShort;
		$n2nLocales = array();
		foreach (self::getN2nLocales() as $n2nLocale) {
			if ($n2nLocale->getLanguage()->getShort() == $languageShort) {
				$n2nLocales[] = $n2nLocale;
			}
		}
		return $n2nLocales;
	}
	/**
	 * 
	 * @param \n2n\l10n\Region $region
	 * @return array<\n2n\l10n\N2nLocale>
	 */
	public static function getN2nLocalesByRegion(Region $region) {
		return self::getN2nLocalesByLanguageId($region);
	}
	/**
	 * 
	 * @param string $regionShort
	 * @return array<\n2n\l10n\N2nLocale>
	 */
	public static function getN2nLocalesByRegionShort($regionShort) {
		$regionShort = (string) $regionShort;
		$n2nLocales = array();
		foreach (self::getN2nLocales() as $n2nLocale) {
			if ($n2nLocale->getRegion()->getShort() == $regionShort) {
				$n2nLocales[] = $n2nLocale;
			}
		}
		return $n2nLocales;
	}
	/**
	 * 
	 * @return array<\n2n\l10n\Language>
	 */
	public static function getLanguages() {
		return self::_i()->languages;
	}
	/**
	 * 
	 * @param string $languageShort
	 * @return boolean
	 */
	public static function hasLanguage($languageShort) {
		return isset(self::_i()->languages[(string) $languageShort]);
	}
	/**
	 * 
	 * @return \n2n\core\module\Module[]
	 */
	public static function getModules() {
		return self::_i()->modules;
	}
	
	public static function registerModule(Module $module) {
		self::_i()->modules[(string) $module] = $module;
		if (null !== ($appConfigSource = $module->getAppConfigSource())) {
			self::_i()->combinedConfigSource->putAdditional((string) $module, $appConfigSource);
		}
	}
	
	public static function unregisterModule($module) {
		$namespace = (string) $module;
		unset(self::_i()->modules[$namespace]);		
		self::_i()->combinedConfigSource->removeAdditionalByKey($namespace);
	}
	
	public static function containsModule($module) {
		return self::_i()->getN2nContext()->getModuleManager()->containsModuleNs($module);
	}
	
// 	public static function getModuleByClassName($className) {
// 		foreach (self::_i()->modules as $namespace => $module) {
// 			if (StringUtils::startsWith($namespace, $className)) {
// 				return $module;
// 			}
// 		}
		
// 		throw new ClassNotInAModuleNamespaceException(
// 				SysTextUtils::get('n2n_error_core_class_is_not_in_a_namespace_of_an_installed_module', 
// 						array('class' => $className)));	
// 	}
	
	public static function getN2nContext() {
		return self::_i()->n2nContext;
	}
	/**
	 * 
	 * @return boolean
	 */
	public static function isHttpContextAvailable() {
		return self::_i()->n2nContext->isHttpContextAvailable();
	}
	
	public static function getHttpContext(): HttpContext {
		return self::_i()->n2nContext->getHttpContext();
	}
	/**
	 * 
	 * @throws \n2n\web\http\HttpContextNotAvailableException
	 * @return \n2n\web\http\Request
	 */
	public static function getCurrentRequest() {
		return self::getHttpContext()->getRequest();
	}
	/**
	 * 
	 * @throws \n2n\web\http\HttpContextNotAvailableException
	 * @return \n2n\web\http\Response
	 */
	public static function getCurrentResponse() {
		return self::getHttpContext()->getResponse();
	}
	
	public static function createControllingPlan($subsystemName = null) {
		$request = self::_i()->n2nContext->getHttpContext()->getRequest();
		if ($subsystemName === null) {
			$subsystemName = $request->getSubsystemName();
		}
		
		return self::_i()->contextControllerRegistry->createControllingPlan(
				self::_i()->n2nContext, $request->getCmdPath(), $subsystemName);
	}
	
	public static function autoInvokeBatchJobs() {
		$n2nContext = self::_i()->n2nContext;
		$batchJobRegistry = $n2nContext->lookup(BatchJobRegistry::class);
		CastUtils::assertTrue($batchJobRegistry instanceof BatchJobRegistry);
		$batchJobRegistry->trigger();
		
	}
	
	public static function autoInvokeControllers() {
		$n2nContext = self::_i()->n2nContext;
		
		$request = $n2nContext->getHttpContext()->getRequest();
		$response = $n2nContext->getHttpContext()->getResponse();
		
		if ($request->getOrigMethodName() != Method::toString(Method::HEAD) 
				&& ($request->getOrigMethodName() != Method::toString($request->getMethod()))) {
			throw new MethodNotAllowedException(Method::HEAD|Method::GET|Method::POST|Method::PUT|Method::OPTIONS|Method::PATCH|Method::DELETE|Method::TRACE);
		}
		
// 		if ($response->sendCachedPayload()) {
// 			return;
// 		}
		
		$controllerRegistry = $n2nContext->lookup(ControllerRegistry::class);
		$controllerRegistry->createControllingPlan($request->getCmdPath(), $request->getSubsystemName())->execute(); 
	}
	
	public static function invokerControllers(string $subsystemName = null, Path $cmdPath = null) {
		$n2nContext = self::_i()->n2nContext;
		$httpContext = $n2nContext->getHttpContext();
		$request = $httpContext->getRequest();
		
		$subsystem = null;
		if ($subsystemName !== null) {
			$subsystem = $httpContext->getAvailableSubsystemByName($subsystemName);
		}
		$request->setSubsystem($subsystem);
				
		$controllerRegistry = $n2nContext->lookup(ControllerRegistry::class);
		
		if ($cmdPath === null) {
			$cmdPath = $request->getCmdPath();
		}
		$controllerRegistry->createControllingPlan($request->getCmdPath(), $request->getSubsystemName())->execute();
	}
	/**
	 * @return \n2n\context\LookupManager
	 */
	public static function getLookupManager() {
		return self::_i()->n2nContext->getLookupManager();
	}
	/**
	 * @return \n2n\core\container\PdoPool
	 */
	public static function getPdoPool() {
		return self::_i()->n2nContext->lookup(PdoPool::class);
	}
	/**
	 * 
	 * @return \n2n\l10n\MessageContainer
	 */
	public static function getMessageContainer() {
		return self::_i()->n2nContext->lookup(MessageContainer::class);
	}
	/**
	 *
	 * @return \n2n\web\dispatch\DispatchContext
	 */
	public static function getDispatchContext() {
		return self::_i()->n2nContext->lookup(DispatchContext::class);
	}
	/**
	 * 
	 * @return \n2n\core\container\TransactionManager
	 */
	public static function getTransactionManager() {
		return self::_i()->n2nContext->getTransactionManager();
	}
	
// 	public static function setTransactionContext(TransactionManager $transactionManager) {
// 		self::_i()->transactionalContext = $transactionManager;
// 	}


	/**
	 *
	 * @return array
	 */
	public static function getLastUserTracePoint($minBack = 0, $scriptPath = null/*, $outOfMdule = null*/) {
		$back = (int) $minBack;
		foreach(debug_backtrace(false) as $key => $tracePoint) {
			if (!$key || !isset($tracePoint['file'])) continue;
	
			if ($back-- > 0) continue;
				
			if (isset($scriptPath)) {
				if ($tracePoint['file'] == $scriptPath) {
					return $tracePoint;
				}
				continue;
			}
				
			// 			if (isset($outOfMdule)) {
			// 				if (TypeLoader::isFilePartOfNamespace($tracePoint['file'], (string) $outOfMdule)) {
			// 					continue;
			// 				} else {
			// 					return $tracePoint;
			// 				}
			// 			}
				
			//if (substr($tracePoint['file'], 0, mb_strlen($modulePath)) == $modulePath) {
			return $tracePoint;
			//}
		}
	
		return null;
	}
}

class N2nHasNotYetBeenInitializedException extends N2nRuntimeException {
	
}

class N2nLocaleNotFoundException extends N2nRuntimeException {
	
}
