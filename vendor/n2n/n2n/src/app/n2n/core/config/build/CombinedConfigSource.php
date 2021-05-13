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

use n2n\config\source\ConfigSource;
use n2n\config\ConfigurationConflictException;
use n2n\config\InvalidConfigurationException;
use n2n\config\ConfigProperty;

class CombinedConfigSource implements ConfigSource {
	private $mainConfigSource;
	private $additionalConfigSources = array();
	
	public function __construct(ConfigSource $mainConfigSource = null) {
		$this->mainConfigSource = $mainConfigSource;
	}
	
	public function setMain(ConfigSource $mainConfigSource = null) {
		$this->mainConfigSource = $mainConfigSource;
	}
	
	public function getMain() {
		return $this->mainConfigSource;
	}
	
	public function putAdditional(string $key, ConfigSource $additonalConfigSource) {
		$this->additionalConfigSources[$key] = $additonalConfigSource;
	}
	
	public function getAdditionalByKey($key) {
		if (isset($this->additionalConfigSources[$key])) {
			return $this->additionalConfigSources[$key];
		}
		return null;
	}
	
	public function getAdditionals() {
		return $this->additionalConfigSources;
	}
	
	public function removeAdditionalByKey($key) {
		unset($this->additionalConfigSources[$key]);
	}
	
	public function removeAllAdditionals() {
		$this->additionalConfigSources = array();
	} 
	
	public function isEmpty()  {
		return $this->mainConfigSource === null && empty($this->additionalConfigSources);
	}
	
	public function readArray(): array {
		$additionalData = array();
		foreach ($this->additionalConfigSources as $additionalConfigSource) {
			try {
				$additionalData = DataMerger::merge($additionalData, $additionalConfigSource->readArray(), false);
			} catch (ConfigurationConflictException $e) {
				throw new InvalidConfigurationException('Config source \'' . (string) $additionalConfigSource 
						. ' could not be applied. Relevant config sources: ' . $this->toAdditionalsString(), 0, $e);
			}
		}
		
		return DataMerger::merge($this->mainConfigSource->readArray(), $additionalData, true);
	}
	
	private function merge(array $data, array $data2, array $path = null) {
		foreach ($data2 as $key => $value) {
			$newPath = $path;
			if ($newPath !== null) $newPath[] = $key;
			
			if (is_numeric($key)) {
				$data[] = $value;
				continue;
			}
			
			if (!array_key_exists($key, $data)) {
				$data[$key] = $value;
				continue;
			}
			
			if (is_array($data[$key]) && is_array($value)) {
				$data[$key] = $this->merge($data[$key], $value, $newPath);
				continue;
			}
			
			if ($newPath === null) continue;
			
			throw new InvalidConfigurationException('Property \'' . ConfigProperty::create($newPath)->__toString() 
					. '\' is multiple times defined in config source: ' . $this->toAdditionalsString());
		}
		
		return $data;
	}
	
	public function hashCode() {
		$mainHashCode = null;
		
		if ($this->mainConfigSource !== null) {
			$mainHashCode = $this->mainConfigSource->hashCode();
			if ($mainHashCode === null) return null;
		}
		
		$additionalHashCodes = array();
		foreach ($this->additionalConfigSources as $additionalHashCode) {
			$additionalHashCode = $additionalHashCode->hashCode();
			if ($additionalHashCode === null) return null;
			$additionalHashCodes[] = $additionalHashCode;
		}
		
		return md5('m:' . $mainHashCode . ' a:' . implode(',', $additionalHashCodes));
	}
	
	public function toAdditionalsString() {
		return 'config source bundle (' . implode(', ', $this->additionalConfigSources) . ')';
	}
	
	public function __toString(): string {
		$bundleDefs = array();
		if ($this->mainConfigSource !== null) {
			$bundleDefs[] = 'main: ' . $this->mainConfigSource->__toString();
		}
		
		if (!empty($this->additionalConfigSources)) {
			$bundleDefs[] = 'additionals:' . implode(', ', $this->additionalConfigSources);
		}
		
		if (empty($bundleDefs)) {
			$bundleDefs[] = 'empty';
		}
		
		return 'config source bundle (' . implode('; ', $bundleDefs) . ')';
	}
}


class DataMerger {

	/**
	 * @param array $data
	 * @param array $data2
	 * @throws InvalidConfigurationException
	 * @param bool $overwrite
	 * @return array
	 */
	public static function merge(array $data, array $data2, $overwrite) {
		return self::rmerge($data, $data2, ($overwrite ? null : array()));
	}

	/**
	 * @param array $data
	 * @param array $data2
	 * @param array $path
	 * @throws ConfigurationConflictException
	 * @return array
	 */
	private static function rmerge(array $data, array $data2, array $path = null) {
		foreach ($data2 as $key => $value) {
			$newPath = $path;
			if ($newPath !== null) $newPath[] = $key;

			if (is_numeric($key)) {
				$data[] = $value;
				continue;
			}

			if (!array_key_exists($key, $data)) {
				$data[$key] = $value;
				continue;
			}

			if (is_array($data[$key]) && is_array($value)) {
				$data[$key] = self::rmerge($data[$key], $value, $newPath);
				continue;
			}

			if ($newPath === null) continue;

			throw new ConfigurationConflictException('Property \'' . ConfigProperty::create($newPath)->__toString()
				. '\' is already defined.');
		}

		return $data;
	}
}
