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

use n2n\io\fs\FsPath;
use n2n\util\uri\Url;

class FilesConfig {
	private $assetsDir;
	private $assetsUrl;
	private $managerPublicDir;
	private $managerPublicUrl;
	private $managerPrivateDir;
	
	public function __construct(FsPath $assetsDir, Url $assetsUrl, FsPath $managerPublicDir, Url $managerPublicUrl, 
			FsPath $managerPrivateDir = null) {
		$this->assetsDir = $assetsDir;
		$this->assetsUrl = $assetsUrl;
		$this->managerPublicDir = $managerPublicDir;
		$this->managerPublicUrl = $managerPublicUrl;
		$this->managerPrivateDir = $managerPrivateDir;
	}
	

	/**
	 * @return string
	 */
	public function getAssetsDir(): FsPath {
		return $this->assetsDir;
	}
	
	public function getAssetsUrl(): Url {
		return $this->assetsUrl;
	}
	
	/**
	 * @return FsPath
	 */
	public function getManagerPublicDir() {
		return $this->managerPublicDir;
	}
	/**
	 * @return Url
	 */
	public function getManagerPublicUrl() {
		return $this->managerPublicUrl;
	}
	/**
	 * @return FsPath
	 */
	public function getManagerPrivateDir() {
		return $this->managerPrivateDir;
	}
}
