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

use n2n\web\http\payload\BufferedPayload;
use n2n\web\http\Response;
use n2n\web\http\payload\impl\Redirect;

class JhtmlRedirectPayload extends BufferedPayload {
	private $directive;
	private $httpLocation;
	private $jhtmlExec;
	private $additionalAttrs;
	
	private $payload = null;
	
	
	public function __construct(string $directive, string $httpLocation, JhtmlExec $jhtmlExec = null,
			array $additionalAttrs = array())  {
		$this->directive = $directive;
		$this->httpLocation = $httpLocation;
		$this->jhtmlExec = $jhtmlExec;
		$this->setAdditionalAttrs($additionalAttrs);
	}
		
	public function setAdditionalAttrs(array $additionalAttrs) {
		$this->additionalAttrs = $additionalAttrs;
	}
	
	/* (non-PHPdoc)
	 * @see \n2n\web\http\payload\BufferedPayload::getBufferedContents()
	 */
	public function getBufferedContents(): string {
	    return $this->payload->getBufferedContents();
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\http\payload\Payload::prepareForResponse()
	 */
	public function prepareForResponse(Response $response) {
		if ('application/json' == $response->getRequest()->getAcceptRange()
	               ->bestMatch(['text/html', 'application/json'])) {
            	$this->payload = new JhtmlJsonPayload($this->additionalAttrs);
               	$this->payload->applyRedirect($this->directive, $this->httpLocation, $this->jhtmlExec);
			$this->payload->prepareForResponse($response);
	        return;
	    } 
	    
		$this->payload = new Redirect($this->httpLocation);
		$this->payload->prepareForResponse($response);
	}
	/* (non-PHPdoc)
	 * @see \n2n\web\http\payload\Payload::toKownPayloadString()
	 */
	public function toKownPayloadString(): string {
		return 'Jhtml Redirect';
	}
	
	/**
	 * @param string $httpLocation
	 * @param JhtmlExec $jhtmlExec
	 * @param array $additionalAttrs
	 * @return \n2n\impl\web\ui\view\jhtml\JhtmlRedirectPayload
	 */
	public static function back(string $httpLocation, JhtmlExec $jhtmlExec = null,
			array $additionalAttrs = array()) {
		return new JhtmlRedirectPayload(JhtmlJsonPayload::DIRECTIVE_REDIRECT_BACK, $httpLocation, $jhtmlExec, $additionalAttrs);
	}
	
	/**
	 * @param string $httpLocation
	 * @param JhtmlExec $jhtmlExec
	 * @param array $additionalAttrs
	 * @return \n2n\impl\web\ui\view\jhtml\JhtmlRedirectPayload
	 */
	public static function referer(string $httpLocation, JhtmlExec $jhtmlExec = null,
	    	array $additionalAttrs = array()) {
	    return new JhtmlRedirectPayload(JhtmlJsonPayload::DIRECTIVE_REDIRECT_TO_REFERER, $httpLocation, $jhtmlExec, $additionalAttrs);
	}
	
	/**
	 * @param string $httpLocation
	 * @param JhtmlExec $jhtmlExec
	 * @param array $additionalAttrs
	 * @return \n2n\impl\web\ui\view\jhtml\JhtmlRedirectPayload
	 */
	public static function redirect(string $httpLocation, JhtmlExec $jhtmlExec = null,
			array $additionalAttrs = array()) {
		return new JhtmlRedirectPayload(JhtmlJsonPayload::DIRECTIVE_REDIRECT, $httpLocation, $jhtmlExec, $additionalAttrs);
	}
}
