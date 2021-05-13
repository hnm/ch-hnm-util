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

use n2n\impl\web\dispatch\map\val\ValImageFile;
use n2n\impl\web\dispatch\map\val\ValFileExtensions;
use n2n\l10n\Lstr;
use n2n\web\dispatch\map\PropertyPath;
use n2n\io\managed\File;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\dispatch\map\val\ValImageResourceMemory;
use n2n\impl\web\dispatch\property\FileProperty;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\impl\web\dispatch\map\val\ValNotEmpty;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\mag\UiOutfitter;
use n2n\impl\web\ui\view\html\HtmlSnippet;

/**
 * Class FileMag
 * @package n2n\impl\web\dispatch\mag\model
 */
class FileMag extends MagAdapter {
	private $mandatory;
	private $allowedExtensions;
	private $inputAttrs;
	private $checkImageResourceMemory;

	/**
	 * FileMag constructor.
	 * @param string|Lstr $label
	 * @param array $allowedExtensions
	 * @param bool $checkImageResourceMemory
	 * @param File|null $value
	 * @param bool $mandatory
	 * @param array $inputAttrs
	 * @param array $containerAttrs
	 */
	public function __construct($label, array $allowedExtensions = null, $checkImageResourceMemory = false,
			File $value = null, bool $mandatory = false, array $inputAttrs = null, array $containerAttrs = null) {
		parent::__construct($label, $value, $containerAttrs);
		$this->inputAttrs = $inputAttrs;
		$this->allowedExtensions = $allowedExtensions;
		$this->inputAttrs = $inputAttrs;
		$this->checkImageResourceMemory = (boolean) $checkImageResourceMemory;
		$this->mandatory = (boolean) $mandatory;
	}

	/**
	 * @param bool $mandatory
	 */
	public function setMandatory($mandatory) {
		$this->mandatory = (boolean) $mandatory;
	}

	/**
	 * @return bool
	 */
	public function isMandatory(): bool {
		return $this->mandatory;
	}

	/**
	 * @param AccessProxy $accessProxy
	 * @return ManagedProperty
	 */
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new FileProperty($accessProxy, false);
	}

	/**
	 * @param BindingDefinition $bd
	 */
	public function setupBindingDefinition(BindingDefinition $bd) {
		if ($this->mandatory) {
			$bd->val($this->propertyName, new ValNotEmpty());
		}
	
		if (null !== $this->allowedExtensions) {
			$bd->val($this->propertyName, new ValFileExtensions($this->allowedExtensions));
		}
		
		if ($this->checkImageResourceMemory) {
			$bd->val($this->propertyName, new ValImageResourceMemory());
		}
		
		$bd->val($this->propertyName, new ValImageFile(false));
	}

	/**
	 * @param PropertyPath $propertyPath
	 * @param HtmlView $htmlView
	 * @return UiComponent
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uo): UiComponent {
		$allowedExtensionString = '';
		$counter = 0;
		if ($this->allowedExtensions !== null) {
			foreach ($this->allowedExtensions as $allowedExtension) {
				$allowedExtensionString .= $allowedExtension;
	
				if (sizeof($this->allowedExtensions) !== ++$counter) {
					$allowedExtensionString .= ',';
				}
			}
			$this->inputAttrs['accept'] = $allowedExtensionString;
		}
		$uiC = new HtmlSnippet($view->getFormHtmlBuilder()->getInputFileWithLabel($propertyPath, $this->inputAttrs));
		
		if (null !== $this->helpTextLstr) {
			$uiC->append($uo->createElement(UiOutfitter::EL_NATURE_HELP_TEXT, null,
					$this->getHelpText($view->getN2nLocale())));
		}
		
		return $uiC;
	}
}
