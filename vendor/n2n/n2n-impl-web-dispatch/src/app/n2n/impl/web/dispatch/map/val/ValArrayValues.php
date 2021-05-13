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
namespace n2n\impl\web\dispatch\map\val;

use n2n\web\dispatch\map\val\SinglePropertyValidator;
use n2n\web\dispatch\map\val\ValidationUtils;
use n2n\web\dispatch\map\PropertyPathPart;
use n2n\web\dispatch\map\MappingResult;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\util\type\ArgUtils;

class ValArrayValues extends SinglePropertyValidator {
	const DEFAULT_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValArrayKeys'; 
	
	private $allowedValues;
	private $errorMessage;
	
	public function __construct(array $allowedValues, $errorMessage = null) {		
		$this->allowedValues = $allowedValues;
		$this->errorMessage = ValidationUtils::createMessage($errorMessage);
		
		$this->restrictType(null, true);
	}
	
	protected function validateProperty($value, ManagedProperty $managedProperty, 
			MappingResult $mappingResult) {
		ArgUtils::valArrayLike($value);
		
		foreach ($value as $aKey => $aValue) {
			if (in_array($aValue, $this->allowedValues)) continue;

			ValidationUtils::registerErrorMessage($mappingResult, 
					new PropertyPathPart($managedProperty->getName(), true, $aKey),
					self::DEFAULT_ERROR_TEXT_CODE, array(), 'n2n\impl\web\dispatch', $this->errorMessage);
		}
	}
}
