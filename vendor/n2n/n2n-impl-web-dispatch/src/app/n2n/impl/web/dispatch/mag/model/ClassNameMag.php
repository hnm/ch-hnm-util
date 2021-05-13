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
namespace n2n\impl\web\dispatch\mag\model;

use n2n\impl\web\dispatch\map\val\ValReflectionClass;
use n2n\web\dispatch\map\bind\BindingDefinition;

/**
 * It is !!!VERY DANGEROUS!!! to use this Mag 
 * Please use this only for ModuleConfiguration!!!
 * 
 * @author thomas
 *
 */
class ClassNameMag extends StringMag {
	private $isAClass;
		
	public function __construct($label, \ReflectionClass $isAClass, string $value = null, 
			$mandatory = false, $maxlength = null, array $attrs = null,  array $inputAttrs = null) {
		parent::__construct($label, $value, $mandatory, $maxlength, false, $attrs, $inputAttrs);
		
		$this->isAClass = $isAClass;
	}
	
	public function setupBindingDefinition(BindingDefinition $bd) {
		parent::setupBindingDefinition($bd);

		$bd->val($this->propertyName, new ValReflectionClass($this->isAClass));
	}
}
