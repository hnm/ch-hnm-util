<?php
namespace ch\hnm\util\rocket\import\model;

use n2n\io\IoUtils;
use n2n\io\managed\File;

class CsvFileReader {
	public static function read(File $file) {
		IoUtils::readfile($file);
	}
}