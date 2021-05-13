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
namespace n2n\core;

use n2n\util\StringUtils;
use n2n\util\type\TypeUtils;

class TypeLoader {
	const SCRIPT_FILE_EXTENSION = '.php';
	
	private static $useIncludePath = false;
	private static $psr4Map = array();
	private static $classMap = array();
	private static $latestException = null;
	
	public static function getIncludePaths() {
		return self::$includePaths;
	}
	
	public static function isRegistered() {
		return self::$includePaths !== null;
	}
	/**
	 * 
	 * @param string $includePath
	 * @param string $moduleIncludePath
	 */
	public static function register(bool $useIncludePath = true, array $psr4Map = array(), array $classMap = array()) {
		self::init($useIncludePath, $psr4Map, $classMap);
		
		spl_autoload_register('n2n\\core\\TypeLoader::load', true);
	}
	
	private static function valStringArray(array &$arg, $argName) {
		foreach ($arg as $value) {
			if (!is_string($value)) {
				throw new \InvalidArgumentException('Invalid ' . $argName);
			}
		}
	}
	
	public static function init(bool $useIncludePath = true, array $psr4Map = array(), array &$classMap = array()) {
		self::$useIncludePath = $useIncludePath;
		
		self::valStringArray($classMap, 'classMap');
		self::$classMap = &$classMap;
		
		self::$psr4Map = array();
		foreach ($psr4Map as $namespacePrefix => $dirPaths) {
			if (!is_string($namespacePrefix) || !isset($namespacePrefix[0])) {
				throw new \InvalidArgumentException('Invalid ps4Map');
			}
			
			self::valStringArray($dirPaths, 'classMap');
			
			if (!isset(self::$psr4Map[$namespacePrefix[0]])) {
				self::$psr4Map[$namespacePrefix[0]] = array();
			}
			
			self::$psr4Map[$namespacePrefix[0]][$namespacePrefix] = array('length' => strlen($namespacePrefix),
					'dirPaths' => $dirPaths);
		}
	}
	
	/**
	 * 
	 * @param string $typeName
	 * @throws TypeLoaderErrorException
	 */
	public static function load($typeName) {
		try {
			self::requireScript(self::getFilePathOfTypeWihtoutCheck($typeName), $typeName);
			return true;
		} catch (TypeNotFoundException $e) {
			$lutp = N2N::getLastUserTracePoint();
			self::$latestException = new TypeLoaderErrorException($typeName, $e->getMessage(), 0, 
					E_ERROR, $lutp['file'], $lutp['line']);
			return false;
		} /*catch (\Exception $e) {
			self::$exceptionHandler->handleThrowable($e);
			die();
		}*/
		return false;
	}
	/**
	 * 
	 * @param string $typeName
	 * @throws TypeNotFoundException
	 * @return \ReflectionClass
	 */
	public static function loadType($typeName) {
		self::requireScript(self::getFilePathOfType($typeName), $typeName);
	}
	
	public static function isTypeLoaded($typeName) {
		$typeName = (string) $typeName;
		return class_exists($typeName, false) || interface_exists($typeName, false) 
				|| (function_exists('trait_exists') && trait_exists($typeName, false)); 
	}
	/**
	 *
	 * @param string $typeName
	 * @throws TypeNotFoundException
	 */
	public static function ensureTypeIsLoaded($typeName) {
		if (self::isTypeLoaded($typeName)) return;
		self::loadType($typeName);
	}
	
// 	public static function loadScript($scriptPath) {
// 		$scriptPath = IoUtils::realpath((string) $scriptPath);
// 		return self::requireScript($scriptPath, str_replace(DIRECTORY_SEPARATOR, '\\', 
// 				mb_substr(trim(self::removeIncludePathOfFilePath($scriptPath), DIRECTORY_SEPARATOR), 0, -strlen(self::SCRIPT_FILE_EXTENSION))));
// 	}
	
// 	public static function pathToTypeName($scriptPath) {
// 		return ReflectionUtils::qualifyTypeName(mb_substr(
// 				trim(self::removeIncludePathOfFilePath($scriptPath), DIRECTORY_SEPARATOR), 
// 				0, -strlen(self::SCRIPT_FILE_EXTENSION)));
// 	}
	
	private static function requireScript($scriptPath, $typeName) {
		require_once $scriptPath;
		
		if (!self::isTypeLoaded($typeName)) {
			throw new TypeLoaderErrorException($typeName, 'Missing type \'' . $typeName . '\' in file: '
					. $scriptPath, 0, E_USER_ERROR, $scriptPath);
		}
	}
	/**
	 * 
	 * @param string $namespace
	 * @throws TypeLoaderErrorException
	 * @return array
	 */
	public static function getNamespaceDirPaths($namespace) {
		if (TypeUtils::hasSpecialChars($namespace, false)) {
			throw new \InvalidArgumentException('Namespace contains invalid characters: ' . $namespace);
		}
		
		$dirPaths = array();
		
		foreach (self::buildPs4Paths($namespace, '') as $psr4Path) {
			if (is_dir($psr4Path)) {
				$dirPaths[] = $psr4Path;
			}
		}
		
		foreach (explode(PATH_SEPARATOR, get_include_path()) as $includePath) {
			$path = $includePath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
			if (is_dir($path)) {
				$dirPaths[] = $path;
			}
		}
		
		return $dirPaths;
	}
	
