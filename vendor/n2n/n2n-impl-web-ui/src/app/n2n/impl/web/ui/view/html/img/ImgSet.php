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
namespace n2n\impl\web\ui\view\html\img;

use n2n\util\type\ArgUtils;

class ImgSet {
	private $defaultSrcAttr;
	private $defaultAltAttr;
	private $defaultWidthAttr;
	private $defaultHeightAttr;
	private $imageSourceSets;
	
	public function __construct(string $defaultSrcAttr, string $defaultAltAttr, int $defaultWidthAttr, 
			int $defaultHeightAttr, array $imageSourceSets) {
		ArgUtils::valArray($imageSourceSets, ImageSourceSet::class);
		$this->defaultSrcAttr = $defaultSrcAttr;
		$this->defaultAltAttr = $defaultAltAttr;
		$this->defaultWidthAttr = $defaultWidthAttr;
		$this->defaultHeightAttr = $defaultHeightAttr;
		$this->setImageSourceSets($imageSourceSets);
	}
	
	public function getDefaultSrcAttr() {
		return $this->defaultSrcAttr;
	}
	
	public function setDefaultSrcAttr(string $defaultImageSrc) {
		$this->defaultSrcAttr = $defaultImageSrc;
	}
	
	public function getDefaultAltAttr() {
		return $this->defaultAltAttr;
	}
	
	public function setDefaultAltAttr(string $defaultAltAttr) {
		$this->defaultAltAttr = $defaultAltAttr;
	}
	
	public function getDefaultWidthAttr() {
		return $this->defaultWidthAttr;
	}
	
	public function setDefaultWidthAttr(int $defaultWidthAttr) {
		$this->defaultWidthAttr = $defaultWidthAttr;
	}
	
	public function getDefaultHeightAttr() {
		return $this->defaultHeightAttr;
	}
	
	public function setDefaultHeightAttr(int $defaultHeightAttr) {
		$this->defaultHeightAttr = $defaultHeightAttr;
	}
	
	/**
	 * @return ImageSourceSet[]
	 */
	public function getImageSourceSets() {
		return $this->imageSourceSets;
	}

	/**
	 * @param array $imageSourceSets
	 */
	public function setImageSourceSets(array $imageSourceSets) {
		ArgUtils::valArray($imageSourceSets, ImageSourceSet::class);
		$this->imageSourceSets = $imageSourceSets;
	}
	
	/**
	 * @return boolean
	 */
	public function isPictureRequired() {
		if (count($this->imageSourceSets) > 1) return true;
		
		foreach ($this->imageSourceSets as $imageSourceSet) {
			if (null !== $imageSourceSet->getMediaAttr()) return true;
		}
		
		return false;
	}
}
