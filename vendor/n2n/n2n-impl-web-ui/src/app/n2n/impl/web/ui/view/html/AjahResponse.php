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
namespace n2n\impl\web\ui\view\html;

use n2n\web\http\payload\BufferedPayload;
use n2n\web\http\Response;
use n2n\util\StringUtils;
use n2n\web\ui\SimpleBuildContext;
/**
 * 
 * @deprecated use {@see \n2n\impl\web\ui\view\jhtml\AjahResponse}
 */
class AjahResponse extends BufferedPayload {
	const HEAD_KEY = 'head';
	const BODY_START = 'bodyStart';
	const BODY_END = 'bodyEnd';
	const ADDITIONAL_KEY = 'additional';
	const CONTENT_KEY = 'content';
	
	private $htmlView;
	private $additionalAttrs;
	
	public function __construct(HtmlView $htmlView, array $additionalAttrs = array())  {
		$this->htmlView = $htmlView;
		$this->setAdditionalAttrs($additionalAttrs);
	}
		
	public function setAdditionalAttrs(array $additionalAttrs) {
		$this->additionalAttrs = $additionalAttrs;
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\web\http\payload\BufferedPayload::getBufferedContents()
	 */
	public function getBufferedContents(): string {
		if (!$this->htmlView->isInitialized()) {
			$this->htmlView->initialize();
		}
		
		$data = array(self::HEAD_KEY => array(), self::BODY_START => array(),
				self::BODY_END => array(), self::ADDITIONAL_KEY => $this->additionalAttrs,
				self::CONTENT_KEY => $this->htmlView->build(new SimpleBuildContext()));
		
		foreach ($this->htmlView->getHtmlProperties()->fetchUiComponentHtmlSnipplets(HtmlBuilderMeta::getKeys()) 
				as $name => $htmlSnipplets) {
			switch ($name) {
				case HtmlBuilderMeta::TARGET_BODY_START:
					$data[self::BODY_START] = array_merge($data[self::BODY_START], $htmlSnipplets);
				case HtmlBuilderMeta::TARGET_BODY_END:
					$data[self::BODY_END] = array_merge($data[self::BODY_END], $htmlSnipplets);
				default:
					$data[self::HEAD_KEY] = array_merge($data[self::HEAD_KEY], $htmlSnipplets);
			}
		}
		
		return StringUtils::jsonEncode($data);
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\http\payload\Payload::prepareForResponse()
	 */
	public function prepareForResponse(Response $response) {
		$response->setHeader('Content-Type: application/json');
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\http\payload\Payload::toKownPayloadString()
	 */
	public function toKownPayloadString(): string {
		return 'Ajah Response';
	}
	
// 	public static function creataFromHtmlView(HtmlView $view, array $additionalData = null) {
// 		if (null !== $additionalData) {
// 			$data = $additionalData;
// 		}
// 		return new AjahResponse(array_merge(self::extractJsonableArray($view), $additionalData));
// 	}
}
