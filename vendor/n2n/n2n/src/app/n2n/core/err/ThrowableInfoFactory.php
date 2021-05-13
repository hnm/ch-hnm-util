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

use n2n\util\ex\err\EnhancedError;
use n2n\util\ex\QueryStumble;
use n2n\util\ex\Documentable;
use n2n\io\IoUtils;
use n2n\io\IoException;
use n2n\util\SyntaxUtils;
use n2n\util\type\TypeUtils;

class ThrowableInfoFactory {

	public static function createFromException(\Throwable $t) {
		$eClass = new \ReflectionClass($t);
		$throwableInfo = new ThrowableInfo($eClass->getShortName(), $eClass->getName(), $t->getFile(), $t->getLine(), 
				self::buildStackTraceString($t));
		
		$throwableInfo->setMessage($t->getMessage());
		
		if ($t instanceof EnhancedError) {
			self::applyEnhancedCodeInfo($throwableInfo, $t);
		} else if ($t instanceof \Error) {
			self::applyCodeInfo($throwableInfo, $t);
		} else if ($t instanceof \ErrorException) {
			self::applyErrorThrowableInfo($throwableInfo, $t);
		}
	
		if ($t instanceof QueryStumble) {
			self::applyQueryStumble($throwableInfo, $t);
		} else if ($t instanceof \PDOException) {
			self::applyPdoException($throwableInfo, $t);
		}
		
		if ($t instanceof Documentable) {
			$throwableInfo->setDocumentId($t->getDocumentId());
		}
		
		return $throwableInfo;
	}
	
	public static function buildStackTraceString(\Throwable $t) {
		return '#t ' . $t->getFile() . ' (' . $t->getLine() . ')' . "\r\n" . $t->getTraceAsString(); 
	}
	
	/**
	 * Makes error infos more user friendly
	 *
	 * @param string $errmsg;
	 * @param string $filePath
	 * @param string $lineNo
	 */
	public static function findCallPos(\Throwable $t, string &$filePath = null, int &$lineNo = null) {
		if (!($t instanceof \TypeError || $t instanceof  WarningError || $t instanceof  RecoverableError)) {
			return;
		}

		$message = $t->getMessage();
		if (false !== strpos($message, '{closure}')) {
			return;
		}
	
		$fmatches = array();
		$lmatches = array();
		if (preg_match('/(?<=called in )[^ \(]+/', $message, $fmatches)
				&& preg_match('/((?<=on line )|(?<=\())[0-9]+/', $message, $lmatches)) {
			$filePath = $fmatches[0];
			$lineNo = $lmatches[0];
		}
		
		$fmatches = array();
		$lmatches = array();
		if (preg_match('/(?<=passed in )[^ \(]+/', $message, $fmatches)
				&& preg_match('/((?<=on line )|(?<=\())[0-9]+/', $message, $lmatches)) {
			$filePath = $fmatches[0];
			$lineNo = $lmatches[0];
		}
	}
	
	private static function applyCodeInfo(ThrowableInfo $throwableInfo, \Error $e) {
		$filePath = $e->getFile();
		$lineNo = $e->getLine();
		self::findCallPos($e, $filePath, $lineNo);
		if ($filePath !== null) {
			$throwableInfo->addCodeInfo(self::createCodeInfo($filePath, $lineNo));
		}
	}
	
	private static function applyErrorThrowableInfo(ThrowableInfo $throwableInfo, \ErrorException $e) {
		if (null !== ($filePath = $e->getFile())) {
			$throwableInfo->addCodeInfo(self::createCodeInfo($filePath, $e->getLine()));
		}
	}
	
	private static function applyEnhancedCodeInfo(ThrowableInfo $throwableInfo, \Throwable $t) {
		if (null !== ($filePath = $t->getFile())) {
			$throwableInfo->addCodeInfo(self::createCodeInfo($t->getFile(), $t->getLine(), $t->getStartLine(), $t->getEndLine()));
		}
		
		foreach ($t->getAdditionalErrors() as $additionalError) {
			$throwableInfo->addCodeInfo(self::createCodeInfo($additionalError->getFilePath(), $additionalError->getLineNo(),
					$additionalError->getStartLineNo(), $additionalError->getEndLineNo(), $additionalError->getDescription()));
		}
	}
	
	private static function applyQueryStumble(ThrowableInfo $throwableInfo, QueryStumble $e) {
		$throwableInfo->setStatementString(SyntaxUtils::formatSql($e->getQueryString()));
	
		$boundValues = array();
		foreach ((array) $e->getBoundValues() as $key => $value) {
			$boundValues[$key] = TypeUtils::buildScalar($value);
		}
		$throwableInfo->setBoundValues($boundValues);
	}
	
	private static function applyPdoException(ThrowableInfo $throwableInfo, \PDOException $e) {
		$pdoCode = $e->errorInfo;
		
		if (is_array($pdoCode)) {
			$throwableInfo->setStatementString(implode('-', $pdoCode));
		} else {
			$throwableInfo->setStatementString($pdoCode);
		}
	}
	
	public static function createCodeInfo(string $fileName, int $line = null, int $startLineNo = null, 
			int $endLineNo = null, string $message = null) {
		if ($line == 0) {
			$line = null;
			if ($startLineNo === null) $startLineNo = 1;
			if ($endLineNo === null) $endLineNo = 4;
		}
	
		$codeInfo = new CodeInfo($fileName, $line, $message);
	
		if ($line !== null) {
			if ($startLineNo === null) $startLineNo = ($line <= 2 ? 1 : $line - 2);
			if ($endLineNo === null) $endLineNo = $line + 2;
		}
	
		if ($startLineNo === null || $endLineNo === null) {
			return $codeInfo;
		}
	
		$codeInfo->setStartLineNo($startLineNo);
		$codeInfo->setEndLineNo($endLineNo);
	
		$fileLines = null;
		try {
			$fileLines = IoUtils::file($fileName);
		} catch (IoException $e) {
			return $codeInfo;
		}
		$numLines = sizeof($fileLines);
		
		$startIndex = $startLineNo - 1;
		$endIndex = $endLineNo - 1;
	
		$snippetLines = array();
		for ($i = $startIndex; $i < $numLines &&  $i <= $endIndex; $i++) {
			$snippetLines[$i + 1] = $fileLines[$i];
		}
	
		$codeInfo->setSnippetLines($snippetLines);
		return $codeInfo;
	}
}
