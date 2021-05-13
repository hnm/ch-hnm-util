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

use n2n\web\dispatch\map\val\SimplePropertyValidator;
use n2n\util\type\ArgUtils;
use n2n\web\dispatch\map\val\ValidationUtils;
use n2n\util\type\CastUtils;
use n2n\impl\web\dispatch\property\DateTimeProperty;

class ValDateTime extends SimplePropertyValidator {
	const DEFAULT_MIN_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValDateTime.min';
	const DEFAULT_MAX_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValDateTime.max';
	
	private $min;
	private $minErrorMessage;
	private $max;
	private $maxErrorMessage;
	
	public function __construct(\DateTime $min = null, $minErrorMessage = null, 
			\DateTime $max = null, $maxErrorMessage = null) {
		$this->min = $min;
		$this->minErrorMessage = ValidationUtils::createMessage($minErrorMessage);
		$this->max = $max;
		$this->maxErrorMessage = ValidationUtils::createMessage($maxErrorMessage);
		
		$this->restrictType(array('n2n\impl\web\dispatch\property\DateTimeProperty'));
	}
	
	public static function minMax(\DateTime $min = null, \DateTime $max = null) {
		return new ValDateTime($min, $max);
	}

	protected function validateValue($value) {
		if ($value === null) return;
		
		ArgUtils::assertTrue($value instanceof \DateTime);
		
		if ($this->min !== null && $value < $this->min) {
			$managedProperty = $this->getManagedProperty();
			CastUtils::assertTrue($managedProperty instanceof DateTimeProperty);
			
			$this->failed($this->minErrorMessage, self::DEFAULT_MIN_ERROR_TEXT_CODE,
					array('date' => $managedProperty->formatDateTime($this->min, $this->getN2nContext()->getN2nLocale())), 
					'n2n\impl\web\dispatch');
			return;
		}
	
		if ($this->max !== null && $value > $this->max) {
			$this->failed($this->maxErrorMessage, self::DEFAULT_MAX_ERROR_TEXT_CODE, 
					array('date' => $managedProperty->formatDateTime($this->max, $this->getN2nContext()->getN2nLocale())), 
					'n2n\impl\web\dispatch');
			return;
		}
	}
}
