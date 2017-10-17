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

use n2n\web\ui\Raw;
use n2n\impl\web\dispatch\mag\model\StringMag;

use n2n\impl\web\ui\view\html\HtmlView;
use rocket\spec\ei\manage\util\model\Eiu;
use n2n\web\dispatch\mag\Mag;

class YoutubeEiProp extends AlphanumericEiProp {
	
	public function getTypeName(): string {
		return 'Youtube Video';
	}
	
	public function createOutputUiComponent(HtmlView $view,
			Eiu $eiu)  {
				$value = $eiu->entry()->getValue($this->getId());
				if ($value === null) return null;
				
				$html = $view->getHtmlBuilder();
				$raw = '<iframe class="rocket-youtube-video-preview" type="text/html" src="http://www.youtube.com/embed/' . $html->getEsc($value) . '"></iframe>';
				return new Raw($raw);
	}
	/* (non-PHPdoc)
	 * @see \rocket\spec\ei\manage\gui\Editable::createOption()
	 */
	public function createMag(string $propertyName, Eiu $eiu): Mag {
		return new StringMag($propertyName, $this->getLabelCode(), null,
				$this->isMandatory($eiu), $this->getMaxlength(), null,
				array('placeholder' => $this->getLabelCode(), 'class' => 'form-control'));
	}
}

