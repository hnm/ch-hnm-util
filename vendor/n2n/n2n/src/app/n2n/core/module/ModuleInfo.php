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
namespace n2n\core\module;

use n2n\util\type\ArgUtils;

class ModuleInfo {
	private $name;
	private $author;
	private $website;
	private $license;
	private $version;
	private $dependencies;
	private $configDescriberClassName;
	
	public function getName() {
		return $this->name;
	}

	public function setName(string $name = null) {
		$this->name = $name;
	}

	public function getAuthor() {
		return $this->author;
	}

	public function setAuthor(string $author = null) {
		$this->author = $author;
	}

	public function getWebsite() {
		return $this->website;
	}

	public function setWebsite(string $website = null) {
		$this->website = $website;
	}

	public function getLicense() {
		return $this->license;
	}

	public function setLicense(string $license = null) {
		$this->license = $license;
	}

	public function getVersion() {
		return $this->version;
	}

	public function setVersion(string $version = null) {
		$this->version = $version;
	}

	public function getDependencies() {
		return $this->dependencies;
	}

	public function setDependencies(array $dependencies) {
		ArgUtils::valArray($dependencies, 'string');
		$this->dependencies = $dependencies;
	}

	public function getConfigDescriberClassName() {
		return $this->configDescriberClassName;
	}

	public function setConfigDescriberClassName(string $configDescriberClassName = null) {
		$this->configDescriberClassName = $configDescriberClassName;
	}
}
