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

use n2n\util\type\attrs\Attributes;
use n2n\core\VarStore;
use n2n\config\source\impl\JsonFileConfigSource;
use n2n\core\container\N2nContext;

abstract class ConfigDescriberAdapter implements ConfigDescriber {
	const CONF_FILE = 'config.json';
	
	protected $module;
	protected $n2nContext;
	private $customConfig;
	
	public function __construct(Module $module, N2nContext $n2nContext) {
		$this->module = $module;
		$this->n2nContext = $n2nContext;
	}
	/**
	 * @return \n2n\core\module\Module
	 */
	public function getModule() {
		return $this->module;
	}
	
	protected function readCustomAttributes(): Attributes {
		$configSource = new JsonFileConfigSource($this->n2nContext->getVarStore()->requestFileFsPath(
				VarStore::CATEGORY_SRV, $this->module, null, self::CONF_FILE, true, true));
		return new Attributes($configSource->readArray());
	}
	
	protected function writeCustomAttributes(Attributes $attributes) {
		$configSource = new JsonFileConfigSource($this->n2nContext->getVarStore()->requestFileFsPath(
				VarStore::CATEGORY_SRV, $this->module, null, self::CONF_FILE, true, true));
		$configSource->writeArray($attributes->toArray());
	}
}
