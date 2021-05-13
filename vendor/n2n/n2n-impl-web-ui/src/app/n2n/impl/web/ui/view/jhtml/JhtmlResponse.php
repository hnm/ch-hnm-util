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
namespace n2n\impl\web\ui\view\jhtml;

use n2n\impl\web\ui\view\html\HtmlView;
use n2n\web\ui\UiComponent;

class JhtmlResponse {
	/**
	 * @param string $httpLocation
	 * @param JhtmlExec $jhtmlExec
	 * @param array $additionalAttrs
	 * @return \n2n\web\http\payload\Payload
	 */
	public static function redirect(string $httpLocation, JhtmlExec $jhtmlExec = null, array $additionalAttrs = array()) {
		return JhtmlRedirectPayload::redirect($httpLocation, $jhtmlExec, $additionalAttrs);
	}
	
	/**
	 * @param string $fallbackHttpLocation
	 * @param JhtmlExec $jhtmlExec
	 * @param array $additionalAttrs
	 * @return \n2n\web\http\payload\Payload
	 */
	public static function redirectBack(string $fallbackHttpLocation, JhtmlExec $jhtmlExec = null, array $additionalAttrs = array()) {
		return JhtmlRedirectPayload::back($fallbackHttpLocation, $jhtmlExec, $additionalAttrs);
	}
	
	/**
	 * @param string $httpLocation
	 * @param JhtmlExec $jhtmlExec
	 * @param array $additionalAttrs
	 * @return \n2n\web\http\payload\Payload
	 */
	public static function redirectToReferer(string $httpLocation, JhtmlExec $jhtmlExec = null, array $additionalAttrs = array()) {
		return JhtmlRedirectPayload::referer($httpLocation, $jhtmlExec, $additionalAttrs);
	}
	
	/**
	 * @param HtmlView $htmlView
	 * @param array $additionalData
	 * @return \n2n\web\http\payload\Payload
	 */
	public static function view(HtmlView $htmlView, array $additionalData = array()) {
		return new JhtmlViewPayload($htmlView, $additionalData);
	}
	
	/**
	 * @param UiComponent $uiComponent
	 * @param array $additionalData
	 * @return \n2n\web\http\payload\Payload
	 */
	public static function uiComponent(UiComponent $uiComponent, array $additionalData = array()) {
		$payload = new JhtmlJsonPayload($additionalData);
		$payload->applyUiComponent($uiComponent);
		return $payload;
	}	
	
	/**
	 * @param array $additionalData
	 * @return \n2n\web\http\payload\Payload
	 */
	public static function data(array $additionalData = array()) {
		return new JhtmlJsonPayload($additionalData);
	}
}
