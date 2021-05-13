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

use n2n\web\ui\Raw;

class CodeInfo {
	private $filePath;
	private $lineNo;
	private $description;
	private $startLineNo;
	private $endLineNo;
	private $snippetLines = array();

	public function __construct(string $filePath, int $lineNo = null, string $description = null) {
		$this->filePath = $filePath;
		$this->lineNo = $lineNo;
		$this->description = $description;		
	}
	
	public function getFilePath(): string {
		return $this->filePath;
	}
	
	public function getLineNo() {
		return $this->lineNo;
	}
	
	public function getDescription() {
		return $this->description;
	}

	public function containsHtml(): bool {
		return (bool) preg_match('/\.html\.php$/', $this->filePath);
	}
	
	public function getStartLineNo() {
		return $this->startLineNo;
	}
	
	public function setStartLineNo(int $startLine = null) { 
		$this->startLineNo = $startLine;
	}
	
	public function getEndLineNo() {
		return $this->endLineNo;
	}
	
	public function setEndLineNo(int $endLineNo = null) {
		$this->endLineNo = $endLineNo;
	}
	
	public function setSnippetLines(array $snippetLines) {
		$this->snippetLines = $snippetLines;
	}
	
	public function getSnippetLines(): array {
		return $this->snippetLines;
	}
	
	public function getSnippetCodeHtml() {
		if (empty($this->snippetLines)) return null;
		
		$raw = new Raw('');
		$raw->appendLn('');
		
		foreach ($this->snippetLines as $nr => $line) {
			if (preg_match('/^\s*$/', $line)) {
				$raw->appendLn('&nbsp;');
				continue;
			}
			
			if ($nr == $this->endLineNo) {
				$line = rtrim($line);
			}
			
			$raw->append(htmlspecialchars($line, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE));
		}
				
		return $raw;
	}

	
}

// if ($k && preg_match('/^\s*$/', $line)) { echo trim($line, "\r\n") . "&nbsp;\r\n"; }
// else {
// 	$k = false;
// 	$html->esc($line);
// }
