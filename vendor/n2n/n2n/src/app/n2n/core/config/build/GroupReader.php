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
namespace n2n\core\config\build;

use n2n\config\InvalidConfigurationException;
use n2n\util\type\attrs\AttributesException;
use n2n\l10n\N2nLocale;
use n2n\l10n\IllegalN2nLocaleFormatException;
use n2n\io\IoUtils;
use n2n\util\type\attrs\InvalidAttributeException;

class GroupReader {
	private $groupName;
	private $stage;
	private $configSourceName;

	private $mainAttributesDefs = array();
	private $additionalAttributesDefs = array();

	public function __construct(string $groupName, string $stage = null, string $configSourceName) {
		$this->groupName = $groupName;
		$this->stage = $stage;
		$this->configSourceName = $configSourceName;
	}

	public function addAttributeDef(AttributesDef $attributesDef, bool $main) {
		if ($main) {
			$this->mainAttributesDefs[] = $attributesDef;
		} else {
			$this->additionalAttributesDefs[] = $attributesDef;
		}
	}
	
	public function getMainAttributesDefs(): array {
		return $this->mainAttributesDefs;
	}
	
	public function getAdditionalAttributesDefs(): array {
		return $this->additionalAttributesDefs;
	}

//	 public function createInvalidAttributeException(string $attributeName, \Throwable $previous) {
//		 if (null !== ($attributesDef = $this->findAttributesDef($attributeName, false))) {
//			 return $this->createInvalidAttributeExceptionFromDef($attributesDef);
//		 }

//		 throw new \InvalidArgumentException('Unknown attribute name: ' . $attributeName);
//	 }

	public function createInvalidAttributeException(string $attributeName, AttributesDef $attributesDef, \Throwable $previous) {
		return new InvalidConfigurationException('Invalid attribute \'' . $attributeName . '\' (group: '
				. $this->groupName . ') defined' . ($this->stage !== null ? ' for stage ' . $this->stage : '')
				. ' in config source: ' . $this->buildConfigSourceName($attributesDef), 0, $previous);
	}

	private function findAttributesDef(string $attributeName, bool $mandatory) {
		$current = null;

		foreach ($this->mainAttributesDefs as $mainAttributesDef) {
			if ($this->replaceCheck($attributeName, $current, $mainAttributesDef)) {
				$current = $mainAttributesDef;
			}
		}

		if ($current !== null) return $current;

		foreach ($this->additionalAttributesDefs as $additionalAttributesDef) {
			if ($this->replaceCheck($attributeName, $current, $additionalAttributesDef)) {
				$current = $additionalAttributesDef;
			}
		}

		if (!$mandatory || $current !== null) return $current;

		throw new InvalidConfigurationException('Missing attribute ' . $attributeName . ' (group: '
				. $this->groupName . ') ' . ($this->stage !== null ? ' for stage ' . $this->stage : '')
				. 'in config source: ' . $this->configSourceName);
	}

	private function replaceCheck(string $name, AttributesDef $current = null, AttributesDef $new): bool {
		if (!$new->getAttributes()->contains($name)) return false;

		if ($current === null || (!$current->isStageRestricted() && $new->isStageRestricted())) {
			return true;
		}

		if ($current->isStageRestricted() && !$new->isStageRestricted()) {
			return false;
		}

		if ($current->getAttributes()->get($name) === $new->getAttributes()->get($name)) {
			return false;
		}
		
		return true;
	}
	
	public function createConflictException($attributeName, AttributesDef $def1, AttributesDef $def2) {
		throw new InvalidConfigurationException('Attribute \'' . $attributeName . '\' (group: ' . $this->groupName
				. ')' . ($this->stage !== null ? ' for stage ' . $this->stage : '')
				. ' is defined in multiple config sources: ' . $this->buildConfigSourceName($def1) . ', '
				. $this->buildConfigSourceName($def2));
	}

	private function buildConfigSourceName(AttributesDef $attributesDef): string {
		$name = $attributesDef->getConfigSourceName();

		if ($attributesDef->isStageRestricted()) {
			$name .= ' (group ' . $this->groupName . ':' . $this->stage . ')';
		}

		return $name;
	}
 
	public function getNames(): array {
		$names = array();

		foreach ($this->mainAttributesDefs as $attributesDef) {
			$names = array_merge($names, $attributesDef->getAttributes()->getNames());
		}

		foreach ($this->additionalAttributesDefs as $attributesDef) {
			$names = array_merge($names, $attributesDef->getAttributes()->getNames());
		}

		return array_unique($names);
	}

	public function contains(string $attributeName) {
		foreach ($this->mainAttributesDefs as $attributesDef) {
			if ($attributesDef->getAttributes()->contains($attributeName)) {
				return true;
			}
		}
		
		foreach ($this->additionalAttributesDefs as $attributesDef) {
			if ($attributesDef->getAttributes()->contains($attributeName)) {
				return true;
			}
		}
		
		return false;
	}
	
	public function getString(string $attributeName, bool $mandatory, $defaultValue = null) {
		if (null !== ($def = $this->findAttributesDef($attributeName, $mandatory))) {
			try {
				return $def->getAttributes()->getString($attributeName);
			} catch (AttributesException $e) {
				throw $this->createInvalidAttributeException($attributeName, $def, $e);
			}
		}

		return $defaultValue;
	}
	
	public function getNoIoStrictSpecialCharsString(string $attributeName, bool $mandatory, $defaultValue = null) {
		$def = $this->findAttributesDef($attributeName, $mandatory);
		if ($def === null) return $defaultValue;
		
		$str = null;
		try {
			$str = $def->getAttributes()->getString($attributeName);
		} catch (AttributesException $e) {
			throw $this->createInvalidAttributeException($attributeName, $def, $e);
		}
		
		if (IoUtils::hasStrictSpecialChars($str)) {
			throw $this->createInvalidAttributeException($attributeName, $def, 
					new InvalidAttributeException('String must not contain any special chars.'));
		}
		
		return $str;
	}

