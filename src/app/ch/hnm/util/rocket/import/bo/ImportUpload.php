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
	private $stateJson;
	private $file;

	public function __construct(string $eiThingPath = null, File $file = null, \DateTime $dateTime = null) {
		$this->eiThingPath = $eiThingPath;
		$this->file = $file;
		$this->dateTime = $dateTime;
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getDateTime() {
		return $this->dateTime;
	}

	public function setDateTime($dateTime) {
		$this->dateTime = $dateTime;
	}

	public function getEiThingPath() {
		return $this->eiThingPath;
	}

	public function setEiThingPath($eiThingPath) {
		$this->eiThingPath = $eiThingPath;
	}

	public function getAssignationJson() {
	    return $this->assignationJson;
    }

    public function setAssignationJson(string $assignationJson) {
	    $this->assignationJson = $assignationJson;
    }

	public function getFile() {
		return $this->file;
	}

	public function getStateJson() {
		return $this->stateJson;
	}

	public function setStateJson($stateJson) {
		$this->stateJson = $stateJson;
	}

	public function setFile($file) {
		$this->file = $file;
	}
}