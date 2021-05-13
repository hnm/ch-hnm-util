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
namespace n2n\impl\web\ui\view\html;

use n2n\web\ui\UiComponent;
use n2n\web\ui\UiException;
use n2n\util\HashUtils;
use n2n\web\ui\BuildContext;
use n2n\web\ui\SimpleBuildContext;
use n2n\util\StringUtils;
use n2n\util\type\TypeUtils;

class HtmlUtils {
	/**
	 * @param array $customAttrs
	 * @param array $reservedAttrNames
	 * @throws AttributeNameIsReservedException
	 */
	public static function validateCustomAttrs(array $customAttrs, array $reservedAttrNames) {
		foreach ($customAttrs as $name => $value) {
			if (in_array($name, $reservedAttrNames)) {
				throw new AttributeNameIsReservedException('Attribute is reserved: ' . $name 
						. ' All reserved attributes: ' . implode(', ', $reservedAttrNames));
			}
		}
	}
	
	/**
	 * @param array $attrs
	 * @param array $customAttrs
	 * @param bool $overwrite
	 * @throws AttributeNameIsReservedException
	 * @return array
	 */
	public static function mergeAttrs(array $attrs, array $customAttrs = null, bool $overwrite = false) {
		if ($customAttrs === null) return $attrs;
		
		foreach ($customAttrs as $name => $value) {
			if (is_numeric($name)) {
				if (in_array($value, $attrs)) continue;
			} else if (isset($attrs[$name])) {
				if ($name == 'class') {
					$attrs['class'] .= ' ' . $value;
					continue;
				} else if (!$overwrite) {
					throw new AttributeNameIsReservedException('Html attribute \'' . $name . '\' is reserved.'
							. ' Reserved attributes: ' . implode(', ', array_keys($attrs)));
				}
			}
			
			$attrs[$name] = $value;
		}
		
		return $attrs;
	}
	
	/**
	 * @param string $str
	 * @return string
	 */
	public static function hsc(string $str) {
		return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE);
		
		// htmlentities returnes empty string  on some characters, but seams to be fixed now
		// return htmlentities($str, ENT_QUOTES | ENT_HTML401 | ENT_SUBSTITUTE, N2N::CHARSET_UPPER);
	}
	
	/**
	 * @param mixed $contents
	 * @param BuildContext $buildContext
	 * @return string
	 */
	public static function contentsToHtml($contents, BuildContext $buildContext) {
		if ($contents instanceof UiComponent) {
			return $contents->build($buildContext);
		}
		
		return self::hsc(StringUtils::strOf($contents, true));
	}
	
	/**
	 * @param mixed $contents
	 * @param \Closure $pcf
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public static function escape($contents, \Closure $pcf = null) {
		$html = null;
		if ($contents instanceof UiComponent) {
			$html = self::hsc($contents->build(new SimpleBuildContext()));
		} else {
			try {
				$html = self::hsc(StringUtils::strOf($contents));
			} catch (\InvalidArgumentException $e) {
				throw new \InvalidArgumentException('Could not convert type to escaped string: '
						. TypeUtils::getTypeInfo($contents));
			}	
		}
		
		if ($pcf !== null) {
			$html = $pcf($html);
		}
		
		return $html;
	}
		
	public static function buildUniqueId($prefix = null) {
		return $prefix . HashUtils::base36Uniqid();
	}

	public static function encode($str) {
		$strHtml = (string) $str;
		
		for($i = "a"; $i <= "z"; $i++) {
			$strHtml = str_replace($i, "&#" . ord($i) . ";", $strHtml);
		}
		
		for($i = "A"; $i <= "Z"; $i++) {
			$strHtml = str_replace($i, "&#" . ord($i) . ";", $strHtml);
		}
		
		$strHtml = str_replace(".", "&#46;", $strHtml);
		$strHtml = str_replace("@", "&#64;", $strHtml);
		
		return $strHtml;
	}
	
	public static function encodedEmailUrl($email) {
		return HtmlUtils::encode('mailto:' . urlencode($email));
	}

	public static function stripHtml($content) {
		return strip_tags($content);
	}
}


class AttributeNameIsReservedException extends UiException {
	
}
