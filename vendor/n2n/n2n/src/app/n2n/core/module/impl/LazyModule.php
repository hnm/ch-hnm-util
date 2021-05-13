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
namespace n2n\core\module\impl;

use n2n\reflection\ReflectionUtils;
use n2n\core\module\ModuleInfo;
use n2n\config\source\ConfigSource;
use n2n\core\config\build\GroupedConfigSourceReader;
use n2n\config\InvalidConfigurationException;
use n2n\core\TypeNotFoundException;
use n2n\core\config\build\CombinedConfigSource;
use n2n\core\container\N2nContext;
use n2n\config\source\WritableConfigSource;
use n2n\util\ex\IllegalStateException;
use n2n\core\module\Module;
use n2n\core\module\ConfigDescriber;
use n2n\util\type\attrs\Attributes;

class LazyModule implements Module {	
	const GROUP_INFO = 'info';
	const GROUP_META = 'meta';
	
	const NAME_KEY = 'name';
	const AUTHOR_KEY = 'author';
	const WEBSITE_KEY = 'website';
	const LICENSE_KEY = 'license';
	
	const VERSION_KEY = 'version';
	const DEPENDENCIES_KEY = 'dependencies';
	const CONFIG_DESCRIBER_KEY = 'config_describer';
	
	private $namespace;
	private $appConfigSource;
	private $moduleConfigSource;
	private $moduleInfo;
	/**
	 * 
	 * @param string $namespace
	 * @param string $path
	 */
	public function __construct($namespace, ConfigSource $appConfigSource = null, 
			WritableConfigSource $moduleConfigSource = null) {
		$this->namespace = $namespace;
		$this->appConfigSource = $appConfigSource;
		$this->moduleConfigSource = $moduleConfigSource;
	}
	/**
	 * 
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}
	/**
	 * @return ConfigSource
	 */
	public function getAppConfigSource() {
		return $this->appConfigSource;
	}
	
	public function getModuleInfo(): ModuleInfo {
		if ($this->moduleInfo !== null) {
			return $this->moduleInfo;
		}
		
		$this->moduleInfo = new ModuleInfo();
		
		if ($this->moduleConfigSource === null) {
			return $this->moduleInfo;
		}
		
		$reader = new GroupedConfigSourceReader(new CombinedConfigSource($this->moduleConfigSource));
		$reader->initialize(null, false, array(self::GROUP_INFO, self::GROUP_META));
		
		$groupReader = $reader->getGroupReaderByGroupName(self::GROUP_INFO, false);
		$this->moduleInfo->setName($groupReader->getString(self::NAME_KEY, false));
		$this->moduleInfo->setAuthor($groupReader->getString(self::AUTHOR_KEY, false));
		$this->moduleInfo->setWebsite($groupReader->getString(self::WEBSITE_KEY, false));
		$this->moduleInfo->setLicense($groupReader->getString(self::LICENSE_KEY, false));
		
		$groupReader = $reader->getGroupReaderByGroupName(self::GROUP_META);
		$this->moduleInfo->setVersion($groupReader->getString(self::VERSION_KEY, false));
		$this->moduleInfo->setDependencies($groupReader->getScalarArray(self::DEPENDENCIES_KEY, false, array()));
		$this->moduleInfo->setConfigDescriberClassName($groupReader->getString(self::CONFIG_DESCRIBER_KEY, false));
			
		return $this->moduleInfo;
	}
	
	public function isModuleInfoEditable(): bool {
		return true;
	}
	
	public function saveModuleInfo(ModuleInfo $moduleInfo) {
		if (!$this->isModuleInfoEditable()) {
			throw new IllegalStateException('ModuleInfo not editable.');
		}
		
		$infoAttributes = new Attributes();
		$infoAttributes->appendAll(array(
				self::NAME_KEY => $moduleInfo->getName(),
				self::AUTHOR_KEY => $moduleInfo->getAuthor(),
				self::WEBSITE_KEY => $moduleInfo->getWebsite(),
				self::LICENSE_KEY => $moduleInfo->getLicense()), true);
		
		$metaAttributes = new Attributes();
		$metaAttributes->appendAll(array(
				self::VERSION_KEY => $moduleInfo->getVersion(),
				self::DEPENDENCIES_KEY => $moduleInfo->getDependencies(),
				self::CONFIG_DESCRIBER_KEY => $moduleInfo->getConfigDescriberClassName()), true);
		
		 $this->moduleConfigSource->writeArray(array(self::GROUP_INFO => $infoAttributes->toArray(),
		 		self::GROUP_META => $metaAttributes->toArray()));
	}
	
	public function hasConfigDescriber(): bool {
		return null !== $this->getModuleInfo()->getConfigDescriberClassName();
	}
	
	public function createConfigDescriber(N2nContext $n2nContext): ConfigDescriber {		
		$configDescriberClassName = $this->getModuleInfo()->getConfigDescriberClassName();
		if ($configDescriberClassName === null) {
			throw new IllegalStateException('No ConfigDescriber available.');
		}
		
		$describerClass = null;
		try {
			$describerClass = ReflectionUtils::createReflectionClass($configDescriberClassName);
		} catch (TypeNotFoundException $e) {
			throw $this->createInvalidConfigDescriberException($e);
		}
		
		if (!$describerClass->implementsInterface(ConfigDescriber::class)) {
			throw $this->createInvalidConfigDescriberException(
					new InvalidConfigurationException('ConfigDescriber must implement interface ' 
							. ConfigDescriber::class . ': ' . $describerClass->getName()));
		}
		
		return $describerClass->newInstance($this, $n2nContext);
	}
	
	private function createInvalidConfigDescriberException(\Exception $previous) {
		throw new InvalidConfigurationException('Invalid ConfigDescriber class defined for module '
				. $this->namespace, 0, $previous);
	}
	
	/**
	 * 
	 * @return string
	 */
	public function __toString(): string {
		return $this->namespace;
	}
	/**
	 * 
	 * @param mixed $o
	 * @return boolean
	 */
	public function equals($o) {
		return $o instanceof Module && $o->getNamespace() == $this->getNamespace();
	} 
}
