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

use n2n\io\IoUtils;

class N2nVars {
	private static $mimeTypeDetector;
	const MIMETYPES_ETC_FILE = 'mimetypes.conf';
	
	public static function getMimeTypeDetector() {
		if (!isset(self::$mimeTypeDetector)) {
			self::$mimeTypeDetector = new MimeTypeDetector(N2N::getVarStore()->requestFileFsPath(
					VarStore::CATEGORY_ETC, N2N::NS, null, self::MIMETYPES_ETC_FILE));
		}
	
		return self::$mimeTypeDetector;
	}	
}



class MimeTypeDetector {
	private $extMimeTypeMappings = array();

	public function __construct($filePath) {
		$mimeTypesStr = IoUtils::getContents($filePath);

		foreach (explode("\n", str_replace("\r", '', $mimeTypesStr)) as $line) {
			$lineParts = preg_split('/\s+/', $line);
			if (!sizeof($lineParts)) continue;
				
			$mimeType = array_shift($lineParts);
			foreach ($lineParts as $extension) {
				$this->extMimeTypeMappings[$extension] = $mimeType;
			}
		}
	}

	public function getMimeTypeByExtension($extension) {
		if (isset($this->extMimeTypeMappings[$extension])) {
			return $this->extMimeTypeMappings[$extension];
		}

		return null;
	}
}
