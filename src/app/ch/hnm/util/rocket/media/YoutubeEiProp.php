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

use rocket\impl\ei\component\prop\string\AlphanumericEiProp;
use n2n\util\uri\Url;
use n2n\web\dispatch\mag\Mag;
use n2n\impl\web\dispatch\mag\model\StringMag;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\ui\Raw;
use rocket\ei\util\Eiu;

class YoutubeEiProp extends AlphanumericEiProp {

//	public function getTypeName(): string {
//		return 'Youtube Video';
//	}

	public function writeDraftValue($object, $value) {
		parent::writeDraftValue($object, $this->parseYoutubeId($value));
	}

	public function write(Eiu $eiu, $value) {
		$eiu->entry()->writeNativeValue($this, $this->parseYoutubeId($value));
	}

	public function createMag(Eiu $eiu): Mag {
		return new StringMag($this->getLabelLstr(), null,
				$this->getEditConfig()->isMandatory(), $this->getAlphanumericConfig()->getMaxlength(),
				inputAttrs: array('placeholder' => $this->getLabelLstr()));
	}

	public function createUiComponent(HtmlView $view, Eiu $eiu)  {
		$html = $view->getHtmlBuilder();
		$value = $eiu->entry()->getValue($this);
		if ($value === null) {
			return new Raw();
		};

		$urlEncodedValue = urlencode($value);
		if (!$eiu->entryGui()->isCompact()) {
			$raw = '<iframe class="rocket-youtube-video-preview" type="text/html" src="https://www.youtube.com/embed/' . $html->getEsc($urlEncodedValue) . '"></iframe>';
			return new Raw($raw);
		}

		$meta = $html->meta();
		$html->meta()->addCss('impl/js/thirdparty/magnific-popup/magnific-popup.min.css', 'screen');
		$html->meta()->addJs('impl/js/thirdparty/magnific-popup/jquery.magnific-popup.min.js');
		$meta->addJs('impl/js/image-preview.js');

		$videoUrl = 'https://www.youtube.com/watch?v=' . $urlEncodedValue;

		return $html->getLink($videoUrl, preg_replace('/^https?:\/\//', '', $videoUrl),
				['class' => 'rocket-video-previewable', 'target' => '_blank']);
	}

	private function parseYoutubeId(string $youtubeId) {
		try {
			$url = Url::create($youtubeId);
			if (!$url->hasScheme()) return $youtubeId;

			switch ((string) $url->getAuthority()) {
				case 'www.youtube.com':
					$pathParts = $url->getPath()->getPathParts();
					if (count($pathParts) > 0 && array_shift($pathParts) === 'shorts') {
						if (count($pathParts) === 0) {
							return null;
						}

						return array_shift($pathParts);
					}
					return $url->getQuery()->get('v');
				case 'youtu.be':
					return $url->getPath()->getFirstPathPart();
			}

			return null;
		} catch (\InvalidArgumentException $e) {
			return $youtubeId;
		}
	}
}