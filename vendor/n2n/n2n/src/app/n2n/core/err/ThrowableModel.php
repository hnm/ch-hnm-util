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
namespace n2n\core\err;

class ThrowableModel {
	const N2N_DOCUMENTATION_URL = 'https://dev.n2n.rocks/';
	const EXCEPTION_DOCUMENTATION_URL = 'https://dev.n2n.rocks/e';
	
	private $e;
	private $throwableInfos = array();
	private $output;
	private $outputCallback;
		
	public function __construct($e) {
		$this->e = $e;
		
		do {
			$this->throwableInfos[] = ThrowableInfoFactory::createFromException($e);
			$e = $e->getPrevious();
		} while ($e !== null);
		
	}
	
	public function getTitle() {
		return get_class($this->e) . ' occurred';
	}
	
	public function getThrowableInfos() {
		return $this->throwableInfos;
	}
	
	public function getPreviousThrowableInfos() {
		$previousThrowableInfos = array();
		$e = $this->e;
		while (null != ($e = $e->getPrevious())) {
			$previousThrowableInfos[] = ThrowableInfoFactory::createFromException($e);
		} 
		return $previousThrowableInfos;
	}
	
	public function getException() {
		return $this->e;
	}
	
	public function getDocumentIds() {
		$documentIds = array();
		
		foreach ($this->throwableInfos as $throwableInfo) {
			if (null !== ($documentId = $throwableInfo->getDocumentId())) {
				$documentIds[$documentId] = $documentId;
			}
		}
		
		return array_values($documentIds);
	}
	
	public function setOutputCallback(\Closure $outputCallback = null) {
		$this->outputCallback = $outputCallback;
	}
	
	public function getOutput() {
		if ($this->outputCallback !== null) {
			$this->output = $this->outputCallback->__invoke();
			$this->outputCallback = null;
		}
		
		return $this->output;
	}
}
