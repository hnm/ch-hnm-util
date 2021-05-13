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
namespace n2n\impl\web\dispatch\mag\model;

use n2n\io\managed\File;
use n2n\io\managed\FileManager;

class ManagedFileMag extends FileMag {
	
	/**
	 * @var FileManager
	 */
	private $fileManager;

	/**
	 * ManagedFileMag constructor.
	 * @param string $label
	 * @param FileManager $fileManager
	 * @param array|null $allowedExtensions
	 * @param bool $checkImageResourceMemory checks if file is bigger than max_file_size.
	 * @param File|null $default
	 * @param bool $required
	 * @param array|null $inputAttrs
	 */
	public function __construct(string $label, FileManager $fileManager, array $allowedExtensions = array(),
			$checkImageResourceMemory = false, string $value = null, bool $mandatory = false, array $inputAttrs = null) {
		$this->fileManager = $fileManager;
		parent::__construct($label, $allowedExtensions, $checkImageResourceMemory, null, $mandatory, $inputAttrs);
		$this->value = $value;
	}

	/**
	 * @return File
	 */
	public function getFormValue() {
		return $this->fileManager->getByQualifiedName($this->value);
	}

	/**
	 * @param mixed $value
	 * @return File|string
	 */
	public function setFormValue($value) {
		if ((string) $value == $this->value) return (string) $value;
		if ($value === $this->value) return  $this->fileManager->getByQualifiedName($value);
		if (!($value instanceof File)) return $value;
		return $this->fileManager->persist($value);
	}
}
