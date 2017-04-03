<?php
namespace ch\hnm\util\rocket\import\bo;

use ch\hnm\util\rocket\import\model\Csv;

class Import {
	private $csv;
	private $eiFields;
	
	public function __construct(Csv $csv, array $eiFields) {
		$this->csv = $csv;
		$this->eiFields = $eiFields;
	}
	
	public function getCsv() {
		return $this->csv;
	}
	
	public function setCsv(Csv $csv) {
		$this->csv = $csv;
	}
	
	public function getEiFieldCollection() {
		return $this->eiFieldCollection;
	}
	
	public function setEiFieldCollection(array $eiFieldCollection) {
		$this->eiFieldCollection = $eiFieldCollection;
	}
}