<?php
namespace ch\hnm\util\rocket\import\bo;

use n2n\persistence\orm\annotation\AnnoId;
use n2n\persistence\orm\annotation\AnnoManagedFile;
use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\ObjectAdapter;

class ImportUpload extends ObjectAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->p('file', new AnnoManagedFile());
	}

	private $id;
	private $dateTime;
	private $eiThingPath;
	private $file;

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * @return mixed
	 */
	public function getDateTime() {
		return $this->dateTime;
	}

	/**
	 * @param mixed $dateTime
	 */
	public function setDateTime($dateTime) {
		$this->dateTime = $dateTime;
	}

	/**
	 * @return mixed
	 */
	public function getEiThingPath() {
		return $this->eiThingPath;
	}

	/**
	 * @param mixed $eiThingPath
	 */
	public function setEiThingPath($eiThingPath) {
		$this->eiThingPath = $eiThingPath;
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
	public function setFile($file) {
		$this->file = $file;
	}
}