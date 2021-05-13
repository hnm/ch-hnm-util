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

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\dispatch\map\PropertyPath;
use n2n\web\dispatch\mag\MagCollection;
use n2n\impl\web\ui\view\html\HtmlUtils;
use n2n\impl\web\dispatch\property\ObjectProperty;
use n2n\reflection\property\AccessProxy;
use n2n\web\dispatch\map\bind\BindingDefinition;
use n2n\util\type\ArgUtils;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\property\ManagedProperty;
use n2n\web\dispatch\mag\UiOutfitter;

/**
 * Class MagCollectionMag
 * @package n2n\impl\web\dispatch\mag\model
 */
class MagCollectionMag extends MagAdapter {
	private $magCollection;

	/**
	 * MagCollectionMag constructor.
	 * @param $label
	 * @param MagCollection $magCollection
	 * @param array|null $containerAttrs
	 */
	public function __construct($label, MagCollection $magCollection,
			array $containerAttrs = null) {
		parent::__construct($label,
				HtmlUtils::mergeAttrs(array('class' => 'n2n-option-collection-option'), (array) $containerAttrs));
		$this->magCollection = $magCollection;
	}

	/**
	 * @return MagCollection
	 */
	public function getMagCollection() {
		return $this->magCollection;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::createUiField($propertyPath, $view)
	 * @return UiComponent
	 */
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uiOutfitter): UiComponent {
		return $view->getImport('\n2n\impl\web\dispatch\mag\view\magCollectionOption.html',
				array('propertyPath' => $propertyPath));
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\dispatch\mag\Mag::createManagedProperty($accessProxy)
	 * @return ManagedProperty
	 */
	public function createManagedProperty(AccessProxy $accessProxy): ManagedProperty {
		return new ObjectProperty($accessProxy, false);
	}

	/**
	 * @return array
	 */
	public function getValue() {
		return $this->magCollection->readValues();
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value) {
		ArgUtils::valType($value, 'array');
		
		$this->magCollection->writeValues($value);
	}

	/**
	 * @return MagForm
	 */
	public function getFormValue() {
		return new MagForm($this->magCollection);
	}

	/**
	 * @param mixed $formValue
	 */
	public function setFormValue($formValue){
		ArgUtils::valObject($formValue, MagForm::class);
		$this->magCollection = $formValue->getMagCollection();
	}

	/**
	 * @param BindingDefinition $bindingDefinition
	 */
	public function setupBindingDefinition(BindingDefinition $bindingDefinition) {
		
	}
}
