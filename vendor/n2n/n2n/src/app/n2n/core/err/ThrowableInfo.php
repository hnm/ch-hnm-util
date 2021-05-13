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

class ThrowableInfo {
	private $navTitle;
	private $title;
	private $message;
	private $filePath;
	private $lineNo;
	private $stackTraceString;
	private $codeInfos = array();
	private $statementString;
	private $boundValues = array();
	private $documentId = null;
	
	public function __construct(string $navTitle, string $title, string $filePath = null, int $lineNo = null, 
			string $stackTraceString) {
		$this->navTitle = $navTitle;
		$this->title = $title;	
		$this->filePath = $filePath;
		$this->lineNo = $lineNo;
		$this->stackTraceString = $stackTraceString;
	}
	
	public function getTitle(): string {
		return $this->title;
	}
	
	public function getNavTitle(): string {
		return $this->navTitle;
	}
	
	public function getMessage() {
		return $this->message;
	}
	
	public function setMessage(string $message = null) {
		$this->message = $message;
	}
	
	public function getFilePath() {
		return $this->filePath;
	}
	
	public function getLineNo() {
		return $this->lineNo;
	}
	
	public function getStackTraceString(): string {
		return $this->stackTraceString;
	}
	
	public function setStackTraceString(string $stackTraceString) {
		$this->stackTraceString = $stackTraceString;
	}
	
	/**
	 * @return CodeInfo[]
	 */
	public function getCodeInfos() {
		return $this->codeInfos;
	}
	
	public function addCodeInfo(CodeInfo $codeInfo) {
		$this->codeInfos[] = $codeInfo;
	}
	
	public function getStatementString() {
		return $this->statementString;
	}
	
	public function setStatementString(string $statementString = null) {
		$this->statementString = $statementString;
	}
	
	public function getBoundValues(): array {
		return $this->boundValues;
	}
	
	public function setBoundValues(array $boundValues) {
		$this->boundValues = $boundValues;
	}
	
	public function getDocumentId() {
		return $this->documentId;
	}

	public function setDocumentId(string $documentId = null) {
		$this->documentId = $documentId;
	}

}
