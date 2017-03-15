<?php
namespace ch\hnm\util\rocket\import\form;

use n2n\impl\web\dispatch\map\val\ValFileExtensions;
use n2n\impl\web\dispatch\map\val\ValNotEmpty;
use n2n\io\managed\File;
use n2n\io\managed\impl\engine\TmpFileSource;
use n2n\persistence\orm\annotation\AnnoManagedFile;
use n2n\reflection\annotation\AnnoInit;
use n2n\web\dispatch\Dispatchable;
use n2n\web\dispatch\map\bind\BindingDefinition;

class ImportForm implements Dispatchable {
	private static function _annos(AnnoInit $ai) {
		$ai->p('file', new AnnoManagedFile());
	}

	const ALLOWED_FILE_TYPES = array('csv');

	protected $file;

	public function save() {
	}

	/**
	 * @return mixed
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * @param mixed $file
	 */
	public function setFile(File $file = null) {
		$this->file = $file;
	}

	private function _validation(BindingDefinition $bd) {
		$bd->val('file', new ValNotEmpty(), new ValFileExtensions(self::ALLOWED_FILE_TYPES, null, false));
	}
}