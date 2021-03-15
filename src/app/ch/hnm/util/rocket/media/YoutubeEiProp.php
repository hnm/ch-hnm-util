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

use rocket\ei\util\Eiu;
use rocket\impl\ei\component\prop\string\AlphanumericEiProp;
use rocket\si\content\SiField;
use rocket\si\content\impl\SiFields;
use rocket\ei\util\factory\EifGuiField;
use rocket\si\control\SiNavPoint;
use n2n\util\uri\Url;

class YoutubeEiProp extends AlphanumericEiProp {
	
	public function getTypeName(): string {
		return 'Youtube Video';
	}
	
	public function saveSiField(SiField $siField, Eiu $eiu) {
		$eiu->field()->setValue($option->getValue());
	}
	
	public function createOutEifGuiField(Eiu $eiu): EifGuiField  {
// 		return $eiu->factory()->newGuiField(SiFields::stringOut($eiu->field()->getValue()));
		
		$value = $eiu->field()->getValue();
		if ($value === null) {
			return $eiu->factory()->newGuiField(SiFields::stringOut(null));
		}
		
		$urlEncodedValue = urlencode($value);
		$videoUrl = 'https://www.youtube.com/watch?v=' . $urlEncodedValue;
		
		$label = preg_replace('/^https?:\/\//', '', $videoUrl);
		return $eiu->factory()->newGuiField(SiFields::linkOut(SiNavPoint::href(Url::create($videoUrl, true)), $label)->setLytebox(true));
		
// 		$html = $view->getHtmlBuilder();
// 		$value = $eiu->entry()->getValue($this);
// 		if ($value === null) return null;
// 		$urlEncodedValue = urlencode($value);
// 		if (!$eiu->guiFrame()->isCompact()) {
// 			$raw = '<iframe class="rocket-youtube-video-preview" type="text/html" src="https://www.youtube.com/embed/' . $html->getEsc($urlEncodedValue) . '"></iframe>';
// 			return new Raw($raw);
// 		}
		
// 		$meta = $html->meta();
// 		$html->meta()->addCss('impl/js/thirdparty/magnific-popup/magnific-popup.min.css', 'screen');
// 		$html->meta()->addJs('impl/js/thirdparty/magnific-popup/jquery.magnific-popup.min.js');
// 		$meta->addJs('impl/js/image-preview.js');
		
// 		$videoUrl = 'https://www.youtube.com/watch?v=' . $urlEncodedValue;
		
// 		return $html->getLink($videoUrl, preg_replace('/^https?:\/\//', '', $videoUrl), 
// 				['class' => 'rocket-video-previewable', 'target' => '_blank']);
	}
}