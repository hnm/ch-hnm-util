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
use n2n\core\container\N2nContext;
use n2n\web\dispatch\mag\MagDispatchable;

interface ConfigDescriber {
	/**
	 * @param Module $module
	 */
	public function __construct(Module $module, N2nContext $n2nContext);
	
	/**
	 * @param N2nContext $n2nContext
	 * @return \n2n\web\dispatch\mag\MagDispatchable
	 */
	public function createMagDispatchable(): MagDispatchable;
	
	/**
	 * @param Attributes $configAttributes
	 */
	public function saveMagDispatchable(MagDispatchable $magDispatchable);
	
	/**
	 * @return mixed
	 */
	public function buildCustomConfig();
	
// 	/**
// 	 * If you return a class name of the custom config object (created in {@link ConfigDescriber::buildCustomConfig()}
// 	 * that object can be injected in magic methods like <code>_init()</code>.
// 	 * @return string 
// 	 */
// 	public function getMagicCustomConfigClassName();
}
