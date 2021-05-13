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
namespace n2n\core\config;

use n2n\log4php\LoggerLevel;
use n2n\util\type\ArgUtils;

class GeneralConfig {
	private $pageName;
	private $pageUrl;
	private $applicationName;
	private $applicationLogLevel;
	private $batchControllerClassNames;
	/**
	 * @param string $pageName
	 * @param string $pageUrl
	 * @param string $pageAssetsDir
	 * @param string $pagePublicUploadDir
	 * @param string $applicationName
	 * @param string $applicationLogLevel
	 * @param array $batchControllerClassNames
	 */
	public function __construct(string $pageName, string $pageUrl = null, string $applicationName, string $applicationLogLevel = null, 
			array $batchControllerClassNames) {
		$this->pageName = $pageName;
		$this->pageUrl = $pageUrl;
		ArgUtils::assertTrue(1 === preg_match('#^\w+$#', $applicationName));
		$this->applicationName = $applicationName;
		$this->applicationLogLevel = $applicationLogLevel;
		$this->batchControllerClassNames = $batchControllerClassNames;
	}
	/**
	 * @return string
	 */
	public function getPageName(): string {
		return $this->pageName;
	}
	/**
	 * @return string
	 */
	public function getPageUrl() {
		return $this->pageUrl;
	}
	
	/**
	 * @return string
	 */
	public function getApplicationName(): string {
		return $this->applicationName;
	}
	/**
	 * @return LoggerLevel
	 */
	public function getApplicationLogLevel(): LoggerLevel {
		$enumValues = array(LoggerLevel::getLevelTrace()->__toString(),
				LoggerLevel::getLevelDebug()->__toString(),	LoggerLevel::getLevelInfo()->__toString(),
				LoggerLevel::getLevelWarn()->__toString(), LoggerLevel::getLevelError()->__toString(),	
				LoggerLevel::getLevelFatal()->__toString(), LoggerLevel::getLevelOff()->__toString());
		// @todo how to determine default loglevel
		return LoggerLevel::toLevel($this->applicationLogLevel, LoggerLevel::getLevelAll());
	}
	/**
	 * @return array
	 */
	public function getBatchJobLookupIds(): array {
		return $this->batchControllerClassNames;
	}
}
