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

use n2n\l10n\L10nConfig;
use n2n\l10n\PseudoL10nConfig;

class AppConfig {
	private $generalConfig;
	private $webConfig;
	private $mailConfig;
	private $ioConfig;
	private $filesConfig;
	private $errorConfig;
	private $dbConfig;
	private $ormConfig;
	private $localeConfig;
	private $l10nConfig;
	private $pseudoL10nConfig;
	
	public function __construct(GeneralConfig $generalConfig, WebConfig $webConfig, MailConfig $mailConfig,
			IoConfig $ioConfig, FilesConfig $filesConfig, ErrorConfig $errorConfig, DbConfig $dbConfig, 
			OrmConfig $ormConfig, N2nLocaleConfig $localeConfig, L10nConfig $l10nConfig, PseudoL10nConfig $pseudoL10nConfig) {
		$this->generalConfig = $generalConfig;
		$this->webConfig = $webConfig;
		$this->mailConfig = $mailConfig;
		$this->ioConfig = $ioConfig;
		$this->filesConfig = $filesConfig;
		$this->errorConfig = $errorConfig;
		$this->dbConfig = $dbConfig;
		$this->ormConfig = $ormConfig;
		$this->localeConfig = $localeConfig;
		$this->l10nConfig = $l10nConfig;
		$this->pseudoL10nConfig = $pseudoL10nConfig;
	}
	/**
	 * @return \n2n\core\config\GeneralConfig
	 */
	public function general() {
		return $this->generalConfig;
	}
	/**
	 * @return \n2n\core\config\WebConfig
	 */
	public function web() {
		return $this->webConfig;	
	}
	/**
	 * @return \n2n\core\config\MailConfig
	 */
	public function mail() {
		return $this->mailConfig;
	}
	/**
	 * @return \n2n\core\config\IoConfig
	 */
	public function io() {
		return $this->ioConfig; 
	}
	/**
	 * @return FilesConfig
	 */
	public function files() {
		return $this->filesConfig;
	}
	/**
	 * @return \n2n\core\config\ErrorConfig
	 */
	public function error() {
		return $this->errorConfig;
	}
	/**
	 * @return \n2n\core\config\DbConfig
	 */
	public function db() {
		return $this->dbConfig;
	}
	/**
	 * @return \n2n\core\config\OrmConfig
	 */
	public function orm() {
		return $this->ormConfig;
	}
	/**
	 * @return \n2n\core\config\N2nLocaleConfig
	 */
	public function locale() {
		return $this->localeConfig;
	}
	/**
	 * @return \n2n\l10n\L10nConfig
	 */
	public function l10n() {
		return $this->l10nConfig;
	}
	/**
	 * @return \n2n\l10n\PseudoL10nConfig
	 */
	public function pseudoL10n() {
		return $this->pseudoL10nConfig;
	}
}
