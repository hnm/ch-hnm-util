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
use n2n\config\InvalidConfigurationException;
use n2n\util\type\attrs\Attributes;
use n2n\util\ex\IllegalStateException;

class GroupedConfigSourceReader {
	const STAGE_SEPARATOR = ':';
	const GROUP_EXTENSION_SEPARATOR = '-';
	
	private $combinedConfigSource;
	private $stage;
	private $stageExplicit;
	private $allowedGroupNames;
	private $extendableGroupNames;
	
	private $groupReaders;
	private $extendedGroupReaders;
	
	public function __construct(CombinedConfigSource $combinedConfigSource) {
		$this->combinedConfigSource = $combinedConfigSource;
	}
	
	public function initialize(string $stage = null, bool $stageExplicit = false, array $allowedGroupNames = null, array $extendableGroupNames = null) {
		$this->stage = $stage;
		$this->stageExplicit = $stageExplicit;
		$this->allowedGroupNames = $allowedGroupNames;
		$this->extendableGroupNames = $extendableGroupNames;
		$this->groupReaders = array();
		$this->extendedGroupReaders = array();
		
		$this->analyzeConfigSource($this->combinedConfigSource->getMain(), true);
		foreach ($this->combinedConfigSource->getAdditionals() as $configSource) {
			$this->analyzeConfigSource($configSource, false);
		}
	}
	
	private function analyzeConfigSource(ConfigSource $configSource, bool $main) {
		foreach ($configSource->readArray() as $groupExpression => $attrs) {
			$groupExpressionParts = explode(self::STAGE_SEPARATOR, $groupExpression);
			$numGroupExpressionParts = count($groupExpressionParts);
		
			if ($numGroupExpressionParts > 2) {
				throw new InvalidConfigurationException('Group name must be conform to pattern \'{group}' 
						. self::STAGE_SEPARATOR . '{stage}\', \'' . $groupExpression 
						. '\' given in ConfigSource: ' . $configSource);
			}
			 
			$stage = null;
			if ($numGroupExpressionParts > 1) {
				$stage = trim($groupExpressionParts[1]);
			}
		
			$groupName = trim($groupExpressionParts[0]);
			$groupExtensionName = null;
			
			if (1 < count($groupNameParts = explode(self::GROUP_EXTENSION_SEPARATOR, $groupName, 2))) {
				$groupName = trim($groupNameParts[0]);
				$groupExtensionName = trim($groupNameParts[1]);
			}
			
			if ($this->allowedGroupNames !== null && !in_array($groupName, $this->allowedGroupNames)) {
				throw new InvalidConfigurationException('Invalid group name \'' . $groupName 
						. '\' given in ConfigSource: ' . $configSource);
			}
			
			if ($this->extendableGroupNames !== null && $groupExtensionName !== null 
					&& !in_array($groupName, $this->extendableGroupNames)) {
				throw new InvalidConfigurationException('Group \'' . $groupName . '\' can not be extended. ConfigSource: ' 
						. $configSource);
			}
			
			if (!is_array($attrs)) continue;
			
			if ($this->stage === $stage || (!$this->stageExplicit && $stage === null)) {
				$groupReader = null;
				if ($groupExtensionName === null) {
					$groupReader = $this->getGroupReaderByGroupName($groupName);
				} else {
					$groupReader = $this->getExtendedGroupReaderByNames($groupName, $groupExtensionName);
				}
				$groupReader->addAttributeDef(
						new AttributesDef(new Attributes((array) $attrs), (string) $configSource, $stage !== null), 
						$main);
			}
		}
	}
	
	public function getGroupReaderByGroupName(string $groupName): GroupReader {
		if ($this->groupReaders === null) {
			throw new IllegalStateException('GroupConfigSourceReader not initialized.');
		}
		
		if (!isset($this->groupReaders[$groupName])) {
			$this->groupReaders[$groupName] = new GroupReader($groupName, $this->stage, $this->combinedConfigSource);
		}
		
		return $this->groupReaders[$groupName];
	}
	
	public function getExtendedGroupReaderByNames(string $groupName, string $extensionName) {
		if ($this->extendedGroupReaders === null) {
			throw new IllegalStateException('GroupConfigSourceReader not initialized.');
		}
		
		if (!isset($this->extendedGroupReaders[$groupName])) {
			$this->extendedGroupReaders[$groupName] = array();
		}
		
		if (!isset($this->extendedGroupReaders[$groupName][$extensionName])) {
			$this->extendedGroupReaders[$groupName][$extensionName] = new GroupReader(
					$groupName . self::GROUP_EXTENSION_SEPARATOR . $extensionName, $this->stage, 
					$this->combinedConfigSource);
		}
		
		return $this->extendedGroupReaders[$groupName][$extensionName];
	}
	
	public function getExtendedGroupReadersByGroupName(string $groupName) {
		if ($this->extendedGroupReaders === null) {
			throw new IllegalStateException('GroupConfigSourceReader not initialized.');
		}
		
		if (isset($this->extendedGroupReaders[$groupName])) {
			return $this->extendedGroupReaders[$groupName];
		}
		
		return array();
	}
}
