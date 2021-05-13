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

use n2n\core\container\N2nContext;

interface Module {	
	/**
	 * 
	 * @return string
	 */
	public function getNamespace();
	
	/**
	 * @return \n2n\config\source\ConfigSource
	 */
	public function getAppConfigSource();
	
	/**
	 * @return \n2n\core\module\ModuleInfo
	 * @throws \n2n\config\InvalidConfigurationException
	 */
	public function getModuleInfo(): ModuleInfo;
	
	/**
	 * 
	 */
	public function isModuleInfoEditable(): bool;
	
	/**
	 * @param ModuleInfo $moduleInfo
	 */
	public function saveModuleInfo(ModuleInfo $moduleInfo);

	/**
	 * @return bool 
	 */
	public function hasConfigDescriber(): bool;
	
	/**
	 * @param N2nContext $n2nContext
	 * @return \n2n\core\module\ConfigDescriber
	 * @throws \n2n\util\ex\IllegalStateException if {@Module::hasConfigDescriber()} returns false.
	 * @throws \n2n\config\InvalidConfigurationException
	 */
	public function createConfigDescriber(N2nContext $n2nContext): ConfigDescriber;
	
	/**
	 * Must return namespace of module.
	 * @return string 
	 */
	public function __toString();
	
	/**
	 * @param mixed $o
	 * @return boolean
	 */
	public function equals($o);
}
