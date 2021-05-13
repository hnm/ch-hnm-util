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

use n2n\core\VarStore;
use n2n\config\source\ConfigSource;
use n2n\core\module\ModuleFactory;
use n2n\config\source\impl\IniFileConfigSource;
use n2n\util\ex\IllegalStateException;
use n2n\util\type\ArgUtils;
use n2n\io\fs\FsPath;

class EtcModuleFactory implements ModuleFactory {
	const DEFAULT_APP_INI_FILE = 'app.ini';
	const DEFAULT_MODULE_INI_FILE = 'module.ini';
	
	private $appIniFileName;
	private $moduleIniFileName;
	
	private $mainFsPath = null;
	private $additionalEtcFsPaths = array();
	
	private $mainAppConfigSource;
	private $modules;
	
	public function __construct(string $appIniFileName = self::DEFAULT_APP_INI_FILE, 
			string $moduleIniFileName = self::DEFAULT_MODULE_INI_FILE) {
		$this->appIniFileName = $appIniFileName;
		$this->moduleIniFileName = $moduleIniFileName;
	}
	
	public function setMainEtcFsPath(?FsPath $fsPath) {
		$this->mainFsPath = $fsPath;
	}
	
	public function setAdditionionalEtcFsPaths(array $fsPaths) {
		ArgUtils::valArray($fsPaths, FsPath::class);
		$this->additionalEtcFsPaths = $fsPaths;
	}
	
	public function init(VarStore $varStore) {
		$this->mainAppConfigSource = new IniFileConfigSource($this->mainFsPath 
				?? $varStore->requestFileFsPath(VarStore::CATEGORY_ETC, null, null, $this->appIniFileName));
		
		$this->modules = array();
		
		$etcFsPaths = [$varStore->requestDirFsPath(VarStore::CATEGORY_ETC, null, null)];
		if (!empty($this->additionalEtcFsPaths)) {
			array_push($etcFsPaths, ...$this->additionalEtcFsPaths);
		}
		
		foreach ($etcFsPaths as $key =>  $etcFsPath) {
			foreach ($etcFsPath->getChildDirectories() as $confDirPath) {
				$moduleNamespace = VarStore::dirNameToNamespace($confDirPath->getName());
					
				$appConfigSource = null;
				if (is_file($appConfigFilePath = $confDirPath . DIRECTORY_SEPARATOR . $this->appIniFileName)) {
					$appConfigSource = new IniFileConfigSource($appConfigFilePath);
				}
							
				$moduleConfigSource = null;
				if (is_file($moduleConfigFilePath = $confDirPath . DIRECTORY_SEPARATOR . $this->moduleIniFileName)) {
					$moduleConfigSource = new IniFileConfigSource($moduleConfigFilePath);
				}
					
				$this->modules[$moduleNamespace] = new LazyModule($moduleNamespace, $appConfigSource,
						$moduleConfigSource);
				
				if ($key != 0) {
					$varStore->overwritePath(VarStore::CATEGORY_ETC, $moduleNamespace, $confDirPath);
				}
			}
		}
	}
	

	public function getMainAppConfigSource(): ConfigSource {
		if ($this->mainAppConfigSource !== null) {
			return $this->mainAppConfigSource;
		}
		
		throw new IllegalStateException();
	}

	public function getModules(): array {
		if ($this->modules !== null) {
			return $this->modules;
		}
		
		throw new IllegalStateException();
	}
	
}