	public function getInt(string $attributeName, bool $mandatory, $defaultValue = null) {
		if (null !== ($def = $this->findAttributesDef($attributeName, $mandatory))) {
			try {
				return $def->getAttributes()->reqInt($attributeName);
			} catch (AttributesException $e) {
				throw $this->createInvalidAttributeException($attributeName, $def, $e);
			}
		}

		return $defaultValue;
	}

	public function getBool(string $attributeName, bool $mandatory, $defaultValue = null) {
		if (null !== ($def = $this->findAttributesDef($attributeName, $mandatory))) {
			try {
				return $def->getAttributes()->reqBool($attributeName);
			} catch (AttributesException $e) {
				throw $this->createInvalidAttributeException($attributeName, $def, $e);
			}
		}

		return $defaultValue;
	}

	public function getEnum(string $attributeName, array $allowedValues, bool $mandatory, $defaultValue = null) {
		if (null !== ($def = $this->findAttributesDef($attributeName, $mandatory))) {
			try {
				return $def->getAttributes()->reqEnum($attributeName, $allowedValues);
			} catch (AttributesException $e) {
				throw $this->createInvalidAttributeException($attributeName, $def, $e);
			}
		}

		return $defaultValue;
	}
	
	public function getN2nLocale(string $attributeName, bool $mandatory, $defaultValue = null) {
		if (null !== ($def = $this->findAttributesDef($attributeName, $mandatory))) {
			try {
				return new N2nLocale($def->getAttributes()->getString($attributeName));
			} catch (AttributesException $e) {
				throw $this->createInvalidAttributeException($attributeName, $def, $e);
			} catch (IllegalN2nLocaleFormatException $e) {
				throw $this->createInvalidAttributeException($attributeName, $def, $e);
			}
		}
		
		return $defaultValue;
	}
	
	public function getScalarArray(string $attributeName) {
		$arrayMerger = new ArrayMerger($this);
		$arrayMerger->loadScalarArray($attributeName);
		return $arrayMerger->getArray();
	}
	
	public function getN2nLocaleArray(string $attributeName) {
		$arrayMerger = new ArrayMerger($this);
		$arrayMerger->loadScalarArray($attributeName);
		
		$n2nLocales = array();
		foreach ($arrayMerger->getArray() as $key => $n2nLocaleId) {
			try {
				$n2nLocales[$key] = new N2nLocale($n2nLocaleId);
			} catch (IllegalN2nLocaleFormatException $e) {
				throw $this->createInvalidAttributeException($attributeName, 
						$arrayMerger->getAttributesDefByKey($key), $e);
			}
		}
		return $n2nLocales;
	}
	
	public function getN2nLocaleKeyArray(string $attributeName) {
		$arrayMerger = new ArrayMerger($this);
		$arrayMerger->loadScalarArray($attributeName);
	
		$n2nLocales = array();
		foreach ($arrayMerger->getArray() as $n2nLocaleId => $value) {
			try {
				$n2nLocales[$value] = new N2nLocale($n2nLocaleId);
			} catch (IllegalN2nLocaleFormatException $e) {
				throw $this->createInvalidAttributeException($attributeName,
						$arrayMerger->getAttributesDefByKey($n2nLocaleId), $e);
			}
		}
		return $n2nLocales;
	}
}

class ArrayMerger {
	private $groupReader; 
	private $attributeName;
	
	private $arr = array();
	private $mainArr = array();
	private $attributesDefs = array();
	
	public function __construct(GroupReader $groupReader) {
		$this->groupReader = $groupReader;
	}
	
	public function loadScalarArray(string $attributeName) {
		$this->attributeName = $attributeName;
		$this->arr = array();
		$this->mainArr = array();
		$this->attributesDefs = array();

		foreach ($this->groupReader->getMainAttributesDefs() as $def) {
			try {
				$this->merge($def->getAttributes()->getScalarArray($attributeName, false), $def, true);
			} catch (AttributesException $e) {
				throw $this->groupReader->createInvalidAttributeException($attributeName, $def, $e);
			}
		}
		
		foreach ($this->groupReader->getAdditionalAttributesDefs() as $def) {
			try {
				$this->merge($def->getAttributes()->getScalarArray($attributeName, false), $def, false);
			} catch (AttributesException $e) {
				throw $this->groupReader->createInvalidAttributeException($attributeName, $def, $e);
			}
		}
	}
	
	private function merge(array $arr, AttributesDef $attributesDef, bool $main) {
		foreach ($arr as $key => $value) {
			if (is_numeric($key)) {
				$this->arr[] = $value;
				continue;
			}
			
			if (!array_key_exists($key, $this->arr) || (!$this->mainArr[$key] && $main)) {
				$this->arr[$key] = $value;
				$this->mainArr[$key] = $main;
				$this->attributesDefs[$key] = $attributesDef;
				continue;
			}
			
			if (($this->mainArr[$key] && !$main) || $this->arr[$key] === $value) {
				continue;
			}
			
			throw $this->groupReader->createConflictException($this->attributeName . '[' . $key . ']', 
					$this->attributesDefs[$key], $attributesDef);
		}
	}
	
	public function getArray() {
		return $this->arr;
	}
	
	public function getAttributesDefByKey($key) {
		if (isset($this->attributesDefs[$key])) {
			return $this->attributesDefs[$key];
		}
		
		throw new \OutOfBoundsException();
	}
}
