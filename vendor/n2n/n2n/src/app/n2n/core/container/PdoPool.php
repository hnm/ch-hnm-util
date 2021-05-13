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

use n2n\persistence\PersistenceUnitConfig;
use n2n\persistence\orm\EntityManager;
use n2n\persistence\orm\LazyEntityManagerFactory;
use n2n\util\type\ArgUtils;
use n2n\persistence\orm\model\EntityModelManager;
use n2n\persistence\orm\proxy\EntityProxyManager;
use n2n\util\magic\MagicContext;
use n2n\context\ThreadScoped;
use n2n\core\config\DbConfig;
use n2n\persistence\orm\model\EntityModelFactory;
use n2n\core\config\OrmConfig;
use n2n\persistence\Pdo;
use n2n\persistence\UnknownPersistenceUnitException;
use n2n\persistence\PdoPoolListener;

class PdoPool implements ThreadScoped {
	const DEFAULT_DS_NAME = 'default';
	
	private $persistenceUnitConfigs = array();
	private $entityModelManager;
	private $entityProxyManager;
	private $transactionManager;
	private $magicContext;
	private $dbhs = array();
	private $entityManagerFactories = array();
	private $dbhPoolListeners = array();
	
	
	private function _init(DbConfig $dbConfig, OrmConfig $ormConfig, N2nContext $n2nContext) {
		foreach ($dbConfig->getPersistenceUnitConfigs() as $persistenceUnitConfig) {
			ArgUtils::assertTrue($persistenceUnitConfig instanceof PersistenceUnitConfig);
			$this->persistenceUnitConfigs[$persistenceUnitConfig->getName()] = $persistenceUnitConfig;
		}
		
		$this->entityModelManager = new EntityModelManager($ormConfig->getEntityClassNames(), 
				new EntityModelFactory($ormConfig->getEntityPropertyProviderClassNames(),
						$ormConfig->getNamingStrategyClassName()));
		$this->entityProxyManager = EntityProxyManager::getInstance();
		$this->transactionManager = $n2nContext->getTransactionManager();
		$this->magicContext = $n2nContext;
	}
	
	/**
	 * @return TransactionManager
	 */
	public function getTransactionManager() {
		return $this->transactionManager;
	}
	/**
	 * @param MagicContext $magicContext
	 */
	public function setMagicContext(MagicContext $magicContext = null) {
		$this->magicContext = $magicContext;
	}
	/**
	 * @return MagicContext
	 */
	public function getMagicContext() {
		return $this->magicContext;
	}
	/**
	 * @return string
	 */
	public function getPersistenceUnitNames() {
		return array_keys($this->persistenceUnitConfigs);
	}
	/**
	 * @param string $persistenceUnitName
	 * @return \n2n\persistence\Pdo
	 */
	public function getPdo(string $persistenceUnitName = null) {
		if ($persistenceUnitName === null) {
			$persistenceUnitName = self::DEFAULT_DS_NAME;
		}
		
		if (!isset($this->persistenceUnitConfigs[$persistenceUnitName])) {
			throw new UnknownPersistenceUnitException('Unknown persitence unit: ' . $persistenceUnitName);
		}
		
		if (!isset($this->dbhs[$persistenceUnitName])) {
			$this->dbhs[$persistenceUnitName] = $this->createPdo(
					$this->persistenceUnitConfigs[$persistenceUnitName]);
		}
		
		return $this->dbhs[$persistenceUnitName];
	}
	
	/**
	 * @return Pdo[]
	 */
	function getInitializedPdos() {
		return $this->dbhs;
	}
	
	/**
	 * @param string $persistenceUnitName
	 * @param Pdo $pdo
	 * @throws \InvalidArgumentException
	 */
	function setPdo(string $persistenceUnitName, Pdo $pdo) {
		if ($persistenceUnitName === null) {
			$persistenceUnitName = self::DEFAULT_DS_NAME;
		}
		
		if (isset($this->dbhs[$persistenceUnitName])) {
			throw new \InvalidArgumentException('Pdo for persistence unit already initialized: ' . $persistenceUnitName);
		}
		
		$this->dbhs[$persistenceUnitName] = $pdo;
	}
	
	
	/**
	 * @param PersistenceUnitConfig $persistenceUnitConfig
	 * @return Pdo
	 */
	public function createPdo(PersistenceUnitConfig $persistenceUnitConfig) {
		return new Pdo($persistenceUnitConfig, $this->transactionManager);
	}
	
	/**
	 *
	 * @param string $persistenceUnitName
	 * @return \n2n\persistence\orm\EntityManagerFactory
	 */
	public function getEntityManagerFactory($persistenceUnitName = null) {
		if ($persistenceUnitName === null) {
			$persistenceUnitName = self::DEFAULT_DS_NAME;
		}
		
		if (!isset($this->entityManagerFactories[$persistenceUnitName])) {
			$this->entityManagerFactories[$persistenceUnitName] 
					= new LazyEntityManagerFactory($persistenceUnitName, $this);
		}
	
		return $this->entityManagerFactories[$persistenceUnitName];
	}
	/**
	 * @param Pdo $dbh
	 * @return EntityManager
	 */
	private function createEntityManagerFactory($persistenceUnitName = null) {
		return new LazyEntityManagerFactory($persistenceUnitName, $this);
	}
	/**
	 * @return EntityModelManager
	 */
	public function getEntityModelManager() {
		return $this->entityModelManager;
	}
	/**
	 * @return EntityProxyManager 
	 */
	public function getEntityProxyManager() {
		return $this->entityProxyManager;
	}
	
	public function registerListener(PdoPoolListener $dbhPoolListener) {
		$this->dbhPoolListeners[spl_object_hash($dbhPoolListener)] = $dbhPoolListener;
	}
	
	public function unregisterListener(PdoPoolListener $dbhPoolListener) {
		unset($this->dbhPoolListeners[spl_object_hash($dbhPoolListener)]);
	}
}
