<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the n2n module ROCKET.
 *
 * ROCKET is free software: you can redistribute it and/or modify it under the terms of the
 * GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * ROCKET is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg...........:	Architect, Lead Developer, Concept
 * Bert Hofmänner.............: Idea, Frontend UI, Design, Marketing, Concept
 * Thomas Günther.............: Developer, Frontend UI, Rocket Capability for Hangar
 */
namespace ch\hnm\util\rocket\media;

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\ui\Raw;
use n2n\impl\web\dispatch\mag\model\StringMag;
use n2n\web\dispatch\map\PropertyPath;
use rocket\spec\ei\component\field\impl\string\AlphanumericEiProp;
use rocket\spec\ei\manage\util\model\Eiu;
use n2n\web\dispatch\mag\Mag;
use n2n\web\ui\UiComponent;
use n2n\web\dispatch\mag\UiOutfitter;


class VimeoEiProp extends AlphanumericEiProp {
	
	public function getTypeName(): string {
		return 'Vimeo Video';
	}
	
	/**
	 * @param HtmlView $view
	 * @param Eiu $eiu
	 * @return NULL|\n2n\web\ui\Raw
	 */
	public function createOutputUiComponent(HtmlView $view, Eiu $eiu)  {
		$html = $view->getHtmlBuilder();
		$eiObject = $eiu->entry()->getEiObject();
		$value = $this->getPropertyAccessProxy()->getValue($eiObject->getCurrentEntity());
		
		if ($value === null) return null;
		
		$raw = '<iframe src="//player.vimeo.com/video/' . $html->getEsc($value)
			. '" width="500" height="281" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		return new Raw($raw);
	}
	/* (non-PHPdoc)
	 * @see \rocket\spec\ei\component\field\StatelessEditable::createOption()
	 */
	public function createMag(Eiu $eiu): Mag {
		return new VimeoOption($this->getLabelCode(), null,
				$this->isMandatory($eiu), $this->getMaxlength(), null,
				array('placeholder' => $this->getLabelCode(), 'class' => 'form-control'));
	}
}

class VimeoOption extends StringMag {
	
	public function createUiField(PropertyPath $propertyPath, HtmlView $view, UiOutfitter $uo): UiComponent {
		return new Raw('<span style="display: inline-block; line-height: 16px">http://vimeo.com/' . parent::createUiField($propertyPath, $view) . '</span>');
	}
	
}
