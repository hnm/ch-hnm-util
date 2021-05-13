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
namespace n2n\core\module;

use n2n\util\StringUtils;

class ModuleManager {
	private $modules = array();
	private $moduleConfigs = array();
	
	/**
	 * @param string $namespace
	 */
	public function containsModuleNs(string $namespace): bool {
		return isset($this->modules[$namespace]);
	}
	
	/**
	 * @return Module []
	 */
	public function getModules(): array {
		return $this->modules;
	}
	
	/**
	 * @param string $namespace
	 * @throws UnknownModuleException
	 * @return Module
	 */
	public function getModuleByNs(string $namespace): Module {
		if (isset($this->modules[$namespace])) {
			return $this->modules[$namespace];
		}
		
		throw new UnknownModuleException('No module found with namespace: ' . $namespace);
	}
	
	/**
	 * Returns the module which the passed type is part of.
	 * @param string $typeName
	 * @param bool $required
	 * @throws UnknownModuleException if $required is true and type is not part of any module.
	 * @return Module or null if $required is false and type is not part of any module.
	 */
	public function getModuleOfTypeName(string $typeName, bool $required = true) {
		$typeName = trim($typeName, '\\');
		
		$module = null;
		$nsLength = 0;
		foreach ($this->modules as $curNamespace => $curModule) {
			if (!StringUtils::startsWith($curNamespace . '\\', $typeName)) continue;
			
			$curNsLength = strlen($curNamespace);
			if ($curNsLength > $nsLength) {
				$module = $curModule;
				$nsLength = $curNsLength;
			}
		}
		
		if (!$required || $module !== null) return $module;
				
		throw new UnknownModuleException('Type is not part of any installed modules: ' . $typeName);
	}
	
	public function registerModule(Module $module) {
		$this->modules[$module->getNamespace()] = $module;
	}
	
	public function unregisterModuleByNamespace(string $namespace) {
		unset($this->modules[$namespace]);
	}
}
