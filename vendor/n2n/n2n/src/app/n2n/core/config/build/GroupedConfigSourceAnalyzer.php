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

class GroupedConfigSourceAnalyzer {
	private $configSource;
	private $groupNames;
	private $stages;
	
	public function __construct(ConfigSource $configSource) {
		$this->configSource = $configSource;
		$this->reload();		
	}
	
	public function reload() {
		$this->stages = array();
		$this->groupNames = array();
		
		foreach ($this->configSource->readArray() as $groupExpression => $groupData) {
			$groupExpressionParts = explode(GroupedConfigSourceReader::STAGE_SEPARATOR, $groupExpression);
			$numGroupExpressionParts = sizeof($groupExpressionParts);
		
			if ($numGroupExpressionParts > 2) {
				throw new InvalidConfigurationException('Group name must be conform to pattern \'{group}:{stage}\', \'' 
						. $groupExpression . '\' given in ConfigSource: ' . $this->configSource);
			}
				
			if ($numGroupExpressionParts > 1) {
				$stage = trim($groupExpressionParts[1]);
				$this->stages[$stage] = $stage;
			}
	
			$groupName = trim($groupExpressionParts[0]);
			$this->groupNames[$groupName] = $groupName; 
		}		
	}
	
	public function getStages() {
		return $this->stages;
	}
	
	public function getGroupNames(){
		return $this->groupNames;
	}
}
