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
use rocket\ei\util\Eiu;
use n2n\web\dispatch\mag\Mag;
use rocket\impl\ei\component\prop\string\AlphanumericEiProp;
use n2n\impl\web\ui\view\html\Link;

class YoutubeEiProp extends AlphanumericEiProp {
	
	public function getTypeName(): string {
		return 'Youtube Video';
	}
	
	public function saveMagValue(Mag $option, Eiu $eiu) {
		$eiu->field()->setValue($option->getValue());
	}
	
	public function createUiComponent(HtmlView $view, Eiu $eiu)  {
		$html = $view->getHtmlBuilder();
		$value = $eiu->entry()->getValue($this);
		if ($value === null) return null;
		if ($eiu->gui()->isCompact()) {
			$meta = $html->meta();
			$html->meta()->addCss('impl/js/thirdparty/magnific-popup/magnific-popup.min.css', 'screen');
			$html->meta()->addJs('impl/js/thirdparty/magnific-popup/jquery.magnific-popup.min.js');
			$meta->addJs('impl/js/image-preview.js');
			
			$videoUrl = 'https://www.youtube.com/watch?v=' . $html->getEsc(urlencode($value));
			
			return new Link($videoUrl , $videoUrl, ['class' => 'rocket-video-previewable']);
		}
		
		$html = $view->getHtmlBuilder();
		$raw = '<iframe class="rocket-youtube-video-preview" type="text/html" src="https://www.youtube.com/embed/' . $html->getEsc(urlencode($value)) . '"></iframe>';
		return new Raw($raw);
	}
	/* (non-PHPdoc)
	 * @see \rocket\spec\ei\manage\gui\Editable::createOption()
	 */
	public function createMag(Eiu $eiu): Mag {
		return new StringMag($this->getLabelLstr(), null,
				$this->isMandatory($eiu), $this->getMaxlength(), false, null,
				array('placeholder' => $this->getLabelLstr(), 'class' => 'form-control'));
	}
}