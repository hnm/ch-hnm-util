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
use n2n\web\dispatch\map\val\ValidationUtils;
use n2n\util\uri\Url;

class ValUrl extends SimplePropertyValidator {
	const DEFAULT_ERROR_TEXT_CODE_INVALID = 'n2n.dispatch.val.ValUrl.invalid';
	const DEFAULT_ERROR_TEXT_CODE_INVALID_SCHEME = 'n2n.dispatch.val.ValUrl.invalidScheme';

	private $allowedSchemes;
	private $errorMessage;
	private $relativeAllowed;
	
	public function __construct(array $allowedSchemes = null, $errorMessage = null, bool $relativeAllowed = false) {
		$this->allowedSchemes = $allowedSchemes;
		$this->errorMessage = ValidationUtils::createMessage($errorMessage);
		$this->relativeAllowed = $relativeAllowed;
	}
	
	protected function validateValue($value) {
		if ($value === null) return;
		
		if (!self::isUrl($value, !$this->relativeAllowed)) {
			$this->failed($this->errorMessage, self::DEFAULT_ERROR_TEXT_CODE_INVALID, array(), 'n2n\impl\web\dispatch');
			return;
		}
		
		if ($this->allowedSchemes === null) return;
		
		$matches = null;
		if (preg_match('#^([^:]+):#', $value, $matches)
				&& in_array($matches[1], $this->allowedSchemes)) {
			return;		
		}
		
		$this->failed($this->errorMessage, self::DEFAULT_ERROR_TEXT_CODE_INVALID_SCHEME, 
				array('allowed_schemes' => implode(', ', $this->allowedSchemes)), 'n2n\impl\web\dispatch');
	}
	/**
	 * checks a string, if it is a valid url address
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function isUrl($url, bool $schemeRequired = true) {
		try {
			$url = Url::create($url)->toIdnaAsciiString();
		} catch (\InvalidArgumentException $e) {
			return false;
		}
		
		if ($schemeRequired) {
			// quick deprecated fix and makes schemeRequired redundant for now
			if (false !== filter_var($url, FILTER_VALIDATE_URL/*, FILTER_FLAG_SCHEME_REQUIRED*/)) {
				return true;
			}
		} else {
			if (false !== filter_var($url, FILTER_VALIDATE_URL)) {
				return true;
			}
		}
		
		return false;
	}
}
