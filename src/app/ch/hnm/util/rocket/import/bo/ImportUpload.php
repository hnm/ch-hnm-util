<?php
namespace ch\hnm\util\rocket\import\bo;

use n2n\persistence\orm\annotation\AnnoManagedFile;
use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\ObjectAdapter;
use n2n\io\managed\File;
use n2n\persistence\orm\annotation\AnnoDateTime;

class ImportUpload extends ObjectAdapter {
	private static function _annos(AnnoInit $ai) {
		$ai->p('file', new AnnoManagedFile());
		$ai->p('dateTime', new AnnoDateTime());
	}

	private $id;
	private $dateTime;
	private $eiThingPath;
	private $assignationJson;
	private $file;

	public function __construct(string $eiThingPath = null, File $file = null, \DateTime $dateTime = null) {
		$this->eiThingPath = $eiThingPath;
		$this->file = $file;
		$this->dateTime = $dateTime;
	}
	
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

	public function getAssignationJson() {
	    return $this->assignationJson;
    }

    public function setAssignationJson(string $assignationJson) {
	    $this->assignationJson = $assignationJson;
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