	public static function isTypeUnsafe($typeName) {
		return false !== strpos($typeName, '/') || preg_match('#(^|\\\\)\\.{1,2}(\\\\|$)#', $typeName)
				|| 0 == mb_strlen($typeName) || mb_substr($typeName, 0, 1) == '\\' 
				|| mb_substr($typeName, -1) == '\\';
	}
	
	public static function doesTypeExist($typeName, $fileExt = self::SCRIPT_FILE_EXTENSION) {
		// @todo do check without exception
		try {
			self::getFilePathOfType($typeName);
			return true;
		} catch (TypeNotFoundException $e) {
			return false;
		}
	}
	
	public static function getFilePathOfType(string $typeName, $fileExt = self::SCRIPT_FILE_EXTENSION) {
		if (self::isTypeUnsafe($typeName)) {
			throw new \InvalidArgumentException('Type name contains invalid characters: ' . $typeName);
		}
				
		return self::getFilePathOfTypeWihtoutCheck($typeName, $fileExt);
	}
	
	public static function namespaceOfTypeName($typeName) {
		$lastPos = strrpos($typeName, '\\');
		if (false === $lastPos) return null;
		return mb_substr($typeName, 0, $lastPos);
	}
	/**
	 * 
	 * @param string $typeName
	 * @param string $fileExt
	 * @throws TypeLoaderErrorException
	 * @throws TypeNotFoundException
	 * @return string
	 */
	private static function getFilePathOfTypeWihtoutCheck($typeName, $fileExt = self::SCRIPT_FILE_EXTENSION) {
		$typeName = (string) $typeName;
		
		$searchedFilePaths = array();
		
		foreach (self::buildPs4Paths($typeName, $fileExt) as $filePath) {
			$searchedFilePaths[] = $filePath;
			
			if (self::testFile($filePath, $typeName)) {
				return $filePath;
			}
		}
		
		if (isset(self::$classMap[$typeName]) && $fileExt === self::SCRIPT_FILE_EXTENSION) {
			$searchedFilePaths[] = $filePath = self::$classMap[$typeName];
			
			if (self::testFile($filePath, $typeName)) {
				return $filePath;
			}
			
		}
		
		$relativeFilePath = str_replace('\\', DIRECTORY_SEPARATOR, $typeName) . $fileExt;
		
		if (false !== ($filePath = stream_resolve_include_path($relativeFilePath))) {
			if (self::testFile($filePath, $typeName)) {
				return $filePath;
			}
		}
		
		foreach (explode(PATH_SEPARATOR, get_include_path()) as $includePath) {
			if (false !== ($realIncludePath = realpath($includePath))) {
				$includePath = $realIncludePath;
			}
			$searchedFilePaths[] = $includePath . DIRECTORY_SEPARATOR . $relativeFilePath;
		}
		
		throw new TypeNotFoundException('Type \'' . $typeName . '\' not found. Paths:' 
				. implode(PATH_SEPARATOR, $searchedFilePaths));
	}
	
	private static function testFile($filePath, $typeName) {
		if (!is_file($filePath)) return false;
		
		if (is_readable($filePath)) return true;
				
		throw new TypeLoaderErrorException($typeName, 'Can not access file: ' . $filePath);
	}
	
	private static function buildPs4Paths($typeName, $fileExt) {
		$firstChar = $typeName[0];
		if (!isset(self::$psr4Map[$firstChar])) return array();
		
		$filePaths = array();
		foreach (self::$psr4Map[$firstChar] as $namespacePrefix => $ps4Map) {
			if (0 !== strpos($typeName, $namespacePrefix)) continue;
			
			foreach ($ps4Map['dirPaths'] as $dirPath) {
				$filePaths[] =  $dirPath . DIRECTORY_SEPARATOR 
						. str_replace('\\', DIRECTORY_SEPARATOR, substr($typeName, $ps4Map['length'])) . $fileExt;
			}
		}
			
		return $filePaths;
	}
	/**
	 * 
	 * @return TypeLoaderErrorException
	 */
	public static function getLatestException() {
		return self::$latestException;
	}
	
	public static function clear() {
		self::$latestException = null;
	}
	
// 	public static function isFilePartOfNamespace($filePath, $namepsace) {
// 		foreach (self::$includePaths as $includePath) {
// 			$path = $includePath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, (string) $namepsace);
// 			if (StringUtils::startsWith($path, $filePath)) return true;
// 		}
		
// 		return false;
// 	}	
	
	public static function removeIncludePathOfFilePath($filePath) {
		foreach (self::$includePaths as $includePath) {
			if (!StringUtils::startsWith($includePath, $filePath)) continue;
			return mb_substr($filePath, strlen($includePath));
		}
		
		throw new FileIsNotPartOfIncludePathException('File path is not part of a include path: '
				. $filePath);
	}
}
