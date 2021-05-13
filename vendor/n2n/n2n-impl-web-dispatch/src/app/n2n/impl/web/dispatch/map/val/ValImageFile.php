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

use n2n\io\managed\File;
use n2n\web\dispatch\map\val\SimplePropertyValidator;
use n2n\web\dispatch\map\val\ValidationUtils;
use n2n\io\img\impl\ImageSourceFactory;

class ValImageFile extends SimplePropertyValidator {
	const DEFAULT_NOT_SUPPORTED_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValImageFile.notSupported';
	const DEFAULT_CORRUPTED_ERROR_TEXT_CODE = 'n2n.dispatch.val.ValImageFile.corrupted';
	
	private $strict;
	private $notSupportedErrorMessage;
	private $corruptedErrorMessage;
	
	public function __construct($strict, $notSupportedErrorMessage = null, $corruptedErrorMessage = null) {
		$this->strict = (boolean)$strict;
		$this->notSupportedErrorMessage = ValidationUtils::createMessage($notSupportedErrorMessage);
		$this->corruptedErrorMessage = ValidationUtils::createMessage($corruptedErrorMessage);

		$this->restrictType(array('n2n\impl\web\dispatch\property\FileProperty'));
	}
	
	protected function validateValue($mapValue) {
		if (!($mapValue instanceof File)) return;
		
		if (!in_array(mb_strtolower($mapValue->getOriginalExtension()), ImageSourceFactory::getSupportedExtensions())) {
			if ($this->strict) {
				$this->failed($this->notSupportedErrorMessage, self::DEFAULT_NOT_SUPPORTED_ERROR_TEXT_CODE, null, 'n2n\impl\web\dispatch');
			}
			
			return;
		}

		if (!$mapValue->getFileSource()->isImage()) {
			$this->failed($this->corruptedErrorMessage, self::DEFAULT_CORRUPTED_ERROR_TEXT_CODE, null, 'n2n\impl\web\dispatch');
		}
	}
}
